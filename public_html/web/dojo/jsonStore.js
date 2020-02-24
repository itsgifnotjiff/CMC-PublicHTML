define(["dojo/_base/declare",
        "dojo/_base/lang",
        "dojo/_base/xhr",
        "dojo/store/util/QueryResults",
        "dojo/store/JsonRest"],
function(declare, lang, xhr, QueryResults, JsonRest) {

   return declare("vaqum/jsonStore", dojo.store.JsonRest, {
      // errorHandler: Function
      //    This will be called when a xhr request fails
      errorHandler: function(response, ioArgs) {
         var error = JSON.parse(response.responseText);
         alert(error.message);
         console.log(error.driverMsg);
         return response;
      },

      get: function(id, options){
         // summary:
         //    Retrieves an object by its identity. This will trigger a GET request to the server using
         //    the url `this.target + id`.
         // id: Number
         //    The identity to use to lookup the object
         // options: Object?
         //    HTTP headers. For consistency with other methods, if a `headers` key exists on this object, it will be
         //    used to provide HTTP headers instead.
         // returns: Object
         //    The object in the store that matches the given id.
         options = options || {};
         var headers = lang.mixin({ Accept: this.accepts }, this.headers, options.headers || options);
         return xhr("GET", {
            url: this.target + id,
            handleAs: "json",
            error: this.errorHandler,
            headers: headers
         });
      },

      put: function(object, options){
         // summary:
         //    Stores an object. This will trigger a PUT request to the server
         //    if the object has an id, otherwise it will trigger a POST request.
         // object: Object
         //    The object to store.
         // options: __PutDirectives?
         //    Additional metadata for storing the data.  Includes an "id"
         //    property if a specific id is to be used.
         // returns: dojo/_base/Deferred
         options = options || {};
         var id = ("id" in options) ? options.id : this.getIdentity(object);
         var hasId = typeof id != "undefined";
         return xhr(hasId && !options.incremental ? "PUT" : "POST", {
            url: hasId ? this.target + id : this.target,
            postData: JSON.stringify(object),
            handleAs: "json",
            error: this.errorHandler,
            headers: lang.mixin({
               "Content-Type": "application/json",
               Accept: this.accepts,
               "If-Match": options.overwrite === true ? "*" : null,
               "If-None-Match": options.overwrite === false ? "*" : null
            }, this.headers, options.headers)
         });
      },

      query: function(query, options) {
         // summary:
         //    Queries the store for objects. This will trigger a GET request to the server, with the
         //    query added as a query string.
         // query: Object
         //    The query to use for retrieving objects from the store.
         // options: __QueryOptions?
         //    The optional arguments to apply to the resultset.
         // returns: dojo/store/api/Store.QueryResults
         //    The results of the query, extended with iterative methods.
         options = options || {};

         var headers = lang.mixin({ Accept: this.accepts }, this.headers, options.headers);

         if(options.start >= 0 || options.count >= 0){
            headers.Range = headers["X-Range"] //set X-Range for Opera since it blocks "Range" header
               = "items=" + (options.start || '0') + '-' +
               (("count" in options && options.count != Infinity) ?
                  (options.count + (options.start || 0) - 1) : '');
         }
         var hasQuestionMark = this.target.indexOf("?") > -1;
         if(query && typeof query == "object"){
            query = xhr.objectToQuery(query);
            query = query ? (hasQuestionMark ? "&" : "?") + query: "";
         }
         if(options && options.sort){
            var sortParam = this.sortParam;
            query += (query || hasQuestionMark ? "&" : "?") + (sortParam ? sortParam + '=' : "sort(");
            for(var i = 0; i<options.sort.length; i++){
               var sort = options.sort[i];
               query += (i > 0 ? "," : "") + (sort.descending ? this.descendingPrefix : this.ascendingPrefix) + encodeURIComponent(sort.attribute);
            }
            if(!sortParam){
               query += ")";
            }
         }
         var results = xhr("GET", {
            url: this.target + (query || ""),
            handleAs: "json",
            headers: headers,
            error: this.errorHandler
         });
         results.total = results.then(function(){
            var range = results.ioArgs.xhr.getResponseHeader("Content-Range");
            return range && (range = range.match(/\/(.*)/)) && +range[1];
         });
         return QueryResults(results);
      },

      remove: function(id, options){
         // summary:
         //    Deletes an object by its identity. This will trigger a DELETE request to the server.
         // id: Number
         //    The identity to use to delete the object
         // options: __HeaderOptions?
         //    HTTP headers.
         options = options || {};
         return xhr("DELETE", {
            url: this.target + id,
            headers: lang.mixin({}, this.headers, options.headers),
            error: this.errorHandler
         });
      }
   });
});