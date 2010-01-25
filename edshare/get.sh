#!/bin/bash
IFS="
"
for file in $(cat qtibox.manifest); do
	scp -p lslvm-pz1:/opt/eprints3/archives/easihe/"$file" "$(dirname "$file")"/
done
