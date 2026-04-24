# Aggregate Invariant Audit: MatchGuarded

1 confident finding (1 domain event), 0 review findings.

## Confident Findings

### Missing domain event

- Missing domain event at .claude/skills/aggregate-invariant-audit/test/fixtures/03_match_guarded.php:19 — `lock()` mutates `$this->status` with no call to `recordThat`, `raise`, or `record`.

## Review Findings

No review findings.
