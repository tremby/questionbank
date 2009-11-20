#!/bin/bash
unison -auto -root . -root ssh://lslvm-bjn1//srv/easihe/www/authoringtool -ignore "Path sync.sh" -ignore "Regex .*.swp$"
