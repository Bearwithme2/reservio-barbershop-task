# Aggregate Invariant Audit: BareSetters

6 confident findings, 0 review findings.

## Confident Findings

### Unguarded state transition

- Unguarded state transition at .claude/skills/aggregate-invariant-audit/test/fixtures/02_bare_setters.php:19 — `close()` assigns `TicketStatus::Closed` to `$this->status` with no preceding conditional inspecting the current value.
- Unguarded state transition at .claude/skills/aggregate-invariant-audit/test/fixtures/02_bare_setters.php:24 — `archive()` assigns `TicketStatus::Archived` to `$this->status` with no preceding conditional inspecting the current value.
- Unguarded state transition at .claude/skills/aggregate-invariant-audit/test/fixtures/02_bare_setters.php:29 — `reopen()` assigns `TicketStatus::Open` to `$this->status` with no preceding conditional inspecting the current value.

### Missing domain event

- Missing domain event at .claude/skills/aggregate-invariant-audit/test/fixtures/02_bare_setters.php:19 — `close()` mutates `$this->status` with no call to `recordThat`, `raise`, or `record`.
- Missing domain event at .claude/skills/aggregate-invariant-audit/test/fixtures/02_bare_setters.php:24 — `archive()` mutates `$this->status` with no call to `recordThat`, `raise`, or `record`.
- Missing domain event at .claude/skills/aggregate-invariant-audit/test/fixtures/02_bare_setters.php:29 — `reopen()` mutates `$this->status` with no call to `recordThat`, `raise`, or `record`.

## Review Findings

No review findings.
