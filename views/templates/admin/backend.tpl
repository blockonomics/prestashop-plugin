{*
 * 2011-2019 Blockonomics
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
 * @copyright 2011-2019 Blockonomics
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * International Registered Trademark & Property of Blockonomics
 *}
{if $temp_api_key && !$api_key && !($total_received > 0)}
    <div class="alert alert-info">
    	<p><b>Blockonomics Wallet</b> (Balance: 0 BTC)</p>
        <p>We are using a temporary wallet on Blockonomics to receive your payments.</p>
        <p>
        	To receive payments directly to your wallet (recommended) -> Follow Wizard by clicking on <i>Get Started for Free</i> on <a href="https://www.blockonomics.co/merchants" target="_blank">Merchants</a> and enter the APIKey below [<a href="https://blog.blockonomics.co/how-to-accept-bitcoin-payments-on-woocommerce-using-blockonomics-f18661819a62">Blog Instructions</a>]
        </p>
    </div>
{elseif $temp_api_key && $total_received > 0}
	<div class="alert alert-info"><p><b>Blockonomics Wallet</b> (Balance: {$total_received|escape:'htmlall':'UTF-8'} BTC)</p>
    {if !$api_key}
	    <p>
	    	To withdraw, follow wizard by clicking on <i>Get Started for Free</i> on <a href="https://www.blockonomics.co/merchants" target="_blank">Merchants</a>, then enter the APIKey below [<a href="https://blog.blockonomics.co/how-to-accept-bitcoin-payments-on-woocommerce-using-blockonomics-f18661819a62">Blog Instructions</a>]
	    </p>
	{else}
	    <p> To withdraw, Click on <b>Test Setup</b></p>
	{/if}
	</div>
{elseif $api_key}
    <div class="alert alert-info">
    	<p><b>Your wallet</b></p>
        <p>
        	Payments will go directly to the wallet you setup on <a href="https://www.blockonomics.co/merchants" target="_blank">Blockonomics</a>. There is no need for withdraw
       	</p>
    </div>
{else}
    <div class="alert alert-danger">
    	<p><b>ERROR:</b> No wallet set up</p>
    </div>
{/if}