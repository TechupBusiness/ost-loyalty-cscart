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

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    if ($mode == 'token_payment') {

        $tokens_to_use = floatval($_REQUEST['tokens_to_use']);
        if ( !empty($tokens_to_use) && abs($tokens_to_use) == $tokens_to_use) {
            Tygh::$app['session']['cart']['ost_loyalty']['spent']['tokens'] = $tokens_to_use;
        }

        $redirect_mode = isset($_REQUEST['redirect_mode']) ? $_REQUEST['redirect_mode'] : 'checkout';

        return array(CONTROLLER_STATUS_REDIRECT, 'checkout.' . $redirect_mode . '.show_payment_options');
    }

    if ($mode == 'delete_tokens_to_spend') {
        unset(Tygh::$app['session']['cart']['ost_loyalty']['spent']);

        $redirect_mode = isset($_REQUEST['redirect_mode']) ? $_REQUEST['redirect_mode'] : 'checkout';

        return array(CONTROLLER_STATUS_REDIRECT, 'checkout.' . $redirect_mode . '.show_payment_options');
    }

    return;
}
