$(document).ready(function(){
    $.inlineEdit({
        componentName: '?m=ordermgmt&a=cedit&field=name&cid=',
        componentNumber: '?m=ordermgmt&a=cedit&field=catalog_nr&cid=',
        componentSupplier: '?m=ordermgmt&a=cedit&field=supplier&cid=',
        componentVPrice: '?m=ordermgmt&a=cedit&field=vprice&cid=',
        componentDiscount: '?m=ordermgmt&a=cedit&field=discount&cid='
    }, {
        animate: true
    });
});