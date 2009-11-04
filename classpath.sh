#!/bin/bash
CLASSPATH="."
CLASSPATH="$CLASSPATH:$(find /usr/share/java -type f -name "slf4j*" | tr "\n" ":")"
CLASSPATH="$CLASSPATH:/usr/share/java/commons-logging.jar"
for JAR in $(find . -iname "*.jar"); do
	CLASSPATH="$CLASSPATH:$JAR"
done
export CLASSPATH
