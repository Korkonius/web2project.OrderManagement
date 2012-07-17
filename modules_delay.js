// Configure dojo to include the custom package set
var dojoConfig = {
    baseUrl: "./modules/ordermgmt/js/",
    tlmSiblingOfDojo: false,
    packages: [
        {name: "dojo", location: "dojo"},
        {name: "dijit", location: "dijit"},
        {name: "dojox", location: "dojox"},
        {name: "w2porder", location: "w2porder"}
    ]
};
require(["dojo/ready", "dojo/behavior", "dijit/Dialog", "dijit/form/TextBox", "dijit/form/Button",
    "dijit/Editor", "dojo/currency", 'dojo/store/Memory', "dojo/data/ObjectStore", 'dijit/form/FilteringSelect', "dojo/_base/xhr",
    "dojo/io-query", "dojo/back"]
    , function(ready, behavior, Dialog, TextBox, Button, Editor, Currency, Memory, ObjectStore, FilteringSelect, xhr, ioquery, back){

    // Set body class to get styling right
    dojo.addClass(dojo.query("body")[0], "claro");
    dojo.place("<link type=\"text/css\" rel=\"stylesheet\" href=\"./modules/ordermgmt/js/dijit/themes/claro/claro.css\" />", dojo.query('head')[0], "last");

    // Variable that determine the currently selected item
    var moduleId = undefined;
    var selectedModule = undefined;
    var componentStore = undefined;
    var newComponents = undefined;
    var removedComponents = undefined;

        xhr.get({
            url: "?m=ordermgmt&a=cedit&op=getfilterlist&suppressHeaders=true",
            handleAs: "json"
        }).then(function(data) {
                componentStore = new ObjectStore({objectStore: new Memory({data: data.items})});
                new FilteringSelect({
                    id: dojo.attr("orderComponentSelect", "id"),
                    name: dojo.attr("orderComponentSelect", "id"),
                    queryExpr: "*${0}*",
                    autoComplete: false,
                    "store": componentStore,
                    searchAttr: "list_display",
                    labelAttr: "list_display",
                    labelType: "html"
                }, dojo.attr("orderComponentSelect", "id"));
            });

    // Register listeners on items in the module list
    var loadDetailsDialog = new Dialog({
        title: "Loading module details...",
        content: "Loading module details...",
        style: "width: 400px"
    });

    // Renders components from component into table
    function renderComponentTable(components, reference) {
        dojo.forEach(components, function(item){
            var total = item.local_price * item.amount;
            dojo.place("<tr class=\"itemLine\"><td>" + item.amount +"x </td><td>" + item.catalog_number + "</td><td>" + item.description +"</td><td style=\"text-align: right\">" + dojo.currency.format(total) + "</td></tr>", reference, "after");
        });
    }

    // Render initial component editing view
    function initComponentEditing() {

        // Reset component arrays
        newComponents = new Array();
        newAmounts = new Array();
        removedComponents = new Array();

        // Clear component table
        dojo.forEach(dojo.query("tr", "orderComponentEditList"), function(item, index){
            if(index != 0) dojo.destroy(item);
        });

        // Render component table
        var reference = dojo.query("tr.tableHeader", "orderComponentEditList")[0];
        dojo.forEach(selectedModule.components, function(item){
            var total = item.local_price * item.amount;
            dojo.place("<tr class=\"itemLine\"><td><img class=\"orderComponentRemoveBtn\" data-rss-orderComponentId=\"" + item.component_id + "\" src=\"./modules/ordermgmt/images/delete.png\" alt=\"Remove Component\" title=\"Remove Component\" /></td><td>" +
                item.amount +"x </td><td>" + item.catalog_number + "</td><td>" + item.description +"</td>", reference, "after");
        });

        dojo.behavior.add({
            ".orderComponentRemoveBtn": {
                onclick: function(e) {
                    var id = dojo.attr(e.target, "data-rss-orderComponentId");
                    removeComponent(id);
                }
            }
        });
        dojo.behavior.apply();
    }

    function removeComponent(componentId) {

        // Remove components and add it to the list
        removedComponents.push(componentId);
        dojo.forEach(selectedModule.components, function(item, index){
            if(item != undefined) {
                if(item.component_id == componentId) {
                    selectedModule.components.splice(index, 1);

                }
            }
        });

        // Clear component table
        dojo.forEach(dojo.query("tr", "orderComponentEditList"), function(item, index){
            if(index != 0) dojo.destroy(item);
        });

        // Render component table
        var reference = dojo.query("tr.tableHeader", "orderComponentEditList")[0];
        dojo.forEach(selectedModule.components, function(item){
            var total = item.local_price * item.amount;
            dojo.place("<tr class=\"itemLine\"><td><img class=\"orderComponentRemoveBtn\" data-rss-orderComponentId=\"" + item.component_id + "\" src=\"./modules/ordermgmt/images/delete.png\" alt=\"Remove Component\" title=\"Remove Component\" /></td><td>" + item.amount +"x </td><td>" + item.catalog_number + "</td><td>" + item.description +"</td>", reference, "after");
        });

        dojo.behaviour.apply();
    }

    function addComponent(component, amount) {
        component.amount = amount;
        newComponents.push(component);
        selectedModule.components.push(component)

        // Clear component table
        dojo.forEach(dojo.query("tr", "orderComponentEditList"), function(item, index){
            if(index != 0) dojo.destroy(item);
        });

        // Render component table
        var reference = dojo.query("tr.tableHeader", "orderComponentEditList")[0];
        dojo.forEach(selectedModule.components, function(item){
            var total = item.local_price * item.amount;
            dojo.place("<tr class=\"itemLine\"><td><img class=\"orderComponentRemoveBtn\" data-rss-orderComponentId=\"" + item.component_id + "\" src=\"./modules/ordermgmt/images/delete.png\" alt=\"Remove Component\" title=\"Remove Component\" /></td><td>" + item.amount +"x </td><td>" + item.catalog_number + "</td><td>" + item.description +"</td>", reference, "after");
        });

        dojo.behaviour.apply();
    }

    function saveComponents(i) {

        dojo.setStyle(dojo.query("body")[0], "cursor", "wait");

        // Delete removed components
        dojo.forEach(removedComponents, function(item) {
            var xhrParam = {
                url: "?m=ordermgmt&a=cedit&suppressHeaders=true&op=delComp",
                handleAs: "json",
                sync: true,
                content: {
                    moduleId: selectedModule.id,
                    componentId: item
                },
                error: function(crap) {
                    alert(crap.message);
                    dojo.setStyle(dojo.query("body")[0], "cursor", "auto");
                }
            }
            dojo.xhrPost(xhrParam);
        });

        // Send added components
        dojo.forEach(newComponents, function(item) {
            var xhrParam = {
                url: "?m=ordermgmt&a=cedit&suppressHeaders=true&op=addComp",
                handleAs: "json",
                sync: true,
                content: {
                    moduleId: selectedModule.id,
                    componentId: item.id,
                    amount: item.amount
                },
                error: function(crap) {
                    alert(crap.message);
                    dojo.setStyle(dojo.query("body")[0], "cursor", "auto");
                }
            }
            dojo.xhrPost(xhrParam);
        });

        // Cleanup and update UI
        dijit.byId("orderModuleComponentEdit").hide();
        dojo.setStyle(dojo.query("body")[0], "cursor", "auto");
        loadModuleData();
    }

        function loadModuleData() {
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
                    renderComponentTable(data.components, headerNode);
                    dojo.html.set(dojo.query(".orderModuleCompPrice", componentTable)[0], dojo.currency.format(data.modulePrice));

                    // Build component lists for all children
                    var lastTable = componentTable;
                    dojo.forEach(data.childModules, function(module){
                        subTotal = 0;
                        componentTable = dojo.clone(refNode);
                        headerNode = dojo.query(".tableHeader", componentTable)[0];
                        renderComponentTable(module.components, headerNode);
                        dojo.html.set(dojo.query(".orderModuleCompPrice", componentTable)[0], dojo.currency.format(module.modulePrice));
                        dojo.place(componentTable, lastTable, "after");
                        dojo.place("<h2>From " + module.name + ":</h2>", lastTable, "after");
                        lastTable = componentTable;
                    });

                    // Update module total prices
                    dojo.forEach(dojo.query(".orderModuleDetailPrice"), function(node) {
                        dojo.html.set(node, dojo.currency.format(data.totalPrice));
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
            document.location.hash = "#id=" + moduleId;

            dojo.forEach(dojo.query("#orderModuleList ul li"), function(item){
                dojo.removeClass(item, "selected");
            });

            dojo.forEach(dojo.query("#orderModuleList ul li"), function(item){
                var lineId = dojo.attr(item, "data-rss-module_id");
                if(lineId == moduleId) dojo.addClass(item, "selected");
            });
        }

    behavior.add({
        "#orderModuleList ul li":{
            onclick: function(e) {
                e.preventDefault(); // Stop default event handling

                // Fetch information about this object
                moduleId = dojo.attr(e.target, "data-rss-module_id");
                loadModuleData();
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
                        loadModuleData();
                    }
                }
                dojo.xhrPost(xhrParam);
            }
        },
        "#orderModuleFileAddBtn": {
            onclick: function(e) {

                dojo.attr("orderModuleId", "value", selectedModule.id);
                dijit.byId("orderFileDialog").show();
            }
        },
        "#orderComponentEditBtn": {
            onclick: function(e) {

                initComponentEditing();
                dijit.byId("orderModuleComponentEdit").show();
            }
        },
        "#orderComponentAddBtn": {
            onclick: function(e) {

                // Fetch item from datastore
                var selection = dijit.byId("orderComponentSelect").get("item");
                addComponent(selection, dojo.attr("orderComponentAmount", "value"));
            }
        },
        "#orderComponentDoneBtn": {
            onclick: function(e) {
                saveComponents();
            }
        },
        "#orderModuleDeleteBtn": {
            onclick: function(e) {
                if(confirm("Are you sure you want to delete this module?")) {
                    var xhrParam = {
                        url: "?m=ordermgmt&a=cedit&suppressHeaders=true&op=delModule",
                        handleAs: "json",
                        sync: true,
                        content: {
                            moduleId: selectedModule.id
                        },
                        error: function(crap) {
                            alert(crap.message);
                            dojo.setStyle(dojo.query("body")[0], "cursor", "auto");
                        }
                    }
                    dojo.xhrPost(xhrParam).then(function(data){
                        window.location.href = window.location.href;
                    });
                }
            }
        }
    });
    behavior.apply();

    // GUI should be ready populate based on fragment
    ready(function(){

        // Check the url fragment and use that to initialize the view
        var objects = ioquery.queryToObject(document.location.hash.substr(1));
        if(objects.id != undefined) {
            moduleId = objects.id;
            loadModuleData();
        } else {
            moduleId = 1;
            loadModuleData();
        }
    })
});