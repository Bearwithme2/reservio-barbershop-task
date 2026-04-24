# Aggregate Invariant Audit — Pattern Reference

This document catalogues the patterns the aggregate invariant audit skill recognizes. Each pattern is presented as a
wrong example, a right example, the rule extracted from the two, and a judgment note identifying any legitimate
counter-case. Load this document before producing a report. A class that matches no patterns produces a report with both
sections explicitly empty, not an absent report.

## Unguarded State Transition

### Wrong

```php
final class Booking
{
    private BookingStatus $status;

    public function confirm(): void
    {
        $this->status = BookingStatus::Confirmed;
    }

    public function reject(): void
    {
        $this->status = BookingStatus::Rejected;
    }
}
```

### Right

```php
final class Booking
{
    private BookingStatus $status;

    public function confirm(): void
    {
        if ($this->status !== BookingStatus::Pending) {
            throw new InvalidBookingTransition(
                "Cannot confirm a booking with status {$this->status->value}."
            );
        }

        $this->status = BookingStatus::Confirmed;
    }

    public function reject(): void
    {
        if ($this->status !== BookingStatus::Pending) {
            throw new InvalidBookingTransition(
                "Cannot reject a booking with status {$this->status->value}."
            );
        }

        $this->status = BookingStatus::Rejected;
    }
}
```

### Rule

A public method whose body contains an assignment to a field declared as an enum type, without a preceding conditional
inspecting the current value of that field, is an unguarded state transition. The conditional may take the form of an
`if` statement, a `match` expression, or a guard clause that throws before reaching the assignment. The rule applies
regardless of method name. Scope is limited to enum-typed fields; booleans, nullable timestamps, and counters are out of
scope to keep the confident tier honest.

The rule looks at the public method's own body. If the public method delegates to a private helper that performs the
mutation, the helper is not flagged on its own — the rule trusts that the guard in the public method governs the call
site. This deliberately differs from the missing-event rule, which walks one helper level: a missing event represents a
gap regardless of where in the call chain the mutation happens, while a missing guard is meaningful only at the public
entry point.

### Judgment Note

Tier: confident. No legitimate counter-case exists within an aggregate method. If the transition is unconditional by
design because the caller is responsible for ensuring the transition is valid, the caller-side check amounts to
invariant enforcement outside the aggregate, which is itself a design weakness this skill is meant to surface. The
finding stands in the confident tier.

## Missing Domain Event

### Wrong

```php
final class Booking
{
    private BookingStatus $status;

    public function confirm(): void
    {
        if ($this->status !== BookingStatus::Pending) {
            throw new InvalidBookingTransition("...");
        }

        $this->status = BookingStatus::Confirmed;
    }
}
```

### Right

```php
final class Booking
{
    private BookingStatus $status;
    /** @var list<object> */
    private array $recordedEvents = [];

    public function confirm(): void
    {
        if ($this->status !== BookingStatus::Pending) {
            throw new InvalidBookingTransition("...");
        }

        $this->status = BookingStatus::Confirmed;
        $this->recordThat(new BookingConfirmed($this->id, new DateTimeImmutable()));
    }

    private function recordThat(object $event): void
    {
        $this->recordedEvents[] = $event;
    }
}
```

### Rule

A public method that mutates any field of the class and does not contain a call to one of the recording method names
listed in the conventions file, or the defaults `recordThat`, `raise`, or `record` when the key is absent, is a missing
domain event. Field mutation is any assignment of the form `$this->field = ...` inside the method body or inside a
helper method called from the public method within the same class. Helper-method indirection is traced one level only;
deeper call chains are out of scope.

Constructors are public methods for the purpose of this rule. A constructor that assigns to fields without recording an
event is reported under this category, with the finding heading "Missing creation event" rather than "Missing domain
event," to distinguish creation-time recording from state-change recording.

### Judgment Note

Tier: confident when `events_expected: true` is declared in the conventions file; review when the key is false or
absent. The legitimate counter-case for the review tier is a codebase that has consciously chosen not to use domain
events, in which case the absence is not a gap. The confident tier exists for codebases that have declared events are
part of the architecture, where a state-changing method that records nothing is unambiguously incomplete.

## Constructor Invariant Gap

### Wrong

```php
final class Booking
{
    public function __construct(
        private readonly Uuid $id,
        private readonly Service $service,
        private readonly Stylist $stylist,
        private readonly DateTimeImmutable $startTime,
        private readonly DateTimeImmutable $endTime,
        private readonly string $customerName,
        private readonly string $customerContact,
    ) {
        $this->status = BookingStatus::Pending;
    }
}
```

### Right

```php
final class Booking
{
    public function __construct(
        private readonly Uuid $id,
        private readonly Service $service,
        private readonly Stylist $stylist,
        private readonly Slot $slot,
        private readonly CustomerName $customerName,
        private readonly CustomerContact $customerContact,
    ) {
        $this->status = BookingStatus::Pending;
    }
}
```

### Rule

A constructor parameter whose declared type matches an entry in the `wrap_primitives` key of the conventions file is a
constructor invariant gap. The match is on the declared type name, not on the parameter name. Paired fields representing
a single concept, such as `DateTimeImmutable $startTime` alongside `DateTimeImmutable $endTime`, are flagged when the
conventions file lists the paired primitive combination explicitly; otherwise they are flagged individually if the
primitive type is listed.

### Judgment Note

Tier: review. The legitimate counter-case is handler-boundary validation, defensible when validation rules are small and
centralized. The review tier exists to surface the question, not to answer it.

## Citation Format

Findings cite the pattern heading in sentence case followed by file and line:
`Unguarded state transition at Booking.php:50`.
