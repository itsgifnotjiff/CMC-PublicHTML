function svgData_to_png_data(svgData, width, height, callback) {
   // Parameters :
   //    svgData : SVG image text
   //    width : Width of the image to render
   //    height : Height of the image to render
   //    callback : A function that will be called when the image is rendered.  Take the PNG data a parameter
   // Return : Nothing
   var ctx;
   var canvas;
   var svgData;
   var img;

   img = new Image();
   // Since rendering is asynchronous. we need to use callbacks
   img.addEventListener('load', function(event) {
      // Draw the SVG image to a canvas
      canvas = document.createElement('canvas');
      canvas.width = width;
      canvas.height = height;
      ctx = canvas.getContext("2d");
      ctx.drawImage(img, 0, 0);

      // Call the callback with the image data
      callback(canvas.toDataURL("image/png"));
   }, false);
   img.src = "data:image/svg+xml," + encodeURIComponent(svgData);
}


function svg_to_png_data(target) {
   // Takes an SVG element as target
   // Return a dataURL
  var ctx, canvas, svg_data, img, child;

  // Flatten CSS styles into the SVG
  for (i = 0; i < target.childNodes.length; i++) {
    child = target.childNodes[i];
    var cssStyle = window.getComputedStyle(child);
    if(cssStyle){
       child.style.cssText = cssStyle.cssText;
    }
  }

  // Construct an SVG image
  svg_data = '<svg xmlns="http://www.w3.org/2000/svg" width="' + target.offsetWidth +
             '" height="' + target.offsetHeight + '">' + target.innerHTML + '</svg>';
  img = new Image();
  img.src = "data:image/svg+xml," + encodeURIComponent(svg_data);

  // Draw the SVG image to a canvas
  canvas = document.createElement('canvas');
  canvas.width = target.offsetWidth;
  canvas.height = target.offsetHeight;
  ctx = canvas.getContext("2d");
  ctx.drawImage(img, 0, 0);

  // Return the canvas's data
  return canvas.toDataURL("image/png");
}

// Takes an SVG element as target
function svg_to_png_replace(target) {
  var data, img;
  data = svg_to_png_data(target);
  img = new Image();
  img.src = data;
  target.parentNode.replaceChild(img, target);
}