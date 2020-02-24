define([
   "dojo/_base/declare",
   "dojo/_base/lang",
   "dojo/request",
   "dojox/charting/Chart",
   "dojox/charting/themes/PrimaryColors",
   "dojox/charting/plot2d/ClusteredColumns",
   "dojox/charting/action2d/Tooltip",
   "dojox/gfx/utils",
   "dijit/form/Button",
   "dojo/dom-construct",
   "dojox/charting/axis2d/Default"
],
function(declare, lang, request, Chart, theme, ClusteredColumns, Tooltip, utils, Button, domConstruct) {
   return declare(null, {
      chart: null,
      stat: null,
      downloadSVGButton: null,
      downloadPNGButton: null,

      constructor: function(container, stat) {
         this.stat = stat;

         var chartNode = domConstruct.create("div", {class: 'graph'}, container);
         this.chart = new Chart(chartNode, {
            title: this.stat,
            htmlLabels: false
         });

         this.chart.addAxis("x", {
            title: "Bin",
            titleOrientation: "away",
            htmlLabels: false,
            fontColor: "#000000",
            font: "normal normal normal 12pt DejaVu Sans"
         });

         this.createSpecifics();

         this.chart.addPlot("default", {
            type: "ClusteredColumns",
            hAxis: "x",
            vAxis: "y",
            gap: 32,
            minBarSize: 32,
            maxBarSize: 32
         });

         new Tooltip(this.chart,"default", {
            text: function(n) {
               return n.y;
            }
         });

         this.chart.setTheme(theme);

         this.chart.addSeries(
            "Base",
            [],
            {plot: "default", stroke: {color:"#3333FF"}, fill: "#3333FF"}
         );
         this.chart.addSeries(
            "Test",
            [],
            {plot: "default", stroke: {color:"#FF3333"}, fill: "#FF3333"}
         );

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
      },

      update: function(comparison, region, variable) {
         this.comparison = comparison;
         this.region = region;
         this.variable = variable;
         request.get(
            "../../rodb/binStat",
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
            this.chart.getSeries('Base').update(reply.Base);
            this.chart.getSeries('Test').update(reply.Test);
            var labels = [];
            reply.Base.forEach(function (bin) {
               labels.push({value: bin.x, text: bin.label});
            })
            var xAxis = this.chart.getAxis('x').opt.labels = labels;
            this.chart.fullRender();
            this.downloadSVGButton.setDisabled(false);
            this.downloadPNGButton.setDisabled(false);
         }));
      },


      // Download chart to svg format
      exportSVG: function() {
         utils.toSvg(this.chart.surface).then(lang.hitch(this, function(svg) {
            var download = document.createElement('a');
            download.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(svg));
            download.setAttribute('download', "binStat_" + this.comparison + "_" + this.variable + "_" + this.region + "_" + this.stat + ".svg");
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
               download.setAttribute('download', "binStat_" + context.comparison + "_" + context.variable + "_" + context.region + "_" + context.stat + ".png");
               document.body.appendChild(download);
               download.click();
               document.body.removeChild(download);
            })

         }));
      }
   });
});