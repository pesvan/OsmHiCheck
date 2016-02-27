#!/bin/sh
#
#Petr Svana, March 2015
#

#change your stuff here
#--------------------------------------------------------
#path to working directory where files will be stored
WORK_DIR=/home/xsvana00/data

#path to logfile
LOGFILE=/home/xsvana00/update.log

#path to web root - where to call PHP scripts and save timestamp
WEB_ROOT=/var/www/html/OsmHiCheck

#database credentials for osmosis
AUTH_FILE=$HOME/.osmosis.auth
#--------------------------------------------------------

PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin
OSMOSIS=osmosis
OSMFILTER=osmfilter
OSMUPDATE=osmupdate
OSMCONVERT=osmconvert

#filenames
PRESENT_DUMP=converted_dump.o5m
PRESENT_FILTERED_DUMP=filtered_dump.o5m
NEXT_DUMP=updated_converted_dump.o5m
NEXT_FILTERED_DUMP=updated_filtered_dump.o5m
UPDATE=update_database.osc

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

#clip by CZ polygon - got it on http://download.geofabrik.de/europe/czech-republic.html
$OSMUPDATE $PRESENT_DUMP $NEXT_DUMP -B=czech-republic.poly
ret=$?
echo `date` >> $LOGFILE
if [ "$ret" = "21" ]; then 	
	echo NOTICE: Your OSM file is already up-to-date. Aborting...  >> $LOGFILE
	echo `date '+%d.%m.%Y %H:%M'` > $WEB_ROOT/last_update.txt
	exit 0
elif [ "$ret" != "0" ]; then
	echo ERROR: Unsuccesful download of updated dump. [$ret] >> $LOGFILE
	exit 1
else
	echo Updated dump downloaded. [$ret] >> $LOGFILE
fi
$OSMFILTER $NEXT_DUMP --keep-ways="highway=" --keep="operator=cz:KČT information=guidepost" --out-o5m > $NEXT_FILTERED_DUMP
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
	echo `date '+%d.%m.%Y %H:%M'` > $WEB_ROOT/last_update.txt
	php $WEB_ROOT/tables/php/saveStats.php
	php $WEB_ROOT/gp/saveStats.php
fi

#remove old unused stuff
mv $NEXT_DUMP $PRESENT_DUMP
mv $NEXT_FILTERED_DUMP $PRESENT_FILTERED_DUMP
rm $UPDATE

cd $CURRENT

exit 0
