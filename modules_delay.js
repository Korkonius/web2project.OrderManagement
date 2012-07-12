require(["dojo/ready", "dojo/behavior", "dijit/Dialog", "dijit/form/TextBox", "dijit/Editor"], function(ready, behavior, Dialog, TextBox, Editor){

    // Variable that determine the currently selected item
    var moduleId = undefined;
    var selectedModule = undefined;

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
                moduleId = dojo.attr(e.target, "data-rss-module_id");
                var xhrParam = {
                    url: "?m=ordermgmt&a=moduleJson&suppressHeaders=true&id=" + moduleId,
                    handleAs: "json",
                    preventCache: true,
                    sync: true,
                    load: function(data) {

                        selectedModule = data;

                        // Set general module details
                        dojo.html.set(dojo.byId("orderModuleDetailName"), data.name);
                        dojo.html.set(dojo.byId("orderModuleDetailDescr"), data.description);
                        dojo.html.set(dojo.byId("orderModuleDetailBuild"), data.buildtime);
                        dojo.html.set(dojo.byId("orderModuleDetailDelivered"), data.delivered);

                        // Build child module list
                        dojo.empty("orderDetailsChildren");
                        var ul = dojo.byId("orderDetailsChildren");
                        dojo.forEach(data.childModules, function(item) {
                            var node = dojo.create("li", {innerHTML: item.name}, ul);
                        });

                        // Clear all component tables
                        dojo.forEach(dojo.query(".orderModuleComponentTable"), function(table, num){
                            if(num == 0) {
                                dojo.forEach(dojo.query(".itemLine"), function(line){
                                    dojo.destroy(line, table);
                                });
                            } else dojo.destroy(table);
                        });
                        dojo.forEach(dojo.query("h2", dojo.byId("orderModuleComponentList")), function(node, num){
                            if(num != 0) dojo.destroy(node);
                        });

                        // Build component list for this
                        var componentTable = dojo.query(".orderModuleComponentTable")[0];
                        var refNode = dojo.clone(componentTable);
                        var headerNode = dojo.query(".tableHeader", componentTable)[0];
                        dojo.forEach(data.components, function(item){
                            var total = item.local_price * item.amount;
                            dojo.place("<tr class=\"itemLine\"><td>" + item.amount +"x </td><td>" + item.catalog_number + "</td><td>" + item.description +"</td><td style=\"text-align: right\">" + total + " NOK</td></tr>", headerNode, "after");
                        });
                        dojo.html.set(dojo.query(".orderModuleCompPrice", componentTable)[0], data.modulePrice + " NOK");

                        // Build component lists for all children
                        var lastTable = componentTable;
                        dojo.forEach(data.childModules, function(module){
                            subTotal = 0;
                            componentTable = dojo.clone(refNode);
                            headerNode = dojo.query(".tableHeader", componentTable)[0];
                            dojo.forEach(module.components, function(item){
                                var total = item.local_price * item.amount;
                                dojo.place("<tr><td>" + item.amount +"x </td><td>" + item.catalog_number + "</td><td> " + item.description +"</td><td style=\"text-align: right\">" + total + " NOK</td></tr>", headerNode, "after");
                            });
                            dojo.html.set(dojo.query(".orderModuleCompPrice", componentTable)[0], module.modulePrice + " NOK");
                            dojo.place(componentTable, lastTable, "after");
                            dojo.place("<h2>From " + module.name + ":</h2>", lastTable, "after");
                            lastTable = componentTable;
                        });

                        // Update module total prices
                        dojo.forEach(dojo.query(".orderModuleDetailPrice"), function(node) {
                            dojo.html.set(node, data.totalPrice + " NOK");
                        });

                        // Update file listings
                        var fileList = dojo.byId("orderModuleFileUl");
                        dojo.empty(fileList);
                        dojo.forEach(data.files, function(file) {
                        dojo.place("<li><a href=\"fileviewer.php?file_id="+ file.file_id + "\">" +
                            file.file_description + " (" + file.file_type + ") " +
                            "<span class=\"orderModuleFileDetails\">Size: " + Math.round((file.file_size /1024)*100)/100  + " Kb Changed: " + file.file_date + "</span></a></li>", fileList, "last");
                        });
                    },
                    error: function(crap) {
                        alert(crap.message);
                    }
                }
                dojo.xhrGet(xhrParam);
            }
        },
        ".dojoTextInput": {
            found: function(node) {
                new TextBox({
                    name: dojo.getAttr(node, "id"),
                    placeholder: dojo.getAttr(node, 'title'),
                    title: dojo.getAttr(node, 'title')
                }, dojo.getAttr(node, "id"));
            }
        },
        ".dojoTextEdit": {
            found: function(node) {
                new Editor({
                    width: "100%",
                    height: "200px",
                    value: dojo.getAttr(node, "title")
                }, dojo.getAttr(node, "id"));
            }
        },
        "#orderModuleEditBtn": {
            onclick: function(e) {
                var dialog = dijit.byId("orderModuleDialog");
                dojo.attr(dojo.byId("orderModuleIdIn"),"value", selectedModule.id);
                dijit.byId("orderModuleNameIn").set("value", selectedModule.name);
                dijit.byId("orderModuleBuildIn").set("value", selectedModule.buildtime);
                dijit.byId("orderModuleDescrIn").set("value", selectedModule.description);

                dialog.set("title", "Edit module: " + selectedModule.name);
                dialog.show();
            }
        },
        "#orderModuleAddBtn": {
            onclick: function(e) {
                var dialog = dijit.byId("orderModuleDialog");
                dojo.attr(dojo.byId("orderModuleIdIn"),"value", "0");
                dijit.byId("orderModuleNameIn").set("value", "");
                dijit.byId("orderModuleBuildIn").set("value", "");
                dijit.byId("orderModuleDescrIn").set("value", "Description...");

                dialog.set("title", "Add new module");
                dialog.show();
            }
        },
        "#orderModuleAedSubmit": {
            onclick: function(e) {

                var xhrParam = {
                    url: "?m=ordermgmt&a=moduleJson&suppressHeaders=true&op=ae",
                    handleAs: "json",
                    preventCache: true,
                    content: {
                        "orderModuleId": dojo.attr(dojo.byId("orderModuleIdIn"),"value"),
                        "orderModuleName": dijit.byId("orderModuleNameIn").get("value"),
                        "orderModuleBuild": dijit.byId("orderModuleBuildIn").get("value"),
                        "orderModuleDescr": dijit.byId("orderModuleDescrIn").get("value")
                    },
                    error: function(crap) {
                        alert("Failed to store module: " + crap.message);
                    },
                    load: function(data) {
                        dijit.byId("orderModuleDialog").hide();
                        window.location.href = window.location.href;
                    }
                }
                dojo.xhrPost(xhrParam);
            }
        }
    });
    behavior.apply();
});