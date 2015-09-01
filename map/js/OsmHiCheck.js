/** globalni promenne */

var map;

//hranicni hodnota zoomu pro rozmezi mezi zobrazenim relace jako linie a jako relace
var NODES_WAYS_EDGE = 12;
//promenna pro manipulaci s prvkem - ovladani vrstev
var controlLayersHandler;
//manipulace s postrannim panelem
var sidebarHandler;

//pouzite vykreslovaci vrstvy
var layerNames = ["tracks", "warning", "error", "guide", "userNotes", "allWays", 'userParts'];
var layers = [];

var TRACKS = 0;
var WARNINGS = 1;
var ERRORS = 2;
var GUIDE = 3;
var USERNOTES = 4;
var ALLWAYS = 5;
var USERPARTS = 6;

for (i = 0; i < layerNames.length; i++){
    layers.push({
        key: i,
        name: layerNames[i],
        layer: null,
        isOn: false,
        list: [],
        drawn: [],
        icon: null
    });
}

// stavove promenne pro vrstvy
var cycleRelationsAreOn = false;

//pomocne promenne pro zamezeni opakovemu nahravani ve stejny moment
var canGetData = true;
var canGetData2 = true;

//ulozeni typu vybraneho elementu
var selectedElement;

/** imitace enum - konstanty pro lepsi orientaci */
var RELATION = 0;
var WAY = 1;
var NODE = 2;
//pole objektu, jenz obsahuji reference na naposledy kliknuty objekt
var selected = [];

for (var i = 0; i < 3; i++){
    selected.push({
        id: null,
        data: null,
        errorNumber: 0,
        incorrectNumber: 0,
        kctKey: null
    })
}
//obsah sidebaru
var DATA = 0;
var LAYERS = 1;
var CR_NODE1 = 2;
var CR_NODE2 = 3;
var CR_PART1 = 5;
var CR_PART2 = 6;
var GUIDEPOST_INFO = 7;
var IMPORT = 8;

//stavova promenna pro vytvoreni uzivatelskeho bodu
var CREATE_NODE = false;

//zpravy uzivateli
var msgCreateNodeBefore = "Pro vytvoření bodu s poznámkou klikněte na požadovanou lokaci na mapě.";
var msgError = "Nevyplněno vše";
var msgErrorCoords = "Špatný formát souřadnic";
var msgErrorPart = "Cesta není kompletní";
var msgErrorDate = "Špatný formát data";
var msgErrorFile = "Nevybrán žádný soubor";
var msgErrorFileType = "Soubor není povoleného typu";
var msgErrorSelect = "Nebyl vybrán žádný typ";
var msgErrorImage = "Špatný formát jednoho nebo více obrázků";
var msgCreateLineBefore = "Pro vyznačení části trasy klikněte na požadovaný úsek. Při úrovni zoomu 15 a blíže (tzn. měřítko vlevo dole ukazuje 300 m a méně) se zobrazí také ostatní cesty, které nejsou součástí turistických relací (way:highway=)";

//vytvoreni bodu - pomocne promenne
var lngLatFromClick = null;
var dataFromClick = false;
//pomocny marker
var temporaryMarker = null;
var tempIcon;
// promenna pro manipulaci s usekem cesty
var selectedWayToControl;
// ulozeni soucasneho stavu sidebaru
var sidebarContent = null;

//vypnuti cachovani AJAXU
$.ajaxSetup({    
    cache: false
});

function initMap() {
    document.getElementById('cycle').checked = false;
    /** mapa **/
    map = new L.Map('mymap', {
        zoomControl: false
    });
    map.user = "";
    /** podklad **/
    var osmUrl = 'http://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
    var osmAttrib = 'Map data &copy; <a href="http://openstreetmap.org">OpenStreetMap</a> contributors';
    var osm = new L.TileLayer(osmUrl, {
        minZoom: 1,
        maxZoom: 19,
        attribution: osmAttrib
    });


    map.setView(new L.LatLng(48.952, 16.734), 13);
    map.addLayer(osm);

    /** ikony */
    L.AwesomeMarkers.Icon.prototype.options.prefix = 'ion';

    layers[TRACKS].icon = L.AwesomeMarkers.icon({
        markerColor: 'green',
        icon: 'navigate'
    });
    layers[WARNINGS].icon = L.AwesomeMarkers.icon({
        markerColor: 'orange',
        icon: 'navigate',
        iconColor: 'black'
    });
    layers[ERRORS].icon = L.AwesomeMarkers.icon({
        markerColor: 'red',
        icon: 'alert'
    });
    layers[GUIDE].icon = L.AwesomeMarkers.icon({
        markerColor: 'blue',
        icon: 'location'
    });
    layers[USERNOTES].icon = L.AwesomeMarkers.icon({
        markerColor: 'darkpurple',
        icon: 'compose'
    });
    layers[USERPARTS].icon = L.AwesomeMarkers.icon({
        markerColor: 'yellow',
        icon: 'navigate',
        iconColor: 'black'
    });
    tempIcon = L.AwesomeMarkers.icon({
        markerColor: 'white',
        icon: 'compose',
        iconColor: 'black'
    });


    /** meritko (na mape vlevo dole) **/
    L.control.scale().addTo(map);


    /** zobrazi zoom a souradnice v adrese  */
    new L.Hash(map);

    /** vyhledavani **/
    var osmGeoCoder = new L.Control.OSMGeocoder();
    osmGeoCoder.setPosition('topleft');
    map.addControl(osmGeoCoder);

    /** posuvne meritko pro ovladani zoomu **/
    new L.Control.Zoomslider().addTo(map);

    /** postranni panel s informacemi a ovladanim **/
    sidebarHandler = L.control.sidebar('sidebar', {
        position: 'right',
        autoPan: false,
        closeButton: false
    });
    map.addControl(sidebarHandler);
    sidebarHandler.show();

    /** nahravani vlastnich *geo* souboru */
    L.Control.FileLayerLoad.LABEL = '<i class="fa fa-folder-open"></i>';
    L.Control.fileLayerLoad({
        layerOptions: {
            style: {
                color: '#22AAFF',
                opacity: '5'
            }
        },
        addToMap: true,
        fileSizeLimit: 2048,
        formats: [
            '.geojson',
            '.kml',
            '.gpx'
        ]
    }).addTo(map);

    /**
     * Definice overlay vrstev
     */
    /** Turisticke relace **/
    layers[TRACKS].layer = L.geoJson(null, {
        style: function (feature) {
            var color;
            switch (feature.properties.color) {
                case 5:
                    color = "#ff00ff";//cyklo
                    break;
                case 1:
                    color = "#ff0000";//red
                    break;
                case 2:
                    color = "#0000ff";//blue
                    break;
                case 3:
                    color = "#00ff00";//green
                    break;
                case 4:
                    color = "#ffff00";//yellow
                    break;
                default:
                    color = "#666666";//gray
                    break;
            }
            return {
                color: color
            }
        },
        onEachFeature: onEachFeature,
        pointToLayer: function (feature, latlng) {
            return L.marker(latlng, {
                icon: layers[TRACKS].icon
            });
        },
        id: TRACKS
    });

    /** relace s chybejicimi tagy **/
    layers[WARNINGS].layer = L.geoJson(null, {
        style: function () {
            return {
                "color": "#ff7800",
                "weight": 20,
                "opacity": 0.2
            }
        },
        onEachFeature: onEachFeature,
        pointToLayer: function (feature, latlng) {
            return L.marker(latlng, {
                icon: layers[WARNINGS].icon
            });
        },
        id: WARNINGS
    });

    /** relace s chybami **/
    layers[ERRORS].layer = L.geoJson(null, {
        style: function () {
            return {
                "color": "#ff0000",
                "weight": 20,
                "opacity": 0.3
            }
        },
        onEachFeature: onEachFeature,
        pointToLayer: function (feature, latlng) {
            return L.marker(latlng, {
                icon: layers[ERRORS].icon
            });
        },
        id: ERRORS
    });

    /** rozcestniky **/
    layers[GUIDE].layer = L.geoJson(null, {
        onEachFeature: onEachFeature,
        pointToLayer: function (feature, latlng) {
            return L.marker(latlng, {
                icon: layers[GUIDE].icon
            });
        },
        id: GUIDE
    });

    /** uzivatelske poznamky **/
    layers[USERNOTES].layer = L.geoJson(null, {
        id: USERNOTES,
        pointToLayer: function (feature, latlng) {
            var image = "";
            var osmLink = ""; //vytvoreni odkazu na OSM, pokud to uzivatel potvrdil pri vytvoreni
            if(feature.properties.osm==1){
                osmLink += "<a target='_blank' href='http://www.openstreetmap.org/user/"+feature.properties.user+"'>(OSM uživatel)</a>";
            }
            if(feature.properties.image.length>0){
                image += "<a href='../uploads/"+feature.properties.image+"' target='_blank'><img src='../uploads/"+feature.properties.image+"'/></a><br />";
            }
            return L.marker(latlng, {
                icon: layers[USERNOTES].icon
            }).bindPopup(
                "<h3>Poznámka</h3>" +
                "typ: "+ resolveSelectType(feature.properties.type) +  " <br />"+
                "poznámka: " + feature.properties.note + "<br/>" +
                "aktuálnost poznámky:" + feature.properties.date + "<br/>" +
                "čas vložení: " + feature.properties.timestamp + "<br/>" +
                 image +
                "uživatel: " + feature.properties.user + " " + osmLink +"<br/>" +
                "<a onclick='deleteUserContent(CR_NODE1,"+ feature.properties.id+")'>Odstranit poznámku</a><br/>"
            );
        }
    });

    /** vrstva pro zobrazeni vsech cest pri pridavani useku cesty */
    layers[ALLWAYS].layer = L.geoJson(null, {
        id: ALLWAYS,
        style: function () {
            return {
                "color": "#444444",
                "opacity": 0.5
            }
        },
        onEachFeature: onEachFeature
    });

    /** vrstva pro zobr. uzivatelskych useku cesty */
    layers[USERPARTS].layer = L.geoJson(null, {
        id: USERPARTS,
        style: function() {
            return {
                "color": "#00FFFF"
            }
        },
        onEachFeature: onEachFeatureUserPart
    });

    /** nastaveni vrstev*/
    var overlays = {
        "Turistické relace": layers[TRACKS].layer,
        "<span class='blue'>Rozcestníky</span>": layers[GUIDE].layer,
        "<span class='red'>Zapnout kontrolu chyb v relacích</span>": layers[ERRORS].layer,
        "<span class='orange'>Zvýraznění relací s chybějícími tagy</span>": layers[WARNINGS].layer,
        "<span class='purple'>Uživatelské poznámky</span>": layers[USERNOTES].layer,
        "<span class='lightblue'>Uživateli zvýrazněné úseky</span>": layers[USERPARTS].layer
    };

    /** ovladaci prvek pro zobrazovani vrstev, se kterym je treba manipulovat, presun na sidebar */
    controlLayersHandler = L.control.layers({
        "OpenStreetMap Mapnik": osm
    }, overlays, {collapsed: false});
    controlLayersHandler.setPosition('topleft').addTo(map);
    controlLayersHandler._container.remove();
    changeSidebarContent(LAYERS);

    //inicializace nastroje pro vyber useku
    selectedWayToControl = new L.Control.LineStringSelect({})
    map.addControl(selectedWayToControl);


    map.on('moveend', mapMoved);
    map.on('overlayadd', onOverlayAdd);
    map.on('overlayremove', onOverlayRemove);
    map.on('click', onClickElsewhere);
    map.on('zoomend', mapZoomed);

    /** zobrazeni udaje o aktualnosti a odkazu na github v attribution*/
    jQuery.get("../last_update.txt", function (data) {
        var x= document.getElementsByClassName('leaflet-control-attribution');
        x[0].innerHTML = "<a href='https://github.com/pesvan/OsmHiCheck'>Github</a> | OSM data updated: "+data+" <br /> "+x[0].innerHTML;
    });
}

/**
 * funkce zajistujici vytvoreni docasneho uzlu
 * @param e
 * @param bind
 */
function createNode(e, bind) {
    if (CREATE_NODE) {
        lngLatFromClick = e.latlng;
        dataFromClick = bind;
        changeSidebarContent(CR_NODE2);
        temporaryMarker = L.marker(lngLatFromClick, {
            clickable: false,
            icon: tempIcon
        });
        temporaryMarker.addTo(map);
    }
}

/** *
 * nastaveni reakce na kliknuti na prvek
 * @param feature
 * @param layer
 */
function onEachFeature(feature, layer) {
    setTimeout(function () {
        setSidebarLoading(false);
    }, 1000);
    layer.on('click', onClick);
}

/**
 * nastaveni reakce na kliknuti na uzivatelem vyznaceny usek cesty
 * @param feature
 * @param layer
 */
function onEachFeatureUserPart(feature, layer){
    var osmLink = "";
    if(feature.properties.osm==1){
        osmLink += "<a target='_blank' href='http://www.openstreetmap.org/user/"+feature.properties.user+"'>(OSM uživatel)</a>";
    }
    layer.bindPopup(
        "<h3>Poznámka</h3>" +
        "typ: "+ resolveSelectType(feature.properties.type) + " <br />"+
        "poznámka: " + feature.properties.note + "<br/>" +
        "aktuálnost poznámky:" + feature.properties.date + "<br/>" +
        "čas: " + feature.properties.timestamp + "<br/>" +
        "uživatel: " + feature.properties.user + " " + osmLink +"<br/>" +
        "<a onclick='deleteUserContent(CR_PART1,"+ feature.properties.id+")'>Odstranit poznámku</a><br/>"

    );
}

/**
 * interakce uzivatele po kliknuti na prvek v mape
 * @param e
 */
function onClick(e) {
    if (CREATE_NODE) {
        createNode(e, true);
    } else if(layers[ALLWAYS].isOn) {
        selectedWayToControl.enable({
            layer: e.target,
            feature: e.target.feature
        });
        changeSidebarContent(CR_PART2);
    } else { //ulozeni informaci o kliknutem prvku
        selected[RELATION].id= e.target.feature.relation_id;
        selected[RELATION].errorNumber = e.target.feature.properties.errValue;
        selected[RELATION].incorrectNumber = e.target.feature.properties.incValue;
        selected[RELATION].kctKey = e.target.feature.properties.kctkey;
        getSpecifics(RELATION);
        if (e.target.feature.int_type == WAY) {
            selected[WAY].id = e.target.feature.member_id;
            selected[NODE].id = null;
            getSpecifics(WAY);
            selectedElement = WAY;
        } else if (e.target.feature.int_type == NODE) {
            selected[NODE].id = e.target.feature.member_id;
            selected[WAY].id = null;
            getSpecifics(NODE);
            selectedElement = NODE;
        }
    }
}

/**
 * @param e
 */
function onClickElsewhere(e) {
    createNode(e, false);
}


/**
 * zapnuti dane vrstvy po pridani do mapy
 * @param e
 */
function onOverlayAdd(e) {
    //fix, aby se funkce nevolala 2x po sobe
    if (!canGetData) {
        return;
    }
    canGetData = false;
    layers[e.layer.options.id].isOn = true;
    getData();
    setTimeout(function () {
        canGetData = true;
    }, 500);
}

/**
 *  vycisteni dat a vypnuti dane vrstvy pri odebrani
 * @param e
 */
function onOverlayRemove(e) {
    var id = e.layer.options.id;
    layers[id].isOn = false;
    layers[id].list = [];
    layers[id].drawn = [];
    layers[id].layer.clearLayers();
    if(map.getZoom()<NODES_WAYS_EDGE){// fix pro spravne zobrazovani
        if((id==WARNINGS || id==ERRORS) && layers[TRACKS].isOn){
            resetLayer(TRACKS);
            getData();
        }
        if(id==ERRORS && layers[WARNINGS].isOn){
            resetLayer(WARNINGS);
            getData();
        }
    }
}
/**
 * pri pohybu mapou je treba zjistit nova data
 */
function mapMoved() {
    getData();
}

/**
 * funkce pro zjisteni blizsich dat k zadanemu prvku
 * @param type
 */
function getSpecifics(type) {

    var url = 'data=' + selected[type].id + '&type=' + type;
    $.ajax({
        url: 'php/getElement.php',
        dataType: 'json',
        data: url,
        success: function (data) {
            selected[type].data = data;
        },
        complete: function () {
            changeSidebarContent(DATA);

        }
    });
}

/**
 * funkce ziska data o relacich ve vyrezu prohlizece a posle je dale ke zpracovani
 */
function getData() {
    if (layers[TRACKS].isOn || layers[WARNINGS].isOn || layers[ERRORS].isOn || layers[GUIDE].isOn ) {
        $.ajax({
            beforeSend: function () {
                setSidebarLoading(true);
            },
            url: 'php/getList.php',
            dataType: 'json',
            data: 'bbox=' + map.getBounds().toBBoxString() + '&type=0&bicycle=' + cycleRelationsAreOn,
            success: function (data) {
                processListDataOSM(data);
            } //success
        });//ajax
    }
    if (layers[USERNOTES].isOn ) {
        $.ajax({
            beforeSend: function () {
                setSidebarLoading(true);
            },
            url: 'php/getList.php',
            dataType: 'json',
            data: 'bbox=' + map.getBounds().toBBoxString() + '&type=1&bicycle=false',
            success: function (data) {

                processListDataUser(data, USERNOTES);
            } //success
        });//ajax
    }
    if (layers[USERPARTS].isOn ) {
        $.ajax({
            beforeSend: function () {
                setSidebarLoading(true);
            },
            url: 'php/getList.php',
            dataType: 'json',
            data: 'bbox=' + map.getBounds().toBBoxString() + '&type=3&bicycle=false',
            success: function (data) {

                processListDataUser(data, USERPARTS);
            } //success
        });//ajax
    }
    if (layers[ALLWAYS].isOn ) {
        $.ajax({
            beforeSend: function () {
                setSidebarLoading(true);
            },
            url: 'php/getList.php',
            dataType: 'json',
            data: 'bbox=' + map.getBounds().toBBoxString() + '&type=2&bicycle=false',
            success: function (data) {
                processListDataAllWays(data);
            } //success
        });//ajax
    }
}

/**
 * funkce pro protrideni relaci a zisk tech, ktere jsou skutecne potreba
 * @param data
 */
function processListDataOSM(data) {
    filterUnused(data);
    var toDraw = filterNewRelations(data);
    var isSomethingToDraw = false;
    for (var array in toDraw){ //jestli neni nic k vykresleni, konec funkce
        if(toDraw[array].length!=0){
            isSomethingToDraw = true;
            break;
        }
    }

    if(isSomethingToDraw){
        for (var relation in toDraw[TRACKS]) {
            if (toDraw[TRACKS].hasOwnProperty(relation) && layers[TRACKS].isOn) {
                getWaysToDraw(toDraw[TRACKS][relation], TRACKS);
            }
        }
        for (relation in toDraw[WARNINGS]) {
            if (toDraw[WARNINGS].hasOwnProperty(relation) && layers[WARNINGS].isOn) {
                getWaysToDraw(toDraw[WARNINGS][relation], WARNINGS);
            }
        }
        for (relation in toDraw[ERRORS]) {
            if (toDraw[ERRORS].hasOwnProperty(relation) && layers[ERRORS].isOn) {
                getWaysToDraw(toDraw[ERRORS][relation], ERRORS);
            }
        }
        for (relation in toDraw[GUIDE]) {
            if (toDraw[GUIDE].hasOwnProperty(relation) && layers[GUIDE].isOn) {
                getWaysToDraw(toDraw[GUIDE][relation], GUIDE);
            }
        }
    } else {
        setSidebarLoading(false);
    }
}

/**
 * zvlastni processing vsech cest, kvuli jinemu charakteru se zpracovava v samostatne funkci
 * @param data
 */
function processListDataAllWays(data){
    var toDraw = filterNewWays(data);
    deleteUnusedGeoJSONRelations($(layers[ALLWAYS].layer).not(data).get(), layers[ALLWAYS].layer);
    for (var way in toDraw[ALLWAYS]) {
        if (toDraw[ALLWAYS].hasOwnProperty(way) && layers[ALLWAYS].isOn) {
            getWaysToDraw(toDraw[ALLWAYS][way], ALLWAYS);
        }
    }
}

/**
 * Funkce zajistujici ziskani uzivatelskych dat
 * @param data
 */
function processListDataUser(data, type) {
    if(type==USERNOTES){
        for (var element in data) {
            if (data.hasOwnProperty(element)  && layers[USERNOTES].isOn && layers[USERNOTES].list.indexOf(data[element]) == -1) {
                layers[USERNOTES].list[layers[USERNOTES].list.length] = data[element];
                getNotesToDraw(data[element], USERNOTES);
            }
        }
    } else if(type==USERPARTS){
        for (element in data) {
            if (layers[USERPARTS].isOn && layers[USERPARTS].list.indexOf(data[element]) == -1) {
                layers[USERPARTS].list[layers[USERPARTS].list.length] = data[element];
                getNotesToDraw(data[element], USERPARTS);
            }
        }
    }
}

/**
 * Funkce ziska ze ziskanych dat rozdilove mnoziny
 * vysledkem je smazani prvku, ktere jsou mimo vyrez a nejsou jiz potreba
 * @param data
 */
function filterUnused(data) {
    for(var i = 0; i < layers.length; i++){
        if (!(i == ALLWAYS || i == USERNOTES)) {
            if (layers[i].isOn) {
                deleteUnusedGeoJSONRelations($(layers[i].list).not(data).get(), layers[i].layer);
            }
        }
    }
}

/**
 * Funkce ziska ze ziskanych dat rozdilove mnoziny
 * vysledkem jsou prvky, ktere jsou ve vyrezu obrazovky a nejsou jeste vykresleny
 * @param data
 * @returns {Array}
 */
function filterNewRelations(data) {
    var toDraw = [];
    for(var i = 0; i < layers.length; i++){
        if (i != USERNOTES && i != ALLWAYS && i!=USERPARTS) {
            if(layers[i].isOn){
                toDraw[i] = $(data).not(layers[i].list).get();
                layers[i].list = data;
            }
        }
    }
    if(map.getZoom()<NODES_WAYS_EDGE) {
        if (layers[TRACKS].isOn && layers[WARNINGS].isOn) {
            toDraw[TRACKS] = $(layers[TRACKS].list).not(layers[WARNINGS].drawn).get();
        }
        if (layers[TRACKS].isOn && layers[ERRORS].isOn) {
            toDraw[TRACKS] = $(layers[TRACKS].list).not(layers[ERRORS].drawn).get();
        }
        if (layers[WARNINGS].isOn && layers[ERRORS].isOn) {
            toDraw[WARNINGS] = $(layers[WARNINGS].list).not(layers[ERRORS].drawn).get();
        }
    }
    return toDraw;
}

function filterNewWays(data) {
    var toDraw = [];
    toDraw[ALLWAYS] = $(data).not(layers[ALLWAYS].list).get();
    layers[ALLWAYS].list = data;
    return toDraw;
}

/**
 * Smazani jiz nepotrebnych GEOJson dat z prislusne vrstvy
 * @param data
 * @param layer
 */
function deleteUnusedGeoJSONRelations(data, layer) {
    for (var object in layer._layers) {
        if (layer._layers.hasOwnProperty(object)) {
            var obj = layer._layers[object];
            for (var relation in data) {
                if (obj.feature.relation_id == relation) {
                    layer.removeLayer(obj);
                }
            }
        }
    }
}

/**
 * Vycisteni vsech vrstev a dat s nimi souvisejicich
 */
function clearAllLayers() {
    for(var i = 0; i < layers.length; i++){
        layers[i].layer.clearLayers();
        layers[i].list = [];
    }
}


function mapZoomed(){
    if(sidebarContent==CR_PART1 || sidebarContent==CR_PART2){
        if(map.getZoom()<15){
            if(map.hasLayer(layers[ALLWAYS].layer)){
                map.removeLayer(layers[ALLWAYS].layer);
            }
        } else {
            if(!(map.hasLayer(layers[ALLWAYS].layer))){
                map.addLayer(layers[ALLWAYS].layer);
                layers[ALLWAYS].isOn=true;
            }
        }
    }
    clearAllLayers();
}
/**
 * ziskani uzivatelskych poznamkovych bodu a jejich vykresleni na mape
 * @param nid node id
 */
function getNotesToDraw(nid, type) {
    $.ajax({
        url: 'php/getNote.php',
        dataType: 'json',
        data: 'nid=' + nid + '&type='+type,
        success: function (data) {
            if(type==USERNOTES){
                layers[USERNOTES].layer.addData(data);
            } else if(type==USERPARTS){
                layers[USERPARTS].layer.addData(data);
            }

        }
    })
}

/**
 * funkce prida ziskana geodata do prislusneho layeru, cimz je vykresli
 * @param rid relation id
 * @param controlType typ dat
 */
function getWaysToDraw(rid, controlType) {
    $.ajax({
        url: 'php/getData.php',
        dataType: 'json',
        data: 'rid=' + rid + '&control=' + controlType + '&zoom=' + map.getZoom(),
        success: function (data) {
            if(data.features.length>0){
                if(map.getZoom()>=NODES_WAYS_EDGE && layers[TRACKS].isOn && layers[ERRORS].isOn){
                    layers[ERRORS].layer.bringToFront();
                }
                layers[controlType].layer.addData(data);
                layers[controlType].drawn[layers[controlType].drawn.length] = rid;
            }

        }
    })
}

/**
 * ziskani uzivatelskych inforaci o rozcestniku
 * @param nid
 */
function getGuideInfo(nid) {
    if (!canGetData2) {
        return;
    }
    canGetData2 = false;
    $.ajax({
        url: 'php/getGuideInfo.php',
        dataType: 'json',
        data: 'nid=' + nid,
        success: function(data){
            printGuideInfo(data);
            setTimeout(function () { //opet fix pro synchronizaci
                canGetData2 = true;
            }, 500);
        }
    });
}

/**
 * vypis uzivatelskych inforaci o rozcestniku na strance prislusneho rozcestniku
 * @param data
 */
function printGuideInfo(data){
    if(sidebarContent==DATA){
        var layer = document.getElementById('side-layer');
        for(var info in data){
            if(data.hasOwnProperty(info)){
                var osmLink = "";
                if(data[info].osm_name==1){
                    osmLink += "<a target='_blank' href='http://www.openstreetmap.org/user/"+data[info].hi_user_id+"'>(OSM uživatel)</a>";
                }
                layer.innerHTML += "Uživatel: "+data[info].hi_user_id + " " + osmLink + "<br />";
                layer.innerHTML += "Typ: "+resolveSelectType(data[info].type)+"</span><br />";
                layer.innerHTML += "Komentář: "+data[info].note+"<br />";
                layer.innerHTML += "Platné k datu: "+data[info].date+"<br />";
                if(data[info].image!='null' && data[info].image!=null){
                    layer.innerHTML += "<a href='../uploads/"+data[info].image+"' target='_blank'><img src='../uploads/"+data[info].image+"'/></a><br />";
                }
                layer.innerHTML += "<a onclick='deleteUserContent(GUIDEPOST_INFO,"+data[info].id+")'>(Odstranit)</a>";
                layer.innerHTML += "<br />";
                layer.innerHTML += "<br />";
            }
        }
    }
}

/**
 * zneviditelneni uzivatelskeho vstupu, je treba zadat jmeno a heslo superuzivatele
 * @param type
 * @param id
 */
function deleteUserContent(type, id){
    var user = prompt("Zadejte prosím jméno super-uživatele");
    var hash = prompt("Zadejte heslo");
    $.post('php/deleteUserContent.php',
        {
        uid: id,
        type: type,
        user: user,
            hash: hash
    },function(data){
            if(type==GUIDEPOST_INFO){
                changeSidebarContent(DATA);
            } else if(type==CR_NODE1){
                resetLayer(USERNOTES);
                getData();
            } else if(type==CR_PART1){
                resetLayer(USERPARTS);
                getData();
            }
    });
}

/**
 * pomocna funkce pro reset vrstvy
 * @param layer
 */
function resetLayer(layer){
    layers[layer].layer.clearLayers();
    layers[layer].list = [];
}

/**
 * prislusne zabarveni uzivatelske poznamky
 * @param type
 * @returns {string}
 */
function resolveSelectType(type){
    if(type==1){
        return "<span class='green'>OK</span>";
    } else if(type==2){
        return "<span class='red'>Problém</span>";
    } else if(type==3){
        return "Jen komentář/foto";
    } else return "chyba";
}

/**
 * Funkce pro vytvoreni tabulky tagu z JSON dat ziskanych ze serveru
 * @param data
 * @returns {string}
 * @constructor
 */
function JSONToTable(data) {
    var table = "";
    table += "<table class='relation'>";
        for (var key in selected[data].data) {
        if (key == 'tags') continue;
        if(selected[data].data.hasOwnProperty(key)){
            table += makeTableRow(key, selected[data].data[key], undefined);
        }
    }

    if (data == RELATION) {
        var faulted = [];
        if(selected[RELATION].errorNumber > 0 || selected[RELATION].incorrectNumber > 0) {
            var error = getErrorTags(selected[data].data['tags'], selected[data].kctKey, selected[data].errorNumber)
            for (var bad in error) {
                if(error.hasOwnProperty(bad)){
                    table += makeTableRow(bad, error[bad], "red white-text");
                    faulted[bad] = '';
                }
            }

            var incorrect = getIncorrectTags(selected[data].data['tags'], selected[data].kctKey, selected[data].incorrectNumber);
            for (bad in incorrect) {
                if(incorrect.hasOwnProperty(bad) && !(bad in error)){
                    table += makeTableRow(bad, incorrect[bad], "mild-red");
                    faulted[bad] = '';
                }
            }

            setWrongMessage(incorrect, selected[RELATION].errorNumber);

        } else {
            setWrongMessage(null, 0);
        }

        var missing = getMissingTags(selected[data].data['tags']);
        for (tag in missing) {
            if (missing.hasOwnProperty(tag)){
                table += makeTableRow(tag, missing[tag], "orange");
            }
        }
    }

    if(data==RELATION){

        for (var tag in selected[data].data['tags']) {
            if (selected[data].data['tags'].hasOwnProperty(tag)) {
                if (!(tag in faulted)) { //zvyraznene tagy jsou jiz vypsany pred timto
                    table += makeTableRow(tag, selected[data].data[key][tag], undefined);
                }
            }
        }

    } else {

        for (tag in selected[data].data['tags']) {
            if (selected[data].data['tags'].hasOwnProperty(tag)) {
                table += makeTableRow(tag, selected[data].data[key][tag], undefined);
            }
        }
    }

    table += "</table>";
    return table;
}

/**
 *
 * @param incorrect
 * @param errNum
 */
function setWrongMessage(incorrect, errNum){
    var element = document.getElementById('side-whatswrong');
    element.innerHTML = "";

    if(!(layers[ERRORS].isOn)){
        element.innerHTML += "<div class='notice-yellow'>Kontrola správnosti hodnot je vypnuta.</div>";
        return;
    }

    if(incorrect==null && errNum==0){
        element.innerHTML += "<div class='notice-yellow'>Automatická kontrola nenalezla nesrovnalosti.</div>";
        return;
    }

    element.innerHTML += "<div class='notice-yellow'>Nalezené nesrovnalosti v relaci: </div><br />";

    for (var value in incorrect){
        element.innerHTML += "<div class='red white-text'>Tag "+value+" ma nepovolenou hodnotu</div><br />";
    }

    if(errNum>=4){
        element.innerHTML += "<div class='red white-text'>Nesouhlasí navzájem barvy trasy u tagů kct_* a osmc:symbol</div><br />";
        errNum -= 4;
    }
    if(errNum>=2){
        element.innerHTML += "<div class='red white-text'>Nesouhlasí navzájem typ trasy u tagů route, osmc:symbol a kct_*<br /></div>";
        errNum -= 2;
    }
    if(errNum>=1){
        element.innerHTML += "<div class='red white-text'>Nesouhlasí typ sítě u tagů network a kct_*</div><br />";
    }
}

/**
 * Urceni chybejicich tagu
 * @param tags
 * @returns {Array}
 */
function getMissingTags(tags) {
    var missing = [];
    if (!("network" in tags)) {
        missing['network'] = "";
    }
    if (!("osmc:symbol" in tags)) {
        missing['osmc:symbol'] = "";
    }
    if (!("complete" in tags)) {
        missing['complete'] = "";
    }
    if (!("destinations" in tags)) {
        missing['destinations'] = "";
    }
    if (!("type" in tags)) {
        missing['type'] = "";
    }
    if (!("route" in tags)) {
        missing['route'] = "";
    }
    return missing;
}

/**
 * Ziskani spatnych hodnot tagu
 * @param tags
 * @param kctKey
 * @param number
 * @returns {Array}
 */
function getIncorrectTags(tags, kctKey, number){
    var incorrect = [];
    if(number>=128){
        if('network' in tags){
            incorrect['network'] = tags['network'];
        }
        number -= 128;
    }
    if(number>=64){
        if(kctKey in tags) {
            incorrect[kctKey] = tags[kctKey];
        }
        number -= 64;

    }
    if(number>=32){
        if('abandoned' in tags) {
            incorrect['abandoned'] = tags['abandoned'];
        }
        number -=32;
    }
    if(number>=16){
        if('complete' in tags) {
            incorrect['complete'] = tags['complete'];
        }
        number -= 16;
    }
    if(number>=8){
        if('type' in tags) {
            incorrect['type'] = tags['type'];
        }
        number -= 8;
    }
    if(number>=4){
        if('route' in tags) {
            incorrect['route'] = tags['route'];
        }
        number -= 4;
    }
    if(number>=2){
        if('osmc:symbol' in tags) {
            incorrect['osmc:symbol'] = tags['osmc:symbol'];
        }
        number -=2;
    }
    if(number>=1){
        if(kctKey in tags && !(kctKey in incorrect)) {
            incorrect[kctKey] = tags[kctKey];
        }
    }
    return incorrect;
}

/**
 * ziskani rozporu ve znackach
 * @param tags
 * @param kctKey
 * @param number
 * @returns {Array}
 */
function getErrorTags(tags, kctKey, number){
    var error = [];
    if(number>=4){
        if('osmc:symbol' in tags){
            error['osmc:symbol'] = tags['osmc:symbol'];
        }
        if(kctKey in tags) {
            error[kctKey] = tags[kctKey];
        }
        number -= 4;
    }

    if(number>=2){
        if('osmc:symbol' in tags && !('osmc:symbol' in error)){
            error['osmc:symbol'] = tags['osmc:symbol'];
        }
        if(kctKey in tags && !(kctKey in error)){
            error[kctKey] = tags[kctKey];
        }
        if('route' in tags  && !('route' in error)){
            error['route'] = tags['route'];
        }
        number -=2;
    }

    if(number>=1){
        if(kctKey in tags && !(kctKey in error)) {
            error[kctKey] = tags[kctKey];
        }
        if('network' in tags) {
            error['network'] = tags['network'];
        }
    }
    return error;
}

/**
 * Pomocna funkce pro vytvoreni radku tabulky pro zobrazeni tagu
 * @param key jmeno tagu
 * @param value nepovinny parametr, hodnota tagu
 * @param htmlclass nepovinny parametr, css trida radku
 * @returns {string}
 */
function makeTableRow(key, value, htmlclass) {
    if (value == undefined) {
        value = "";
    }
    if (htmlclass == undefined) {
        htmlclass = "";
    }
    return "<tr><td class=" + htmlclass + ">" + key + "<td class=" + htmlclass + ">" + value;
}

/**
 * Smazani obsahu sidebar, vola se pred nactenim noveho obsahu
 * take maze docasny marker, ktery nebyl ulozen do DB
 */
function sidebarClear() {
    document.getElementById('side-layer').innerHTML = "";
    document.getElementById('side-content').innerHTML = "";
    document.getElementById('side-whatswrong').innerHTML = "";
    if (map.hasLayer(temporaryMarker)) {
        map.removeLayer(temporaryMarker);
    }
    document.getElementById('side-layer2').style.display = "none";
}

/**
 * Ovladani elementu, jenz signalizuje nacitani
 * @param mapIsLoading boolean hodnota
 */
function setSidebarLoading(mapIsLoading) {
    var div = document.getElementById('side-loading');
    if (mapIsLoading) {
        $('#load-circle').addClass('red').removeClass('green');
        div.innerHTML = " Načítají se data...";

    } else {
        $('#load-circle').addClass('green').removeClass('red');
        div.innerHTML = "<br />";
    }
}

/**
 * Funkce pro zajisteni prepinanu obsahu Sidebaru
 * @param content pozadovany obsah
 */
function changeSidebarContent(content) {
    sidebarClear();
    sidebarContent = content;
    CREATE_NODE = false;
    if(content!=CR_PART1 && content!=CR_PART2){        
        layers[ALLWAYS].list = [];
        layers[ALLWAYS].layer.clearLayers();
        map.removeLayer(layers[ALLWAYS].layer);
        layers[ALLWAYS].isOn = false;
    }
    var element = document.getElementById('side-content');    


        /** vrstvy */
    if (content == LAYERS) {        
        $(function () {
            $('#side-content').load("layers.html");
        })

        /** data & tagy */
    } else if (content == DATA) {       
        $(function () {
            $('#side-content').load("data.html");
        })

        /** vlozit info k rozcestniku */
    } else if (content==GUIDEPOST_INFO){
        $(function () {
            $('#side-content').load("new_guide_info.html");
        })


        /** vytvorit poznamku - cast 1 */
    } else if (content == CR_NODE1) {
        map.addLayer(layers[USERNOTES].layer);
        CREATE_NODE = true;
        element.innerHTML = "<div class='notice-yellow'>"+msgCreateNodeBefore+"</div>";


        /** vytvorit poznamku - cast 2 */
    } else if (content == CR_NODE2) {
        $(function () {
            $('#side-content').load("new_note.html");
        })


        /** vyznacit cast cesty - cast 1 */
    } else if (content == CR_PART1) {
        if(map.getZoom()>=15){
            layers[ALLWAYS].isOn = true;
            map.addLayer(layers[ALLWAYS].layer);
        }
        map.addLayer(layers[USERPARTS].layer);

        getData();

        element.innerHTML = "<div class='notice-yellow'>"+msgCreateLineBefore+"</div>";

        if(selectedWayToControl._startMarker!=null){
            selectedWayToControl.disable();
        }


        /** vyznacit cast cesty - cast 2 */
    } else if (content == CR_PART2) {
        $(function () {
            $('#side-content').load("new_part.html");
        })


        /** seznam poznamek*/
    } else if(content==IMPORT) {
        $(function () {
            $('#side-content').load("import.html");
        })
    }
}

/**
 * Vytvoreni odkazu pro otevreni dane casti v JOSM
 * @param relation
 * @param type
 * @param element
 * @returns {string}
 * @constructor
 */
function JOSMLinkBuilder(relation, type, element) {
    var t;
    if (type == WAY) {
        t = "w";
    } else if (type == NODE) {
        t = "n";
    }
    if(relation==null){
        return "http://localhost:8111/load_object?objects="+t + element.id;
    }
    return "http://localhost:8111/load_object?objects=r" + relation.id + "," + t + element.id;
}

/**
 * Funkce pro ovladani checkboxu pro zahrnuti cyklotras
 */
function processCheckbox() {
    cycleRelationsAreOn = document.getElementById('cycle').checked;
    if (!cycleRelationsAreOn) {
        clearAllLayers();
    }
    getData();
}

/**
 * priprava formulare pro vlozeni uzivatelskeho bodu
 */
function prepareForm() {
    var element = document.forms["addNote"];
    element['lat'].value = lngLatFromClick.lat;
    element['lng'].value = lngLatFromClick.lng;
    if (map.user!=""){
        element['name'].value = map.user;
    }
    prepareDate(document.forms["addNote"]);
}

/**
 * predpripraveni aktualniho data
 * @param element
 */
function prepareDate(element){
    var date = new Date();
    element['date'].value = date.getDate()+"/"+(date.getMonth()+1)+"/"+date.getFullYear();

}

/**
 * ulozeni uzivatelskeho bodu
 * @param images
 */
function saveNote(images) {
    setError("");
    dataFromClick = false;
    $.post('php/saveNote.php', {
        lng: getFormValue('lng', 'addNote'),
        lat: getFormValue('lat', 'addNote'),
        name: getFormValue('name', 'addNote'),
        note: getFormValue('note', 'addNote'),
        date: getFormValue('date', 'addNote'),
        osm: getOsmNameValue(),
        type: getSelectedValue(),
        images: images  
    }, function (data) {
        if (map.hasLayer(temporaryMarker)) {
            map.removeLayer(temporaryMarker);
            map.user = getFormValue('name', 'addNote');
        }
        layers[USERNOTES].layer.clearLayers();
        layers[USERNOTES].list = [];
        getData();
        changeSidebarContent(LAYERS);
    });
    
}

/**
 * ulozeni useku
 */
function savePart() {
    if (validateFormPart()) {
        setError("");
        $.post('php/savePart.php', {
            name: getFormValue('name', 'addPart'),
            note: getFormValue('note', 'addPart'),
            date: getFormValue('date', 'addPart'),
            type: getSelectedValue(),
            osm: getOsmNameValue(),
            obj: JSON.stringify(selectedWayToControl.getSelection())
        }, function (data) {
            selectedWayToControl.disable();
            map.user = getFormValue('name', 'addPart');
            getData();
            changeSidebarContent(LAYERS);
        });
    }
}

/**
 * ulozeni informace k rozcestniku
 * @param images
 */
function saveGuideInfo(images) {
    setError("");
    $.post('php/saveGuideInfo.php', {
        name: getFormValue('name', 'addGuideInfo'),
        note: getFormValue('note', 'addGuideInfo'),
        date: getFormValue('date', 'addGuideInfo'),
        node: getFormValue('node', 'addGuideInfo'),
        type: getSelectedValue(),
        osm: getOsmNameValue(),
        images: images    
    }, function (data) {
        map.user = getFormValue('name', 'addGuideInfo');
        changeSidebarContent(DATA);
    });    
}

/**
 * importovani dat - klientska cast
 */
function importData() {
    setError("");
    var file = document.getElementById('file-select').files[0];
    if(file==undefined){
        setError(msgErrorFile);
        return;
    }
    if (!(validateSelect())){
        setError(msgErrorSelect);
        return;
    }
    var reader = new FileReader();
    reader.readAsText(file);
    reader.onload = function(){

        if(file.type!="application/json"){
            setError(msgErrorFileType);
            return;
        }
        $.post('php/import.php', {
            data: reader.result,
            type: getSelectedValue()
        }, function (data) {
            getData();
            changeSidebarContent(LAYERS);
        });
    };

}

/**
 * @returns {boolean}
 */
function validateFormPart() {
    var element = document.forms["addPart"];
    if (!(validateSelect())){
        setError(msgErrorSelect);
        return false;
    }
    if ((isEmpty(element["name"])) || (isEmpty(element["note"])) || (isEmpty(element['date']))) {
        setError(msgError);
        return false;
    }
    if (selectedWayToControl._startMarker==null ||
        selectedWayToControl._endMarker==null){
        setError(msgErrorPart);
        return false;
    }
    if (!(validateDate(element['date'].value))){
        setError(msgErrorDate);
        return false;
    }
    return true;

}

/**
 * @returns {boolean}
 */
function validateFormGuideInfo() {
    var element = document.forms["addGuideInfo"];
    if (!(validateSelect())){
        setError(msgErrorSelect);
        return false;
    }
    if ((isEmpty(element["name"])) || (isEmpty(element["note"])) || (isEmpty(element['date']))) {
        setError(msgError);
        return false;
    }
    if (!(validateDate(element['date'].value))){
        setError(msgErrorDate);
        return false;
    }
    if(!(validateImages(element['photos[]'].files))){
        setError(msgErrorImage);
        return false;
    }
    if(element['photos[]'].files.length>0){
        uploadImages(document.forms["addGuideInfo"]['photos[]'].files, getFormValue('name', 'addGuideInfo'), GUIDEPOST_INFO);
    } else {
        saveGuideInfo(null);
    }
       
    return true;
}

/**
 * @returns {boolean}
 */
function validateFormNote() {
    var element = document.forms["addNote"];
    if (CoordsDMS) { //pro ulozeni do db potrebuji decimal format souradnice
        changeCoordinatesRepresentation();
    } else {
        if (!(validateCoords(element['lat'].value)) || !(validateCoords(element['lng'].value))) {
            setError(msgErrorCoords);
            return false;
        }
    }
    if (!(validateSelect())){
        setError(msgErrorSelect);
        return false;
    }
    if ((isEmpty(element["name"])) || (isEmpty(element["note"])) || (isEmpty(element["date"]))){
        setError(msgError);
        return false;
    }
    if (!(validateDate(element['date'].value))){
        setError(msgErrorDate);
        return false;
    }
    if(!(validateImages(element['photos[]'].files))){
        setError(msgErrorImage);
        return false;
    }
    if(element['photos[]'].files.length>0){
        uploadImages(document.forms["addNote"]['photos[]'].files, element["name"].value, USERNOTES);
    } else {
        saveNote(null);
    }
    return true;
}

/**
 * pom. funkce pro zjisteni hodnoty daneho prvku formulare
 * @returns {string|Number|value|*|.serializeArray.value|Document.addNote.lat}
 */
function getSelectedValue(){
    var e = document.getElementById('select');
    return e.options[e.selectedIndex].value;
}

/**
 *
 * @returns {number}
 */
function getOsmNameValue(){
    return document.getElementById('osm_name').checked ? 1 : 0;
}

/**
 *
 * kontrola vybranych moznosti
 * @returns {boolean}
 */
function validateSelect(){
    return getSelectedValue()!=0;
}

/**
 * kontrola obrazku
 * @param files
 * @returns {boolean}
 */
function validateImages(files){
    var type;
    for(var i = 0; i < files.length; i++) {
        type = files[i].type;
        if((type != "image/jpeg" && type != "image/png") || files[i].size > 5242880 /*5MB */) {
            return false;
        }
    }
    return true;
}

/**
 * nahrani obrazku na server
 * @param files
 * @param name
 * @param type
 */
function uploadImages(files, name, type){
    var reader = new FileReader();    
    reader.readAsDataURL(files[0]);
    reader.onload = function(event){
        $.post('php/uploadImage.php', {
            name: name,
            data: event.target.result
        }, function(data){
            if(type==GUIDEPOST_INFO) {
                saveGuideInfo(data);
            } else if(type==USERNOTES) {
                saveNote(data);
            }   
                
        })
    }
}

/**
 * overeni platnosti data
 * @param date
 * @returns {boolean}
 */
function validateDate(date){
    var re = /[0-9]{1,2}[\/][0-9]{1,2}[\/][0-9]{4}/;
    var m;
    if ((m=re.exec(date)) !== null){
        return true;
    } else {
        return false;
    }
}

/**
 * overeni souradnic
 * @param coordValue
 * @returns {boolean}
 */
function validateCoords(coordValue) {
    var re = /[0-9]{1,2}[.][0-9]+/;
    var m;

    if ((m = re.exec(coordValue)) !== null) {
        return true;
    } else {
        return false;
    }
}

function setError(error) {
    var x = document.getElementById('error');
    x.innerHTML = error;
}

function isEmpty(input) {
    if (input.value == "" || input.value == null) {
        return true;
    } else {
        return false;
    }
}

function getFormValue(element, form) {
    return document.forms[form][element].value;
}


/**
 * prevodni funkce
 * @param decimal
 * @returns {Array}
 */
function degreesDecimalToDMS(decimal) {
    var DMS = [];
    decimal = parseFloat(decimal);
    DMS[0] = parseInt(decimal, 10);
    DMS[1] = parseInt(60 * (decimal - DMS[0]), 10);
    DMS[2] = parseInt(3600 * (decimal - DMS[0] - (DMS[1] / 60)), 10);
    return DMS;
}

/**
 * @return {number}
 */
function DMSToDecimal(DMS) {
    return (parseInt(DMS[0]) + (parseInt(DMS[1]) / 60) + (parseInt(DMS[2]) / 3600)).toFixed(5);
}

/**
 *
 * @param DMSString
 * @returns {*}
 */
function simpleParseDMS(DMSString) {
    var DMS = [];
    var reDeg = /[0-9]{1,2}(?=°)/;
    var reMin = /[0-9]{1,2}(?=')/;
    var reSec = /[0-9]{1,2}(?='')/;

    DMS[0] = reDeg.exec(DMSString);
    DMS[1] = reMin.exec(DMSString);
    DMS[2] = reSec.exec(DMSString);
    if (DMS[0] === null || DMS[1] === null || DMS[2] === null) {
        return null;
    } else {
        DMS[0] = DMS[0][0];
        DMS[1] = DMS[1][0];
        DMS[2] = DMS[2][0];
        return DMS;
    }
}

/**
 * prepnuti zobrazeni souradnic
 */
function changeCoordinatesRepresentation() {
    if (CoordsDMS) {
        CoordsDMS = false;
        var inputLat = simpleParseDMS(document.forms['addNote']['lat'].value);
        var inputLng = simpleParseDMS(document.forms['addNote']['lng'].value);
        document.forms['addNote']['lat'].value = DMSToDecimal(inputLat);
        document.forms['addNote']['lng'].value = DMSToDecimal(inputLng);
    } else {
        CoordsDMS = true;
        document.forms['addNote']['lat'].value = DMSToString(degreesDecimalToDMS(document.forms['addNote']['lat'].value));
        document.forms['addNote']['lng'].value = DMSToString(degreesDecimalToDMS(document.forms['addNote']['lng'].value));
    }
}


/**
 * @return {string}
 */
function DMSToString(DMS) {
    return DMS[0] + "° " + DMS[1] + "\' " + DMS[2] + "\'\'";
}
