define([
   "dojo/_base/declare",
   "dojo/_base/lang",
   "dojo/request",
   "dojo/dom-construct",
   "dojox/charting/Chart",
   "dojox/charting/themes/PrimaryColors",
   "dojox/charting/action2d/Tooltip",
   "dojox/charting/action2d/Magnify",
   "dojox/gfx/utils",
   "dijit/form/Button",
   "dojox/charting/plot2d/Lines",
   "dojox/charting/plot2d/Indicator",
   "dojox/charting/axis2d/Default",
   "dojo/domReady!"
],
function(declare, lang, request, domConstruct, Chart, theme, Tooltip, Magnify, utils, Button) {
    return declare(null, {
      chart: null,
      downloadSVGButton: null,
      downloadPNGButton: null,
      downloadButton: null,
      startHour: null,

      constructor: function(container, startHour) {
         this.startHour = startHour;

         var chartNode = domConstruct.create("div", {class: "graph"}, container);
         this.chart = new Chart(chartNode, {
            title: startHour
         });

         this.chart.addAxis("x", {
            title: "Forecast hour / Heure de prévision",
            titleOrientation: "away",
            majorTickStep: 6,
            minorTickStep: 2,
            minorTicks: true,
            htmlLabels: false,
            min: 0,
            fontColor: "#000000",
            font: "normal normal normal 10pt DejaVu Sans"
         });

         this.chart.addAxis("y", {
            vertical: true,
            title: "Value / Valeur",
            min: 0,
            fontColor: "#000000",
            font: "normal normal normal 10pt DejaVu Sans"
         });

         this.chart.addPlot("default", {
            type: "Lines",
            markers: true,
            fill: "#EFFFEF"
         });

         this.chart.addPlot("markers", {
            type: "Indicator",
            vertical: false,
            lineStroke: { color: "#E0E0E0", style: "Lines"},
            labels: false,
            values: [10, 20, 30, 40, 50, 60, 70, 80, 90]
         });

         new Magnify(this.chart, "default");

         new Tooltip(this.chart,"default", {
            text: function(n) {
               return ("(" + n.x  + ", " + n.y + ")");
            }
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


      update: function(comparison, region, variable) {
         this.comparison = comparison;
         this.region = region;
         this.variable = variable;

         // Well... apparently when evaluating a switch statement, JavaSscript
         // does not do its Magic Voodoo Bullshit implicit type casting.
         // Hence the hack below :
         if (typeof variable != 'Number') { variable = parseInt(variable, 10); }
         switch (variable) {
            case 1:
               max = 60;
               break;
            case 3:
               max = 30;
               break;
            case 4:
               max = 30;
               break;
            default:
               max = 100;
         }
         this.chart.getAxis("y").opt.max = max;

         request.get(
            "../../rodb/fctHourAvg",
            {
               handleAs: "json",
               query: {
                  comparison: comparison,
                  variable: variable,
                  region: region,
                  startHour: this.startHour
               }
            }
         ).then(lang.hitch(this, function(reply) {
            this.chart.addSeries(
               "Obs", reply.Obs,
               {plot: "default", stroke: {color:"#33FF33"},
               fill: "#33FF33"}
            );
            this.chart.addSeries(
               "Base", reply.Base,
               {plot: "default", stroke: {color:"#3333FF"},
               fill: "#3333FF"}
            );
            this.chart.addSeries(
               "Test", reply.Test,
               {plot: "default", stroke: {color:"#FF3333"},
               fill: "#FF3333"}
            );
            this.chart.render();
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
            download.setAttribute('download', "fctHourAvg_" + this.comparison + "_" + this.variable + "_" + this.region + "_" + this.startHour  + ".svg");
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
               download.setAttribute('download', "fctHourAvg_" + context.comparison + "_" + context.variable + "_" + context.region + "_" + context.startHour + ".png");
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
         download.setAttribute('download', "fctHourAvg_" + context.comparison + "_" + context.variable + "_" + context.region + "_" + context.startHour + ".csv");
         document.body.appendChild(download);
         download.click();
         document.body.removeChild(download);
      }

   });
});