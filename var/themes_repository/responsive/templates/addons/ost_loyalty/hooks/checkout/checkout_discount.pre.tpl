{if $cart.ost_loyalty.spent}
    <li class="ty-cart-statistic__item">
        {assign var="_redirect_url" value=$config.current_url|escape:url}
            {if $use_ajax}{assign var="_class" value="cm-ajax"}{/if}
        {$token ="`$cart.ost_loyalty.spent.tokens|fn_format_token` (`$cart.ost_loyalty.spent.price|fn_format_token_value`)"}
        <span class="ty-cart-statistic__title">{__("tokens_to_use", ['[token]'=>$token])}&nbsp{include file="buttons/button.tpl" but_href="checkout.delete_tokens_to_spend?redirect_url=`$_redirect_url`" but_meta="cm-post token__delete-icon" but_role="delete" but_target_id="checkout_totals,subtotal_price_in_points,checkout_steps`$additional_ids`"}</span>
    </li>
{/if}

{if $cart.ost_loyalty.income.token>0}
    <li class="ty-cart-statistic__item">
        <span class="ty-cart-statistic__title">{__("tokens")}</span>
        <span class="ty-cart-statistic__value">+{$cart.ost_loyalty.income.token|fn_format_token}</span>
    </li>
{/if}