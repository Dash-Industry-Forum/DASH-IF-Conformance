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
wget -r -np -R "index.html*" -e robots=off https://dash.akamaized.net/IsoSegmentValidator/regressionTests
pushd dash.akamaized.net/IsoSegmentValidator/regressionTests
for i in `ls` ; do
    pushd $i

    #run test
    $1 -atomxml -configfile generic.cfg || true

    #compare with reference result
    for res in `ls *.txt *.xml` ; do
        #md5sum $res > $res.md5
        md5sum $res > /tmp/$res.md5
        cmp /tmp/$res.md5 $res.md5
    done

    popd
done
popd

################
#synthetic tests
################
