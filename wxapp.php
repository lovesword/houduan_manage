<?php

define("APP_PATH", __DIR__ . "/application/");
define("BIND_MODULE", "api");
include "thinkphp/base.php";
$config = \think\App::initCommon();
global $_W, $_GPC;
$do = strtolower($_GPC["do"]);
$opts = explode("|", $do);
if (count($opts) > 1) {
	$control = $opts[0];
	$do = $opts[1];
} else {
	$control = "index";
	$do = "index";
}
\think\App::module("api/" . $control . "/" . $do, $config);
class sqtg_sunModuleWxapp extends WeModuleWxapp
{
}