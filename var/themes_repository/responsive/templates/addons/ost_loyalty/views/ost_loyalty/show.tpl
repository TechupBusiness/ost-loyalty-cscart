
<table class="ty-table">
    {$current_exchange_rate = 0|fn_token_get_exchange_rate}
    <tr>
        {if $balances->token_balance && $balances->airdropped_balance}
            {$token_bal = $balances->token_balance * $current_exchange_rate}
            <td>Earned: {$balances->token_balance|fn_format_token} ({$token_bal|fn_format_token_value})</td>
            {$airdrop_bal = $balances->airdropped_balance * $current_exchange_rate}
            <td>Received as gift: {$balances->airdropped_balance|fn_format_token} ({$airdrop_bal|fn_format_token_value})</td>
        {/if}
        {if $balances->available_balance}
            {$avail_bal = $balances->available_balance * $current_exchange_rate}
            <td><strong>Total available: {$balances->available_balance|fn_format_token} ({$avail_bal|fn_format_token_value})</strong>
            {if $addons.ost_loyalty.ostview !== "disabled"}
                {foreach from=$ostuser->addresses item=address name=ostviewuser}
                    {if $smarty.foreach.ostviewuser.first != true}, {/if}
                    <a href="https://view.ost.com/chain-id/{$address[0]}/address/{$address[1]}" target="_blank">{__("show_in_ost_explorer")}</a>
                {/foreach}
            {/if}
            </td>
        {/if}
    </tr>
</table>

<table class="ty-table">
<thead>
    <tr>
        <th class="">{__("date")}</th>
        <th class="">{__("action")}</th>
        <th class="">{__("tokens")}</th>
        {if $addons.ost_loyalty.ostview !== "disabled"}
            <th class="">{__("action")}</th>
        {/if}
    </tr>
</thead>

{foreach from=$transactions item="tx"}
    {$fiat_value = $tx.ost->amount * $tx.db.coefficient|fn_token_get_exchange_rate}
    <tr>
        <td>{($tx.ost->timestamp/1000)|date_format:"`$settings.Appearance.date_format`, `$settings.Appearance.time_format`"}</td>
        <td>{if $tx.db.action_name}{__("action_description_`$tx.db.action_name`")}{/if}</td>
        <td>{if $tx.ost->amount>0}{$tx.ost->amount|fn_format_token}  {if $tx.db.coefficient}({$fiat_value|fn_format_token_value}){/if}{else}{__("will_appear_soon")}{/if}</td>
        {if $addons.ost_loyalty.ostview !== "disabled"}
            <td>{if $tx.ost->transaction_hash}<a href="https://view.ost.com/chain-id/1409/transaction/{$tx.ost->transaction_hash}" target="_blank">{__("show_in_ost_explorer")}</a>{else}{__("will_appear_soon")}{/if}</td>
        {/if}
    </tr>
{foreachelse}
<tr class="ty-table__no-items">
    <td colspan="4"><p class="ty-no-items">{__("no_items")}</p></td>
</tr>
{/foreach}
</table>

{capture name="mainbox_title"}{__("your_wallet_headline", [ '[symbol]'=>$currencies.OBT.symbol ])}{/capture}
