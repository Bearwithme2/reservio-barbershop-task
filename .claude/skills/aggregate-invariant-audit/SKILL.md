---
name: aggregate-invariant-audit
description: Audits a single PHP class for aggregate invariant weaknesses — unguarded state transitions, missing domain events, constructor primitive gaps. Reports findings in two tiers (confident vs. review). Does not modify code.
---

# Aggregate Invariant Audit

## Purpose

This skill inspects a single PHP class and reports weaknesses in how that class enforces its own invariants. It is
intended for use on aggregate roots in Domain-Driven Design codebases, but it makes no attempt to verify that the input
class is actually an aggregate. The caller is trusted to pass a class they consider to be an aggregate, and the skill
reports findings about what the class does rather than what it is.

The skill does not modify any code. It produces a report and stops. The decision of whether and how to address each
finding belongs to the developer, because aggregate design involves judgment about which invariants belong in the
aggregate versus in domain services or application handlers.

## When to Use

Invoke this skill when the user asks to audit, review, or check the invariants of a PHP class that represents a domain
entity or aggregate. Typical triggers include phrases such as "audit the Booking aggregate," "check the invariants on
this class," "is this aggregate properly guarded," or similar requests that ask for an assessment of a domain class
rather than a fix.

Do not invoke this skill for code review on classes outside the domain layer, or for requests that ask for a refactor or implementation change. The skill produces analysis, not edits. When a code review touches a domain entity or aggregate, invoking the skill is appropriate and often the highest-value moment to do so.

## Input

The skill accepts a single file path as input. The caller is expected to identify which class they want audited; the
skill does not scan directories or attempt to detect aggregates automatically.

## Output

The skill produces a structured Markdown report with the following skeleton.

```
# Aggregate Invariant Audit: {ClassName}

{Summary line — see "Summary line format" below.}

## Confident Findings

### {Category name, sentence case}

- {One bullet per finding — file:line citation plus a brief statement.}

## Review Findings

### {Category name, sentence case}

- {One bullet per finding — file:line citation plus a brief question.}
```

### Summary line format

The summary line breaks down the count by category in parentheses, so a wrong total does not add up and is caught by
the reader (and by you, on re-read). The format is:

`{N} confident finding(s) ({A} unguarded + {B} creation event + {C} domain event), {M} review finding(s) ({D} constructor gap).`

Omit any category whose count is zero from the parenthetical, and drop the parenthetical entirely when its tier has no
findings. When both tiers are empty, the summary line is `No findings.` Examples:

- `5 confident findings (2 unguarded + 1 creation event + 2 domain event), 4 review findings (4 constructor gap).`
- `2 confident findings (2 unguarded), 0 review findings.`
- `0 confident findings, 1 review finding (1 constructor gap).`
- `No findings.`

The breakdown is not decorative. It is the mechanism that prevents the count from drifting away from the rendered list.
A reader (or you, on re-read) can verify the total by adding the parenthetical numbers; if they do not sum to the
leading total, the report is internally inconsistent and must be corrected before emission.

### Production order

This is a strict procedure, not a suggestion. Follow it in order.

1. Identify findings during analysis. Do not write the report yet.
2. Render the report body — everything from `## Confident Findings` through the end of `## Review Findings`. Do not
   yet write the title or the summary line.
3. Re-read the body you just produced. Count bullets per category, literally, by scanning your own output. Write the
   four counts down explicitly (A, B, C for confident; D for review).
4. Compute N = A + B + C and M = D.
5. Now prepend the title line, a blank line, and the summary line using the format above with the counts from step 3.
6. Emit the final report top-to-bottom.

Do not skip step 3. The miscount failure mode happens when the agent writes the summary from memory of the analysis
pass instead of from the rendered text. Counting from the rendered text is the entire point — if you count from the
analysis pass, the breakdown format will not save you.

When a tier contains findings, group them under a `### Category name` subsection per category present (see Finding
Categories below for the three category names). Subsection headings are always singular regardless of how many findings
the category contains — `### Unguarded state transition`, not `### Unguarded state transitions`. When a tier is empty,
write the single line "No confident findings." or "No review findings." with no subsection headers — empty categories
are not rendered at all. A reader scanning a clean audit should see "No confident findings." and "No review findings."
plainly, not a wall of empty subsections.

One finding per bullet. Do not merge multiple findings into a single bullet, even when they share a category, sit on
adjacent lines, or trace to the same underlying cause. Each distinct method, parameter, or line gets its own entry; the
reader should be able to count findings by counting bullets. Merging a block of four constructor parameters into one
bullet, or collapsing two unguarded transitions into a combined entry, violates this rule.

Each finding entry cites the file path and the single line number of the offending statement. Confident findings are
phrased as statements; review findings are phrased as questions. Do not collapse the two tiers into a single list. The
phrasing distinction is load-bearing: confident findings are actionable without further investigation, while review
findings signal that the developer needs to decide whether the observed pattern is a gap or a deliberate choice.

A finding's tier is determined by the conventions file and the rules in this document alone. Do not downgrade a
confident finding to review based on surrounding code evidence — reasoning like "this project declares
`events_expected: true` but has no visible event infrastructure in this class, so the finding is soft" is out of scope.
The conventions file is the single source of truth on tier assignment; if it says events are expected, every
state-changing method without a recording call is a confident finding, full stop. Ambient context is for the developer
reading the report, not for the skill producing it.

The summary count is derived from the rendered list of findings, not from the analysis pass. The Production Order
procedure above is the binding mechanism for this — follow it. The breakdown format is the second line of defence:
even if the numbers drift, an inconsistent total will not add up and the error becomes self-evident on re-read.

## Finding Categories

The skill reports on three categories of invariant weakness, rendered as subsections within the appropriate tier.

The first category is unguarded state transitions. A public method that modifies a state field without first inspecting
the current value is reported as a confident finding. The canonical example is a `confirm` or `reject` method that
assigns directly to a status enum without checking whether the transition is legal from the current state.

The second category is missing domain events. A state-changing method that does not record or raise any event
representing the change is reported as a confident finding when the conventions file declares `events_expected: true`,
and as a review finding otherwise. Detection of event recording is based on calls to method names listed in the
`event_recording_patterns` key of the conventions file, defaulting to `recordThat`, `raise`, and `record` when the key
is absent. Constructors are in scope for this check, because a creation event is a domain event in event-sourced
architectures; findings about constructors are phrased as "missing creation event" rather than "missing domain event."

The third category is constructor invariant gaps. A constructor parameter whose declared type matches an entry in the
conventions file's `wrap_primitives` key is reported as a review finding, cited at the parameter declaration line. When
the class uses non-promoted fields, do not additionally flag the corresponding field declaration or the assignment line
in the constructor body — those are the same gap as the parameter, and flagging them separately inflates the count.
Each wrapped-primitive parameter produces exactly one finding. This category depends on the conventions file and is
suppressed entirely when the file is absent. When the file exists but `wrap_primitives` is empty or omitted, the
category runs but produces no findings.

## Conventions File

The skill reads project conventions from `.claude/conventions.yaml` at the repository root. The skill reads only the
`aggregate_audit` section and ignores other top-level keys, so sibling skills can share the same file without
interference.

The `aggregate_audit` section declares the following keys.

The `value_objects` key lists the fully qualified type names the project uses as value objects.

The `wrap_primitives` key lists raw types that the project considers to require wrapping, such as `string` for contact
information or `DateTimeImmutable` fields that represent time ranges.

The `events_expected` key is a boolean declaring whether the project uses domain events. When true, missing events are
reported as confident findings. When false or absent, missing events are reported as review findings.

The `event_recording_patterns` key lists the method names the skill should recognise as event recording. When absent,
the default list is `recordThat`, `raise`, and `record`. Event-sourced projects that use `apply` for recording should
add it explicitly via this key.

When the conventions file is absent, the constructor invariant gap category is suppressed entirely and the missing
domain event category falls back to review tier. The unguarded state transition category is unaffected by the presence
or absence of the conventions file, because it does not depend on project-specific vocabulary.

This degradation is deliberate. Without a project-defined value object vocabulary, every raw primitive in a constructor
looks suspect, and reporting on all of them produces noise that trains the developer to ignore the skill. Suppressing
the category entirely is more honest than flagging with low confidence.

## Report Phrasing

Findings are phrased around what the class does, not around what the class is. The skill does not claim the input is an
aggregate. It reports on patterns in the class as presented. This avoids smuggling in an identification the skill did
not make and keeps the report appropriate for cases where the caller has passed in a plain entity or a class that does
not fit the strict DDD definition of an aggregate.

## Reference

Load `REFERENCE.md` before producing the report.
