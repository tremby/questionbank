#!/bin/bash
CLASSPATH="."
CLASSPATH="$CLASSPATH:/usr/share/java/slf4j-api-1.5.2.jar:/usr/share/java/slf4j-simple-1.5.2.jar"
for JAR in $(find lib -iname "*.jar"); do
	CLASSPATH="$CLASSPATH:$JAR"
done
export CLASSPATH
