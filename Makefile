WEB?=/var/www/html/OsmHiCheck

PHP_FILES=index.html uploads
CSS_FILES=css/stylesheet.css
IMAGE_FILES=images/screen.png

install: 
	rsync -vap $(PHP_FILES) $(WEB)/
	rsync -vap $(IMAGE_FILES) $(WEB)/images
	rsync -vap $(CSS_FILES) $(WEB)/css
	make -C tables
	make -C map
	make -C gp

