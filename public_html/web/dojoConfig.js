var dojoConfig = {
   isDebug: false,
   async: true,
   parseOnLoad: false,
   packages: [{
      name: "vaqum",
      location: document.location.pathname.replace(/[^\/]*$/, '') + 'root/dojo'
   }]
};