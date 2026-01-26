#!/bin/sh

NUMBER_OF_MPDS=58

for (( i = 1; i <= NUMBER_OF_MPDS; i++ ))
do
  # shellcheck disable=SC2039
  if ((i < 10))
  then
    file_counter="0${i}"
  else
    file_counter="${i}"
  fi
  echo "Starting to process MPD $file_counter"
  java -jar ../saxon9he.jar -versionmsg:off -s:examples/ex"$file_counter".mpd -o:output/result_ex"$file_counter".xml -xsl:output/val_schema.xsl
done
