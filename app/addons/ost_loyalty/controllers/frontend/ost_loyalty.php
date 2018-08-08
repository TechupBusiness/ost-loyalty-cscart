<?php

use Techup\Addons\OstLoyalty\Main;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

$view = \Tygh\Registry::get('view');
$auth = & Tygh::$app['session']['auth'];

if(!$auth['user_id']) {
	return array(CONTROLLER_STATUS_DENIED);
}

if($mode == "show") {
	fn_add_breadcrumb(__('my_wallet'));

	$old_balance = Main::getInstance()->getUserBalance($auth['user_id']);
	$balance = Main::getInstance()->updateUserDataLive($auth['user_id']);

	$diff = $balance-$old_balance;

	if($balance!==null && ($diff > 0.01 || $diff < -0.01)) { // rounding issues - ugly workaround
		return array(CONTROLLER_STATUS_REDIRECT, 'ost_loyalty.show');
	}

	$entries = Main::getInstance()->getUserTransactions($auth['user_id']);
	$view->assign('transactions',$entries);

	$balances = Main::getInstance()->getBalances($auth['user_id']);
	$view->assign('balances',$balances);

	$user = Main::getInstance()->getOstUser($auth['user_id']);
	$view->assign('ostuser',$user);

}