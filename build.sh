#/bin/sh

docker build -t dash-if-conformance:latest -t dash-if-conformance:$(git rev-parse --short HEAD) .
