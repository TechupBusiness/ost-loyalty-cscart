<?php
/***************************************************************************
*                                                                          *
*   (c) 2004 Vladimir V. Kalynyak, Alexey V. Vinokurov, Ilya M. Shalnev    *
*                                                                          *
* This  is  commercial  software,  only  users  who have purchased a valid *
* license  and  accept  to the terms of the  License Agreement can install *
* and use this program.                                                    *
*                                                                          *
****************************************************************************
* PLEASE READ THE FULL TEXT  OF THE SOFTWARE  LICENSE   AGREEMENT  IN  THE *
* "copyright.txt" FILE PROVIDED WITH THIS DISTRIBUTION PACKAGE.            *
****************************************************************************/

$schema['conditions']['ost_loyalty_tokens_balance'] = array (
	'operators' => array ('lte', 'gte', 'lt', 'gt'),
	'type' => 'input',
	'field' => '@auth.tokens',
	'zones' => array('cart'),
	'filter' => 'fn_promotions_filter_int_condition_value'
);
//$schema['conditions']['ost_loyalty_tokens_income'] = array (
//    'operators' => array ('lte', 'gte', 'lt', 'gt'),
//    'type' => 'input',
//    'field' => '@cart.ost_loyalty.income.tokens',
//    'zones' => array('cart'),
//    'filter' => 'fn_promotions_filter_int_condition_value'
//);
//$schema['conditions']['ost_loyalty_tokens_spent'] = array (
//	'operators' => array ('lte', 'gte', 'lt', 'gt'),
//	'type' => 'input',
//	'field' => '@cart.ost_loyalty.spent.tokens',
//	'zones' => array('cart'),
//	'filter' => 'fn_promotions_filter_int_condition_value'
//);

$schema['bonuses']['ost_loyalty_give_tokens'] = array (
    'type' => 'input',
    'function' => array('fn_ost_loyalty_promotion_tokens', '#this', '@cart', '@auth', '@cart_products'),
    'zones' => array('cart'),
    'filter' => 'intval'
);


return $schema;
