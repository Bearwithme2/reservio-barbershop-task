# Aggregate Invariant Audit: HelperTwoLevels

No findings.

## Confident Findings

No confident findings.

## Review Findings

No review findings.

<!--
This fixture demonstrates the recursion cap. The mutation lives two helper levels
deep (ship → transition → commit). Per the spec, the skill traces helper-method
indirection one level only, so it cannot observe the mutation and produces no
findings. This is a true negative: a real bug exists in the fixture but the
skill's stated scope does not cover it. Documented as a known limitation rather
than treated as a test failure.
-->
