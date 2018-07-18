{if $runtime.mode == "checkout" && $cart_products && $user_info.tokens > 0}
<div class="ty-right">
    {include file="addons/ost_loyalty/hooks/checkout/payment_options.post.tpl"}
</div>
{/if}
