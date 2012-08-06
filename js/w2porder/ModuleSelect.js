define (["dojo/_base/declare", "dijit/_WidgetBase", "dijit/_TemplatedMixin",
    "dojo/_base/lang","dojo/text!./templates/moduleSelect.html", "dijit/_WidgetsInTemplateMixin",
    "dijit/form/NumberSpinner", "dijit/form/Button", "dijit/form/FilteringSelect", "w2porder/COrderModuleStore"],
    function (declare, _WidgetBase, _TemplatedMixin, lang, template, _WidgetsInTemplateMixin, NumberSpinner, Button, FilteringSelect, ModuleStore) {
        return declare([ _WidgetBase, _TemplatedMixin, _WidgetsInTemplateMixin], {
            editable: false,
            templateString: template,
            widgetsInTemplate: true,
            amounts: new Array(),
            modules: new Array(),
            moduleList: undefined,

            // Attach points
            amountInput: undefined,
            moduleInput: undefined,
            submitBtn: undefined,
            constructor: function(args) {
                //this.inherited(args);
                this.moduleList = new ModuleStore();
            },
            saveChanges: function(moduleId) {
            },
            postCreate: function() {

                // Create the required widgets contained within this object
                this.amountInput = new NumberSpinner({
                    value: 1,
                    delta: 1
                }, dojo.query("input.moduleAmount", this.domNode)[0]);

                this.moduleInput = new FilteringSelect({
                    store: new ModuleStore(),
                    searchAttr: "display"
                }, dojo.query("input.moduleSelect", this.domNode)[0]);

                this.submitBtn = new Button({

                }, dojo.query("div.moduleAdd", this.domNode)[0]);
            },
            _updateModuleList: function() {

            }
        });
    });