<?php
/**
 * Created by PhpStorm.
 * User: flach
 * Date: 30.04.2018
 * Time: 13:21
 */

// Cron should run every minute for each action!

use Tygh\Registry;
use Techup\Addons\OstLoyalty\Repositories\ostQueueRepository;
use Techup\Addons\OstLoyalty\Enums\ostQueueStatusEnum;
use Techup\Addons\OstLoyalty\Main;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

$cron_password = Registry::get('settings.Security.cron_password');

if ((!isset($_REQUEST['pw']) || $cron_password != $_REQUEST['pw']) && (!empty($cron_password))) {
	return array(CONTROLLER_STATUS_DENIED);
}

switch($mode) {
	case "run_ost_queue":
		$queueRepo = ostQueueRepository::getInstance();
		$waitingTx = $queueRepo->load(ostQueueStatusEnum::Waiting());
		// TODO
		break;
	case "update_pending_tx": // Cron every 5min
		Main::getInstance()->updateProcessingTransactionStatus();
		die("OK");
		break;
	case "update_pending_tx_loop":
		while(true) {
			Main::getInstance()->updateProcessingTransactionStatus();
			sleep(mt_rand(60,120));
		}
		break;
	case "update_currency_info": // Cron every 10min
		Main::getInstance()->updateExchangeRateOstInDb();
		die("OK");
		break;
	case "update_currency_info_loop":
		while(true) {
			Main::getInstance()->updateExchangeRateOstInDb();
			sleep(60 * mt_rand(4,7));
		}
		break;
	case "update_user_balances": // Cron every 1min
		Main::getInstance()->updateAllUserDataLive();
		die("OK");
		break;
	case "update_user_balances_loop":
		while(true) {
			Main::getInstance()->updateAllUserDataLive();
			sleep(20, 100);
		}
		break;
	case "generate_transactions":
		if(Registry::get('addons.ost_loyalty.mode')!='test') {
			die("Addon not in test mode - dont run this action simulation!");
		}
		$new_user_count = mt_rand(30,60);
		$transactions_count = mt_rand(200,800);

		$userRepo = Main::getInstance()->getUserRepository();

		// Get users from API
		$allUsers = Main::getInstance()->getAllLiveUser();

		$max_user_i = count($allUsers);
		$tx_count = 0;
		for($i=0;$i<$transactions_count; $i++) {
			if($i % mt_rand(5,15) == 0) {
				try {
					$newUser = $userRepo->create( md5($i + $max_user_i) );
					$tx = Main::getInstance()->userRegistration( $newUser->id );
					$tx_count++;
				} catch(Exception $ex) {
					echo "<br />".date("H:i:s")." (".$i."+): userRegistration error: ".$ex->getMessage();
				}
				if($tx->id) {
					echo "<br />" . date( "H:i:s" ) . " (" . $i . "+): userRegistration executed";
				} else {
					echo "<br />" . date( "H:i:s" ) . " (" . $i . "+): userRegistration error";
				}
				usleep(floatval(mt_rand( 0, 3 ).".".mt_rand(1,9)) * 1000000);
			}

			$random_user_i = mt_rand(0, $max_user_i);
			$user = $allUsers[$random_user_i];

			$do_createOrderPay = boolval(mt_rand(0,1));

			if($user->token_balance>10 && $do_createOrderPay) {
				$tx = Main::getInstance()->createOrderPay( mt_rand( 1, intval( floor( $user->token_balance ) ) ), $user->id );
				$tx_count++;
				if($tx->id) {
					echo "<br />" . date( "H:i:s" ) . " (" . $i . "): createOrderPay executed";
				} else {
					echo "<br />" . date( "H:i:s" ) . " (" . $i . "): createOrderPay error";
				}
				usleep( floatval( mt_rand( 0, 3 )."." . mt_rand( 1, 9 ) ) * 1000000 );
			} else {
				$tx = Main::getInstance()->createOrderReward(mt_rand(1,40).".".mt_rand(0,99), $user->id);
				$tx_count++;
				if($tx->id) {
					echo "<br />" . date( "H:i:s" ) . " (" . $i . "): createOrderReward executed";
				} else {
					echo "<br />" . date( "H:i:s" ) . " (" . $i . "): createOrderReward error";
				}
				usleep(floatval(mt_rand( 0, 3 ).".".mt_rand(1,9)) * 1000000);
			}
		}

		echo "<br><b>TOTAL TX: ".$tx_count."</b>";
		exit;

		break;
	default:
		return array(CONTROLLER_STATUS_DENIED);
}