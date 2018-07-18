{if $order_info.ost_loyalty.income.tokens>0}
    <tr class="ty-orders-summary__row">
        <td><strong>{__("order_token_bonus")}:</strong></td>
        <td>{$order_info.ost_loyalty.income.tokens|fn_format_token} ({$order_info.ost_loyalty.income.price|fn_format_token_value})</td>
    </tr>
{/if}
{if $order_info.ost_loyalty.spent.tokens>0}
    <tr class="ty-orders-summary__row">
        <td><strong>{__("order_token_payed")}:</strong></td>
        <td>{$order_info.ost_loyalty.spent.tokens|fn_format_token} ({$order_info.ost_loyalty.spent.price|fn_format_token_value})</td>
    </tr>
{/if}