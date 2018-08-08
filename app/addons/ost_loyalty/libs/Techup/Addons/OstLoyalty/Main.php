<?php

namespace Techup\Addons\OstLoyalty;

use Techup\Addons\OstLoyalty\Enums\ostQueueStatusEnum;
use Techup\Addons\OstLoyalty\Repositories\ActionDefinitionRepository;
use Techup\Addons\OstLoyalty\Repositories\ostQueueRepository;
use Techup\Helper\SettingsHelper;
use Techup\Helper\Singleton;
use Techup\SimpleTokenApi\Enums\KindEnum;
use Techup\SimpleTokenApi\Enums\StatusEnum;
use Techup\SimpleTokenApi\Models\ActionModel;
use Techup\SimpleTokenApi\Models\PricePointsModel;
use Techup\SimpleTokenApi\Models\TokenModel;
use Techup\SimpleTokenApi\Models\UserModel;
use Techup\SimpleTokenApi\Models\TransactionModel;
use Techup\SimpleTokenApi\Repositories\ActionRepository;
use Techup\SimpleTokenApi\Repositories\BalancesRepository;
use Techup\SimpleTokenApi\Repositories\LedgerRepository;
use Techup\SimpleTokenApi\Repositories\TokenRepository;
use Techup\SimpleTokenApi\Repositories\UserRepository;
use Techup\SimpleTokenApi\Repositories\TransactionRepository;
use Tygh\Settings;
use Tygh\Registry;

class Main extends Singleton {

	private $settingMode;
	private $settingApiKey;
	private $settingApiSecret;
	private $settingCompanyUuid;
	private $settingUserPrivacy;

	private $addonId = 'ost_loyalty';
	/** @var SettingsHelper */
	public $settingsHelper;

	public function __construct() {
		$this->getSettings();
		$this->settingsHelper = SettingsHelper::getInstance();
	}

	/**
	 * Reads settings from CS-Cart Addon settings
	 */
	private function getSettings() {
 		$settings = Settings::instance();
		$this->settingMode = $settings->getValue('mode', $this->addonId);
		$this->settingApiKey = $settings->getValue('api_key', $this->addonId);
		$this->settingApiSecret = $settings->getValue('api_secret', $this->addonId);
		$this->settingCompanyUuid = $settings->getValue('company_uuid', $this->addonId);
		$this->settingUserPrivacy = $settings->getValue('user_privacy', $this->addonId);
	}

	/**
	 * @param $name
	 *
	 * @return bool|mixed
	 */
	public function getSetting($name) {
		return $this->settingsHelper->getOptionValue($this->addonId, $name);
	}

	/**
	 * @param null $api_key
	 * @param null $api_secret
	 *
	 * @return ActionRepository
	 */
	public function getActionRepository($api_key = null, $api_secret = null) {
		if($api_key==null ) $api_key = $this->settingApiKey;
		if($api_secret==null ) $api_secret = $this->settingApiSecret;
		return new ActionRepository( $this->settingMode, $api_key, $api_secret, $this->settingCompanyUuid );
	}

	/**
	 * @return TransactionRepository
	 */
	public function getTransactionRepository() {
		return new TransactionRepository( $this->settingMode, $this->settingApiKey, $this->settingApiSecret, $this->settingCompanyUuid );
	}

	/**
	 * @return UserRepository
	 */
	public function getUserRepository() {
		return new UserRepository($this->settingMode, $this->settingApiKey, $this->settingApiSecret, $this->settingCompanyUuid);
	}

	/**
	 * @return BalancesRepository
	 */
	public function getBalancesRepository() {
		return new BalancesRepository($this->settingMode, $this->settingApiKey, $this->settingApiSecret, $this->settingCompanyUuid);
	}

	/**
	 * @return LedgerRepository
	 */
	public function getLedgerRepository() {
		return new LedgerRepository($this->settingMode, $this->settingApiKey, $this->settingApiSecret, $this->settingCompanyUuid);
	}

	/**
	 * @param null $api_key
	 * @param null $api_secret
	 *
	 * @return TokenRepository
	 */
	private function getTokenRepository($api_key = null, $api_secret = null) {
		if($api_key==null ) $api_key = $this->settingApiKey;
		if($api_secret==null ) $api_secret = $this->settingApiSecret;
		return new TokenRepository($this->settingMode, $api_key, $api_secret , $this->settingCompanyUuid);
	}
	/**
	 * Creates user in OST and saves his UUID in database and execute transactions
	 *
	 * @param int   $user_id            cs-cart user id
	 * @param bool  $execute_userreg    if true, user registration action will be triggered
	 *
	 * @return UserModel
	 */
	public function createOstUser($user_id, $execute_userreg = true) {
		try {
			// Create user
			$userRepo = $this->getUserRepository();
			$user = $userRepo->create($this->settingUserPrivacy == 'public' ? $user_id : sha1($user_id));

			// Save to db
			db_query('UPDATE ?:users SET ost_uuid=?s WHERE user_id=?i', $user->id, $user_id);

			if($execute_userreg) {
				// Execute transaction UserRegistration
				$this->userRegistration( $user_id );
			}

		} catch(\Exception $ex) {
			$this->displayErrorMessage($ex->getMessage());
			return null;
		}
	}

	public function getLiveUserBalance($user_id, $save = true) {
		$user_uuid = $this->getOstUserUuid($user_id);

		try {
			if(empty($user_uuid)) {
				throw new \Exception("No user_uuid");
			}
			$balancesRepo = $this->getBalancesRepository();
			$balancesModel = $balancesRepo->get( $user_uuid );
			if($save) {
				$this->updateUserBalance($user_uuid, $balancesModel->available_balance);
			}
			return $balancesModel->available_balance;
		} catch(\Exception $ex) {
			$this->displayErrorMessage($ex->getMessage());
			return null;
		}
	}

	public function getOstUserUuid($user_id) {
		$user_uuid = db_get_field('SELECT ost_uuid FROM ?:users WHERE user_id=?i', $user_id);
		try {
			if(empty($user_uuid)) {
				throw new \Exception("No user_uuid");
			}
			return $user_uuid;
		} catch(\Exception $ex) {
			$user = $this->createOstUser($user_id , false );
			return $user->id;
		}
	}

	/**
	 * @param $user_id
	 *
	 * @return null|UserModel
	 */
	public function getOstUser($user_id) {
		try {
			$repo    = $this->getUserRepository();
			$uuid    = $this->getOstUserUuid( $user_id );
			$ostUser = $repo->get( $uuid );
			return $ostUser;
		} catch(\Exception $ex) {
			return null;
		}
	}

	/**
	 * Returns transactions of a user
	 *
	 * @param $user_id
	 *
	 * @return TransactionModel[]
	 */
	public function getUserTransactions($user_id) {
		try {

			// Get all transactions for a user ToDo use pagination
			$ostTxs = [ ];
			try {
				$balancesRepo = $this->getLedgerRepository();
				$results = true;
				$page = 1;
				while($results) {
					try {
						$txes = $balancesRepo->list( $this->getOstUserUuid( $user_id ) , $page, 100 );
						if(count($txes)>0) {
							$ostTxs = array_merge( $ostTxs, $txes );
							if(count($txes)==100) {
								$page ++;
							} else {
								throw new \Exception("Just one page, skip another request");
							}
						} else {
							throw new \Exception("Not more results");
						}
					} catch (\Exception $ex) {
						$results = false;
					}
				}
			} catch(\Exception $ex) {
				// ToDo Handle
			}

			$tx_ids = [];
			// Get transactions from OST
			foreach($ostTxs as $tx) {
				$tx_ids[] = $tx->id;
			}

			// Get additional TX data from database
			$dbTxs = db_get_hash_multi_array('SELECT * FROM ?:ost_tx WHERE tx_reference IN (?a)', ['tx_reference'], $tx_ids);

			$transactions = [];
			foreach($ostTxs as $tx) {
				$transactions[] = [
					'ost' => $tx,
					'db' => isset($dbTxs[$tx->id]) ? $dbTxs[$tx->id][0] : []
				];
			}

			return $transactions;

		} catch(\Exception $ex) {
			// ToDo proper error handling
		}
	}

	public function getBalances($user_id) {
		try {
			$balancesRepo = $this->getBalancesRepository();
			$uuid         = $this->getOstUserUuid( $user_id );
			$balances = $balancesRepo->get($uuid);
			return $balances;
		} catch(\Exception $ex) {
			return null;
		}
	}

	/**
	 * Updates all user balances
	 */
	public function updateAllUserDataLive() {
		/** @var BalancesRepository $balancesRepo */
		$balancesRepo  = $this->getBalancesRepository();

		/** @var UserModel[] $user */
		$user = $this->getAllLiveUser();

		foreach ($user as $u) {
			try {
				$user_id = db_get_field('SELECT user_id FROM ?:users WHERE ost_uuid=?i', $u->id );
				if($user_id>0) {
					$balancesModel = $balancesRepo->get( $u->id );
					$this->updateUserBalance( $u->id, $balancesModel->available_balance );
				}
			} catch(\Exception $ex) {
				// ToDo Handling
			}
		}
	}

	public function updateUserDataLive($user_id) {
		/** @var BalancesRepository $balancesRepo */
		$balancesRepo  = $this->getBalancesRepository();
		try {
			$balancesModel = $balancesRepo->get( $this->getOstUserUuid($user_id) );
			$this->updateUserBalance( $this->getOstUserUuid($user_id), $balancesModel->available_balance );
			return $balancesModel->available_balance;
		} catch(\Exception $ex) {
			return null;
		}
	}

	/**
	 * @param $uuid
	 */
	public function updateUserBalanceLive($uuid) {
		try {
			$balancesRepo  = $this->getBalancesRepository();
			$balancesModel = $balancesRepo->get( $uuid );
			$this->updateUserBalance( $uuid, $balancesModel->available_balance );
		}
		catch(\Exception $ex) {
			// ToDo Handling
		}
	}

	/**
	 * @param $user_uuid
	 * @param $balance
	 */
	public function updateUserBalance($user_uuid, $balance) {
		$rows = db_query("UPDATE ?:users SET ost_balance=?d WHERE ost_uuid=?s", $balance, $user_uuid);
		if($rows==0 && $balance>0) {
			fn_log_event(LOGGER_OST_LOYALTY, 'user_mismatch', [ 'uuid' => $user_uuid, 'balance' => $balance ]);
		}
	}

	/**
	 * @return UserModel[]
	 */
	public function getAllLiveUser() {
		$allUser = [ ];

		try {
			$userRepo = $this->getUserRepository();
			$results = true;
			$page = 1;
			while($results) {
				try {
					$users = $userRepo->list( $page, false, 100 );
					if(count($users)>0) {
						$allUser = array_merge( $allUser, $users );
						if(count($users)==100) {
							$page ++;
						} else {
							throw new \Exception("Just one page, skip another request");
						}
					} else {
						throw new \Exception("Not more results");
					}
				} catch (\Exception $ex) {
					$results = false;
				}
			}
		} catch(\Exception $ex) {
			// ToDo Handle
		}
		return $allUser;
	}

	public function updateProcessingTransactionStatus() {
		$pending_tx = db_get_fields("SELECT tx_reference FROM ?:ost_tx WHERE status='P'");

		$repo = $this->getTransactionRepository();
		foreach($pending_tx as $tx) {
			try {
				$tx = $repo->get( $tx );

				if($tx->status != 'processing') {
					db_query("UPDATE ?:ost_tx SET status=?s,value=?d,commission=?d,tx_hash=?s WHERE tx_reference=?s",  strtoupper(substr($tx->status,0,1)), $tx->amount, $tx->commission_amount, $tx->transaction_hash, $tx->id );
				}
			} catch (\Exception $ex) {
				$results = false;
			}
		}

		// Disabled because API does not respond on this request
//		$tx_limit_groups = [ ];
//		$limit_index = 1;
//		foreach($pending_tx as $tx) {
//			if(count($tx_limit_groups[$limit_index]) > 100) {
//				$limit_index++;
//			}
//			$tx_limit_groups[$limit_index][] = $tx;
//		}
//
//		try {
//			$repo = $this->getTransactionRepository();
//			$results = true;
//			$page = 1;
//			while($results) {
//				try {
//					$txs = $repo->list( $page, null, null,100, 'id="'.implode(",", $tx_limit_groups[$page]).'"'  );
//
//					if(count($txs)>0) {
//						foreach($txs as $tx) {
//							if($tx->status != 'processing') {
//								db_query("UPDATE ?:ost_tx SET status=?s,value=?d,commission=?d WHERE tx_reference=?s",  strtoupper(substr($tx->status,0,1)), $tx->amount, $tx->commission_amount, $tx->id);
//							}
//						}
//
//						if(count($txs)==100) {
//							$page ++;
//						} else {
//							throw new \Exception("Just one page, skip another request");
//						}
//					} else {
//						throw new \Exception("Not more results");
//					}
//				} catch (\Exception $ex) {
//					$results = false;
//				}
//			}
//		} catch(\Exception $ex) {
//
//		}
	}

	/**
	 * @param $userId int
	 *
	 * @return string
	 */
	public function getUserBalance($userId) {
		return db_get_field('SELECT ost_balance FROM ?:users WHERE user_id=?i', $userId);
	}

	/**
	 * @param $userId int
	 *
	 * @return string
	 */
	public function getUserBalanceUuid($uuid) {
		return db_get_field('SELECT ost_balance FROM ?:users WHERE ost_uuid=?i', $uuid);
	}

	public function getOrCreateOstAction($actionName, $api_key=null, $api_secret=null) {
		$ostActionRepo = $this->getActionRepository($api_key, $api_secret);
		$actionId = $this->getActionId($actionName);
		$model = ActionDefinitionRepository::getTemplate($actionName);
		try {
			if(empty($actionId)) {
				$installDate = db_get_field("SELECT install_datetime FROM ?:addons WHERE addon=?s",$this->addonId);
				$newActionName = substr($actionName, 0, 16).substr($installDate,-4); // to avoid conflicts when using this tokens on multiple shops or install it multiple times

				$settingsAmount = $this->settingsHelper->getOptionValue($this->addonId,$actionName.'_amount'); // only for userRegistration_amount at the moment
				if(!empty($settingsAmount)) {
					$model->amount = $settingsAmount;
				}
				$actionModel = $ostActionRepo->create( $newActionName, $model->kind, $model->currency, $model->arbitrary_amount, $model->amount);

			} else {
				$actionModel = $ostActionRepo->get($actionId);
			}
		} catch(\Exception $ex ) {
			$this->displayErrorMessage($ex->getMessage());
		}
		return $actionModel;
	}

	private function displayErrorMessage($exceptionMsg) {
		$msg = json_decode( $exceptionMsg );

		if($msg->success == false) {
			$message = $msg->err->code.($msg->err->msg ? ' - '.str_replace("\"err.error_data\"","below",$msg->err->msg) : '').'<br />'; // e.g. BAD_REQUEST

			$err_array = $msg->err->error_data;
			if(count($err_array)>0) $message .= '<ul>';
			foreach($err_array as $err) {
				$message .= '<li>'.$err->parameter.": ".$err->msg.'</li>';
			}
			if(count($err_array)>0) $message .= '</ul>';
		}

		$msgArr = json_decode($exceptionMsg, true);
		$errData = array_merge($msgArr['err'],$msgArr['err']['error_data']);
		unset($errData['error_data']);

		fn_log_event(LOGGER_OST_LOYALTY, 'errors', $errData);

		if(AREA != 'A') {
			$message = __('ost_kit_error_message_customers');
		}
		$title = "ost_kit_error_title_".strtolower(AREA);
		fn_set_notification('E',__($title), $message);
	}

	/**
	 * Show a success message if not in admin backend
	 *
	 * @param $token
	 * @param bool $live
	 */
	public function displaySuccessReceiveMessage($token, $live = true) {
		if(AREA != 'A') {
			if(empty($token)) {
				$txt = __( 'message_tokens_to_receive_noval', [
					'[symbol]' => Registry::get('currencies.OBT.symbol'),
				] );
			} else {
				$value = $token * $this->getExchangeRate();
				if ( $live ) {
					$txt = __( 'message_tokens_received', [
						'[value]' => fn_format_token_value( $value ),
						'[token]' => fn_format_token( $token ),
					] );
				} else {
					$txt = __( 'message_tokens_to_receive', [
						'[value]' => fn_format_token_value( $value ),
						'[token]' => fn_format_token( $token ),
					] );
				}
			}
			fn_set_notification( 'N', __( "message_tokens_header" ), $txt );
		}
	}

	/**
	 * Runs and executes the waiting queue for OST
	 */
	public function executeWaitingQueue() {
		$ostQueueRepo = ostQueueRepository::getInstance();
		$waitingTxs = $ostQueueRepo->load(ostQueueStatusEnum::Waiting());

		foreach($waitingTxs as $tx) {
			$execAction = $this->executeOstAction($tx->actionName, $tx->userId, $tx->value);
			if( $execAction->status == StatusEnum::processing() || $execAction->status == StatusEnum::complete() ) {
				$tx->status = 'F'; // Finished
				$tx->updatedTimestamp = time();
				$ostQueueRepo->save($tx);
			} elseif( $execAction->status == StatusEnum::failed() ) {
				$tx->status = 'E'; // Error
				$tx->updatedTimestamp = time();
				$ostQueueRepo->save($tx);
			}
		}
	}

	protected function getActionId($actionName) {
		return $this->settingsHelper->getOptionValue($this->addonId, $actionName.'Id');
	}

	/**
	 * @param $actionName
	 * @param $user_id
	 * @param null $value
	 *
	 * @return null|TransactionModel
	 */
	public function executeOstAction($actionName, $user_uuid, $value = null) {
		try {
			$actionId = $this->getActionId($actionName);
			$baseModel = ActionDefinitionRepository::getTemplate($actionName);

			if (!empty($value) || !$baseModel->arbitrary_amount) {
				$txRepo = $this->getTransactionRepository();

				if($baseModel->kind == KindEnum::user_to_company()) {
					$to = $this->settingCompanyUuid;
					$from = $user_uuid;
				} elseif($baseModel->kind == KindEnum::company_to_user()) {
					$to = $user_uuid;
					$from = $this->settingCompanyUuid;
				}
				return $txRepo->execute($from, $to, $actionId, $baseModel->arbitrary_amount ? $value : null );
			}

		} catch(\Exception $ex) {
			$this->displayErrorMessage($ex->getMessage());
			return null;
		}
	}

	private function txGenericExecution($actionName, $user_id, $tokens = null) {
		if(is_numeric($user_id)) {
			$uuid = $this->getOstUserUuid( $user_id );
		} else {
			$uuid = $user_id;
		}
		$tx = $this->executeOstAction($actionName, $uuid, $tokens);

		if($tx!=null) {
			$this->updateUserBalanceLive($uuid);
			$this->displaySuccessReceiveMessage($tx->amount, true);
			if($uuid==$user_id) {
				$user_id = db_get_field('SELECT user_id FROM ?:users WHERE ost_uuid=?s', $user_id);
			}
			$this->writeTxLog($user_id, $actionName, $tx);
		}
		return $tx;
	}

	public function createOrderPay($tokens, $user_id) {
		return $this->txGenericExecution('orderPay', $user_id, $tokens );
	}

	public function revertOrderPay($tokens, $user_id) {
		return $this->txGenericExecution('orderPayRefund', $user_id, $tokens );
	}

	public function createOrderReward($tokens, $user_id) {
		return $this->txGenericExecution('orderReward', $user_id, $tokens );
	}

	public function revertOrderReward($tokens, $user_id) {
		return $this->txGenericExecution('orderRewardDeny', $user_id, $tokens );
	}

	public function userRegistration($user_id) {
		return $this->txGenericExecution('userRegistration', $user_id, null );
	}

	/**
	 * Writes transaction to DB
	 * @param int $user_id
	 * @param string $actionName
	 * @param TransactionModel $tx
	 * @return int
	 */
	public function writeTxLog($user_id, $actionName, $tx) {

		$data = [
			'tx_reference' => $tx->id,
			'tx_hash' => $tx->transaction_hash,
			'user_id' => $user_id,
			'action_name' => $actionName,
			'action_id' => $this->getActionId($actionName),
			'value' => $tx->amount,
			'coefficient' => db_get_field( "SELECT coefficient FROM ?:currencies WHERE currency_code = 'OBT'" ),
			'commission' => $tx->commission_amount,
			'status' => strtoupper(substr($tx->status,0,1)), // Processing, Failed, Complete
			'created_ts' => $tx->timestamp / 1000,
			'updated_ts' => time(),
		];

		$id = db_query("INSERT INTO ?:ost_tx ?e", $data);
		return $id;
	}

//	public function updateTxLog($tx_reference, $status) {
//		return db_query("UPDATE ?:ost_tx SET status=?s,value=?d,commission=?d WHERE tx_reference=?s",  strtoupper(substr($tx->status,0,1)), $tx->amount, $tx->commission_amount, $tx->id);
//	}

	/**
	 * @param $user_id
	 * @param array $status
	 *
	 * @return array
	 */
	public function getTxLog($user_id, $status = ['P','C']) {
		return db_get_array("SELECT * FROM ?:ost_tx WHERE user_id=?i AND status IN (?a) ORDER BY created_ts DESC", $user_id, $status);
	}


	/**
	 * Returns the exchange rate of our branded OST token to the specified currency
	 *
	 * @param string $currency
	 * @param float $coefficient
	 *
	 * @return float
	 */
	public function getExchangeRate($currency = CART_SECONDARY_CURRENCY, $coefficient = null) {

		if(empty($coefficient)) {
			$ost_bt        = db_get_row( "SELECT * FROM ?:currencies WHERE currency_code = 'OBT'" );
			$coefficient = $ost_bt['coefficient'];
		}

		if(empty(CART_PRIMARY_CURRENCY)) {
			$primary_currency = CART_SECONDARY_CURRENCY;
		} else {
			$primary_currency = CART_PRIMARY_CURRENCY;
		}

		if(empty($currency)) {
			$currency = CART_SECONDARY_CURRENCY;
		}

		$currency_primary_factor = 1.0;
		if($currency!=$primary_currency) {
			$currency_primary_factor = db_get_field("SELECT coefficient FROM ?:currencies WHERE currency_code=?s", $currency);
		}

		return $coefficient * $currency_primary_factor;
	}

	/**
	 * Calculates exchange rate to USD from desired currency
	 *
	 * @param string $currency
	 *
	 * @return float
	 */
	public function getExchangeRateUsd($currency = CART_SECONDARY_CURRENCY) {
		if(empty(CART_PRIMARY_CURRENCY)) {
			$primary_currency = CART_SECONDARY_CURRENCY;
		} else {
			$primary_currency = CART_PRIMARY_CURRENCY;
		}

		$usdToPrimary = db_get_field("SELECT coefficient FROM ?:currencies WHERE currency_code='USD'");

		$primaryToCurrency = 1.0;
		if($currency!=$primary_currency) {
			$primaryToCurrency = db_get_field("SELECT coefficient FROM ?:currencies WHERE currency_code=?s", $currency);
		}

		return $usdToPrimary * $primaryToCurrency;
	}


	/**
	 * Gets current live exchange rate OST to USD from API
	 *
	 * @param null $tokenDataList
	 *
	 * @return float|int
	 * @throws \Exception
	 */
	public function getLiveExchangeRateUsdOst($tokenDataList = null) {
		try {
			$repoToken = $this->getTokenRepository();
			if ( $tokenDataList == null ) {
				$tokenDataList = $repoToken->info();
			}

			$ost_conversion = $repoToken->filterToken( $tokenDataList )->conversion_factor;
			$usd_conversion = $repoToken->filterPricePoints( $tokenDataList )->OST['USD'];
			$usdRate        = $usd_conversion / $ost_conversion; // Branded token in USD
		}
		catch(\Exception $ex) {
			$this->displayErrorMessage($ex->getMessage());
		}

		return $usdRate;
	}

	/**
	 * Updates UserRegistration action on OST
	 * @param $value
	 *
	 * @return bool
	 * @throws \Exception
	 */
	public function sendUserRegistrationValue($value) {
		$actionId = $this->settingsHelper->getOptionValue($this->addonId, 'userRegistrationId');
		try {
			$repo        = $this->getActionRepository();

			$usd_value = $this->getExchangeRateUsd() * $value;
			$actionModel = $repo->update( $actionId, null, null, null, null, $usd_value );

			if ( $actionModel->amount == $value ) {
				return true;
			} else {
				return false;
			}
		} catch(\Exception $ex) {
			$this->displayErrorMessage($ex->getMessage());
			return false;
		}
	}

	/**
	 * Validates credentials and get UUID (save it in db)
	 * @return bool
	 */
	public function validateCredentials($api_key, $api_secret) {
		$repoToken = $this->getTokenRepository($api_key, $api_secret);
		try {
			$tokenDataList = $repoToken->info();
			$tokenData = $repoToken->filterToken($tokenDataList);
			$priceData = $repoToken->filterPricePoints($tokenDataList);

			$this->settingsHelper->updateOption( $this->addonId, "general", 'company_uuid', $tokenData->company_uuid );

			if(empty($tokenData)) {
				throw new \Exception("Empty token data");
			}
			return true;
		}
		catch(\Exception $ex) {
			$this->displayErrorMessage($ex->getMessage());
			return false;
		}
	}

	/**
	 * Setup OST Loyalty API
	 */
	public function setupOstLoyaltyApi($api_key, $api_secret) {
		// Check API credentials (or show error) and get company UUID
		if(!$this->validateCredentials($api_key, $api_secret)) {
			return false;
		}

		// Install actions (if not done yet)
		$this->installActions($api_key, $api_secret);

		// Install currency
		$this->insertOrActualizeOstCurrencies($api_key, $api_secret);
	}

	/**
	 * Install actions
	 *
	 * @return bool Indicates if all actions could be installed successfully
	 */
	protected function installActions($api_key, $api_secret) {
		$baseActions = ActionDefinitionRepository::getActionNameArray();
		foreach($baseActions as $key=>$action) {
			$model = $this->getOrCreateOstAction($action, $api_key, $api_secret);
			if($model==null) {
				return false;
			} else {
				$this->settingsHelper->updateOption( $this->addonId, "actions", $action . 'Id', $model->id);
			}
		}
		return true;
	}

	/**
	 * @return array
	 */
	public function getCurrencyOBT() {
		return db_get_row("SELECT * FROM ?:currencies WHERE currency_code = 'OBT'");
	}

	/**
	 * Installs or fix currencies for OST token usage
	 * @throws \Exception
	 */
	public function insertOrActualizeOstCurrencies($api_key = null, $api_secret= null) {

		if(empty(CART_PRIMARY_CURRENCY)) {
			$primary_currency = CART_SECONDARY_CURRENCY;
		} else {
			$primary_currency = CART_PRIMARY_CURRENCY;
		}

		// Install USD if it does not exist create it (disabled, just for having conversion to USD)
		$currencies = array_keys(fn_get_currencies());
		if(!in_array('USD',$currencies)) {
			$usd_currency = [
				'currency_code'=>'USD',
				'after'=>'N',
				'symbol'=>'$',
				'coefficient'=>'1.0',
				'is_primary'=>'N',
				'position'=>'1001',
				'decimals_separator'=>'.',
				'thousands_separator'=>',',
				'decimals'=>2,
				'status'=>'D',
				'description'=>'US Dollars',
			];
			fn_update_currency($usd_currency,0);
			fn_set_notification('W',__('msg_usd_installed_title'),__('msg_usd_installed'));
		}

		$db_currency = $this->getCurrencyOBT();
		if(empty($db_currency)) {
			try {
				// Get primary currency
				$db_primary_currency = db_get_row( "SELECT * FROM ?:currencies WHERE currency_code=?s", $primary_currency );

				// Insert our OST token as currency
				$repoToken     = $this->getTokenRepository( $api_key, $api_secret );
				$tokenDataList = $repoToken->info();
				/** @var TokenModel $tokenData */
				$tokenData = $repoToken->filterToken( $tokenDataList );
				/** @var PricePointsModel $priceData */
				$priceData = $repoToken->filterPricePoints( $tokenDataList );

				// INSERT
				$ost_currency = [
					'currency_code'       => 'OBT',
					'after'               => 'Y',
					'symbol'              => $tokenData->symbol,
					'coefficient'         => $this->getLiveExchangeRateUsdOst( $tokenDataList ) * $this->getExchangeRateUsd( $primary_currency ),
					// Calculate primary currency exchange rate
					'position'            => '1000',
					'decimals_separator'  => $db_primary_currency['decimals_separator'],
					'thousands_separator' => $db_primary_currency['thousands_separator'],
					'decimals'            => 2,
					'status'              => 'H',
					'description'         => $tokenData->name,
				];
				fn_update_currency( $ost_currency, 0 );

			} catch(\Exception $ex) {
				$this->displayErrorMessage($ex->getMessage());
			}
		} else {
			$this->updateExchangeRateOstInDb($api_key, $api_secret);
		}
	}

	/**
	 * Updates exchange rate in DB
	 *
	 * @param null $api_key
	 * @param null $api_secret
	 *
	 * @return bool
	 */
	public function updateExchangeRateOstInDb($api_key=null, $api_secret=null) {

		if(empty(CART_PRIMARY_CURRENCY)) {
			$primary_currency = CART_SECONDARY_CURRENCY;
		} else {
			$primary_currency = CART_PRIMARY_CURRENCY;
		}

		try {
			$db_currency = $this->getCurrencyOBT();

			// Update current exchange rate
			$repoToken     = $this->getTokenRepository( $api_key, $api_secret );
			$tokenDataList = $repoToken->info();
			$tokenData     = $repoToken->filterToken( $tokenDataList );
			$ost_currency  = [
				'currency_code' => 'OBT',
				'symbol'        => $tokenData->symbol,
				'coefficient'   => $this->getLiveExchangeRateUsdOst( $tokenDataList ) * $this->getExchangeRateUsd( $primary_currency ),
			];
			fn_update_currency($ost_currency,$db_currency['currency_id']);

			return true;
		}
		catch(\Exception $ex ) {
			$this->displayErrorMessage($ex->getMessage());
			return false;
		}
	}


	/**
	 * @deprecated Not longer used
	 * @param $actionName
	 * @param int $position
	 */
	protected function initializeDbActionOption($actionName, $position=10) {
		$settingTypes = $this->settingsHelper->getTypes();

		$actionModel = $this->getOrCreateOstAction($actionName);

		// Create header
		$this->settingsHelper->updateOption( $this->addonId, $this->getOptionName( $actionName, 'tab' ), $this->getOptionName( $actionName, 'Head' ), '', $position * 10, $settingTypes['header'] );

		// Create currency value
		$this->settingsHelper->updateOption( $this->addonId, $this->getOptionName( $actionName, 'Tab' ), $this->getOptionName( $actionName, 'Value' ), 0.1, $position * 10 + 2, $settingTypes['input'] );

		// Create hidden value
		$this->settingsHelper->updateOption( $this->addonId, $this->getOptionName( $actionName, 'Tab' ), $this->getOptionName( $actionName, 'Id' ), $actionModel->id, $position * 10 + 3, $settingTypes['hidden'] );

		// Clear cache
		$this->settingsHelper->clearCache();
	}

	/**
	 * Deletes all transaction type options from database
	 * @deprecated Not used
	 */
	public function deleteAllActionSettings() {
		$transactionTypes = $this->getAvailableAddonActions();
		foreach($transactionTypes as $t) {
			$this->deleteDbActionOption($t);
		}
	}

	/**
	 * @deprecated Not longer used
	 * @return array Available addon types with their translation
	 */
	public function getAvailableAddonActions() {
		$transactionTypes = ActionDefinitionRepository::getActionNameArray();
		return $transactionTypes;
	}

	/**
	 * @deprecated Not longer used
	 * @return array Available addon types with their translation
	 */
	public function getAvailableAddonActionDescriptions() {
		$transactionTypes = $this->getAvailableAddonActions();
		$addonTransactionTypes = [];

		foreach($transactionTypes as $key) {
			$addonTransactionTypes[$key] = __('addonTransactionType.'.$key);
		}

		return $addonTransactionTypes;
	}

	/**
	 * @deprecated Not longer used
	 * @param $new_value
	 * @param $old_value
	 */
	public function processActionOptions($new_value, $old_value) {

		// Compare old and new value array
		$transactionTypes = Main::getInstance()->getAvailableAddonActions();
		foreach($transactionTypes as $position=>$type) {
			// Value is new - install config
			if($new_value[$type]=="Y" && ($old_value[$type]=="N" || empty($old_value[$type]))) {
				$this->initializeDbActionOption($type, $position + 1);
			}
			// Value is removed - uninstall config
			elseif ($new_value[$type]=="N" && $old_value[$type]=="Y") {
				$this->deleteDbActionOption($type);
			}
		}

	}

	/**
	 * @param $actionName
	 * @param $fieldName
	 *
	 * @return string
	 * @deprecated Not longer used
	 */
	protected function getOptionName($actionName, $fieldName) {
		return 'action_' . strtolower($actionName) . ($fieldName? '_'.strtolower($fieldName) : '');
	}


	/**
	 * @param $actionName
	 * @deprecated Not longer used
	 */
	protected function deleteDbActionOption($actionName) {
		$this->settingsHelper->removeOption($this->addonId, $this->getOptionName($actionName, 'Head'));
		$this->settingsHelper->removeOption($this->addonId, $this->getOptionName($actionName, 'Type'));
		$this->settingsHelper->removeOption($this->addonId, $this->getOptionName($actionName, 'Value'));
		//$this->settingsHelper->removeOption($this->addonId, $this->getOptionName($actionName, 'Id')); Dont delete option
	}
}