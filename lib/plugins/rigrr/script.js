
jQuery(window).load(function () {
    runRigrr();
});

function runRigrr() {
    try {

        jQuery("[id^=rigrr_bpmn]").each(function () {
            var bpmn  = jQuery(this).text();
            var tag = jQuery(this).attr('tag');

            var lib = new com.rapilabs.Rigrr('rigrr_canvas' + tag);
            lib.drawDiagram("rigrr_canvas" + tag, bpmn);
        });

//        alert(prueba);

//        alert("Value: " + jQuery("#rigrr_bpmn").text());
    }
    catch (err) {
        txt = "There was an error on this page.\n\n";
        txt += "Error description: " + err.message + "\n\n";
        txt += "Click OK to continue.\n\n";
        alert(txt);
    }
}
