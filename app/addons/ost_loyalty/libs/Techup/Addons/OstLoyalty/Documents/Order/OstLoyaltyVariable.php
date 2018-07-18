<?php

namespace Techup\Addons\OstLoyalty\Documents\Order;

use Tygh\Template\Document\Order\Context;
use Tygh\Template\IActiveVariable;
use Tygh\Template\IVariable;
use Tygh\Tools\Formatter;

class OstLoyaltyVariable implements IVariable, IActiveVariable
{
    public $value;
    public $tokens;
    public $spent;
    public $spent_text;
    public $base = array();

    public function __construct(Context $context, Formatter $formatter)
    {
        $order = $context->getOrder();

        if(!empty($order->data['ost_loyalty']['income'])) {
            $this->tokens = $order->data['ost_loyalty']['income']['tokens'];

        }

        if(!empty($order->data['ost_loyalty']['spent'])) {
            $this->spent       = $order->data['ost_loyalty']['spent']['tokens'];
            $this->spent_text  = __('tokens', array($order->data['ost_loyalty']['spent']['tokens']), $context->getLangCode());
            $this->value       = $formatter->asPrice($order->data['ost_loyalty']['spent']['price']);
            $this->base['price'] = $order->data['ost_loyalty']['spent']['price'];
        }
    }

    public static function attributes()
    {
        return array(
            'price', 'tokens', 'spent', 'spent_text',
            'base' => array(
                'price'
            )
        );
    }
}