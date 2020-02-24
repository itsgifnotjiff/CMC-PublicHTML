import 'ol/ol.css';
import Map from 'ol/Map';
import View from 'ol/View';
import {getCenter} from 'ol/extent';
import TileLayer from 'ol/layer/Tile';
import {transformExtent} from 'ol/proj';
import Stamen from 'ol/source/Stamen';
import TileWMS from 'ol/source/TileWMS';

function threeHoursAgo() {
  return new Date(Math.round(Date.now() / 3600000) * 3600000 - 3600000 * 3);
}

var extent = transformExtent([-126, 24, -66, 50], 'EPSG:4326', 'EPSG:3857');
var startDate = threeHoursAgo();
var frameRate = 0.5; // frames per second
var animationId = null;

var layers = [
  new TileLayer({
    source: new Stamen({
      layer: 'terrain'
    })
  }),
  new TileLayer({
    extent: extent,
    source: new TileWMS({
      attributions: ['Iowa State University'],
      url: 'https://ahocevar.com/geoserver/wms',
        params: {'LAYERS': 'ne:NE1_HR_LC_SR_W_DR'})
  })
];
var map = new Map({
  layers: layers,
  target: 'map',
  view: new View({
    center: getCenter(extent),
    zoom: 4
  })
});

function updateInfo() {
  var el = document.getElementById('info');
  el.innerHTML = startDate.toISOString();
}

function setTime() {
  startDate.setMinutes(startDate.getMinutes() + 15);
  if (startDate > new Date()) {
    startDate = threeHoursAgo();
  }
  layers[1].getSource().updateParams({'TIME': startDate.toISOString()});
  updateInfo();
}
setTime();

var stop = function() {
  if (animationId !== null) {
    window.clearInterval(animationId);
    animationId = null;
  }
};

var play = function() {
  stop();
  animationId = window.setInterval(setTime, 1000 / frameRate);
};

var startButton = document.getElementById('play');
startButton.addEventListener('click', play, false);

var stopButton = document.getElementById('pause');
stopButton.addEventListener('click', stop, false);

updateInfo();
