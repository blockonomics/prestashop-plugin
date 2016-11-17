
{capture name=path}{l s='Bitcoin payment' mod='blockonomics'}{/capture}
{include file="$tpl_dir./breadcrumb.tpl"}

<h2>{l s='Order summary' mod='blockonomics'}</h2>

{assign var='current_step' value='payment'}
{include file="$tpl_dir./order-steps.tpl"}

{if isset($nbProducts) && $nbProducts <= 0}
	<p class="warning">{l s='Your shopping cart is empty.'}</p>
{else}

<h3>{l s='Bitcoin payment' mod='blockonomics'}</h3>
<form action="{$this_path_ssl}payment-execution.php" method="post">
	<p>
		<img src="{$this_path_ssl}img/confirmation-logo.png" alt="{l s='bitcoin' mod='blockonomics'}" style="float:left; margin: 0px 10px 5px 0px;" />
		{l s='You have chosen to pay by bitcoin.' mod='blockonomics'}
		<br/><br />
	</p>
	<p style="margin-top:20px;">
		{l s='The total amount of your order is' mod='blockonomics'}
		<span><strong>{$total_btc} BTC</strong></span>
		{if $use_taxes == 1}
    		{l s='(tax incl.)' mod='blockonomics'}
    	{/if}
	</p>
<br/>
	<p>
		<b>{l s='Confirm your order by clicking \'I confirm my order\'' mod='blockonomics'}.</b>
	</p>
	<p class="cart_navigation">
		<a href="{$link->getPageLink('order.php', true)}?step=3" class="button_large">{l s='Other payment methods' mod='blockonomics'}</a>
		<input type="submit" name="submit" value="{l s='I confirm my order' mod='blockonomics'}" class="exclusive_large" />
	</p>
</form>
{/if}
