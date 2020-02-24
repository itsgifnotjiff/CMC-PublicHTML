define([
   "dojo/_base/declare",
   "dojo/_base/lang",
   "dojo/request",
   "dojo/dom-construct",
   "dojo/domReady!"
],
function(declare, lang, request, domConstruct) {
    return declare(null, {
      tableNode: null,
      legendNode: null,

      constructor: function(tableNode, legendNode) {
         this.tableNode = tableNode;
         this.legendNode = legendNode;

         var table = domConstruct.create("table", {class: 'legend'}, legendNode);
         var caption = domConstruct.create("caption", null, table);
         caption.innerHTML = "Legend";

         var header = domConstruct.create("thead", null, table);
         var headerRow = domConstruct.create("tr", null, header);
         var cell = domConstruct.create("th", null, headerRow);
         cell.innerHTML = "Color";
         var cell = domConstruct.create("th", null, headerRow);
         cell.innerHTML = "AQPI Difference";

         // muchWorse, worse, slightlyWorse, similar, slightlyBetter, better, muchBetter
         var body = domConstruct.create("tbody", null, table);
         var row = domConstruct.create("tr", null, body);
         var cell = domConstruct.create("td", {class: "muchBetter"}, row);
         var cell = domConstruct.create("td", {class: "label"}, row);
         cell.innerHTML = "[5; ∞";
         var row = domConstruct.create("tr", null, body);
         var cell = domConstruct.create("td", {class: "better"}, row);
         var cell = domConstruct.create("td", {class: "label"}, row);
         cell.innerHTML = "[3; 5[";
         var row = domConstruct.create("tr", null, body);
         var cell = domConstruct.create("td", {class: "slightlyBetter"}, row);
         var cell = domConstruct.create("td", {class: "label"}, row);
         cell.innerHTML = "[1; 3[";
         var row = domConstruct.create("tr", null, body);
         var cell = domConstruct.create("td", {class: "similar"}, row);
         var cell = domConstruct.create("td", {class: "label"}, row);
         cell.innerHTML = "[-1; 1[";
         var row = domConstruct.create("tr", null, body);
         var cell = domConstruct.create("td", {class: "slightlyWorse"}, row);
         var cell = domConstruct.create("td", {class: "label"}, row);
         cell.innerHTML = "[-3; -1[";
         var row = domConstruct.create("tr", null, body);
         var cell = domConstruct.create("td", {class: "worse"}, row);
         var cell = domConstruct.create("td", {class: "label"}, row);
         cell.innerHTML = "[-5; -3[";
         var row = domConstruct.create("tr", null, body);
         var cell = domConstruct.create("td", {class: "muchWorse"}, row);
         var cell = domConstruct.create("td", {class: "label"}, row);
         cell.innerHTML = "∞; -5[";
      }, // constructor

      classify: function(diff) {
         if (diff < -5)
            return "muchWorse";
         if (diff >= -5 && diff < -3)
            return "worse";
         if (diff >= -3 && diff < -1)
            return "slightlyWorse";
         if (diff >= -1 && diff < 1)
            return "similar";
         if (diff >= 1 && diff < 3)
            return "slightlyBetter";
         if (diff >= 3 && diff < 5)
            return "better";
         return "muchBetter";
      },


      round: function round(number, precision) {
         var shift = function (number, precision, reverseShift) {
            if (reverseShift) {
               precision = -precision;
            }
            var numArray = ("" + number).split("e");
            return +(numArray[0] + "e" + (numArray[1] ? (+numArray[1] + precision) : precision));
         };
         return shift(Math.round(shift(number, precision, false)), precision, true);
      },


      update: function(monthlyStatConfig, month, group) {
         request.get(
            "../../rodb/monthlyStat",
            {
               handleAs: "json",
               query: {
                  monthlyStatConfig: monthlyStatConfig,
                  month: month,
                  group: group
               }
            }
         ).then(lang.hitch(this, function(reply) {
            // "this" ends up as being the window in the inner loops!
            var summaryTableObj = this;
            domConstruct.empty(this.tableNode);

            var periods = ['current', 'avg5year'];

            var table = domConstruct.create("table", {class: "summary"}, this.tableNode);
            var caption = domConstruct.create("caption", null, table);
            caption.innerHTML = "AQPI " + reply.month;

            var header = domConstruct.create("thead", null, table);
            var row = domConstruct.create("tr", null, header);
            var cell = domConstruct.create("th", {colspan: 3}, row);
            var cell = domConstruct.create("th", {colspan: reply.config.variables.length * reply.config.forecastDays.length, class: 'forecastDay'}, row);
            cell.innerHTML = 'Forecast Day';
            var row = domConstruct.create("tr", null, header);
            var cell = domConstruct.create("th", {colspan: 3}, row);
            reply.config.forecastDays.forEach(function (forecastDay) {
               var cell = domConstruct.create("th", {colspan: reply.config.variables.length, class: 'forecastDay'}, row);
               cell.innerHTML = forecastDay;
            });
            var row = domConstruct.create("tr", null, header);
            var cell = domConstruct.create("th", null, row);
            cell.innerHTML = 'Region';
            var cell = domConstruct.create("th", null, row);
            cell.innerHTML = 'Series';
            var cell = domConstruct.create("th", null, row);
            cell.innerHTML = 'Period';
            reply.config.forecastDays.forEach(function (forecastDay) {
               reply.config.variables.forEach(function (variable) {
                  var cell = domConstruct.create("th", null, row);
                  cell.innerHTML = variable.name;
               });
            });

            var body = domConstruct.create("tbody", null, table);

            reply.config.regions.forEach(function (region) {
               var firstRegion = true;
               for (var serieName in reply.serie) {
                  var curRow = domConstruct.create("tr", null, body);
                  var avgRow = domConstruct.create("tr", null, body);

                  var newRegionClass = '';
                  if (firstRegion) {
                     newRegionClass = 'newRegion';
                     var cell = domConstruct.create("td", {rowspan: Object.keys(reply.serie).length * periods.length, class: newRegionClass}, curRow);
                     cell.innerHTML = region.name;
                     firstRegion = false;
                  }

                  var cell = domConstruct.create("td", {rowspan: 2, class: newRegionClass}, curRow);
                  cell.innerHTML = serieName;

                  var cell = domConstruct.create("td", {class: newRegionClass}, curRow);
                  cell.innerHTML = 'Current';
                  var cell = domConstruct.create("td", {class: 'avg5year'}, avgRow);
                  cell.innerHTML = '5 Year Average';

                  reply.config.forecastDays.forEach(function (forecastDay) {
                     reply.config.variables.forEach(function (variable) {
                        var valueClass = "";
                        // Not all series have all variables and JS will throw an error if we try to index a non existant object
                        // sometimes there is no data for this month, but there is data is present for other years, check if 'current' exist
                        var dataPresent = (variable.name in reply.serie[serieName].forecastDay[forecastDay].variable) 
                            && (reply.serie[serieName].forecastDay[forecastDay].variable[variable.name].region[region.name].current !== undefined);

                        if ( dataPresent ){
                           var current = reply.serie[serieName].forecastDay[forecastDay].variable[variable.name].region[region.name].current.aqpi;
                           var average = reply.serie[serieName].forecastDay[forecastDay].variable[variable.name].region[region.name].avg5years.aqpi;
                           valueClass = summaryTableObj.classify(current - average);
                        }

                        var curCell = domConstruct.create("td", {class: 'value ' + valueClass + ' ' + newRegionClass}, curRow);
                        var avgCell = domConstruct.create("td", {class: 'value avg5year ' + newRegionClass}, avgRow);

                        if ( dataPresent ) {
                           curCell.innerHTML = summaryTableObj.round(current, 1);
                           avgCell.innerHTML = summaryTableObj.round(average, 1);
                        }
                     });
                  });
               }
            });


         }));
      }
   });
});