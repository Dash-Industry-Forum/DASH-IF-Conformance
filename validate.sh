#!/bin/bash

docker run --rm -ti -w="/var/www/html/Utils" --entrypoint "" --mount type=bind,source="$(pwd)"/Conformance-Frontend,target=/var/www/html/Conformance-Frontend dash-if-conformance:latest php Process_cli.php -s -i "$1"