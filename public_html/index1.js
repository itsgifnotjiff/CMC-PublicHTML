import 'ol/ol.css';
import Map from 'ol/Map';
import View from 'ol/View';
import TileLayer from 'ol/layer/Tile';
import BingMaps from 'ol/source/BingMaps';


var view = new View({
  center: [-6655.5402445057125, 6709968.258934638],
  zoom: 13
});

var roadLayer = new ol.Map({
        target: 'roadMap',
        layers: [
          new ol.layer.Tile({
            source: new ol.source.OSM()
          })
        ],
        view: view
      });

var aerialLayer = new ol.Map({
        target: 'aerialMap',
        layers: [
          new ol.layer.Tile({
            source: new ol.source.OSM()
          })
        ],
        view: view
      });
