# SOLUTION

## Summary

Customers occasionally received two confirmations for the same booking, with two identical rows appearing in the admin
panel. The fix is a partial unique index on the booking table combined with an exception translation in the command
handler. Scope is kept narrow. Adjacent architectural concerns are documented below rather than addressed in the diff.

## Root Cause

`CreateBookingCommandHandler` performs no uniqueness verification before persisting. It loads the service and stylist,
constructs a `Booking`, and calls `save` without consulting existing bookings. `BookingRepositoryInterface` offers only
`getById` and `save`, and the schema had only a non-unique index on `stylist_id`. Any duplicate request reaching the
handler produces a duplicate row.

The observed symptom is caused by the frontend submitting the booking mutation twice on each action. The same backend
weakness would also permit duplicates from retries, double-clicks, or concurrent submissions from different users.

## The Fix

Migration `Version20260418000000` adds a partial unique index on `(stylist_id, start_time)` scoped to
`status IN ('pending', 'confirmed')`. Rejected bookings are excluded so they do not block new bookings in the same slot.
The handler catches `UniqueConstraintViolationException` and throws a new `SlotAlreadyTakenException`, which extends
`\DomainException`. The GraphQL resolver already catches `\DomainException`, so no resolver changes were needed.
Integration tests in `tests/Integration/CreateBookingHandlerTest.php` cover the duplicate case.

The index covers exact start-time duplicates only, not overlapping intervals.

## Alternatives Considered

- **`BEGIN IMMEDIATE` transaction around an application-level check**, rejected because SQLite locks at the database
  level and the pattern has more moving parts than a constraint.
- **Application-level overlap query**, addresses a broader problem than was reported.
- **Idempotency key on the mutation**, would improve the double-submit user experience but requires frontend changes
  and does not address concurrent users.

## How to Verify

Run `make up`, then `make db-reset`. Inside `make bash`, run `vendor/bin/phpunit` to execute the integration tests. For
manual verification, book a slot at http://localhost:3000, then attempt to book it again. The second attempt returns a
user-facing error.

## Known Gaps (Deliberately Not Fixed)

- The GraphQL API performs no authorization, any caller can confirm or reject any booking by guessing identifiers.
- Query handlers return Doctrine entities directly, coupling the API surface to the persistence model.
- `Booking::confirm` and `Booking::reject` are unguarded state setters and raise no domain events, so state machine
  invariants live nowhere.
- `Service::price` is stored as a `float`, which is unsafe for monetary values.
- Several handlers bypass the domain repositories and use `EntityManagerInterface` directly, leaking persistence into
  the application layer.
- The project composes Nette with Laravel Illuminate packages and the Rebing GraphQL library, including a facade-root
  swap in the GraphQL bootstrap, an unusually large surface for one library.

Because `Booking::confirm` performs no state check, a rejected booking can be silently re-confirmed back into the index
scope. A state-transition guard on the aggregate would close this.

## Bonus: Aggregate Invariant Audit Skill

A Claude Code skill at `.claude/skills/aggregate-invariant-audit/` audits a single PHP class for unguarded state transitions, missing domain events, and constructor primitive gaps, reporting findings in two tiers (confident vs. review). Running it against `Booking.php` reproduces the gaps documented above. A self-contained test suite lives under `test/` with seven fixtures and expected outputs; `test/README.md` shows how to invoke it.

## Follow-up Work for Production

- Domain events for booking state changes, dispatched via a transactional outbox, decouple notifications and webhooks
  from the command path while preserving at-least-once delivery.
- Frontend submit-button guard and an idempotency key on the mutation, turns the duplicate path from an error into a
  no-op for the legitimate retry case.
- `Money` value object replacing the `float` price, eliminates rounding hazards and pairs amount with currency.
- Aggregate invariants and state-transition guards on `Booking`, closes the re-confirm path noted above and centralizes
  the state machine.
- Authorization at the GraphQL resolver level, the largest open risk and the natural place to gate per-business roles.
