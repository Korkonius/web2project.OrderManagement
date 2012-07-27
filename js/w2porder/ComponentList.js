define (["dojo/_base/declare", "dijit/_WidgetBase", "dijit/_TemplatedMixin",
    "dojo/_base/lang","dojo/text!./templates/componentList.html"],
    function (declare, _WidgetBase, _TemplatedMixin, lang, template) {
    return declare([ _WidgetBase, _TemplatedMixin], {
        editable: false,
        components: [],
        removedCache: [],
        addedCache: [],
        removeFunc: function(e){
            var id = dojo.attr(e.target, "data-rss-orderComponentId");
            this.removeComponent(id);
        },
        templateString: template,
        addComponent: function(component, amount) {
            component.amount = amount;
            this.addedCache.push(component);
            this.components.push(component);

            this.refresh();
        },
        removeComponent: function(id) {

            // Locate component and remove it from the cache
            this.removedCache.push(id);
            dojo.forEach(this.components, lang.hitch(this, function(item, index){
                if(item != undefined) {
                    if(item.component_id == id) {
                        this.components.splice(index, 1);
                    }
                }
            }));

            this.refresh();
        },
        saveChanges: function(moduleId) {

            dojo.setStyle(dojo.query("body")[0], "cursor", "wait");

            // Delete the removed components
            dojo.forEach(this.removedCache, function(item){
               dojo.xhrPost({
                   url: "?m=ordermgmt&a=cedit&suppressHeaders=true&op=delComp",
                   handleAs: "json",
                   sync: true,
                   content: {
                       moduleId: moduleId,
                       componentId: item
                   },
                   error: function(crap){
                       alert(crap.message);
                       dojo.setStyle(dojo.query("body")[0], "cursor", "auto");
                   }
               });
            });

            // Send added components
            dojo.forEach(this.addedCache, function(item){
                dojo.xhrPost({
                    url: "?m=ordermgmt&a=cedit&suppressHeaders=true&op=addComp",
                    handleAs: "json",
                    sync: true,
                    content: {
                        moduleId: moduleId,
                        componentId: item.id,
                        amount: item.amount
                    },
                    error: function(crap) {
                        alert(crap.message);
                        dojo.setStyle(dojo.query("body")[0], "cursor", "auto");
                    }
                })
            });

            this._clearCache();
            this.refresh();
            dojo.setStyle(dojo.query("body")[0], "cursor", "auto");
        },
        postCreate: function() {

            // Set up inheritance chain
            this.inherited(arguments);

            // Fetch default values if they are present in the props
            var props = dojo.attr(this.srcNodeRef, "data-dojo-props");
            if(props.editable) this.editable = props.editable;
        },
        refresh: function () {
            var domRoot = this.domNode;
            var rows = dojo.query("tr", domRoot);

            // Remove non-header information
            dojo.forEach(rows, function(row, index){
                if(index == 0) {
                    // Nothing, leave header and footer intact
                } else {
                    dojo.destroy(row);
                }
            });

            // Build component list for this
            this._renderTable();
            this._connectListener();
        },
        _renderTable: function() {

            // Render all the component rows in the table
            var headerNode = dojo.query(".tableHeader", this.domNode)[0];
            var components = this.components;
            if(this.editable) {
                dojo.forEach(components, function(item){
                    var total = item.local_price * item.amount;
                    dojo.place("<tr class=\"itemLine\"><td><img class=\"orderComponentRemoveBtn\" data-rss-orderComponentId=\"" + item.component_id + "\" src=\"./modules/ordermgmt/images/delete.png\" alt=\"Remove Component\" title=\"Remove Component\" /></td><td>" +
                        item.amount +"x </td><td>" + item.catalog_number + "</td><td>" + item.description +"</td>", headerNode, "after");
                });
            }
            else {
                dojo.forEach(components, function(item){
                var total = item.local_price * item.amount;
                dojo.place("<tr class=\"itemLine\"><td></td><td>" + item.amount +"x </td><td>" + item.catalog_number + "</td><td>" + item.description +"</td><td style=\"text-align: right\">" + dojo.currency.format(total) + "</td></tr>", headerNode, "after");
            });
            }
        },
        _setComponentsAttr: function(value) {
            this.components = value;
            this._clearCache();
            this.refresh();
        },
        _getComponentsAttr: function() {
            return this.components;
        },
        _connectListener: function() {
                var nodes = dojo.query(".orderComponentRemoveBtn", this.domNode);
                dojo.query(".orderComponentRemoveBtn", this.domNode).on("click", lang.hitch(this, "removeFunc"));
        },
        _clearCache: function() {
            this.addedCache = [];
            this.removedCache = [];
        }
    });
});