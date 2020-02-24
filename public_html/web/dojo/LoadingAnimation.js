define([
      "dojo/_base/declare",
      "dojo/dom",
      "dojo/dom-style",
      "dojo/_base/fx",
      "dojo/_base/lang"
   ],
   function(declare, dom, domStyle, fx, lang) {
      return declare(null, {
         overlayNode: null,
         constructor: function(node) {
            this.overlayNode = dom.byId(node);
            domStyle.set(this.overlayNode, 'display', 'none');
            this.overlayNode.innerHTML = "";

            var container = document.createElement("div");
            container.setAttribute("class", "loadingContainer");

            var animation = document.createElement("div");
            animation.setAttribute("class", "loadingAnimation");
            container.appendChild(animation);

            var message = document.createElement("div");
            message.setAttribute("class", "loadingMessage");
            message.innerHTML = "Loading...";
            container.appendChild(message);

            this.overlayNode.appendChild(container);
         },

         load: function(callback) {
            fx.fadeIn({
               node: this.overlayNode,
               onEnd: function(node) {
                  domStyle.set(node, 'display', 'block');
               }
            }).play();
            setTimeout(lang.hitch(this, function() {
               callback();
               fx.fadeOut({
                  node: this.overlayNode,
                  onEnd: function(node) {
                        domStyle.set(node, 'display', 'none');
                  }
               }).play();
            }), 1000);
         }
    });
});