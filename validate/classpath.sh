#!/bin/bash
CLASSPATH="."
for JAR in $(find lib -iname "*.jar"); do
	CLASSPATH="$CLASSPATH:$JAR"
done
export CLASSPATH
