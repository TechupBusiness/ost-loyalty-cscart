{if $cart.ost_loyalty.spent}
{assign var="_redirect_url" value=$config.current_url|escape:url}
    <tr class="ty-checkout-summary__order_discount">
        <td class="ty-checkout-summary__item">
            {$token ="`$cart.ost_loyalty.spent.tokens|fn_format_token` (`$cart.ost_loyalty.spent.price|fn_format_token_value`)"}
            {__("tokens_to_use", ['[token]'=>$token])}
        </td>
        <td class="ty-checkout-summary__item ty-right discount-price">
            {include file="buttons/button.tpl" but_href="checkout.delete_tokens_to_spend?redirect_url=`$_redirect_url`" but_meta="cm-post" but_role="delete" but_target_id="checkout_totals,subtotal_price_in_points,checkout_steps`$additional_ids`"}
        </td>
    </tr>
{/if}