function parentPreToCM() {
   var arrScripts = document.getElementsByTagName('script');
   var currScript = arrScripts[arrScripts.length - 1];
   var containerdiv = $(currScript).closest('div.highlight');
   var pre = $(containerdiv).find('pre');
   for (var i=0; i<pre.length; i++) {
      var text = pre[i].innerText;
      var myCodeMirror = CodeMirror(function(elt) {pre[i].parentNode.replaceChild(elt, pre[i]);}, {
         value: text,
         mode:  "text/x-c++src",
         readOnly: true,
         theme: "neat"
      });
   }
}
