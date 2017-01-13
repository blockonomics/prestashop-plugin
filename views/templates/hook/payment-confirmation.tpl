{*
 * 2011-2016 Blockonomics
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
 * @copyright 2011-2016 Blockonomics
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * International Registered Trademark & Property of Blockonomics
 *}

{capture name=path}{l s='Bitcoin payment' mod='blockonomics'}{/capture}
{include file="$tpl_dir./breadcrumb.tpl"}

<h2>{l s='Order summary' mod='blockonomics'}</h2>

{assign var='current_step' value='payment'}
{include file="$tpl_dir./order-steps.tpl"}

{if isset($nbProducts) && $nbProducts <= 0}
	<p class="warning">{l s='Your shopping cart is empty.' mod='blockonomics'}</p>
{else}

<h3>{l s='Bitcoin payment' mod='blockonomics'}</h3>
<form action="{$this_path_ssl|escape:'htmlall':'UTF-8'}payment-execution.php" method="post">
	<p>
		<img src="{$this_path_ssl|escape:'htmlall':'UTF-8'}views/img/confirmation-logo.png" alt="{l s='bitcoin' mod='blockonomics'}" style="float:left; margin: 0px 10px 5px 0px;" />
		{l s='You have chosen to pay by bitcoin.' mod='blockonomics'}
		<br/><br />
	</p>
	<p style="margin-top:20px;">
		{l s='The total amount of your order is' mod='blockonomics'}
		<span><strong>{$total_btc|escape:'htmlall':'UTF-8'} BTC</strong></span>
		{if $use_taxes == 1}
    		{l s='(tax incl.)' mod='blockonomics'}
    	{/if}
	</p>
<br/>
	<p>
		<b>{l s='Confirm your order by clicking \'I confirm my order\'' mod='blockonomics'}.</b>
	</p>
	<p class="cart_navigation">
		<a href="{$order_link|escape:'htmlall':'UTF-8'}?step=3" class="button_large">{l s='Other payment methods' mod='blockonomics'}</a>
		<input type="submit" name="submit" value="{l s='I confirm my order' mod='blockonomics'}" class="exclusive_large" />
	</p>
</form>
{/if}
