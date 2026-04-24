# Aggregate Invariant Audit: PrimitiveConstructor

1 confident finding, 3 review findings.

## Confident Findings

### Missing creation event

- Missing creation event at .claude/skills/aggregate-invariant-audit/test/fixtures/07_primitive_constructor.php:11 — `__construct()` mutates fields with no call to `recordThat`, `raise`, or `record`.

## Review Findings

### Constructor invariant gap

- Constructor invariant gap at .claude/skills/aggregate-invariant-audit/test/fixtures/07_primitive_constructor.php:13 — `string $name` is a raw primitive listed in `wrap_primitives`. Is non-emptiness validated by the caller?
- Constructor invariant gap at .claude/skills/aggregate-invariant-audit/test/fixtures/07_primitive_constructor.php:14 — `string $email` is a raw primitive listed in `wrap_primitives`. Is format validation done elsewhere?
- Constructor invariant gap at .claude/skills/aggregate-invariant-audit/test/fixtures/07_primitive_constructor.php:15 — `\DateTimeImmutable $registeredAt` is a raw primitive listed in `wrap_primitives`. Is the timestamp validated by the caller?
