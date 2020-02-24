define([
   "dojo/_base/declare",
   "dojo/_base/lang",
   "dojo/request",
   "dojo/date/stamp",
   "dojo/date/locale",
   "dojox/charting/Chart",
   "dojox/charting/themes/PrimaryColors",
   "dojox/gfx/utils",
   "dijit/form/Button",
   "dojo/dom-construct",
   "dojox/charting/plot2d/Grid",
   "dojox/charting/plot2d/Lines",
   "dojox/charting/plot2d/Indicator",
   "dojox/charting/axis2d/Default",
   "dojo/domReady!"
],
function(declare, lang, request, stamp, locale, Chart, theme, utils, Button, domConstruct, Grid, Lines) {
    return declare(null, {
      comparison: null,
      metArea: null,
      variable: null,
      chart: null,
      downloadSVGButton: null,
      downloadPNGButton: null,

      constructor: function(container) {
         var chartNode = domConstruct.create("div", {class: 'metAreaTsGraph'}, container);
         this.chart = new Chart(chartNode, {
            title: "Average Metropolitan Area Concentration<br>Concentration moyenne de la région métropolitaine"
         });

         this.chart.addAxis("x", {
            title: "Date",
            titleOrientation: "away",
            minorTicks: true,
            htmlLabels: false,
            minorLabels: true,
            majorTickStep: 86400000,
            minorTickStep: 21600000,
            fontColor: "#000000",
            font: "normal normal normal 10pt DejaVu Sans",
            labelFunc: function(text, value, precision) {
               return locale.format(new Date(value), {
                  selector: "date",
                  datePattern: "y-M-d",
                  locale: "en"
               });
            }
         });

         this.chart.addAxis("y", {
            vertical: true,
            title: "Concentration",
            includeZero: true,
            fontColor: "#000000",
            font: "normal normal normal 10pt DejaVu Sans"
         });

         this.chart.addPlot("default", {
            type: Lines,
            markers: false,
            fill: "#EFEFEF"
         });

         this.chart.addPlot("Grid", {
            type: Grid,
            hMajorLines: true,
            hMinorLines: false,
            vMajorLines: false,
            vMinorLines: false,
            majorHLine: { color: "#CCCCCC", width: 1 }
         });

         this.chart.setTheme(theme);

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


      update: function(comparison, metArea, variable) {
         this.comparison = comparison;
         this.metArea = metArea;
         this.variable = variable;

         request.get(
            "../../rodb/metAreaTs",
            {
               handleAs: "json",
               query: {
                  comparison: comparison,
                  metArea: metArea,
                  variable: variable
               }
            }
         ).then(lang.hitch(this, function(reply) {
            var obs = [];
            var base = [];
            var test = [];
            for (i = 0; i < reply.length; i++) {
               var isoDateStr = reply[i].date.replace(/\+\d{2}$/, '').replace(/\s/, 'T');
               var date = stamp.fromISOString(isoDateStr);
               obs.push({x: date, y: reply[i].obs});
               base.push({x: date, y: reply[i].base});
               test.push({x: date, y: reply[i].test});
            }
            this.chart.addSeries(
               "Obs", obs,
               {plot: "default", stroke: {width: 1, color:"#33CC33"},
               fill: "#33CC33"}
            );
            this.chart.addSeries(
               "Base", base,
               {plot: "default", stroke: {width: 1, color:"#3333CC"},
               fill: "#3333CC"}
            );
            this.chart.addSeries(
               "Test", test,
               {plot: "default", stroke: {width: 1, color:"#CC3333"},
               fill: "CC3333"}
            );
            this.chart.fullRender();
            this.downloadSVGButton.setDisabled(false);
            this.downloadPNGButton.setDisabled(false);
            this.downloadCSVButton.setDisabled(false);
         }));
      },


      resize: function(width) {
         this.chart.resize(width, this.chart.dim.height);
         this.chart.render();
      },


      // Download chart to svg format
      exportSVG: function() {
         utils.toSvg(this.chart.surface).then(lang.hitch(this, function(svg) {
            var download = document.createElement('a');
            download.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(svg));
            download.setAttribute('download', "metAreaTs_" + this.comparison + "_" + this.metArea  + "_" + this.variable + ".svg");
            document.body.appendChild(download);
            download.click();
            document.body.removeChild(download);
         }));
      },


      // Download chart to svg format
      exportPNG: function() {
         utils.toSvg(this.chart.surface).then(lang.hitch(this, function(svg) {
            var context = this;
            svgData_to_png_data(svg, this.chart.dim.width, this.chart.dim.height, function(imageData){
               var download = document.createElement('a');
               download.setAttribute('href', imageData);
               download.setAttribute('download', "metAreaTs_" + context.comparison + "_" + context.metArea  + "_" + context.variable  + ".png");
               document.body.appendChild(download);
               download.click();
               document.body.removeChild(download);
            })

         }));
      },


      // Download chart to csv format
      exportCSV: function() {
         var context = this;
         var csv = "run,time,value" + "\n";

         Object.keys(context.chart.runs).forEach( function(run) { // for every run (Base, Obs, Test)
            let runID = context.chart.runs[run];
            // console.log(runID);

            let serie = context.chart.series[runID];
            serie.data.forEach(function(item) {
               csv += '"' + run + '",' + item.x + ',' + item.y + "\n";
            });
         });

         var download = document.createElement('a');
         download.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(csv));
         download.setAttribute('download', "metAreaTs_" + context.comparison + "_" + context.metArea  + "_" + context.variable  + ".csv");
         document.body.appendChild(download);
         download.click();
         document.body.removeChild(download);
      }

   });
});