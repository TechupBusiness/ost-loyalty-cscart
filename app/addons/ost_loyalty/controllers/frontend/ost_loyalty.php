<?php

if (!defined('BOOTSTRAP')) { die('Access denied'); }

$view = \Tygh\Registry::get('view');
$auth = & Tygh::$app['session']['auth'];

if(!$auth['user_id']) {
	return array(CONTROLLER_STATUS_DENIED);
}

if($mode == "show") {
	fn_add_breadcrumb(__('my_wallet'));

	$entries = \Techup\Addons\OstLoyalty\Main::getInstance()->getTxLog($auth['user_id']);
	$view->assign('transactions',$entries);
}
