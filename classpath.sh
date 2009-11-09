#!/bin/bash
CLASSPATH="."
CLASSPATH="$CLASSPATH:/usr/share/java/slf4j-api.jar:/usr/share/java/slf4j-nop.jar"
for JAR in $(find lib -iname "*.jar"); do
	CLASSPATH="$CLASSPATH:$JAR"
done
export CLASSPATH
