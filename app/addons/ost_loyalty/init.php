<?php

if (!defined('BOOTSTRAP')) { die('Access denied'); }

fn_register_hooks(
    'place_order',
    'change_order_status',
    'get_order_info',
    array('calculate_cart_taxes_pre', 300),
    'place_suborders',
	'get_status_params_definition',
    'update_profile',
    'user_init',
    'save_log'
);

