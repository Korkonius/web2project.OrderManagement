define (["dojo/_base/declare", "dijit/_WidgetBase", "dijit/_TemplatedMixin", "dojo/text!./templates/componentList.html"],
    function (declare, _WidgetBase, _TemplatedMixin, template) {
    return declare([ _WidgetBase, _TemplatedMixin], {
        components: [],
        templateString: template,
        postCreate: function () {
            var domRoot = this.domNode;
            var rows = dojo.query("tr", domRoot);
        }
    });
});