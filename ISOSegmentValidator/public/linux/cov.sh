#!/bin/bash
set -euo pipefail

ROOT="$PWD"
BIN="$ROOT/bin"
EXEC="$BIN/ValidateMP4.exe"

# Run tests
$ROOT/../../tests/run.sh

# Generate coverage report
find $BIN -path "*/unittests/*.gcda" -delete
lcov --capture -d $BIN -o $BIN/profile-full.txt
lcov --remove $BIN/profile-full.txt \
  '/usr/include/*' \
  '/usr/lib/*' \
  '*/extra/*' \
  -o $BIN/profile.txt

genhtml -o cov-html $BIN/profile.txt

echo "Coverage report is available in cov-html/index.html"
