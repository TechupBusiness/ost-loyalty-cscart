{if $cart_products && $user_info.tokens > 0}
<div class="ty-coupons__container">
    <div id="token_payment" class="code-input discount-coupon">
        <form class="cm-ajax" name="token_payment_form" action="{""|fn_url}" method="post">
        <input type="hidden" name="redirect_mode" value="{$location}" />
        <input type="hidden" name="result_ids" value="checkout_totals,checkout_steps" />
        
        <div class="ty-discount-coupon__control-group ty-input-append ty-inline-block">
            <input type="text" class="ty-input-text ty-valign cm-hint" name="tokens_to_use" size="40" value="{__("pay_with_tokens")}" />
            {include file="buttons/go.tpl" but_name="checkout.token_payment" alt=__("apply") but_text=__("apply")}
            <input type="submit" class="hidden" name="dispatch[checkout.token_payment]" value="" />
        </div>
        </form>
        <div class="ty-discount-info">
            <span class="ty-caret-info"><span class="ty-caret-outer"></span><span class="ty-caret-inner"></span></span>
            {$spent_value = $cart.ost_loyalty.user.price|fn_format_price:$cart.ost_loyalty.user.currency}
            <span class="">{__("tokens_available", ['[token]'=>$cart.ost_loyalty.user.tokens|fn_format_token, '[value]'=>$spent_value|fn_format_token_value])}</span>
            
            {*{if $cart.ost_loyalty.spent.tokens}*}
                {*{assign var="_redirect_url" value=$config.current_url|escape:url}*}
                {*{if $use_ajax}{assign var="_class" value="cm-ajax"}{/if}*}
                {*<span class="">{__("tokens_to_use", ['[token]'=>$cart.ost_loyalty.spent.tokens|fn_format_token, '[value]'=>$cart.ost_loyalty.spent.value|fn_format_token_value])}</span>*}
            {*{/if}*}
        </div>
</div>
    <!--token_payment--></div>
{/if}