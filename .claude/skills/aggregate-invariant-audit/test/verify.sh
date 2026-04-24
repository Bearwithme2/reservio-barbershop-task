#!/usr/bin/env bash
# Verify aggregate-invariant-audit test outputs against expected results.
#
# Usage:
#   1. Ask Claude to audit each fixture and write the report to test/actual/<NN>_<name>.md
#      using the conventions in test/conventions.yaml.
#   2. Run this script from any directory: bash verify.sh
#
# Exit code: 0 if every actual matches its expected, non-zero on any divergence
# (missing actual file, diff, or unexpected extra file).

set -u

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
EXPECTED_DIR="$SCRIPT_DIR/expected"
ACTUAL_DIR="$SCRIPT_DIR/actual"

if [ ! -d "$ACTUAL_DIR" ]; then
    echo "FAIL: $ACTUAL_DIR does not exist. Ask Claude to produce audit outputs first."
    exit 2
fi

pass=0
fail=0
missing=0

for expected_file in "$EXPECTED_DIR"/*.md; do
    name="$(basename "$expected_file")"
    actual_file="$ACTUAL_DIR/$name"

    if [ ! -f "$actual_file" ]; then
        echo "MISSING  $name (no actual output produced)"
        missing=$((missing + 1))
        continue
    fi

    # Strip HTML comments from expected before diffing — they exist for human
    # readers (e.g. the recursion-cap explanation in 05) and are not part of
    # the report Claude is asked to produce.
    if diff -u \
        <(sed '/<!--/,/-->/d' "$expected_file") \
        "$actual_file" > /dev/null; then
        echo "PASS     $name"
        pass=$((pass + 1))
    else
        echo "FAIL     $name"
        diff -u \
            <(sed '/<!--/,/-->/d' "$expected_file") \
            "$actual_file" | sed 's/^/         /'
        fail=$((fail + 1))
    fi
done

# Detect orphaned actuals (Claude produced an output for a fixture that does
# not exist in expected/).
for actual_file in "$ACTUAL_DIR"/*.md; do
    [ -e "$actual_file" ] || continue
    name="$(basename "$actual_file")"
    if [ ! -f "$EXPECTED_DIR/$name" ]; then
        echo "ORPHAN   $name (no matching expected file)"
        fail=$((fail + 1))
    fi
done

echo
echo "Summary: $pass pass, $fail fail, $missing missing."

[ "$fail" -eq 0 ] && [ "$missing" -eq 0 ]
