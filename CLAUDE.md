# CLAUDE.md

Guidance for Claude Code in this repo. Loaded every session — keep it lean.

## Project

USE WSL and WSL commands

Online booking for two barbershops. Customers pick service + stylist + time; staff confirm/reject in admin panel.

- **Backend:** PHP 8+, Nette, Doctrine ORM, GraphQL, SQLite
- **Frontend:** Next.js, Apollo Client, Tailwind
- **Infra:** Docker Compose, Make

Full setup is in `README.md`. Do not duplicate it here.

## Quick Commands

```
make up          # start containers
make db-reset    # migrations + fixtures
make bash        # shell into backend
make logs        # tail backend logs
```

Frontend `:3000` · GraphQL `:8080/graphql` · Admin `/business-panel` (`admin` / `barber`)

## Architecture Boundaries

- **Write side:** GraphQL mutation → command → command handler → aggregate method → repository. Handlers return `void` or IDs, never entities.
- **Read side:** GraphQL query → query handler → DTO / read model. Do not return Doctrine entities.
- **Aggregates** are the only place domain invariants are enforced. No business rules in handlers or resolvers.
- **Domain events** (when introduced) are raised inside aggregates and dispatched after transaction commit via an outbox — not mid-transaction.

If a proposed change crosses these boundaries, flag it in the response instead of silently doing it.

## Code Style & Quality Gates

- `declare(strict_types=1);` on every file
- PHPStan max level — must pass before commit
- No `mixed` in public APIs
- Money: integer minor units + currency, never `float`
- Value objects for anything with invariants (email, phone, time slot, money)

## Testing

- Unit tests for aggregates and value objects — pure PHP, no container
- Integration tests for command/query handlers — in-memory SQLite
- One test per invariant, not per method

`make bash` → `vendor/bin/phpunit`

## Known Gotchas

- **SQLite locks at database level, not row level.** `SELECT ... FOR UPDATE` is a no-op here. Use unique constraints or `BEGIN IMMEDIATE` for concurrency.
- GraphQL API has **no authorization** — intentional interview discussion topic. Do not patch unless asked.
- Query handlers currently return entities directly — intentional, do not refactor unprompted.
- `Service::price` is `float` — intentional, do not refactor unprompted.
- Admin credentials are hardcoded in fixtures — demo only.

## Subagents

Delegate isolated work to subagents so main context stays clean. Agent definitions live in `.claude/agents/`.

- **`php-reviewer`** — reviews PHP diffs for CQRS/aggregate hygiene, strict types, return types. Run before opening a PR.
- **`graphql-auditor`** — audits schema and resolvers for authz gaps, field-level leaks, N+1 risks.
- **`test-writer`** — writes PHPUnit tests against a named aggregate or handler. Targets invariants, not implementation details.

Invoke by name: `@php-reviewer check the Booking aggregate`. Subagents run in isolated context and return only the result — use them aggressively for anything that would otherwise dump 500+ lines into main context.

## Plan Mode

Use plan mode (`shift+tab` twice) before:
- Concurrency / locking changes
- Schema migrations
- Anything touching `Booking::confirm` or `Booking::reject`

No silent multi-file edits on critical paths.

## Token Hygiene

- This file is under 200 lines on purpose. Don't grow it — put depth in `docs/` and @-import.
- `@docs/cqrs-conventions.md` — command/query patterns (create on demand)
- `@docs/domain-model.md` — aggregate boundaries (create on demand)
- `/clear` between unrelated tasks (bug fix → new feature)
- `/compact` before context auto-truncates, not after
- `view` with `view_range` instead of reading whole files
- `.claudeignore` excludes `vendor/`, `node_modules/`, `var/`, `.next/`, `build/`, `*.sqlite`

## Dependency Policy

- No new dependencies without an explicit ask and a one-line justification
- Pin exact versions + hashes — no `^` or `~` ranges, no floating tags
- Prefer vendoring small utilities over adding a package
- Context: March 2026 TeamPCP supply chain attack (compromised Trivy, backdoored LiteLLM on PyPI, CanisterWorm on npm). Minimal-dep posture is deliberate.

## When Stuck

Ask before guessing. Especially:
- Nette-specific conventions (DI wiring, presenter lifecycle) — legacy in this codebase, confirm before inventing
- Doctrine event listener and flush semantics
- Concurrency assumptions (SQLite ≠ Postgres / MySQL)

## What Not To Do

- Do not fix the known architectural gaps (auth, entity-returning queries, `float` price, missing domain events) unless explicitly asked — they are interview discussion topics.
- Do not generate migrations without showing the SQL first.
- Do not commit `.env` or fixture credentials.
- Do not add retries, timeouts, or circuit breakers without discussing the failure model.