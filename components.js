$(document).ready(function(){
    $.inlineEdit({
        componentName: '?m=ordermgmt&suppressHeaders=true&a=cedit&field=name&cid=',
        componentNumber: '?m=ordermgmt&suppressHeaders=true&a=cedit&field=catalog_nr&cid=',
        componentSupplier: '?m=ordermgmt&suppressHeaders=true&a=cedit&field=supplier&cid=',
        componentVPrice: '?m=ordermgmt&suppressHeaders=true&a=cedit&field=vprice&cid=',
        componentDiscount: '?m=ordermgmt&suppressHeaders=true&a=cedit&field=discount&cid='
    }, {
        animate: true
    });
});