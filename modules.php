<script type="text/javascript" src="./modules/ordermgmt/js/dojo/dojo.js" data-dojo-config="parseOnLoad: true, isDebug: true"></script>
<script type="text/javascript">
    dojo.require("dijit.layout.ContentPane");
    dojo.require("dijit.layout.BorderContainer");
</script>
    <style type="text/css">
        #orderModuleDetailContainer {
            height: 800px;
            width: 1400px;
        }
        #orderModuleDetailContainer h1 {
            background-image: -webkit-gradient(linear, 0% 0%, 0% 100%, from(rgb(237,242,247)), to(rgb(208, 223, 234)));
            border-bottom: white 1px solid;
            border-left: white 1px solid;
            border-top: white 1px solid;
            border-right: #333 0px solid;
            color: #333;
            font-weight: bold;
            margin: -8px;
            padding: 8px;
            display: block;
        }
        #orderModuleSummaryBar {
            background-image: -webkit-gradient(linear, 0% 0%, 0% 100%, from(rgb(237,242,247)), to(rgb(208, 223, 234)));
            border-bottom: white 1px solid;
            border-left: white 1px solid;
            border-top: white 1px solid;
            border-right: #333 0px solid;
            color: #333;
            font-weight: bold;
            text-align: right;
        }
        #orderModuleDetailContainer hr {
            height: 1px;
            border: 0;
            background: black;
            background: -webkit-gradient(linear, 0 0, 100% 0, from(rgba(217, 232, 249,0)), to(rgba(217, 232, 249,0)), color-stop(33%, rgba(167, 172, 199, 255)), color-stop(66%, rgba(167, 172, 199, 255)));
        }
        #orderModuleDetailContainer ul {
            padding: 0;
            margin: 0;
            font-size: small;
            display: block;
        }
        #orderModuleDetailContainer ul li {
            display: block;
            margin: 0px -8px;
            padding: 4px 0px;
            -webkit-border-image: -webkit-gradient(linear, 0 0, 100% 0, from(rgba(217, 232, 249,0)), to(rgba(217, 232, 249,0)), color-stop(33%, rgba(167, 172, 199, 0.3)), color-stop(66%, rgba(167, 172, 199, 0.3))) 0 0 100% 0 stretch stretch;
            border: white 1px solid;
        }
        #orderModuleDetailContainer ul li.selected {
            background-image: -webkit-gradient(linear, 0% 0%, 0% 100%, from(rgb(247,252,255)), to(rgb(233, 243, 255)));
        }
        ul#orderModuleFileUl li {
            padding: 4px 12px;
        }
        .orderModuleFileDetails {
            color: #999;
        }
        .orderModuleComponentTable {
            padding: 0;
            margin: 0;
            border-collapse: collapse;
            border-spacing: 0;
        }
        .orderModuleComponentTable tr td {
            padding: 3px;
        }
        .orderModuleComponentTable tr td {
            border-bottom: 1px solid #EEEEFF;
        }
        .orderModuleCompHead {
            border-bottom: 2px solid silver;
        }
        .orderModuleComponentTable tr td.orderModuleCompFoot {
            border-top: 2px solid silver;
            border-bottom: 3px double silver;
        }
        .orderModuleCompDescr {
            border-right: 2px solid silver;
        }
    </style>
<div id="orderModuleDetailContainer" data-dojo-type="dijit.layout.BorderContainer" data-dojo-props="design:'sidebar',gutters:true">
    <div id="orderModuleList" data-dojo-type="dijit.layout.ContentPane" data-dojo-props="region:'leading'" style="width: 250px">
        <h1>
            Module List
            <img src="./modules/ordermgmt/images/add.png" alt="Add module" title="Add module" />
        </h1>
        <hr />
        <ul>
            <li>Kjerneholder MK3</li>
            <li class="selected">Kjerneholder MK4</li>
            <li>PCRI</li>
        </ul>
    </div>
    <div id="orderModuleSummary" data-dojo-type="dijit.layout.ContentPane" data-dojo-props="region:'top'" style="height: 250px">
        <h1>
            Details
            <img src="./modules/ordermgmt/images/cog_edit.png" alt="Edit module" title="Edit module"/>
        </h1>
        <hr />
        <h2>Kjerneholder MK4</h2>
        <div id="orderModuleOverview">
            Lorem ipsum dolor sit amet, consectetur adipiscing elit. Pellentesque leo nibh, tristique et interdum quis, lacinia et dolor.
            Nulla eleifend sapien ipsum, eget commodo magna. Curabitur nunc nisl, pretium at porta eu, sollicitudin ut orci. Phasellus blandit, lorem vitae facilisis rutrum,
            diam turpis mattis sem, vitae venenatis nulla risus eget leo. Etiam porta sem massa. Nulla accumsan cursus felis.
            <hr />
            <strong>Build Price: </strong> 34,000.00 NOK <strong>Delivered: </strong> 35 <strong>Est. build time: </strong> 12 hours
            <hr />
        </div>
        <div id="orderSuperList">
            <h2>Consists of modules</h2>
            Kjerneholder MK3
        </div>
    </div>
    <div id="orderModuleFileList" data-dojo-type="dijit.layout.ContentPane" data-dojo-props="region:'top'" style="height: 150px">
        <h1>
            Files
            <img src="./modules/ordermgmt/images/add.png" alt="Add file" title="Add file" />
        </h1>
        <hr />
        <ul id="orderModuleFileUl">
            <li><a href="">Kjerneholder MK4 tegninger (.pdf) <span class="orderModuleFileDetails">Size: 512 Kb Changed: 10/07/12</span></a></li>
            <li><a href="">Trykktestmal (.docx) <span class="orderModuleFileDetails">Size: 512 Kb Changed: 10/07/12</span></a></li>
        </ul>
    </div>
    <div id="orderModuleComponentList" data-dojo-type="dijit.layout.ContentPane" data-dojo-props="region:'center'">
        <h1>
            Components
            <img src="./modules/ordermgmt/images/add.png" alt="Add components" title="Add components" />
        </h1>
        <hr />
        <h2>This module:</h2>
        <table class="orderModuleComponentTable">
            <tr>
                <th width="5%" class="orderModuleCompHead">Amount</th><th width="80%" class="orderModuleCompHead">Description</th><th width="15%" class="orderModuleCompHead">Price</th>
            </tr>
            <tr>
                <td>15x</td><td class="orderModuleCompDescr">Lorem Ipsum dolor sit</td><td style="text-align: right">15,000.00 NOK</td>
            </tr>
            <tr>
                <td>4x</td><td class="orderModuleCompDescr">Consectetur adipiscing elit</td><td style="text-align: right">100.00 NOK</td>
            </tr>
            <tr>
                <td colspan="3" style="text-align: right" class="orderModuleCompFoot">Subtotal: 15,100.00 NOK</td>
            </tr>
        </table>
        <h2>From: Kjerneholder MK3</h2>
        <table class="orderModuleComponentTable">
            <tr>
                <th width="5%" class="orderModuleCompHead">Amount</th><th width="80%" class="orderModuleCompHead">Description</th><th width="15%" class="orderModuleCompHead">Price</th>
            </tr>
            <tr>
                <td>15x</td><td class="orderModuleCompDescr">Lorem Ipsum dolor sit</td><td style="text-align: right">15,000.00 NOK</td>
            </tr>
            <tr>
                <td>4x</td><td class="orderModuleCompDescr">Consectetur adipiscing elit</td><td style="text-align: right">100.00 NOK</td>
            </tr>
            <tr>
                <td colspan="3" style="text-align: right" class="orderModuleCompFoot">Subtotal: 15,100.00 NOK</td>
            </tr>
        </table>
    </div>
    <div id="orderModuleSummaryBar" data-dojo-type="dijit.layout.ContentPane" data-dojo-props="region:'bottom'">
        <div id="orderModuleTotalPrice"><strong>Total costs: 30,200.00 NOK</strong></div>
    </div>
</div>