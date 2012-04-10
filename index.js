
/**
 * Primary view JavaScript component
 *
 * This file contains the code for the index/list component of the Order Management module written for Web2Project.
 * Module is developed and maintained by Eirik Eggesbø Ottesen.
 *
 * @package Ordermgmt
 * @version 1.0
 * @author Eirik Eggesbø Ottesen <Korkonius@gmail.com>
 */
$(document).ready(function(){
    $('.paginate').each(function(index, value) {
        $(value).smartpaginator({
            totalrecords: 100,
            recordsperpage: 10,
            initval: 0
        });
    });

    // Make all order lines clickable and lead to specific project views
    $('tr.order_clickable').on({
        click: function() {
            event.preventDefault();
            var strId = this.id.substr(8);

            window.location = "?m=ordermgmt&order_id=" + strId;
        }
    });

    // Text ajax call to see if we can get the expected object to work
    $.ajax({
        type: 'POST',
        data: {
            'dosql': 'do_ajaxrequest'
        },
        url: '?m=ordermgmt&suppressHeaders=true',
        success: function(data) {
            alert(data);
        },
        error: function(data) {
            alert('Call failed!');
        }
    });
});
