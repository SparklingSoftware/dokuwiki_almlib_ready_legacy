
jQuery(window).load(function () {
    runRigrr();
});

function runRigrr() {
    var BPMN = document.getElementById("rigrr_bpmn").value;
    try {
        var lib = new com.rapilabs.Rigrr('rigrr_canvas');
        lib.drawDiagram("rigrr_canvas", BPMN);
    }
    catch (err) {
        txt = "There was an error on this page.\n\n";
        txt += "Error description: " + err.message + "\n\n";
        txt += "Click OK to continue.\n\n";
        alert(txt);
    }
}
