{if $order_info.ost_loyalty.income.tokens>0}
    <tr>
        <td><strong>{__("order_token_bonus")} {$order_info.ost_loyalty.income.tokens|fn_format_token}:</strong></td>
        <td>+{$order_info.ost_loyalty.income.price|fn_format_token_value}</td>
    </tr>
{/if}
{if $order_info.ost_loyalty.spent.tokens>0}
    <tr>
        <td><strong>{__("order_token_payed")} {$order_info.ost_loyalty.spent.tokens|fn_format_token}:</strong></td>
        <td>{$order_info.ost_loyalty.spent.price|fn_format_token_value}</td>
    </tr>
{/if}