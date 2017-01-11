<div class="panel kpi-container">
<fieldset>

<legend>{l s='Bitcoin Transaction Details' mod='blockonomics'}</legend>

<div id="info">
<table>
<tr><td>{l s='Bitcoin Address    ' mod='blockonomics'}</td> <td> : {$addr}</td></tr>
<tr><td>{l s='Status    ' mod='blockonomics'}</td> <td> : {$status}</td></tr>
<tr><td>{l s='Cart Value    ' mod='blockonomics'}</td> <td> : {math equation='x/y' x=$bits y=100000000} BTC</td></tr>
{if $txid != ''}
<tr><td>{l s='Amount Paid    ' mod='blockonomics'}</td> <td> : {math equation='x/y' x=$bits_payed y=100000000} BTC</td></tr>
<tr><td>{l s='Transaction Link    ' mod='blockonomics'}</td> <td> : <a href="{$base_url}/api/tx?txid={$txid}&addr={$addr}"> {$txid} <a></td></tr>
{if $bits != $bits_payed}
<tr><td>{l s='Payment Error    ' mod='blockonomics'}</td><td style='color:red'> : {l s='Amount paid not matching cart value' mod='blockonomics'}</td></tr>
{/if}
{/if}
</table>
</div>

</fieldset>
</div>
