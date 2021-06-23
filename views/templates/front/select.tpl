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
{extends $layout}

{block name="content"}
<div class="bnomics-order-container">
  <div class="bnomics-select-container">
    <table width="100%">
      <tr>
        {foreach $active_cryptos as $crypto}
            <td class="bnomics-select-options">
                <a href="{url entity='module' name='blockonomics' controller='payment' params=['crypto' => $crypto.code]}" style="color: inherit; text-decoration: inherit;">
                    <p class="top-margin">{l s='Pay With' mod='blockonomics' }</p>
                    <span class="bnomics-icon-{$crypto.code|escape:'htmlall':'UTF-8'} bnomics-rotate-{$crypto.code|escape:'htmlall':'UTF-8'}"></span>
                    <p class="top-margin">{$crypto.name|escape:'htmlall':'UTF-8'}</p>
                    <p class="top-margin"><b>{$crypto.code|escape:'htmlall':'UTF-8'}</b></p>
                </a>
            </td>
        {/foreach}
      </tr>
    </table>
  </div>
</div>

{/block}
