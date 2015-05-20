Readme k bakalarske praci "Kontrola turistickych tras v KCT v datech OSM"

10/2014 - 05/2015

Autor:
Petr Svana, xsvana00@stud.fit.vutbr.cz nebo petr.svana@gmail.com

URL aplikace:
http://osm.fit.vutbr.cz/xsvana00/

Prace s aplikaci bez instalace:
K prohlizeni aplikace staci novejsi verze soucasnych internetovych prohlizecu, aplikace muze vykazovat problemy v prohlizeci Internet Explorer 9 a starsi.
Je potreba povolit vykonavani JavaScriptu.
Ovladani a moznosti uzivatele aplikace jsou popsany v textu bakalarske prace v kapitole c. 6: Manual k vysledne aplikaci

uzivatelske ucty:
aplikace je nepouziva s jednou vyjimkou, a to je zneviditelneni uzivatelskeho vstupu, pro vyzkouseni teto funkcionality pouzijte parametry:
username: ukazka, heslo: fit_v_brne

SW pozadavky pro vlastni instanci aplikace:
postgresql 9.3, postgis 2.1, osmosis, webovy server (apache), php 5 + GL knihovna, osmconvert, osmfilter, osmupdate

Instalace vlastni instance(v zavorkach odkazy do textove casti bakalarske prace):
1) Konfigurace databaze (5.2.1)
2) Prvotni import dat do databaze (5.2.2)
3) Nastaveni pravidelneho vykonavani skriptu src/update.sh pomoci nastroje cronjob
4) Vytvoreni uzivatelskych tabulek pomoci sql souboru umistenych v src/sql_import/
5) Zkopirovani obsahu slozky src/ na Vas webovy server

Zdrojovy kod je mj. dostupny take na GitHubu:
https://github.com/pesvan/OsmHiCheck


Adresarova struktura aplikace:
src/ - root projektu
src/css/ - css soubory k webovemu rozcestniku mezi mapovou a tabukovou casti
src/images/ - obrazky k vzhledu rozcestniku mezi mapovou a tabukovou casti
src/map/ - implementace mapove casti
src/map/css/ - css soubory k mapove casti
src/map/css/leaflet/ - css soubory pouzite knihovny leaflet a jejich zasuvnych modulu
src/map/images/ - pouzite obrazky pro vzhled mapove casti
src/map/js/ - funkcionalita na strane klienta
src/map/js/jquery/
src/map/js/leaflet/ - pouzite JS knihovny a zasuvne moduly
src/map/json/ - ukazkove soubory pro import
src/map/php/ - soubory zajistujici serverovou cast aplikace
src/sql_import/ - soubory pro vytvoreni tabulek mimo OSM data
src/tables/ - implementace tabulkove casti
src/tables/css/ - css soubory k tabulkove casti
src/tables/php - funkcionalita tabulkove casti v PHP
src/tables/php/phplot - knihovna pro vytvoreni grafu
src/uploads/ - slozka, kam se ukladaji uzivateli nahrane obrazky

...
src/db_conf.php - konfiguracni soubor pro databazove udaje
src/update.sh - skript pro pravidelnou aktualizaci databaze

soubory vygenerovane skriptem src/update.sh :
src/error.log - obsahuje posledni chybovy vystup aktualizace
src/last_update.txt - obsahuje datum a cas posledni aktualizace OSM dat
src/update.log - logovaci soubor pravidelnych updatu OSM databaze


Seznam pouzitych externich zasuvnych modulu a knihoven:
[nazev] - [ucel v aplikaci]
    [url adresa projektu]
    [typ a url licence]

Mapova cast, JavaScript:
Leaflet - zaklad pro vykresleni mapy
    leafletjs.com
    https://github.com/Leaflet/Leaflet/blob/master/LICENSE
Leaflet Control OSM Geocoder - vyhledavani v mape
    https://github.com/k4r573n/leaflet-control-osm-geocoder
    https://github.com/k4r573n/leaflet-control-osm-geocoder/blob/master/LICENSE
Leaflet Control LineStringSelect - usnadneni implementace vyberu useku cesty uzivatelem
    https://github.com/w8r/L.Control.LineStringSelect
    [MIT] https://github.com/w8r/L.Control.LineStringSelect/blob/master/LICENSE
Leaflet Control Sidebar - zaklad postranniho panelu aplikace
    https://github.com/turbo87/leaflet-sidebar/
    [MIT] https://github.com/Turbo87/leaflet-sidebar/blob/master/LICENSE
Leaflet Control Zoomslider - posuvny zoom
    https://github.com/kartena/Leaflet.zoomslider
    https://github.com/kartena/Leaflet.zoomslider/blob/master/LICENSE
Leaflet Awesome Markers - design zobrazovanych znacek
    https://github.com/lvoogdt/Leaflet.awesome-markers
    https://github.com/lvoogdt/Leaflet.awesome-markers/blob/master/LICENSE
Leaflet FileLayer - nahravani gpx a podobnych souboru do mapy
    https://github.com/makinacorpus/Leaflet.FileLayer
    [MIT] https://github.com/makinacorpus/Leaflet.FileLayer/blob/master/LICENSE
Leafet Hash - hash aktualni polohy a zoomu v adresnim radku
    http://mlevans.github.io/leaflet-hash/
    https://github.com/mlevans/leaflet-hash/blob/master/LICENSE.md
ToGeoJSON - pomocny modul pro Leaflet FileLayer
    https://github.com/mapbox/togeojson
    [ok] https://github.com/mapbox/togeojson/blob/master/LICENSE

Tabulkova cast, PHP:
PHPlot - vykresleni grafu
    http://www.phplot.com
