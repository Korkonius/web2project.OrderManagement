require(["dojo/behavior", "dijit/Dialog"], function(behavior, Dialog){

    // Register listeners on items in the module list
    var loadDetailsDialog = new Dialog({
        title: "Loading module details...",
        content: "Loading module details...",
        style: "width: 400px"
    });
    behavior.add({
        "#orderModuleList ul li":{
            onclick: function(e) {
                e.preventDefault(); // Stop default event handling

                // Fetch information about this object
                var moduleId = dojo.attr(e.target, "data-rss-module_id");
                var xhrParam = {
                    url: "?m=ordermgmt&a=moduleJson&suppressHeaders=true&id=" + moduleId,
                    handleAs: "json",
                    preventCache: true,
                    sync: true,
                    load: function(data) {
                        dojo.html.set(dojo.byId("orderModuleDetailName"), data.name);
                        dojo.html.set(dojo.byId("orderModuleDetailDescr"), data.description);
                        dojo.html.set(dojo.byId("orderModuleDetailBuild"), data.buildtime);
                        dojo.html.set(dojo.byId("orderModuleDetailDelivered"), data.delivered);
                    },
                    error: function(crap) {
                        alert(crap.message);
                    }
                }
                dojo.xhrGet(xhrParam);
            }
        }
    });
    behavior.apply();
});