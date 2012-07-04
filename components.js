$(document).ready(function(){
    $.inlineEdit({
        componentName: '?m=ordermgmt&a=cedit&field=name&cid=',
        componentNumber: '?m=ordermgmt&a=cedit&field=catalog_nr&cid=',
        componentSupplier: '?m=ordermgmt&a=cedit&field=supplier&cid=',
        componentPrice: '?m=ordermgmt&a=cedit&field=name&cid=',
    }, {
        animate: true
    });
});