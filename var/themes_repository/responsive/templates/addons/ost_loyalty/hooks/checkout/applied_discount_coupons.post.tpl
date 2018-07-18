{if $runtime.mode == "checkout" && $cart_products && $user_info.tokens > 0}
    <form class="cm-ajax cm-ajax-full-render" name="token_payment_form" action="{""|fn_url}" method="post">
        <input type="hidden" name="redirect_mode" value="{$location}" />
        <input type="hidden" name="result_ids" value="checkout*,cart_status*" />

        <div class="ty-discount-coupon__control-group ty-input-append ty-inline-block">
            <input type="text" class="ty-input-text ty-valign cm-hint" name="tokens_to_use" size="40" value="{__("tokens_use_here", ['[symbol]'=>$currencies.OBT.symbol])}" />
            {include file="buttons/go.tpl" but_name="checkout.token_payment" alt=__("apply") but_text=__("apply")}
            <input type="submit" class="hidden" name="dispatch[checkout.token_payment]" value="" />
        </div>
    </form>

    <div class="ty-discount-info">
        <span class="ty-caret-info"><span class="ty-caret-outer"></span><span class="ty-caret-inner"></span></span>
        {$spent_value = $cart.ost_loyalty.user.price|fn_format_price:$cart.ost_loyalty.user.currency}
        <span class="">{__("tokens_available", ['[token]'=>$cart.ost_loyalty.user.tokens|fn_format_token, '[value]'=>$spent_value|fn_format_token_value])}</span>

        {* if $cart.ost_loyalty.spent.tokens}
            {assign var="_redirect_url" value=$config.current_url|escape:url}
            {if $use_ajax}{assign var="_class" value="cm-ajax"}{/if}
            <span class="">
                    {include file="common/price.tpl" value=$cart.ost_loyalty.spent.value
                    {include file="buttons/button.tpl" but_href="checkout.delete_tokens_to_spend?redirect_url=`$_redirect_url`" but_meta="cm-post" but_role="delete" but_target_id="checkout*,cart_status*,subtotal_price_in_tokens"}
            </span>
        {/if *}
    </div>

{/if}

{if $cart.ost_loyalty.income}
    <div class="clearfix" style="margin-top: 20px;">
        {$income_value = $cart.ost_loyalty.income.price|fn_format_price:$cart.ost_loyalty.user.currency}
        <span>{__("tokens_to_earn", ['[token]'=>$cart.ost_loyalty.income.tokens|fn_format_token,'[value]'=>$income_value|fn_format_token_value])}</span>
    </div>
{/if}