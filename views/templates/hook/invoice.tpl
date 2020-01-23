{*
 * 2011-2020 Blockonomics
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@blockonomics.co so we can send you a copy immediately.
 *
 * @author    Blockonomics Admin <admin@blockonomics.co>
 * @copyright 2011-2020 Blockonomics
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * International Registered Trademark & Property of Blockonomics
 *}

<div class="panel kpi-container">
<fieldset>

<legend>{l s='Bitcoin Transaction Details' mod='blockonomics'}</legend>

<div id="info">
<table>
<tr><td>{l s='Bitcoin Address' mod='blockonomics'}</td> <td> : {$addr|escape:'htmlall':'UTF-8'}</td></tr>
<tr><td>{l s='Status' mod='blockonomics'}</td> <td> : {$status|escape:'htmlall':'UTF-8'}</td></tr>
<tr><td>{l s='Cart Value' mod='blockonomics'}</td> <td> : {math equation='x/y' x=$bits y=100000000} BTC</td></tr>
{if $txid != ''}
<tr><td>{l s='Amount Paid' mod='blockonomics'}</td> <td> : {math equation='x/y' x=$bits_payed y=100000000} BTC</td></tr>
<tr><td>{l s='Transaction Link' mod='blockonomics'}</td> <td> : <a href="{$base_url|escape:'htmlall':'UTF-8'}/api/tx?txid={$txid|escape:'htmlall':'UTF-8'}&addr={$addr|escape:'htmlall':'UTF-8'}"> {$txid|escape:'htmlall':'UTF-8'} <a></td></tr>
{if $bits > $bits_payed}
<tr><td>{l s='Payment Error' mod='blockonomics'}</td><td style='color:red'> : {l s='Amount paid less than cart value' mod='blockonomics'}</td></tr>
{/if}
{/if}
</table>
</div>

</fieldset>
</div>
