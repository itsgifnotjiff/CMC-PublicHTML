define([
   "dojo/_base/declare",
   "dojo/_base/lang",
   "dojo/request",
   "dojox/charting/Chart",
   "dojox/charting/themes/Claro",
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
            htmlLabels: false
         });

         this.chart.addAxis("y", {
            vertical: true,
            title: "Value",
            min: (stat == "corr")?-1:((stat == "rmse" || stat == "urmse")?0:-10),
            max: (stat == "corr")?1:((stat == "rmse" || stat == "urmse")?20:10)
         });

         this.chart.addPlot("default", {
            type: "Lines",
            markers: true
         });

         // Add red at 0
         if (this.stat == "corr" || this.stat == "mbias") {
            this.chart.addPlot("threshold", {
               type: "Indicator",
               vertical: false,
               lineStroke: { color: "red", style: "Lines"},
               labels: false,
               values: 0
            });
         }

         new Magnify(this.chart, "default");

         new Tooltip(this.chart,"default", {
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
            label: "Download " + this.stat,
            onClick: lang.hitch(this, function() {
               this.export();
            }),
            disabled: true
         }, "export_" + this.stat);
      }, // constructor

      change: function (region, variable, dateMin, dateMax) {
         this.downloadButton.setDisabled(true);

         // Remove all previous series
         while (this.chart.series.length > 0) {
            this.chart.removeSeries(this.chart.series[0].name);
         }

         dateMin = dateMin.getFullYear() + "-" + (number.parse(dateMin.getMonth()) + 1) + "-" + dateMin.getDate();
         dateMax = dateMax.getFullYear() + "-" + (number.parse(dateMax.getMonth()) + 1) + "-" + dateMax.getDate();

         // Load data
         request.get("db.php?query=data&region=" + region + "&variable=" + variable + "&dateMin=" + dateMin + "&dateMax=" + dateMax + "&stat=" + this.stat, {
            handleAs: "json"
         }).then(lang.hitch(this, function(data) {
            for(var serie in data) {
               var serieValues = [];
               var serieValues00 = [];
               var serieValues12 = [];
               console.log(data[serie][0]);
//                data[serie].forEach(function(value) {
//                   var splitDate = (value.x).split(/-|\s|:|\+/);
//                   var date = new Date(splitDate[0], splitDate[1] - 1, splitDate[2], splitDate[3], splitDate[4], splitDate[5]);
//                   value.x = date;
// 
//                   var hour = (value.x).getHours();
//                   if (hour == "00") {
//                         serieValues00.push(value);
//                   } else if (hour == "12") {
//                         serieValues12.push(value);
//                   }
//                   serieValues.push(value);
//                });

               this.chart.addSeries(serie+" integration 00",serieValues00);
               this.chart.addSeries(serie+" integration 12",serieValues12);
               this.chart.addSeries(serie,serieValues);
            }

            this.chart.fullRender();
            this.legend.refresh();
         }));

         this.downloadButton.setDisabled(false);
      }, // change

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