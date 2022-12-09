#!/bin/bash
set -euo pipefail

# parsing
$1 || true
$1 --help || true

# basics
$1 -atomxml -configfile generic.cfg

################
#get some real life samples
################
#Romain: wget -r -np -R "index.html*" -e robots=off https://dash.akamaized.net/IsoSegmentValidator/regressionTests
pushd dash.akamaized.net/IsoSegmentValidator/regressionTests
for i in `ls` ; do
    pushd $i
    $1 -atomxml -configfile generic.cfg || true
    popd
done
popd

################
#synthetic tests
################
