var layers = [
    new ol.layer.Tile({
    source: new ol.source.OSM()
    }),
    new ol.layer.Image({
    opacity: 0.5,
    source: new ol.source.ImageWMS({
        url: 'http://geo.wxod-stage.cmc.ec.gc.ca/geomet',
        params: {'LAYERS': 'GDPS.ETA_TT'},
        ratio: 1,
        serverType: 'mapserver'
    })
    })
]
var view = new ol.View({
    center: ol.proj.fromLonLat([-3, 17]),
    zoom: 4
})

var map = new ol.Map({
target: 'map',
layers: layers,
view: view ,
controls: ol.control.defaults({
    attribution: false
})
});

function setLayer() {
layers[1].getSource().updateParams({'LAYERS': document.getElementById('layer_').value});
}

function setTime() {
layers[1].getSource().updateParams({'TIME': document.getElementById('time_').value});
}



var update_map = document.getElementById('update_');
update_map.addEventListener('click', setLayer, false);

var update_time = document.getElementById('updatetime_');
update_time.addEventListener('click', setTime, false);