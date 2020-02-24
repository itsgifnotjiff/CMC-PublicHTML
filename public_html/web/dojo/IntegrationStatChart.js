define([
   "dojo/_base/declare",
   "dojo/_base/lang",
   "dojo/request",
   "dojox/charting/Chart",
   "dojox/charting/themes/Shrooms",
   "dojo/date/locale",
   "dojo/number",
   "dojox/charting/widget/SelectableLegend",
   "dojox/charting/action2d/Tooltip",
   "dojox/charting/action2d/Magnify",
   "dojox/gfx/utils",
   "dijit/form/Button",
   "dojox/charting/plot2d/Lines",
   "dojox/charting/plot2d/Indicator",
   "dojox/charting/axis2d/Default",
   "dojo/domReady!"
],
function(declare, lang, request, Chart, theme, locale, number, Legend, Tooltip, Magnify, utils, Button) {
    return declare(null, {
      stat: "",
      chart: [],
      legend: null,
      downloadButton: null,
      magnify: null,
      tooltip: null,

      constructor: function(stat) {
         this.stat = stat;
         this.chart = new Chart("chart_" + stat, {
            scrolling: true,
            title: this.stat,
            htmlLabels: false
         });

         this.chart.addAxis("x", {
            title: "Date",
            titleOrientation: "away",
            labelFunc: function(n) {
               var date = new Date(number.parse(n));
               return locale.format(date, {
                  selector: "date",
                  datePattern: "y-M-d",
                  locale: "en"
               });
            },
            majorTickStep: 500000000,
            minorTicks: false,
            htmlLabels: false,
            fontColor: "#000000",
            font: "normal normal normal 12pt DejaVu Sans"
         });

         this.chart.addAxis("y", {
            vertical: true,
            title: "Value",
            min: 0,
            max: 1,
            fontColor: "#000000",
            font: "normal normal normal 10pt DejaVu Sans",
            htmlLabels: false
         });

         this.chart.addPlot("default", {
            type: "Lines",
            markers: true
         });

         // Add line at 0
         if (this.stat == "corr" || this.stat == "mbias") {
            this.chart.addPlot("threshold", {
               type: "Indicator",
               vertical: false,
               lineStroke: { color: "#AAAAAA", style: "Lines"},
               labels: false,
               values: 0
            });
         }

         this.magnify = new Magnify(this.chart, "default");

         this.tooltip = new Tooltip(this.chart,"default", {
            text: function(n) {
               return ("(" + locale.format(n.x, {
                  selector: "date",
                  datePattern: "y-M-d HH:mm:ss",
                  locale: "en"
               }) + ", " + n.y + ")");
            }
         });

         this.chart.setTheme(theme);

         this.legend = new Legend({
            title: "Series",
            chart: this.chart,
            horizontal: false
         }, "legend_" + this.stat);

         // Create export button
         this.downloadButton = new Button({
            label: "Save " + this.stat + " graph",
            onClick: lang.hitch(this, function() {
               this.export();
            }),
            disabled: true
         }, "export_" + this.stat);
      }, // constructor

      setYAxisRange: function (variable) {
         yAxisMin = 0;
         yAxisMax = 0;

         switch (this.stat) {
            case "avg":
               yAxisMin = 0;
               switch (variable) {
                  case "1":
                     yAxisMax = 50;
                     break;
                  case "3":
                     yAxisMax = 15;
                     break;
                  case "4":
                     yAxisMax = 20;
                     break;
                  default:
                     yAxisMax = 0;
               }
               break;
            case "corr":
               yAxisMin = -1;
               yAxisMax = 1;
               break;
            case "mbias":
               yAxisMin = -10;
               yAxisMax = 10;
               break;
            case "rmse":
               yAxisMin = 0;
               yAxisMax = 20;
               break;
            case "urmse":
               yAxisMin = 0;
               yAxisMax = 20;
               break;
         }
         axis = this.chart.getAxis("y");
         axis.opt.min = yAxisMin;
         axis.opt.max = yAxisMax;
      },

      removeAllSeries: function() {
         for (i = this.chart.series.length - 1; i >= 0; i--) {
            this.chart.removeSeries(this.chart.series[i].name);
         }
      },

      change: function (dateMin, dateMax, variable) {
         this.downloadButton.setDisabled(true);

         this.removeAllSeries();

         dateMinStr = dateMin.getFullYear() + "-" + (number.parse(dateMin.getMonth()) + 1) + "-" + dateMin.getDate();
         dateMaxStr = dateMax.getFullYear() + "-" + (number.parse(dateMax.getMonth()) + 1) + "-" + dateMax.getDate();

         this.setYAxisRange(variable);

         targetURL = "../../rodb/integrationStats" +
            "?stat=" + this.stat +
            "&dateMin=" + dateMinStr +
            "&dateMax=" + dateMaxStr +
            "&variable=" + variable;
         request.get(targetURL, {
            handleAs: "json"
         }).then(lang.hitch(this, function(series) {
            for (var serieName in series) {
               if (series.hasOwnProperty(serieName)) {
                  var serieValues = [];

                  for (var i = 0; i < series[serieName].length; i++) {
                     var point = series[serieName][i];
                     var splitDate = (point.x).split(/-|\s|:|\+/);
                     var date = new Date(splitDate[0], splitDate[1] - 1, splitDate[2], splitDate[3], splitDate[4], splitDate[5]);
                     point.x = date;
                     serieValues.push(point);
                  }
                  this.chart.addSeries(serieName, serieValues, {plot: "default", stroke: {width: 1}});
               }
            }
            this.chart.render();
            this.legend.refresh();

            this.downloadButton.setDisabled(false);
         }));
      }, // change

      setInteractive: function(interactive) {
         var plot = this.chart.getPlot("default");
         if (interactive) {
            this.magnify.connect();
            this.tooltip.connect();
            plot.opt.markers = true;
         } else {
            this.magnify.disconnect();
            this.tooltip.disconnect();
            plot.opt.markers = false;
         }
         plot.dirty = true;
         this.chart.render();
      },

      // Download chart to svg format
      export: function() {
         utils.toSvg(this.chart.surface).then(lang.hitch(this, function(svg) {
            var download = document.createElement('a');
            download.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(svg));
            download.setAttribute('download', this.stat + ".svg");
            document.body.appendChild(download);
            download.click();
            document.body.removeChild(download);
         }));
      }
   });
});