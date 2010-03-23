#!/bin/bash
. ./classpath.sh
if [ ! -f Validate.class ]; then
	echo "validate must be compiled first -- run the make.sh script" >&2
	exit 253
fi
exec java Validate "$@"
