$(document).keypress("b",function(e) {
   if (e.ctrlKey) {
      if (! websheets.authinfo.logged_in)
         websheets.auth_reload('Google');
      else {

         $('html').addClass('notop');
         $('#wpadminbar').remove();
         
         for (var i=0; i<websheets.all.length; i++) {
            websheets.all[i].reset();
         }
      }
      return false;
   }  
});
