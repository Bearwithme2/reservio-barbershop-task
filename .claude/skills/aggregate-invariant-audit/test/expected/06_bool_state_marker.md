# Aggregate Invariant Audit: BoolStateMarker

1 confident finding (1 domain event), 0 review findings.

## Confident Findings

### Missing domain event

- Missing domain event at .claude/skills/aggregate-invariant-audit/test/fixtures/06_bool_state_marker.php:14 — `cancel()` mutates `$this->isCancelled` and `$this->cancelledAt` with no call to `recordThat`, `raise`, or `record`.

## Review Findings

No review findings.
