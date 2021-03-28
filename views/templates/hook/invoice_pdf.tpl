{*
 * 2011 Blockonomics
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
 * @copyright 2011 Blockonomics
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * International Registered Trademark & Property of Blockonomics
 *}

<table class="product" width="100%" cellpadding="4" cellspacing="0">
<thead>
<tr>
<th class="header small" width="20%">{l s='Bitcoins Payed' pdf='true' mod='blockonomics'}</th>
<th class="header small" width="60%">{l s='Transaction' pdf='true' mod='blockonomics'}</th>
</tr>
</thead>
<tbody>
<tr>
<td class="white">
{math equation="x/y" x=$bits_payed y=100000000} BTC
</td>
<td class="center white">
<a href="{$base_url|escape:'htmlall':'UTF-8'}/api/tx?txid={$txid|escape:'htmlall':'UTF-8'}&addr={$addr|escape:'htmlall':'UTF-8'}">{$txid|escape:'htmlall':'UTF-8'}</a>
</td>
</tr>

</tbody>
</table>
