define (["dojo/_base/declare", "dijit/_WidgetBase", "dijit/_TemplatedMixin", "dojo/text!./templates/componentList.html"],
    function (declare, _WidgetBase, _TemplatedMixin, template) {
    return declare([ _WidgetBase, _TemplatedMixin], {
        components: [],
        templateString: template,
        postCreate: function() {

            // Set up inheritance chain
            this.inherited(arguments);
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
        },
        _renderTable: function() {

            // Render all the component rows in the table
            var headerNode = dojo.query(".tableHeader", this.domNode)[0];
            var components = this.components;
            dojo.forEach(components, function(item){
                var total = item.local_price * item.amount;
                dojo.place("<tr class=\"itemLine\"><td>" + item.amount +"x </td><td>" + item.catalog_number + "</td><td>" + item.description +"</td><td style=\"text-align: right\">" + dojo.currency.format(total) + "</td></tr>", headerNode, "after");
            });
        },
        _setComponentsAttr: function(value) {
            this.components = value;
            this.refresh();
        },
        _getComponentsAttr: function() {
            return this.components;
        }
    });
});