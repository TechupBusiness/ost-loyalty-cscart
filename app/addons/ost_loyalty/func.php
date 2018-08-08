<?php

use Techup\Addons\OstLoyalty\Callbacks;
use Techup\Addons\OstLoyalty\Hooks;
use Techup\Addons\OstLoyalty\Main;
use Techup\Helper\SettingsHelper;
use Techup\SimpleTokenApi\Repositories\ActionRepository;
use Tygh\Registry;
use Tygh\Settings;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

Registry::get('class_loader')->add('Techup', Registry::get('config.dir.addons') . 'ost_loyalty/libs');

/**
 * Returns converted tokens in main currency (for usage in smarty template engine)
 * @param null $tokens
 *
 * @return float|int
 */
function fn_get_current_ost_exchange_rate($tokens = null) {
	$exchangeRate = Main::getInstance()->getExchangeRate();
	if($tokens>0) {
		return $tokens * $exchangeRate;
	} else {
		return $exchangeRate;
	}
}

/**
 * HOOK: After updating user profile
 *
 * @param $action
 * @param $user_data
 * @param $current_user_data
 */
function fn_ost_loyalty_update_profile(&$action, &$user_data, &$current_user_data) {

	// Create user if it was created
	if($action==='add' && $user_data['user_type']=='C' && $user_data['user_id']>0) {
		// Create user
		Main::getInstance()->createOstUser($user_data['user_id']);
	}
}

/**
 * HOOK: Saves cart data for tokens in the order additional data
 * @param $order_id
 * @param $action
 * @param $order_status
 * @param $cart
 * @param $auth
 */
function fn_ost_loyalty_place_order(&$order_id, &$action, &$order_status, &$cart, &$auth) {
	Hooks::getInstance()->place_order($order_id, $cart);
}

/**
 * HOOK: Extracts data from additional data and add it to the order data
 * @param $order
 * @param $additional_data
 */
function fn_ost_loyalty_get_order_info(&$order, &$additional_data) {
	Hooks::getInstance()->get_order_info($order,$additional_data);
}

/**
 * HOOK:
 * @param $status_to
 * @param $status_from
 * @param $order_info
 * @param $force_notification
 * @param $order_statuses
 * @param $place_order
 */
function fn_ost_loyalty_change_order_status(&$status_to, &$status_from, &$order_info, &$force_notification, &$order_statuses, &$place_order) {
	Hooks::getInstance()->change_order_status($status_to, $status_from, $order_info, $order_statuses, $place_order);
}

/**
 * HOOK: Main code to add spending of tokens into CS-Cart
 * @param $cart
 * @param $cart_products
 * @param $shipping_rates
 * @param $calculate_taxes
 * @param $auth
 */
function fn_ost_loyalty_calculate_cart_taxes_pre(&$cart, &$cart_products, &$shipping_rates, &$calculate_taxes, &$auth) {
	Hooks::getInstance()->calculate_cart_taxes_pre($cart, $cart_products, $shipping_rates, $calculate_taxes, $auth);
}

function fn_ost_loyalty_form_cart(&$order_info, &$cart, &$auth) {
	Hooks::getInstance()->form_cart($order_info, $cart, $auth);
}

/**
 * HOOK: Add token count to user data
 * @param $auth
 * @param $user_info
 */
function fn_ost_loyalty_user_init(&$auth, &$user_info) {
	Hooks::getInstance()->user_init($auth,$user_info);
}

function fn_ost_loyalty_get_orders(&$params, &$fields, &$sortings, &$condition, &$join) {
	Hooks::getInstance()->get_orders($fields, $sortings, $join);
}

/**
 * HOOK: When order is getting deleted, we want to remove the points from the user's account
 * @param $order_id
 */
function fn_ost_loyalty_delete_order(&$order_id)
{
	// TODO We are missing important order data here...
//	$order_info = array('deleted_order' => true);
//	$status_to = $status_from = '';
//	$empty_array = array();
//	$place_order = false;
//	fn_ost_loyalty_change_order_status($status_to, $status_from, $order_info, $empty_array, $empty_array, $place_order);
}

/**
 * HOOK: Deletes defined company ID
 *
 * @param integer $company_id Company ID
 */
function fn_ost_loyalty_ult_delete_company(&$company_id) {
	// TODO ?? Send all tokens from this company (=user) to our company (=marketplace vendor or storefront)
	// Check if we can still get our UUID or we can lookup the company without!?
}

/**
 * Apply points to cart data
 *
 * @param array $cart Array of cart content and user information necessary for purchase
 * @param array $new_cart_data Array of new data for products, totals, discounts and etc. update
 * @param array $auth Array of user authentication data (e.g. uid, usergroup_ids, etc.)
 * @return boolean Always true
 */
function fn_ost_loyalty_update_cart_by_data_post(&$cart, &$new_cart_data, &$auth)
{
	Hooks::getInstance()->update_cart_by_data_post($cart, $new_cart_data);
}

/**
 * Changes before applying promotion rules
 * @param array $promotions List of promotions
 * @param string $zone - promotiontion zone (catalog, cart)
 * @param array $data data array (product - for catalog rules, cart - for cart rules)
 * @param array $auth (optional) - auth array (for car rules)
 * @param array $cart_products (optional) - cart products array (for car rules)
 * @return boolean Always true
 */
function fn_ost_loyalty_promotion_apply_pre(&$promotions, &$zone, &$data, &$auth, &$cart_products)
{
	if (!fn_allowed_for('ULTIMATE:FREE')) {
		// If we're in cart, set flag that promotions available
		if ($zone == 'cart') {
			if (empty($data['stored_subtotal_discount'])) {
				// unset token discount if discount amount is not set manually
				unset($data['ost_loyalty']['spent']['price']);
			}
		}
	}

	return true;
}

/**
 * HOOK: If suborders are created (for multi vendor), these orders
 * @param $cart
 * @param $suborder_cart
 */
function fn_ost_loyalty_place_suborders(&$cart, &$suborder_cart) {
	Hooks::getInstance()->place_suborders($cart, $suborder_cart);
}

/**
 * HOOK: Adds the option to an order status if a user gets reward at that order status
 *
 * @param $status_params
 * @param $type
 */
function fn_ost_loyalty_get_status_params_definition(&$status_params, &$type) {
	Hooks::getInstance()->get_status_params_definition($status_params, $type);
}


/**
 * HOOK: Log transactions
 *
 * @param $type
 * @param $action
 * @param $data
 * @param $user_id
 * @param $content
 * @param $event_type
 * @param $object_primary_keys
 */
function fn_ost_loyalty_save_log(&$type, &$action, &$data, &$user_id, &$content, &$event_type, &$object_primary_keys)
{
	if($type==LOGGER_OST_LOYALTY)
	{
		// Adding individual content
		switch($action)
		{
			case "errors":
				$content = $data;
				break;
			case "api_calls":
				$content = $data;
				break;
			case "user_mismatch":
				$content = $data;
				break;
		}
		if(isset($data['lang_code']))
			$content['lang_code'] = $data['lang_code'];
	}
}


/**
 * SETTINGS: Executed when transactionType_userRegistration_CurrencyType field changes in settings (on save)
 *
 * @param $new_value
 * @param $old_value
 */
function fn_settings_actions_addons_ost_loyalty_userRegistration_amount($new_value, $old_value) {
	if($new_value!=$old_value && !empty(Main::getInstance()->getSetting('api_key'))) {
		Main::getInstance()->sendUserRegistrationValue($new_value);
	}
}

/**
 * SETTINGS: Executed when api_key changes
 *
 * @param $new_value
 * @param $old_value
 */
function fn_settings_actions_addons_ost_loyalty_api_key($new_value, $old_value) {
	if($new_value!=$old_value && !empty($new_value) && !empty(Main::getInstance()->getSetting('api_secret'))) {
		Main::getInstance()->setupOstLoyaltyApi($new_value, Main::getInstance()->getSetting('api_secret'));
	}
}

/**
 * SETTINGS: Setup loyalt api if api_secret or api_key was changed
 *
 * @param $new_value
 * @param $old_value
 */
function fn_settings_actions_addons_ost_loyalty_api_secret($new_value, $old_value) {
	if($new_value!=$old_value && !empty($new_value) && !empty(Main::getInstance()->getSetting('api_key'))) {
		Main::getInstance()->setupOstLoyaltyApi(Main::getInstance()->getSetting('api_key'), $new_value);
	}
}

/**
 * CALLBACK: Calculate promotion
 *
 * @param $bonus
 * @param $cart
 * @param $auth
 * @param $cart_products
 *
 * @return bool
 */
function fn_ost_loyalty_promotion_tokens(&$bonus, &$cart, &$auth, &$cart_products) {
	Callbacks::getInstance()->promotion_tokens( $bonus,$cart);
	return true;
}

/**
 * CALLBACK: In addon.xml - on installation
 *
 * @return bool
 */
function fn_ost_loyalty_add_logs()
{
	$setting = Settings::instance()->getSettingDataByName('log_type_'.LOGGER_OST_LOYALTY);

	if (!$setting) {
		$setting = array(
			'name' => 'log_type_'.LOGGER_OST_LOYALTY,
			'section_id' => 12, // Logging
			'section_tab_id' => 0,
			'type' => 'N',
			'position' => 10,
			'is_global' => 'N',
			'edition_type' => 'ROOT,VENDOR',
			'value' => '#M#errors=Y&user_mismatch=Y'
		);

		foreach (fn_get_translation_languages() as $lang_code => $_lang) {
			$descriptions[] = array(
				'object_type' => Settings::SETTING_DESCRIPTION,
				'lang_code' => $lang_code,
				'value' => __('logger_ost_loyalty', $lang_code)
			);
		}

		$setting_id = Settings::instance()->update($setting, null, $descriptions, true);

		$variants = [
			[
				'object_id'  => $setting_id,
				'name'       => 'errors',
				'position'   => 5,
			],
			[
				'object_id'  => $setting_id,
				'name'       => 'api_calls',
				'position'   => 10,
			],
			[
				'object_id'  => $setting_id,
				'name'       => 'user_mismatch',
				'position'   => 15,
			]
		];
		foreach($variants as $variant) {
			$variant_id = Settings::instance()->updateVariant($variant);
			foreach ( fn_get_translation_languages() as $lang_code => $_lang ) {
				$description = array(
					'object_id'   => $variant_id,
					'object_type' => Settings::VARIANT_DESCRIPTION,
					'lang_code'   => $lang_code,
					'value'       => __( 'logger_variant_'.$variant['name'], $lang_code )
				);
				Settings::instance()->updateDescription( $description );
			}
		}
	}

	return true;
}

function fn_ost_loyalty_remove_logs() {
	Settings::instance()->remove('log_type_'.LOGGER_OST_LOYALTY);
}


function fn_token_get_exchange_rate($value) {
	return Main::getInstance()->getExchangeRate(CART_SECONDARY_CURRENCY, $value);
}

function fn_format_token($value) {
	return fn_format_token_price_by_currency($value, 'OBT');
}

function fn_format_token_value($value, $currency_code = CART_SECONDARY_CURRENCY) {
	return fn_format_token_price_by_currency($value, $currency_code);
}

function fn_format_token_price_by_currency($price, $currency_code = CART_SECONDARY_CURRENCY)
{
	$currencies = Registry::get('currencies');
	$currency = $currencies[$currency_code];
	$result = $value = number_format(fn_format_price($price, '', $currency['decimals']), $currency['decimals'], $currency['decimals_separator'], $currency['thousands_separator']);
	if ($currency['after'] == 'Y') {
		$result .= ' ' . $currency['symbol'];
	} else {
		if(strlen($currency['symbol'])>1) {
			$currency['symbol'] = $currency['symbol'] . ' ';
		}
		$result = $currency['symbol'] . $result;
	}

	return $result;
}