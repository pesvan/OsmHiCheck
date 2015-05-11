        $.ajaxSetup({
            // Disable caching of AJAX responses
            cache: false
        });

        //globalni promenne //mapa
        var map;
        //pouzite vykreslovaci vrstvy
        var touristRelationsLayer, errorRelationsLayer, warningRelationsLayer, guidepostsNodesLayer, userNotesNodesLayer;
        //promenna pro manipulaci s prvkem - ovladani vrstev
        var controlLayersHandler;
        //manipulace s postrannim panelem
        var sidebarHandler;

        // stavove promenne pro vrstvy
        var tracksLayerIsOn = false;
        var guideLayerIsOn = false;
        var errorLayerIsOn = false;
        var warningLayerIsOn = false;
        var userLayerIsOn = false;
        var cycletrailsAreOn = false;

        //pomocna promenna pro zamezeni opakovemu nahravani ve stejny moment
        var canGetData = true;

        // id elementu, na ktere uzivatel kliknul
        var selectedIds = [];
        // data elementu -||-
        var selectedData = [];

        var selectedElement;

        // "cache"
        var relationList = [];
        var warningList = [];
        var errorList = [];
        var guidepostList = [];
        var usernotesList = [];

        // imitace enum - konstanty pro lepsi orientaci
        var RELATION = 0;
        var WAY = 1;
        var NODE = 2;

        var DATA = 0;
        var LAYERS = 1;
        var CR_NODE1 = 2;
        var CR_NODE2 = 3;
        var NODE_LIST = 4;

        var GET_WAYS = 0;
        var GET_WAYS_WARNINGS = 1;
        var GET_WAYS_ERRORS = 2;
        var GET_GUIDEPOSTS = 3;
        var GET_USER_NOTES = 4;
        var GET_CYCLE = 5;

        var CREATE_NODE = false;

        var msgCreateNodeBefore = "Pro vytvoreni bodu s poznamkou kliknete na pozadovanou lokaci na mape.";

        var lngLatFromClick = null;
        var dataFromClick = false;

        var cnt = 0;
        var temporaryMarker = null;
        function initmap() {

            document.getElementById('cycle').checked = false;

            /** mapa **/
            map = new L.Map('mymap', {
                zoomControl: false
            });

            /** podklady **/
            var osmUrl = 'http://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
            var osmAttrib = 'Map data &copy; <a href="http://openstreetmap.org">OpenStreetMap</a> contributors';
            var osm = new L.TileLayer(osmUrl, {
                minZoom: 1,
                maxZoom: 19,
                attribution: osmAttrib
            });
            var none = new L.TileLayer(null, {
                minZoom: 1,
                maxZoom: 19,
                attribution: osmAttrib
            });

            map.setView(new L.LatLng(48.952, 16.734), 13);
            map.addLayer(osm);

            /** meritko (na mape vlevo dole) **/
            L.control.scale().addTo(map);

            /** zobrazi zoom a souradnice v adrese  */
            var hash = new L.Hash(map);

            /** vyhledavani **/
            var osmGeocoder = new L.Control.OSMGeocoder();
            osmGeocoder.setPosition('topleft');
            map.addControl(osmGeocoder);

            /** posuvne meritko pro ovladani zoomu **/
            var slide = new L.Control.Zoomslider().addTo(map);

            /** postranni panel s informacemi a ovladanim **/
            sidebarHandler = L.control.sidebar('sidebar', {
                position: 'right',
                autoPan: false,
                closeButton: false
            });
            map.addControl(sidebarHandler);
            sidebarHandler.show();


            /**
             * Definice overlay vrstev
             */
            /** Turisticke relace **/
            touristRelationsLayer = L.geoJson(null, {
                style: function (feature) {
                    var color;
                    switch (feature.properties.color) {
                        case 5:
                            color= "#ff00ff";//cyklo
                            break;
                        case 1:
                            color= "#ff0000";//red
                            break;
                        case 2:
                            color= "#0000ff";//blue
                            break;
                        case 3:
                            color= "#00ff00";//green
                            break;
                        case 4:
                            color= "#ffff00";//yellow
                            break;
                        default:
                            color= "#666666";//gray
                            break;
                    }
                    return {
                        color: color
                    }
                },
                onEachFeature: onEachFeature,
                id: GET_WAYS
            });

            /** relace s chybejicimi tagy **/
            warningRelationsLayer = L.geoJson(null, {
                style: function (feature) {
                    return {
                        "color": "#ff7800",
                        "weight": 20,
                        "opacity": 0.2
                    }
                },
                onEachFeature: onEachFeature,
                id: GET_WAYS_WARNINGS
            });

            /** relace s chybami **/
            errorRelationsLayer = L.geoJson(null, {
                style: function (feature) {
                    return {
                        "color": "#ff0000",
                        "weight": 20,
                        "opacity": 0.9
                    }
                },
                onEachFeature: onEachFeature,
                id: GET_WAYS_ERRORS
            });

            /** rozcestniky **/
            guidepostsNodesLayer = L.geoJson(null, {
                onEachFeature: onEachFeature,
                id: GET_GUIDEPOSTS
            });


            //ikona pro uzivatelske poznamky
            var redicon = L.icon({
                iconUrl: 'leaflet/images/marker_red.png',
                iconSize: [20, 37],
                iconAnchor: [10, 37],
                shadowUrl: 'leaflet/images/marker-shadow.png',
                shadowSize: [35, 50],
                shadowAnchor: [9, 53]
            });


            /** uzivatelske poznamky **/
            userNotesNodesLayer = L.geoJson(null, {
                onEachFeature: onEachFeature,
                id: GET_USER_NOTES,
                pointToLayer: function (feature, latlng) {
                    return L.marker(latlng, {
                        icon: redicon
                    }).bindPopup(
                            "<h3>Poznámka</h3>" +
                            "poznámka: " + feature.properties.note + "<br/>" +
                            "čas: " + feature.properties.timestamp + "<br/>" +
                            "uživatel: " + feature.properties.user + "<br/>",
                            {
                                offset: L.point(0, -37)
                            }
                    );
                }
            });



            /** nastaveni vrstev*/
            var overlays = {
                "Zobrazit turistické relace": touristRelationsLayer,
                "Zobrazit rozcestníky": guidepostsNodesLayer,
                "<span class='red'>Zvýraznit relace s chybami</span>": errorRelationsLayer,
                "<span class='orange'>Zvýraznit relace s chybejicimi tagy</span>": warningRelationsLayer,
                "Zobrazit uživatelské body s poznámkami": userNotesNodesLayer
            };

            //ovladaci prvek pro zobrazovani vrstev, se kterym je treba manipulovat
            controlLayersHandler = L.control.layers({
                "OpenStreetMap Mapnik": osm,
                "nic": none
            }, overlays, {collapsed: false});
            controlLayersHandler.setPosition('topleft').addTo(map);
            controlLayersHandler._container.remove();
            //document.getElementById('side-content').appendChild(control.onAdd(map));
            changeSidebarContent(LAYERS);

            map.on('moveend', mapMoved);
            map.on('overlayadd', onOverlayAdd);
            map.on('overlayremove', onOverlayRemove);
            map.on('click', onClickElsewhere);
            map.on('zoomend', clearAllLayers);
        }

        function createNode(e, bind) {
            if (CREATE_NODE) {
                lngLatFromClick = e.latlng
                dataFromClick = bind;
                changeSidebarContent(CR_NODE2);
                temporaryMarker = L.marker(lngLatFromClick, {
                    clickable: false
                });
                temporaryMarker.addTo(map);
            }
        }

        function onEachFeature(feature, layer) {

            setTimeout(function () {
                setSidebarLoading(false);
            }, 1000);
            layer.on('click', onClick);
        }

        function onClick(e) {
            if (CREATE_NODE) {
                createNode(e, true);
            } else {
                selectedIds[RELATION] = e.target.feature.relation_id;
                getSpecifics(RELATION);
                if (e.target.feature.int_type == WAY) {
                    selectedIds[WAY] = e.target.feature.member_id;
                    selectedIds[NODE] = null;
                    getSpecifics(WAY);
                    selectedElement = WAY;
                } else if (e.target.feature.int_type == NODE) {
                    selectedIds[NODE] = e.target.feature.member_id;
                    selectedIds[WAY] = null;
                    getSpecifics(NODE);
                    selectedElement = NODE;
                }
            }
        }

        function onClickElsewhere(e) {
            createNode(e, false);
        }

        function onOverlayAdd(e) {
            //fix, aby se funkce nevolala 2x po sobe
            if (!canGetData) {
                return;
            }
            canGetData = false;

            if (e.layer.options.id == GET_WAYS) {
                tracksLayerIsOn = true;
            } else if (e.layer.options.id == GET_WAYS_ERRORS) {
                errorLayerIsOn = true;
            } else if (e.layer.options.id == GET_WAYS_WARNINGS) {
                warningLayerIsOn = true;
            } else if (e.layer.options.id == GET_GUIDEPOSTS) {
                guideLayerIsOn = true;
            } else if (e.layer.options.id == GET_USER_NOTES) {
                userLayerIsOn = true;
            }
            else
            {
                return;
            }
            getData();
            setTimeout(function () {
                canGetData = true;
            }, 500);
        }

        function onOverlayRemove(e) {
            if (e.layer.options.id == GET_WAYS) {
                tracksLayerIsOn = false;
                touristRelationsLayer.clearLayers();
                relationList = [];
            } else if (e.layer.options.id == GET_WAYS_ERRORS) {
                errorLayerIsOn = false;
                errorRelationsLayer.clearLayers();
                errorList = [];
            } else if (e.layer.options.id == GET_WAYS_WARNINGS) {
                warningLayerIsOn = false;
                warningRelationsLayer.clearLayers();
                warningList = [];
            } else if (e.layer.options.id == GET_GUIDEPOSTS) {
                guideLayerIsOn = false;
                guidepostsNodesLayer.clearLayers();
                guidepostList = [];
            } else if (e.layer.options.id == GET_USER_NOTES) {
                userLayerIsOn = false;
                userNotesNodesLayer.clearLayers();
                usernotesList = [];
            }
        }

        function mapMoved(e) {
            getData();
        }

        function getSpecifics(type) {
            var url = 'data=' + selectedIds[type] + '&type=' + type;
            $.ajax({
                url: 'php/getElement.php',
                dataType: 'json',
                data: url,
                success: function (data) {
                    selectedData[type] = data;
                },
                complete: function () {
                    changeSidebarContent(DATA);
                }
            });
        }

        function getData() {
            if (tracksLayerIsOn || warningLayerIsOn || errorLayerIsOn || guideLayerIsOn) {
                $.ajax({
                    beforeSend: function () {
                        setSidebarLoading(true);
                    },
                    url: 'php/getList.php',
                    dataType: 'json',
                    data: 'bbox=' + map.getBounds().toBBoxString() + '&type=0&bicycle='+cycletrailsAreOn,
                    success: function (data) {
                        processListDataOSM(data);
                    } //success
                })//ajax
            }
            if (userLayerIsOn) {
                $.ajax({
                    beforeSend: function () {
                        setSidebarLoading(true);
                    },
                    url: 'php/getList.php',
                    dataType: 'json',
                    data: 'bbox=' + map.getBounds().toBBoxString() + '&type=1&bicycle=false',
                    success: function (data) {
                        processListDataUser(data);
                    } //success
                })//ajax
            }
        }
        function processListDataOSM(data) {
            var temp = relationList.length;
            var toDelete = $(relationList).not(data).get();
            var toDraw = $(data).not(relationList).get();
            deleteUnusedGeoJSONRelations(toDelete, touristRelationsLayer);
            relationList = data;
            //clearAllLayers();
            for (var relation in toDraw) {
                if (tracksLayerIsOn) {
                    getWaysToDraw(toDraw[relation], GET_WAYS);
                }
                if (warningLayerIsOn) {
                    getWaysToDraw(toDraw[relation], GET_WAYS_WARNINGS);
                }
                if (errorLayerIsOn) {
                    getWaysToDraw(toDraw[relation], GET_WAYS_ERRORS);
                }
                if (guideLayerIsOn) {
                    getWaysToDraw(toDraw[relation], GET_GUIDEPOSTS);
                }
            } //for
            if (temp == relationList.length) {
                setSidebarLoading(false);
            }
        }

        function clearAllLayers() {
            touristRelationsLayer.clearLayers();
            warningRelationsLayer.clearLayers();
            errorRelationsLayer.clearLayers();
            guidepostsNodesLayer.clearLayers();
            relationList = [];
            warningList = [];
            errorList = [];
            guidepostList = [];
        }


        function processListDataUser(data) {
            var temp = usernotesList.length;
            for (var note in data) {
                if (userLayerIsOn && usernotesList.indexOf(data[note]) == -1) {
                    usernotesList[usernotesList.length] = data[note];
                    getNotesToDraw(data[note]);
                }
            }
            if (temp == usernotesList.length) {
                setSidebarLoading(false);
            }
        }

        function getNotesToDraw(nid) {
            $.ajax({
                url: 'php/getNote.php',
                dataType: 'json',
                data: 'nid=' + nid + '&type=1',
                success: function (data) {
                    userNotesNodesLayer.addData(data);
                }
            })
        }

        function getWaysToDraw(rid, controlType) {
            $.ajax({
                url: 'php/getData.php',
                dataType: 'json',
                data: 'rid=' + rid + '&control=' + controlType + '&zoom=' + map.getZoom(),
                success: function (data) {
                    if (controlType == GET_WAYS) {
                        touristRelationsLayer.addData(data);
                    } else if (controlType == GET_WAYS_WARNINGS) {
                        warningRelationsLayer.addData(data);
                    } else if (controlType == GET_WAYS_ERRORS) {
                        errorRelationsLayer.addData(data);
                    } else if (controlType == GET_GUIDEPOSTS) {
                        guidepostsNodesLayer.addData(data);
                    }
                }
            })
        }

        /**
         * @return {string}
         */
        function JSONToTable(data) {
            var table = "<table class='relation'>";
            for (key in selectedData[data]) {
                if (key == 'tags') continue;
                table += makeTableRow(key, selectedData[data][key]);
            }
            if (data == RELATION) {
                var missing = getMissingTags(selectedData[data]['tags']);
                for (tag in missing) {
                    table += makeTableRow(tag, missing[tag], "orange");
                }
            }
            for (tag in selectedData[data]['tags']) {
                table += makeTableRow(tag, selectedData[data][key][tag]);
            }

            table += "</table>";
            return table;
        }

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

        function makeTableRow(key, value, htmlclass) {
            if (value == undefined) {
                value = "";
            }
            if (htmlclass == undefined) {
                htmlclass = "";
            }
            return "<tr><td class=" + htmlclass + ">" + key + "<td class=" + htmlclass + ">" + value;
        }

        function sidebarClear() {
            document.getElementById('side-layer').innerHTML = "";
            document.getElementById('side-load').innerHTML = "";
            document.getElementById('side-content').innerHTML = "";
            if (map.hasLayer(temporaryMarker)) {
                map.removeLayer(temporaryMarker);
            }
        }

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

        function deleteUnusedGeoJSONRelations(data, layer){
            for(var object in layer._layers){
                if(layer._layers.hasOwnProperty(object)){
                    var obj = layer._layers[object];
                    for(var relation in data){
                        if(obj.feature.relation_id==relation){
                            //delete touristRelationsLayer._layers[object];
                            layer.removeLayer(obj);
                        }
                    }
                }
            }
        }

        function changeSidebarContent(content) {
            sidebarClear();
            CREATE_NODE = false;
            var element = document.getElementById('side-content');
            document.getElementById('side-layer2').style.display = "none";
            /** vrstvy */
            if (content == LAYERS) {
                document.getElementById('side-layer2').style.display = "block";
                element.innerHTML = "Poslední aktualizace OSM dat: ";
                $(function () {
                    $('#side-load').load("../last_update.txt");
                });
                document.getElementById('side-layer').appendChild(controlLayersHandler.onAdd(map));


                /** data & tagy */
            } else if (content == DATA) {
                if (selectedElement == undefined) {
                    element.innerHTML = "Klikněte na některou trasu nebo bod pro zobrazení dat (Je potřeba zapnout některou vrstvu)";
                } else {
                    var link = JOSMLinkBuilder(selectedData[RELATION], selectedElement, selectedData[selectedElement]);
                    element.innerHTML = "<a target='_blank' href='"+link+"'>Upravit v JOSM</a>"
                    element.innerHTML += "<h3>Relace</h3>" + JSONToTable(RELATION);
                    if (selectedElement == WAY) {
                        element.innerHTML += "<h3>Cesta</h3>" + JSONToTable(WAY);
                    } else if (selectedElement == NODE) {
                        element.innerHTML += "<h3>Uzel</h3>" + JSONToTable(NODE);
                    }
                }


                /** vytvorit poznamku - cast 1 */
            } else if (content == CR_NODE1) {
                console.log(touristRelationsLayer._layers);

                console.log(touristRelationsLayer._layers);
                CREATE_NODE = true;
                element.innerHTML = msgCreateNodeBefore;

                /** vytvorit poznamku - cast 2 */
            } else if (content == CR_NODE2) {
                $(function () {
                    $('#side-content').load("new_note.html");
                })


                /** seznam poznamek*/
            } else if (content == NODE_LIST) {
                setSidebarLoading(true);
                element.innerHTML = "<h3>Seznam uživatelských poznámek</h3>";
                $.ajax({
                    url: 'php/getNote.php',
                    dataType: 'json',
                    data: 'nid=0&type=0',
                    error: function () {
                        element.innerHTML += "<div class='red'>Nepodařilo se získat data ze serveru</div>";
                    },
                    success: function (data) {
                        printUserNodes(data, element);
                    },
                    complete: function () {
                        setSidebarLoading(false);
                    }
                })
            }
        }

        /**
         * @return {string}
         */
        function JOSMLinkBuilder(relation, type, element){
            var t;
            if(type==WAY) {
                t = "w";
            } else if(type==NODE) {
                t = "n";
            }
            console.log(relation, element);
            return "http://localhost:8111/load_object?new_layer=true&objects=r" + relation.id + "," + t + element.id;
        }

        function printUserNodes(data, element) {
            element.innerHTML += JSON.stringify(data);
        }

        function processCheckbox(){
            cycletrailsAreOn = document.getElementById('cycle').checked;
            if(!cycletrailsAreOn){
                clearAllLayers();
            }
            getData();
        }