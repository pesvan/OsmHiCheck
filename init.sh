#!/bin/bash

#
# need to TRUNCATE all data tables in postgresql before you run this!
# TRUNCATE nodes, relations, relation_members, users, ways, way_nodes;
#


PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin
#
#variables to set
#
#paths
OSMOSIS=osmosis
OSMFILTER=osmfilter
OSMCONVERT=osmconvert

#path to working directory
WORK_DIR=/home/xsvana00/data

#path to logfile
LOGFILE=/home/xsvana00/update.log
LASTUPDATE=/var/www/html/xsvana00/last_update.txt

#filenames
GEOFABRIK_FILE=czech-republic-latest.osm.pbf
FIRST_IMPORT=first_import.pbf

PRESENT_DUMP=converted_dump.o5m
PRESENT_FILTERED_DUMP=filtered_dump.o5m
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

rm $GEOFABRIK_FILE
wget http://download.geofabrik.de/europe/$GEOFABRIK_FILE
ret=$?
echo `date` >> $LOGFILE
if [ "$ret" != "0" ]; then
	echo ERROR: Unsuccesful download of OSM data. [$ret] >> $LOGFILE
	exit 1
else
	echo OSM data downloaded. [$ret] >> $LOGFILE
fi

$OSMCONVERT --out-o5m $GEOFABRIK_FILE >$PRESENT_DUMP
ret=$?
echo `date` >> $LOGFILE
if [ "$ret" != "0" ]; then 
	echo ERROR: Convert operation failed. [$ret] >> $LOGFILE
	exit 1
else
	echo OSM download succesfully converted. [$ret] >> $LOGFILE
fi

$OSMFILTER $PRESENT_DUMP --keep-ways="highway=" --keep="operator=cz:KČT" --out-o5m > $PRESENT_FILTERED_DUMP
ret=$?
echo `date` >> $LOGFILE
if [ "$ret" != "0" ]; then 
	echo ERROR: Filter operation failed. [$ret] >> $LOGFILE
	exit 1
else
	echo OSM download succesfully filtered. [$ret] >> $LOGFILE
fi

$OSMCONVERT --out-pbf $PRESENT_FILTERED_DUMP >$FIRST_IMPORT

$OSMOSIS --rb $FIRST_IMPORT --wp authFile=$AUTH_FILE
ret=$?
echo `date` >> $LOGFILE
if [ "$ret" != "0" ]; then 
	echo ERROR: Failed to import database. [$ret] >> $LOGFILE
	exit 1
else
	echo "Database imported. [$ret]" >> $LOGFILE
	echo `date '+%d.%m.%Y %H:%M'` > $LASTUPDATE
	php /var/www/html/xsvana00/tables/php/saveStats.php
fi

rm $FIRST_IMPORT
rm $GEOFABRIK_FILE

cd $CURRENT

exit 0
