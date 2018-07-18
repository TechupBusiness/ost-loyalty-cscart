<?php

namespace Techup\Addons\OstLoyalty;


use Techup\Helper\SettingsHelper;
use Techup\Helper\Singleton;
use Techup\Addons\OstLoyalty\Main;
use Tygh\Registry;

class Hooks extends Singleton {
	private $addonId = 'ost_loyalty';

	public function change_order_status(&$status_to, &$status_from, &$order_info, &$order_statuses, &$place_order)
	{
		if (!empty($order_info['ost_loyalty'])) {
			$ost_loyalty = $order_info['ost_loyalty'];

			$grant_tokens_from_status = !empty($order_statuses[$status_from]['params']['grant_tokens']) ? $order_statuses[$status_from]['params']['grant_tokens'] : 'N';
			$grant_tokens_to_status = !empty($order_statuses[$status_to]['params']['grant_tokens']) ? $order_statuses[$status_to]['params']['grant_tokens'] : 'N';
			$accept_tokens_from_status = !empty($order_statuses[$status_from]['params']['accept_tokens']) ? $order_statuses[$status_from]['params']['accept_tokens'] : 'N';
			$accept_tokens_to_status = !empty($order_statuses[$status_to]['params']['accept_tokens']) ? $order_statuses[$status_to]['params']['accept_tokens'] : 'N';

			// Handle transfer of tokens as payment depending on order status
			if(!empty($ost_loyalty['spent']['tokens'])) {
				if ( $accept_tokens_from_status == 'N' && $accept_tokens_to_status == 'Y' ) {
					// Todo Warning if user has too less tokens to send back
					Main::getInstance()->createOrderPay($ost_loyalty['spent']['tokens'], $order_info['user_id']);
				} elseif( $accept_tokens_from_status == 'Y' && $accept_tokens_to_status == 'N' ) {
					Main::getInstance()->revertOrderPay($ost_loyalty['spent']['tokens'], $order_info['user_id']);
				}
			}

			// Handle transfer of tokens as bonus / loyalty income depending on order status
			if (!empty($ost_loyalty['income']['tokens'])) {
				if ($grant_tokens_from_status == 'N' && $grant_tokens_to_status == 'Y') {
					Main::getInstance()->createOrderReward($ost_loyalty['income']['tokens'], $order_info['user_id']);
				} elseif ($grant_tokens_from_status == 'Y' && $grant_tokens_to_status == 'N') {
					Main::getInstance()->revertOrderReward($ost_loyalty['income']['tokens'], $order_info['user_id']);
				}
			}
		}
	}

	/**
	 * Extracts data from additional data and add it to the order data
	 * @param $order
	 * @param $additional_data
	 */
	public function get_order_info(&$order, &$additional_data) {
		if(empty($order)) {
			return;
		}

		if(isset($additional_data[OST_TOKENS_ORDER_INCOME])) {
			$order['ost_loyalty']['income'] = unserialize($additional_data[OST_TOKENS_ORDER_INCOME]);
		}
		if(!empty($additional_data[OST_TOKENS_ORDER_SPENT])) {
			$order['ost_loyalty']['spent'] = unserialize($additional_data[OST_TOKENS_ORDER_SPENT]);
		}
	}

	public function user_init(&$auth, &$user_info) {
		if(empty($auth['user_id']) || AREA != 'C') {
			return;
		}
		$exchangeRate = Main::getInstance()->getExchangeRate();
		$auth['tokens'] = $user_info['tokens'] = Main::getInstance()->getUserBalance($auth['user_id']);
		$auth['tokens_value'] = $user_info['tokens_value'] = $auth['tokens'] * $exchangeRate;
		$auth['token_rate'] = $user_info['token_rate'] = $exchangeRate;
	}

	public function get_orders(&$fields, &$sortings, &$join)	{
		$fields[] = "?:order_data.data as tokens";
		$sortings['points'] = '?:order_data.data';
		$join .= db_quote(" LEFT JOIN ?:order_data ON ?:order_data.order_id = ?:orders.order_id AND ?:order_data.type = ?s", OST_TOKENS_ORDER_INCOME);
	}

	public function get_status_params_definition(&$status_params, &$type) {
		if ($type == STATUSES_ORDER) {
			$status_params['grant_tokens'] = array (
				'type' => 'checkbox',
				'label' => 'grant_tokens',
				'default_value' => 'Y'
			);
			$status_params['accept_tokens'] = array (
				'type' => 'checkbox',
				'label' => 'accept_tokens',
				'default_value' => 'Y'
			);
		}
	}

	/**
	 * Applies the tokens from user to the order (user decide how much he wants to use)
	 * @param $cart
	 * @param $new_cart_data
	 */
	public function update_cart_by_data_post(&$cart, &$new_cart_data) {
		if (isset($new_cart_data['tokens_to_use'])) {
			$tokens_to_use = floatval($new_cart_data['tokens_to_use']);
			if (!empty($tokens_to_use)) {
				$cart['ost_loyalty']['spent']['tokens'] = $tokens_to_use;
			}
		}
	}

	/**
	 * Needed when orders are splitted (especially multi-vendor)
	 * @param $cart
	 * @param $suborder_cart
	 */
	public function place_suborders(&$cart, &$suborder_cart) {
		// TODO test this properly
		if (!empty($cart['ost_loyalty']['spent']) && !empty($suborder_cart['ost_loyalty']['spent'])) {
			$cart['ost_loyalty']['spent']['tokens'] -= $suborder_cart['ost_loyalty']['spent']['tokens'];
			$cart['ost_loyalty']['spent']['price'] -= $suborder_cart['ost_loyalty']['spent']['price'];
		}
		if (!empty($cart['ost_loyalty']['income']) && !empty($suborder_cart['ost_loyalty']['income'])) {
			$cart['ost_loyalty']['income']['tokens'] -= $suborder_cart['ost_loyalty']['income']['tokens'];
			$cart['ost_loyalty']['income']['price'] -= $suborder_cart['ost_loyalty']['income']['price'];
		}
	}

	/**
	 * Save our cart data to the order (in database)
	 * @param $order_id
	 * @param $cart
	 */
	public function place_order(&$order_id, &$cart) {
		if(!empty($order_id)) {
			// Tokens that a user spent for this order
			if(isset($cart['ost_loyalty']['spent'])) {
				$order_data = array(
					'order_id' => $order_id,
					'type' => OST_TOKENS_ORDER_SPENT,
					'data' => serialize($cart['ost_loyalty']['spent'])
				);
				db_query("REPLACE INTO ?:order_data ?e", $order_data);

			} elseif(isset($cart['ost_loyalty_history']['spent'])) {
				db_query("DELETE FROM ?:order_data WHERE order_id = ?i AND type = ?s", $order_id, OST_TOKENS_ORDER_SPENT);
			}

			// Tokens that a user will get for the order
			if(isset($cart['ost_loyalty']['income'])) {
				$order_data = array(
					'order_id' => $order_id,
					'type' => OST_TOKENS_ORDER_INCOME,
					'data' => serialize($cart['ost_loyalty']['income'])
				);
				db_query("REPLACE INTO ?:order_data ?e", $order_data);

				Main::getInstance()->displaySuccessReceiveMessage($cart['ost_loyalty']['income']['token'], false);

			} elseif(isset($cart['ost_loyalty_history']['spent'])) {
				db_query("DELETE FROM ?:order_data WHERE order_id = ?i AND type = ?s", $order_id, OST_TOKENS_ORDER_INCOME);
			}
		}
	}

	/**
	 * Calculate cart sums/totals in checkout
	 * @param $cart
	 * @param $cart_products
	 * @param $shipping_rates
	 * @param $calculate_taxes
	 * @param $auth
	 */
	public function calculate_cart_taxes_pre(&$cart, &$cart_products, &$shipping_rates, &$calculate_taxes, &$auth) {

		$exchangeRate = Main::getInstance()->getExchangeRate();
		$user_tokens = Main::getInstance()->getLiveUserBalance( $auth['user_id'] );

		/*
		 * Tokens as payment
		 */
		$ost_spend = & $cart['ost_loyalty']['spent'];
		if($ost_spend['tokens'] > 0 &&
		   Registry::get('runtime.controller') == 'checkout' ||
		   (defined('ORDER_MANAGEMENT') && in_array(Registry::get('runtime.mode'), array('update', 'place_order', 'add')))
		) {
			if ($ost_spend['tokens'] > $user_tokens) {
				$ost_spend['tokens'] = $user_tokens;
				fn_set_notification('W', __('warning'), __('msg_too_less_tokens', ['[spend]'=>$ost_spend['tokens'],'[account]'=>$user_tokens]));
			}

			$max_order_tokens = $cart['subtotal'] / $exchangeRate;
			if ($ost_spend['tokens'] > $max_order_tokens) {
				$ost_spend['tokens'] = $max_order_tokens;
				fn_set_notification('W', __('warning'), __('msg_too_many_tokens', ['[spend]'=>$ost_spend['tokens'],'[max]'=>$max_order_tokens]));
			}

			// Calculate price of tokens
			$ost_spend['price'] = $ost_spend['tokens'] * $exchangeRate;
			$cart['subtotal_discount'] += $ost_spend['price'];
		}
		$cart['ost_loyalty']['user']['tokens'] = $user_tokens-$ost_spend['tokens'];
		$cart['ost_loyalty']['user']['price'] = $cart['ost_loyalty']['user']['tokens'] * $exchangeRate;
		$cart['ost_loyalty']['user']['currency'] = CART_SECONDARY_CURRENCY;

		/*
		 * Tokens as rewards
		 */
		$final_total = $cart['subtotal'] - $cart['subtotal_discount'];

		// Get tokens for the desired percentage of sales volume
		$percent_bonus = (SettingsHelper::getInstance()->getOptionValue($this->addonId, 'userRewardFactor') / 100);

		$reward_tokens = ($final_total * $percent_bonus) / $exchangeRate;
		$ost_income = & $cart['ost_loyalty']['income'];
		$ost_income['tokens'] = $reward_tokens;


		/*
		 * Tokens as bonus via promotion
		 */
		if (isset($cart['ost_loyalty']['bonus']['tokens'])) {
			$ost_income['tokens'] += $cart['ost_loyalty']['bonus']['tokens'];
			unset($cart['ost_loyalty']['bonus']['tokens']);
		}


		$ost_income['price'] = $ost_income['tokens'] * $exchangeRate;
	}

}