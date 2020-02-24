define([
   "dojo/_base/declare",
   "dojo/_base/lang",
   "dojo/request",
   "dojox/charting/Chart",
   "dojox/charting/themes/PrimaryColors",
   "dojox/charting/action2d/Tooltip",
   "dojox/charting/action2d/Magnify",
   "dojox/gfx/utils",
   "dijit/form/Button",
   "dojo/dom-construct",
   "dojox/charting/plot2d/Lines",
   "dojox/charting/plot2d/Indicator",
   "dojox/charting/axis2d/Default",
   "dojo/domReady!"
],
function(declare, lang, request, Chart, theme, Tooltip, Magnify, utils, Button, domConstruct) {
    return declare(null, {
      chart: null,
      downloadSVGButton: null,
      downloadPNGButton: null,

      constructor: function(container, stat) {
         this.stat = stat;

         var chartNode = domConstruct.create("div", {class: 'graph'}, container);
         this.chart = new Chart(chartNode, {
            title: stat
         });

         this.chart.addAxis("x", {
            title: "Forecast hour / Heure de prévision",
            titleOrientation: "away",
            majorTickStep: 6,
            minorTickStep: 2,
            minorTicks: true,
            htmlLabels: false,
            fontColor: "#000000",
            font: "normal normal normal 10pt DejaVu Sans"
         });

         this.chart.addAxis("y", {
            vertical: true,
            title: "Value / Valeur",
            min: (stat == "corr")?-1:((stat == "rmse" || stat == "urmse")?0:-10),
            max: (stat == "corr")?1:((stat == "rmse" || stat == "urmse")?20:10),
            fontColor: "#000000",
            font: "normal normal normal 10pt DejaVu Sans"
         });

         this.chart.addPlot("default", {
            type: "Lines",
            markers: true,
            fill: "#EFFFEF"
         });

         if (stat == "corr" || stat == "mbias") {
            this.chart.addPlot("threshold", {
               type: "Indicator",
               vertical: false,
               lineStroke: { color: "#949784", style: "Lines"},
               labels: false,
               values: 0
            });
         }

         var markerValues = [];
         switch (stat) {
            case "rmse":
               markerValues = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20];
               break;
            case "corr":
               markerValues = [-0.9, -0.8, -0.7, -0.6, -0.5, -0.4, -0.3, -0.2, -0.1, 0.1, 0.2, 0.3, 0.4, 0.5, 0.6, 0.7, 0.8, 0.9, 1.0];
               break;
            case "mbias":
               markerValues = [-9, -8, -7, -6, -5, -4, -3, -2, -1, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10];
               break;
         }

         this.chart.addPlot("markers", {
            type: "Indicator",
            vertical: false,
            lineStroke: { color: "#E0E0E0", style: "Lines"},
            labels: false,
            values: markerValues
         });

         new Magnify(this.chart, "default");

         new Tooltip(this.chart,"default", {
            text: function(n) {
               return ("(" + n.x  + ", " + n.y + ")");
            }
         });

         //theme.plotarea.fill = "#EFEFEF";
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


      update: function(comparison, region, variable) {
         this.comparison = comparison;
         this.region = region;
         this.variable = variable;

         request.get(
            "../../rodb/fctHourStat",
            {
               handleAs: "json",
               query: {
                  comparison: comparison,
                  region: region,
                  variable: variable,
                  stat: this.stat
               }
            }
         ).then(lang.hitch(this, function(reply) {
            if (reply[00] !== undefined) {
               this.chart.addSeries(
                  "00_Base", reply[00].Base,
                  {plot: "default", stroke: {color:"#3333FF"},
                  fill: "#3333FF"}
               );
               this.chart.addSeries(
                  "00_Test", reply[00].Test,
                  {plot: "default", stroke: {color:"#FF3333"},
                  fill: "#FF3333"}
               );
            }

            if (reply[12] !== undefined) {
               this.chart.addSeries(
                  "12_Base", reply[12].Base,
                  {plot: "default", stroke: {color:"#8888FF"},
                  fill: "#8888FF"}
               );
               this.chart.addSeries(
                  "12_Test", reply[12].Test,
                  {plot: "default", stroke: {color:"#FF8888"},
                  fill: "#FF8888"}
               );
            }
            this.chart.fullRender();
            this.downloadSVGButton.setDisabled(false);
            this.downloadPNGButton.setDisabled(false);
            this.downloadCSVButton.setDisabled(false);
         }));
      },


      // Download chart to svg format
      exportSVG: function() {
         utils.toSvg(this.chart.surface).then(lang.hitch(this, function(svg) {
            var download = document.createElement('a');
            download.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(svg));
            download.setAttribute('download', "fctHourStat_" + this.comparison + "_" + this.variable + "_" + this.region + "_" + this.stat + ".svg");
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
               download.setAttribute('download', "fctHourStat_" + context.comparison + "_" + context.variable + "_" + context.region + "_" + context.stat + ".png");
               document.body.appendChild(download);
               download.click();
               document.body.removeChild(download);
            })

         }));
      },


      // Download chart to csv format
      exportCSV: function() {
         var context = this;
         var csv = "run,hour,value" + "\n";

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
         download.setAttribute('download', "fctHourStat_" + context.comparison + "_" + context.variable + "_" + context.region + "_" + context.stat + ".csv");
         document.body.appendChild(download);
         download.click();
         document.body.removeChild(download);
      }

   });
});