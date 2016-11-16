<table class="product" width="100%" cellpadding="4" cellspacing="0">
<thead>
<tr>
<th class="header small" width="20%">{l s='Bitcoins Payed' pdf='true'}</th>
<th class="header small" width="60%">{l s='Address' pdf='true'}</th>
<th class="header-right small" width="20%">{l s='Receipt' pdf='true'}</th>
</tr>
</thead>
<tbody>
<tr>
<td class="white">
{math equation="x/y" x=$bits_payed y=100000000} BTC
</td>
<td class="center white">
{$addr}
</td>
<td class="right white">
<a href="https://www.blockonomics.co/api/tx?txid={$txid}&addr={$addr}">Link</a>
</td>
</tr>

</tbody>
</table>
