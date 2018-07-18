{if $auth.user_id>0}
<div class="ty-dropdown-box__title top-my-account">
    <a href="{"ost_loyalty.show"|fn_url}">
        {$token = $auth.tokens|fn_format_token}
        {if $auth.tokens>0}
            {$value = $auth.tokens_value|fn_format_token_value}
            <span>{__("token_balance", ['[token]'=>$token, '[value]'=>$value])}</span>
        {else}
            <span>{__("token_balance_empty", ['[token]'=>$token])}</span>
        {/if}
    </a>
</div>
{/if}