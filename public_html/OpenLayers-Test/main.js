
 window.onload = init;
 
 function init()
 {
     const map = new ol.Map({
         view: new ol.View({
             center: [-8140448, 5710013], 
             zoom: 6,
             maxZoom: 10,
             minZoom: 4
        }),
        target: 'js-map'
     })
     
    map.on('click', function(e){
        console.log(e.coordinate);
    })
    
    
    const openStreetStandard = new ol.layer.Tile({
        source: new ol.source.OSM(),
        visible: true,
        title:'OSMStandard'
    })
    
     
    const openStreetMapHumanitarian = new ol.layer.Tile({
        source: new ol.source.OSM({
            url:'https://{a-c}.tile.openstreetmap.fr/hot/{z}/{x}/{y}.png'
        }),
        visible: false,
        title:'OSMHumanitarian'
    })
    
    const openStreetStamen = new ol.layer.Tile({
        source: new ol.source.XYZ({
            url: 'http://tile.stamen.com/terrain/{z}/{x}/{y}.jpg',
        }),
        visible: false,
        title:'StamenTerrain'
    })
    
    const openStreetMapGeoMetCurrentConditions = new ol.layer.Image({
        source: new ol.source.ImageWMS({
            url: 'http://geo.wxod-stage.cmc.ec.gc.ca/geomet',
            params: {'LAYERS': 'CURRENT_CONDITIONS'},
            ratio: 1,
            serverType: 'mapserver'
        })
    })
    
    const baseLayerGroup = new ol.layer.Group({
        layers: [
            openStreetStandard , openStreetMapHumanitarian ,openStreetStamen
        ]
    })

    const geometLayerGroup = new ol.layer.Group({
        layers: [
            openStreetMapGeoMetCurrentConditions
        ]
    })
    
    map.addLayer(baseLayerGroup);
    map.addLayer(geometLayerGroup);
    
    const baseLayerElements = document.querySelectorAll('.sidebar > input[type=radio]');
    for(let baseLayerElement of baseLayerElements){
        baseLayerElement.addEventListener('change', function() {
            let baseLayerElementValue = this.value;
            baseLayerGroup.getLayers().forEach(function(element, index, array){
                let baseLayerTitle = element.get('title');
                element.setVisible(baseLayerTitle === baseLayerElementValue);
                // map.addLayer(openStreetMapGeoMetCurrentConditions);
            })
        })
    }







 }

