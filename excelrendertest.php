<?php
require_once('classes/ordermoduleexcelrender.class.php');
require_once('classes/ordermodule.class.php');
$module = COrderModule::createFromDb(1);
$test = new COrderModuleExcelRender($module);
$test->stream();