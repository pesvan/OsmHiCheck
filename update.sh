#!/bin/sh
PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin
#Petr Svana, March 2015
#
#variables to set
#
#paths
OSMOSIS=/home/xsvana00/osmosis-0.43.1/bin/osmosis
OSMFILTER=/home/xsvana00/osmfilter
OSMUPDATE=/home/xsvana00/osmupdate
OSMCONVERT=/usr/local/bin/osmconvert

#path to working directory
FILES=/home/xsvana00

#path to logfile
LOGFILE=/var/www/html/xsvana00/update.log
LASTUPDATE=/var/www/html/xsvana00/last_update.txt

#filenames
PRESENT_DUMP=converted_dump.o5m
PRESENT_FILTERED_DUMP=filtered_dump.o5m
NEXT_DUMP=updated_converted_dump.o5m
NEXT_FILTERED_DUMP=updated_filtered_dump.o5m
UPDATE=update_database.osc

#database credential
DATABASE=xsvana00
USER=xsvana00
PASS=osm

echo ------------------------------------------------------------ >> $LOGFILE 

CURRENT=`pwd`
cd $FILES

$OSMUPDATE $PRESENT_DUMP $NEXT_DUMP
ret=$?
echo `date` >> $LOGFILE
if [ "$ret" = "21" ]; then 	
	echo NOTICE: Your OSM file is already up-to-date. Aborting...  >> $LOGFILE
	echo `date -R` > $LASTUPDATE
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
$OSMOSIS --rxc file=$UPDATE --wpc database=$DATABASE user=$USER password=$PASS &> /dev/null
ret=$?
echo `date` >> $LOGFILE
if [ "$ret" != "0" ]; then 
	echo ERROR: Failed to update database. [$ret] >> $LOGFILE
	exit 1
else
	echo Database updated. [$ret] >> $LOGFILE
	echo `date -R` > $LASTUPDATE
fi

mv $NEXT_DUMP $PRESENT_DUMP
mv $NEXT_FILTERED_DUMP $PRESENT_FILTERED_DUMP
cd $CURRENT
exit 0
