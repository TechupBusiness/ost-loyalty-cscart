
<table class="ty-table">
<thead>
    <tr>
        <th class="">{__("date")}</th>
        <th class="">{__("action")}</th>
        <th class="">{__("tokens")}</th>
        <th class="">{__("action")}</th>
    </tr>
</thead>
{foreach from=$transactions item="tx"}
    {$fiat_value = $tx.value * $tx.coefficient|fn_token_get_exchange_rate}
    <tr>
        <td>{$tx.created_ts|date_format:"`$settings.Appearance.date_format`, `$settings.Appearance.time_format`"}</td>
        <td>{__("action_description_`$tx.action_name`")}</td>
        <td>{if $tx.value>0}{$tx.value|fn_format_token}  {if $tx.coefficient}({$fiat_value|fn_format_token_value}){/if}{else}{__("will_appear_soon")}{/if}</td>
        <td>{if $tx.tx_hash}<a href="https://view.ost.com/chain-id/1409/transaction/{$tx.tx_hash}" target="_blank">{__("show_in_ost_explorer")}</a>{else}{__("will_appear_soon")}{/if}</td>
    </tr>
{foreachelse}
<tr class="ty-table__no-items">
    <td colspan="3"><p class="ty-no-items">{__("no_items")}</p></td>
</tr>
{/foreach}
</table>

{capture name="mainbox_title"}{__("your_wallet_headline", [ '[symbol]'=>$currencies.OBT.symbol ])}{/capture}
