#!/bin/sh
PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin
#Petr Svana, March 2015
#
#variables to set
#
#paths
OSMOSIS=osmosis
OSMFILTER=osmfilter
OSMUPDATE=osmupdate
OSMCONVERT=osmconvert

#path to working directory
WORK_DIR=/home/xsvana00/data

#path to logfile
LOGFILE=/home/xsvana00/update.log
LASTUPDATE=/var/www/html/xsvana00/last_update.txt

#filenames
PRESENT_DUMP=converted_dump.o5m
PRESENT_FILTERED_DUMP=filtered_dump.o5m
NEXT_DUMP=updated_converted_dump.o5m
NEXT_FILTERED_DUMP=updated_filtered_dump.o5m
UPDATE=update_database.osc

#database credential
AUTH_FILE=$HOME/.osmosis.auth

LOCK=/tmp/xsvana00.lock
test -f $LOCK && { echo "Lock file $LOCK exists. Exiting..." >>$LOGFILE; exit; }
touch $LOCK

function finish {
	rm $LOCK
}

trap finish EXIT

echo ------------------------------------------------------------ >> $LOGFILE 

CURRENT=`pwd`
cd $WORK_DIR

$OSMUPDATE $PRESENT_DUMP $NEXT_DUMP
ret=$?
echo `date` >> $LOGFILE
if [ "$ret" = "21" ]; then 	
	echo NOTICE: Your OSM file is already up-to-date. Aborting...  >> $LOGFILE
	echo `date '+%d.%m.%Y %H:%M'` > $LASTUPDATE
	exit 0
elif [ "$ret" != "0" ]; then
	echo ERROR: Unsuccesful download of updated dump. [$ret] >> $LOGFILE
	exit 1
else
	echo Updated dump downloaded. [$ret] >> $LOGFILE
fi
$OSMFILTER $NEXT_DUMP --keep-ways="highway=" --keep="operator=cz:KČT" --out-o5m > $NEXT_FILTERED_DUMP
ret=$?
echo `date` >> $LOGFILE
if [ "$ret" != "0" ]; then 
	echo ERROR: Filter operation failed. [$ret] >> $LOGFILE
	exit 1
else
	echo Updated dump succesfully filtered. [$ret] >> $LOGFILE
fi
$OSMCONVERT $PRESENT_FILTERED_DUMP $NEXT_FILTERED_DUMP  --diff -o=$UPDATE
ret=$?
echo `date` >> $LOGFILE
if [ "$ret" != "0" ]; then 
	echo ERROR: Convert operation failed. [$ret] >> $LOGFILE
	exit 1
else
	echo Filtered updated dump succesfully converted. [$ret] >> $LOGFILE
fi
$OSMOSIS --rxc file=$UPDATE --wpc authFile=$AUTH_FILE
ret=$?
echo `date` >> $LOGFILE
if [ "$ret" != "0" ]; then 
	echo ERROR: Failed to update database. [$ret] >> $LOGFILE
	exit 1
else
	echo "Database updated. [$ret]" >> $LOGFILE
	echo `date '+%d.%m.%Y %H:%M'` > $LASTUPDATE
	php /var/www/html/xsvana00/tables/php/saveStats.php
fi

#remove stuff outside of region of interest - CZ
$OSMCONVERT -B=czech-republic.poly $NEXT_DUMP >$PRESENT_DUMP
rm $NEXT_DUMP

mv $NEXT_FILTERED_DUMP $PRESENT_FILTERED_DUMP
cd $CURRENT

exit 0
