#!/bin/bash
set -euo pipefail

ROOT="$PWD"
BIN="$ROOT/bin"
EXEC="$BIN/ValidateMP4.exe"
REPORT_FULL=$BIN/profile-full.txt
REPORT=$BIN/profile.txt

rm $REPORT $REPORT_FULL || true
find $BIN -path "*.gcda" -delete

# Run tests
pushd $ROOT/../../tests
./run.sh "$EXEC"
popd

# Generate coverage report
lcov --capture -d $BIN -o $REPORT_FULL
lcov --remove $REPORT_FULL \
  '/usr/include/*' \
  '/usr/lib/*' \
  '*/extra/*' \
  -o $REPORT

genhtml -o cov-html $REPORT

echo "Coverage report is available in cov-html/index.html"
