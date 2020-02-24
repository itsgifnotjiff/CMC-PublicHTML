function linkifyUrls(text) {
   // Capture the whole URL in group 1 to keep string.split() support
   const urlRegex = /((?:https?(?::\/\/))(?:www\.)?[a-zA-Z\d-_.]+(?:\.[a-zA-Z\d]{2,})(?:(?:[-a-zA-Z\d:%_+.~#!?&//=@]*)(?:[,](?![\s]))*)*)/g;
   return text.replace(urlRegex, function(match) {
      return `<a href=\"${match}\">${match}</a>`;
   });
}