#!/bin/bash

# $1 : series ID
# $2 : Name of the series
# $3 : Implmentation
# $4 : IP1 of the surface level
# $5 : File matching pattern
# $6 : E-Mail address

# Patch vraiment horrible puisque le home n'est même pas définit lorsqu'on est applé par un exec fait dans un script PHP exécuté par apache
export HOME="/users/dor/afsu/dev"
# Load ENV definitions required by PostgreSQL
. ~/.profile.d/postgres.azathoth


loaderPath="/home/afsu/air/verification/bin/fctloader"
logFilePath="/tmp/fctloader.$$"

env | sort > ${logFilePath}
echo "================================================================================" >> ${logFilePath}
echo -e "${loaderPath} \"${1}\" \"${3}\" \"${4}\" \"${5}\"" >> ${logFilePath}
echo "================================================================================" >> ${logFilePath}
${loaderPath} "${1}" "${3}" "${4}" "${5}" >> ${logFilePath} 2>&1

cat ${logFilePath} | mail -s "fctloader ${2}" "${6}"
rm -f ${logFilePath}