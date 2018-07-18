<?php

namespace Techup\Addons\OstLoyalty;


use Techup\Helper\Singleton;

class Callbacks extends Singleton {

	/**
	 * Process promotion hook
	 * @param $bonus
	 * @param $cart
	 */
	public function promotion_tokens(&$bonus, &$cart) {
		if ($bonus['bonus'] == 'ost_loyalty_give_tokens') {
			$cart['ost_loyalty']['bonus']['tokens'] = (!empty($cart['ost_loyalty']['bonus']['tokens']) ? $cart['ost_loyalty']['bonus']['tokens'] : 0) + $bonus['value'];
		}
		$cart['promotions'][$bonus['promotion_id']]['bonuses'][$bonus['bonus']] = $bonus;
	}
}