# Aggregate Invariant Audit — Test Suite

This suite exercises the skill against seven hand-designed PHP fixtures. Each fixture targets one capability axis of the
skill, and each has a paired expected-output file describing what the audit should produce.

## How to run

The skill is invoked by Claude Code, not by a script. The workflow is two steps: ask the agent to produce audit outputs, then run `verify.sh` to diff them against the expected results.

### Mechanical run (recommended)

Ask the agent:

> Audit each fixture under `test/fixtures/` using the conventions in `test/conventions.yaml`. Write each report to `test/actual/<same-filename>.md` (e.g. `test/actual/02_bare_setters.md`). Do not summarise — just produce the seven files.

Then run:

```
bash test/verify.sh
```

The script diffs each `actual/*.md` against `expected/*.md`, prints PASS / FAIL / MISSING / ORPHAN per fixture, and exits non-zero if anything diverges. HTML comments in expected files (used for human-readable notes such as the recursion-cap explanation in fixture 05) are stripped before diffing.

### One fixture at a time

To exercise a single capability:

> Audit `test/fixtures/02_bare_setters.php` using `test/conventions.yaml`.

Then compare visually against `test/expected/02_bare_setters.md`, or write the output to `test/actual/02_bare_setters.md` and run `verify.sh`.

## What each fixture tests

| Fixture                        | Capability under test                                                             | Expected outcome                                                             |
|--------------------------------|-----------------------------------------------------------------------------------|------------------------------------------------------------------------------|
| `01_clean_aggregate.php`       | Whole-class clean path: VO-typed fields, guarded transitions, recorded events     | Zero findings; both sections explicitly empty                                |
| `02_bare_setters.php`          | Unguarded enum transitions across multiple methods                                | 3 confident transition findings + 3 confident missing-event findings         |
| `03_match_guarded.php`         | `match` expression with `default => throw` recognised as a guard                  | No transition finding; missing-event finding stands                          |
| `04_helper_one_level.php`      | Public method delegates to one-level helper that mutates and records              | No findings (guard in public method, event in helper traced one level)       |
| `05_helper_two_levels.php`     | Mutation lives two helper levels deep                                             | True negative: no findings, mutation is beyond stated scope                  |
| `06_bool_state_marker.php`     | Boolean and nullable-timestamp state markers are out of scope for transition rule | No transition finding; missing-event finding stands                          |
| `07_primitive_constructor.php` | Constructor with promoted properties typed as raw primitives                      | Missing creation event + three review findings for `wrap_primitives` matches |

## Known-limitation fixtures

`05_helper_two_levels.php` is a true-negative test. The fixture contains a real bug (an unrecorded state mutation), but
the bug lives two helper levels deep and the skill's spec caps recursion at one level. The expected output is "no
findings," and the comment in `expected/05_helper_two_levels.md` explains why. This is a deliberate honesty test: a tool
that silently misses out-of-scope cases is more trustworthy than one that overreaches.

## Conventions used

`test/conventions.yaml` pins the conventions for the suite so results do not depend on the consuming project's
`.claude/conventions.yaml`. The pinned values are:

- `value_objects`: `App\Domain\ValueObject\Uuid`, `App\Domain\Barbershop\ValueObject\Slot`
- `wrap_primitives`: `string`, `DateTimeImmutable`
- `events_expected`: `true`

If you want to exercise the missing-event tier-fallback behaviour (confident → review when `events_expected` is false or
absent), copy `conventions.yaml`, flip `events_expected` to `false`, and re-run fixtures 02, 03, 06, and 07. The
missing-event findings should move from the Confident Findings section to the Review Findings section, with the phrasing
changing from statements to questions.

## Adding a fixture

1. Drop a PHP file under `fixtures/` with a sequential prefix (`08_`, `09_`, ...).
2. Drop the expected report under `expected/` with the same prefix.
3. Add a row to the table above explaining what capability the fixture targets.

Keep each fixture small and single-purpose. The test suite's value comes from each fixture pinning down one rule, not
from any single fixture exercising the skill broadly.
