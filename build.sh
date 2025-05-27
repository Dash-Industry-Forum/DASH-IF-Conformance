#/bin/sh

docker build --build-arg SHORT_SHA=$(git rev-parse HEAD) -t dash-if-conformance:latest -t dash-if-conformance:$(git rev-parse --short HEAD) .
