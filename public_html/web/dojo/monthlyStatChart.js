define([
   "dojo/_base/declare",
   "dojo/_base/lang",
   "dojo/request",
   "dojox/charting/Chart",
   "dojox/charting/themes/Shrooms",
   "dojo/date/locale",
   "dojo/number",
   "dojox/charting/widget/SelectableLegend",
   "dojox/gfx/utils",
   "dojox/charting/action2d/Tooltip",
   "dojox/charting/action2d/Magnify",
   "dijit/form/Button",
   "dojo/dom-construct",
   "dojo/date/stamp",
   "dojo/date/locale",
   "dojo/query",
   "dijit/registry",
   "dojox/charting/plot2d/Lines",
   "dojox/charting/axis2d/Default",
   "dojo/domReady!"
],
function(declare, lang, request, Chart, theme, locale, number, Legend, utils, Tooltip, Magnify, Button, domConstruct, stamp, locale, query, registry) {
    return declare(null, {
      variableName: null,
      variable: null,
      chart: null,
      legend: null,
      downloadButton: null,
      groupSeriesMap: null,

      constructor: function(container, variableId, variableName) {
         var chartNode = domConstruct.create("div", {class: 'graph'}, container);

         this.variableName = variableName;
         this.variable = variableId;
         this.chart = new Chart(chartNode, {
            scrolling: false,
            title: variableName,
            htmlLabels: false
         });

         this.chart.addAxis("x", {
            title: "Month",
            titleOrientation: "away",
            labelSizeChange: true,
            labelFunc: function(n) {
               var date = new Date(number.parse(n));
               return locale.format(date, {
                  selector: "date",
                  datePattern: "y-M",
                  locale: "en"
               });
            },
            minorTicks: false,
            htmlLabels: false,
            fontColor: "#000000",
            font: "normal normal normal 12pt DejaVu Sans"
         });

         this.chart.addAxis("y", {
            vertical: true,
            title: "Value",
            fontColor: "#000000",
            font: "normal normal normal 10pt DejaVu Sans",
            htmlLabels: false
         });

         this.chart.addPlot("default", {
            type: "Lines",
            markers: true
         });

         new Magnify(this.chart, "default");

         new Tooltip(this.chart,"default", {
            text: function(n) {
               var monthStr = locale.format(n.x, {
                  selector: "date",
                  datePattern: "y-M",
                  locale: "en"
               });
               return ("(" + monthStr  + ", " + n.y + ")");
            }
         });

         this.chart.setTheme(theme);

         var legendNode = domConstruct.create("div", {class: 'legend'}, container);
         this.legend = new Legend({
            title: "Series",
            chart: this.chart,
            horizontal: false
         }, legendNode);

         var buttonSVGNode = domConstruct.create("div", {}, container);
         this.downloadSVGButton = new Button({
            label: "Save / Enregistrer SVG",
            onClick: lang.hitch(this, function() {
               this.exportSVG();
            }),
            disabled: true
         }, buttonSVGNode);
         this.downloadSVGButton.startup();

         var buttonPNGNode = domConstruct.create("div", {}, container);
         this.downloadPNGButton = new Button({
            label: "Save / Enregistrer PNG",
            onClick: lang.hitch(this, function() {
               this.exportPNG();
            }),
            disabled: true
         }, buttonPNGNode);
         this.downloadPNGButton.startup();

         var buttonCSVNode = domConstruct.create("div", {}, container);
         this.downloadCSVButton = new Button({
            label: "Save / Enregistrer CSV",
            onClick: lang.hitch(this, function() {
               this.exportCSV();
            }),
            disabled: true
         }, buttonCSVNode);
         this.downloadCSVButton.startup();
      }, // constructor

      removeAllSeries: function() {
         for (i = this.chart.series.length - 1; i >= 0; i--) {
            this.chart.removeSeries(this.chart.series[i].name);
         }
      },

      update: function (monthlyStatConfig, region, forecastDay, monthMin, monthMax, stat, groups) {

         this.initialized = false; 

         this.downloadSVGButton.setDisabled(true);
         this.downloadPNGButton.setDisabled(true);
         this.downloadCSVButton.setDisabled(true);

         // Save the parameters to the object so that we can use them in the export functions
         this.monthlyStatConfig = monthlyStatConfig;
         this.region = region;
         this.forecastDay = forecastDay;
         this.monthMin = monthMin;
         this.monthMax = monthMax;
         this.stat = stat;
         this.groupSeriesMap = new Map();

         this.removeAllSeries();

         targetURL = "../../rodb/monthlyStatTS"
         request.get(
            targetURL, 
            {
               handleAs: "json",
               query: {
                  monthlyStatConfig: monthlyStatConfig,
                  variable: this.variable,
                  region: region,
                  forecastDay: forecastDay,
                  monthMin: monthMin,
                  monthMax: monthMax,
                  stat: stat
               }
            }
         ).then(lang.hitch(this, function(series) {
            var seriesCount = 0;
            for (var serieName in series) {
               for (var serieGroup in series[serieName]) {
                  // Convert the x dimension to JS dates
                  for (i = 0; i < series[serieName][serieGroup].length; i++) {
                     series[serieName][serieGroup][i].x = stamp.fromISOString(series[serieName][serieGroup][i].x + '-01');
                  }

                  let displayName = serieName + ' (' + serieGroup + ')';
                  var displayGroup = ( groups && groups.length > 0 ) ? groups.includes(serieGroup) : serieGroup === '7';

                  if (displayGroup) {  
                     this.chart.addSeries(displayName, series[serieName][serieGroup], {plot: "default", stroke: {width: 1}});
                  } else {
                     this.chart.addSeries(displayName, series[serieName][serieGroup], {hidden: "true", plot: "default", stroke: {width: 1}});
                  }

                  // record which series added belong to which group
                  if (this.groupSeriesMap.has(serieGroup)) {
                     this.groupSeriesMap.get(serieGroup).push(seriesCount++);
                  } else {
                     this.groupSeriesMap.set(serieGroup, [seriesCount++]);
                  }
               }

            }

            bounds = this.getChartDataBoundaries();
            var minX = bounds.get('minX'), maxX = bounds.get('maxX'), minY = bounds.get('minY'), maxY = bounds.get('maxY');

            var axis = this.chart.getAxis("x");
            let newMinX = new Date(minX), newMaxX = new Date(maxX);
            axis.opt.min = newMinX.setDate(minX.getDate() - 7);
            axis.opt.max = newMaxX.setDate(maxX.getDate() + 7);

            axis = this.chart.getAxis("y");
            var range = Math.abs(maxY - minY);

            axis.opt.min = minY - range * 0.05;
            axis.opt.max = maxY + range * 0.05;

            this.chart.render();
            this.legend.refresh();

            this.downloadSVGButton.setDisabled(false);
            this.downloadPNGButton.setDisabled(false);
            this.downloadCSVButton.setDisabled(false);
            this.initialized = true;
         }));
      }, // change

      updateGroupVisibility: function(group, visible) {
         if (this.initialized) {
            var legend = this.legend, chart = this.chart;
            var isHidden = !visible, isChecked = visible;

            this.groupSeriesMap.get(group).forEach( function(serieIdx){
               // legend.toogle("default", serieIdx, isHidden);

               // manual toggling instead of 'toogle', which renders the chart after every toggle
               // see src at https://github.com/dojo/dojox/blob/master/charting/widget/SelectableLegend.js
               // shower thought: did the dojo dev team never corrected the typo of 'toogle' or was it just a cooler slang for 'toggle'?
               var plot = chart.getPlot("default");
               chart.series[serieIdx].hidden = isHidden; // hide/unhide series
               legend.autoScale ? chart.dirty = true: plot.dirty = true; // was in original 'toogle' function
            });
            this.chart.render();
            this.legend.refresh();
         }
      },

      // return map of maxX, minX, maxY, minY for all series data in the chart
      getChartDataBoundaries: function() {
         var bounds = new Map();
         var minX, maxX, minY, maxY;
         this.chart.series.forEach(function (serie) {
            serie.data.forEach( function (point) {
               if (minX === undefined) {
                  minX = point.x;
               } else {
                  if ( point.x < minX ) {
                     minX = point.x;
                  }
               }
               if (maxX === undefined) {
                  maxX = point.x;
               } else {
                  if ( point.x > maxX ) {
                     maxX = point.x;
                  }
               }

               if (point.y !== null && point.y.length !== 0) {
                  if (minY === undefined) {
                     minY = Number(point.y);
                  } else {
                     if ( point.y < minY ) {
                        minY = Number(point.y);
                     }
                  }
                  if (maxY === undefined) {
                     maxY = Number(point.y);
                  } else {
                     if ( Number(point.y) > maxY ) {
                        maxY = Number(point.y);
                     }
                  }
               }
            })
         });

         bounds.set('minX', minX);
         bounds.set('maxX', maxX);
         bounds.set('minY', minY);
         bounds.set('maxY', maxY);
         return bounds;
      },

      getSerieGroup: function(serieIdx) {
         for (let [k, v] of this.groupSeriesMap) {
            if (v.includes(serieIdx)) {
               // console.log(val);
               console.log(k);
               return k;
            }
         }
      },


      exportSVG: function() {
         utils.toSvg(this.chart.surface).then(lang.hitch(this, function(svg) {
            var download = document.createElement('a');
            download.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(svg));
            download.setAttribute('download', "monthlyStatTS_" + this.monthlyStatConfig + '_' + this.variableName + "_" + this.region + "_" + this.forecastDay + "_" + this.monthMin + '_' + this.monthMax + '_' + this.stat + ".svg");
            document.body.appendChild(download);
            download.click();
            document.body.removeChild(download);
         }));
      },


      exportPNG: function() {
         utils.toSvg(this.chart.surface).then(lang.hitch(this, function(svg) {
            var self = this;
            svgData_to_png_data(svg, this.chart.dim.width, this.chart.dim.height, function(imageData){
               var download = document.createElement('a');
               download.setAttribute('href', imageData);
               download.setAttribute('download', "monthlyStatTS_" + self.monthlyStatConfig + '_' + self.variableName + "_" + self.region + "_" + self.forecastDay + "_" + self.monthMin + '_' + self.monthMax + '_' + self.stat + ".png");
               document.body.appendChild(download);
               download.click();
               document.body.removeChild(download);
            })
         }));
      },


      exportCSV: function() {
         var self = this; // 'this' will not be visible in the forEach scope otherwise
         var csv = "serie,group,month," + self.stat + "\n";
         // TODO: loop through with groupsMap instead of chart series directly to sort by group?
         this.chart.series.forEach(function(serie, i) {

            serie.data.forEach(function(item) {
               var monthStr = locale.format(item.x, {
                  selector: "date",
                  datePattern: "yyyy-MM",
                  locale: "en"
               });

               csv += '"' + serie.name + '",' + self.getSerieGroup(i) + ',"' + monthStr + '",' + (item.y != null ? item.y : "") + "\n";
            });
         });
         var download = document.createElement('a');
         download.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(csv));
         download.setAttribute('download', "monthlyStatTS_" + self.monthlyStatConfig + '_' + self.variableName + "_" + self.region + "_" + self.forecastDay + "_" + self.monthMin + '_' + self.monthMax + '_' + self.stat + ".csv");
         document.body.appendChild(download);
         download.click();
         document.body.removeChild(download);
      }
   });
});