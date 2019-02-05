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

{assign var='current_step' value='payment'}

{include file="$tpl_dir./order-steps.tpl"}

<div ng-app="shopping-cart-demo">

  <script>
      var blockonomics_time_period={$blockonomics_timeperiod|escape:'htmlall':'UTF-8'};
      var get_uuid="{$uuid|escape:'htmlall':'UTF-8'}";
      var addr="{$addr}";
      var id_order="{$id_order}";
      var bits ="{$bits}";
      var value ="{$value}";
      var timestamp ="{$timestamp}";
      var finish_order_url = "{$redirect_link}";
      var ajax_url = "{$altcoin_ctrl_url}";
  </script>

  <div ng-controller="CheckoutController">
    <div class="bnomics-order-container" style="max-width: 700px;">
      <!-- Heading row -->
      <div class="bnomics-order-heading">
        <div class="bnomics-order-heading-wrapper">
		  {if $blockonomics_altcoins }
          <div class="bnomics-payment-option" ng-hide="altcoin_waiting == 1 || order.altstatus == 1 || order.altstatus == 2 || order.altstatus == 3">
			<span class="bnomics-paywith-label" ng-cloak> {l s='Pay with' mod='blockonomics'}</span>
			<span>
				<span class="bnomics-paywith-option bnomics-paywith-btc" ng-class="{literal}{'bnomics-paywith-selected':show_altcoin=='0'}{/literal}" ng-click="show_altcoin=0">BTC</span>
				<span class="bnomics-paywith-option bnomics-paywith-altcoin" ng-class="{literal}{'bnomics-paywith-selected':show_altcoin=='1'}{/literal}" ng-click="show_altcoin=1">Altcoins</span>			
			</span>
          </div><br>
		  {/if}
          <div class="bnomics-order-id">
            <span class="bnomics-order-number" ng-cloak> {l s='Order #' mod='blockonomics'} {literal}{{order.id_order}}{/literal}</span>
          </div>
        </div>
      </div>

      <!-- Amount row -->
      <div class="bnomics-order-panel">
        <div class="bnomics-order-info">

          <div class="bnomics-bitcoin-pane" ng-hide="show_altcoin != 0" ng-init="show_altcoin=0">
            <div class="bnomics-btc-info">
              <!-- QR and Amount -->
              <div class="bnomics-qr-code">
        				<div class="bnomics-qr">
                          <a href="bitcoin:{literal}{{order.address}}?amount={{order.bits}}{/literal}">
                            <qrcode data="bitcoin:{literal}{{order.address}}{/literal}?amount={literal}{{order.bits}}{/literal}" size="160" version="6">
                              <canvas class="qrcode"></canvas>
                            </qrcode>
                          </a>
        				</div>
                <div class="bnomics-qr-code-hint">{l s='Click on the QR code to open in the wallet' mod='blockonomics'}</div>
              </div>
              <!-- BTC Amount -->
              <div class="bnomics-amount">
				      <div class="bnomics-bg">
  		          <!-- Order Status -->
  		          <div class="bnomics-order-status-wrapper">
  		            <span class="bnomics-order-status-title"
ng-show="order.status == -1" ng-init="order.status=-1" ng-cloak >{l s='To confirm your order, please send
the exact amount of ' mod='blockonomics'} <label>BTC</label> {l s=' to the given address' mod='blockonomics' }</span>
  		            <span class="warning bnomics-status-warning" ng-show="order.status == -3" ng-cloak>{l s='Payment Expired (Use the browser back button and try again)' mod='blockonomics' }</span>
  		            <span class="warning bnomics-status-warning" ng-show="order.status == -2" ng-cloak>{l s='Payment Error' mod='blockonomics' }</span>
  		            <span ng-show="order.status == 0" ng-cloak>{l s='Unconfirmed' mod='blockonomics' }</span>
  		            <span ng-show="order.status == 1" ng-cloak>{l s='Partially Confirmed' mod='blockonomics' }</span>
  		            <span ng-show="order.status >= 2" ng-cloak >{l s='Confirmed' mod='blockonomics' }</span>
  		          </div>
                    <label>
  				  	       {literal}{{order.bits}}{/literal} BTC
                    </label>
                    <div class="bnomics-amount-wrapper">
  				             ≈ <span ng-cloak>{literal}{{order.value}}{/literal}</span>
                      <small ng-cloak>{$currency_iso_code}</small>
                    </div>
  			      <!-- Bitcoin Address -->
  		          <div class="bnomics-address">
  		            <input ng-click="btc_address_click()" id="bnomics-address-input" class="bnomics-address-input" type="text" ng-value="order.address" readonly="readonly">
                  <i ng-click="btc_address_click()" class="material-icons bnomics-copy-icon">file_copy</i>
  		          </div>
                <div class="bnomics-copy-text" ng-show="copyshow" ng-cloak>Copied to clipboard</div>
  				  <!-- Countdown Timer -->
  		          <div ng-cloak ng-hide="order.status != -1" class="bnomics-progress-bar-wrapper">
  		            <div class="bnomics-progress-bar-container">
  		              <div class="bnomics-progress-bar" style="width: {literal}{{progress}}% {/literal};"></div>
  		            </div>
  		          </div>
  				      <span class="ng-cloak bnomics-time-left" ng-hide="order.status != -1">{literal}{{clock*1000 | date:'mm:ss' : 'UTC'}}{/literal} min left to pay your order</span>
				      </div>
				<!-- Blockonomics Credit -->
		        <div class="bnomics-powered-by">
		          {l s='Powered by ' mod='blockonomics' }Blockonomics
		        </div>
              </div>
            </div>
          </div>

          {if $blockonomics_altcoins }
          <div class="bnomics-altcoin-pane" ng-style="{literal}{'border-left': (altcoin_waiting)?'none':''}{/literal}" ng-hide="show_altcoin != 1">
      			<div class="bnomics-altcoin-bg">
                <div class="bnomics-altcoin-bg-color" ng-hide="altcoin_waiting" ng-cloak>
  			         <div class="bnomics-altcoin-info-wrapper">
  		            <span class="bnomics-altcoin-info" >{l s='Select your preferred ' mod='blockonomics'} <strong>Altcoin</strong> {l s=' then click on the button below.' mod='blockonomics' }</span>
  			         </div>
  			         </br>
                 <!-- Coin Select -->
                 <div class="bnomics-address">
                   <select ng-model="altcoinselect" ng-options="x for (x, y) in altcoins" ng-init="altcoinselect='Ethereum'"></select>
                 </div>
                 <div class="bnomics-altcoin-button-wrapper">
                  <a ng-click="pay_altcoins()" href=""><button><i class="cf" ng-hide="altcoinselect!='Ethereum'" ng-class={literal}{'cf-eth':'{{altcoinselect}}{/literal}'!=''} ></i><i class="cf" ng-hide="altcoinselect!='Litecoin'" ng-class={literal}{'cf-ltc':'{{altcoinselect}}{/literal}'!=''} ></i> {l s='Pay with' mod='blockonomics' } {literal}{{altcoinselect}}{/literal}</button></a>
                 </div>
                </div>

                <div class="bnomics-altcoin-waiting" ng-show="altcoin_waiting" ng-init=
								{if $uuid != "" }
					     		"altcoin_waiting=true"
					      {else}
					    		"altcoin_waiting=false"
								{/if} ng-cloak>
	              <!-- Alt status WAITING_FOR_DEPOSIT -->
	              <div class="bnomics-btc-info" style="display: flex;flex-wrap: wrap;" ng-show="order.altstatus == 0" ng-cloak>
                    <div style="flex: 1">
                      <!-- QR -->
                      <div class="bnomics-qr-code">
                        <div class="bnomics-qr">
                                  <a href="{literal}{{altcoinselect}}{/literal}:{literal}{{order.altaddress}}{/literal}?amount={literal}{{order.altamount}}&value={{order.altamount}}{/literal}">
                                    <qrcode data="{literal}{{altcoinselect}}:{{order.altaddress}}?amount={{order.altamount}}&value={{order.altamount}}{/literal}" size="160" version="6">
                                      <canvas class="qrcode"></canvas>
                                    </qrcode>
                                  </a>
                        </div>
                        <div class="bnomics-qr-code-hint">{l s='Click on the QR code to open in the wallet' mod='blockonomics' }</div>
                      </div>
                    </div>
                    <div style="flex: 2;">
                      <div class="bnomics-altcoin-bg-color">
                        <!-- Alt Order Status -->
                        <div class="bnomics-order-status-wrapper">
                          <span class="bnomics-order-status-title" ng-show="order.altstatus == 0" ng-cloak >{l s='To confirm your order, please send the exact amount of ' mod='blockonomics'} 
<strong>{literal}{{altcoinselect}}{/literal}</strong> {l s=' to the given address' mod='blockonomics' }</span>
                        </div>
    	                  <label>
    	                   {literal}{{order.altamount}} {{order.altsymbol}}{/literal}
    	                  </label>
    	                  <!-- Alt Address -->
    	                  <div class="bnomics-address">
    	                    <input ng-click="alt_address_click()" id="bnomics-alt-address-input" class="bnomics-address-input" type="text" ng-value="order.altaddress" readonly="readonly">
    	                   <i ng-click="alt_address_click()" class="material-icons bnomics-copy-icon">file_copy</i>
    	                  </div>
    	                  <div class="bnomics-copy-text" ng-show="copyshow" ng-cloak>{l s='Copied to clipboard' mod='blockonomics' }</div>
    	                  <!-- Countdown Timer -->
    	                  <div ng-cloak ng-hide="order.altstatus != 0" class="bnomics-progress-bar-wrapper">
    	                    <div class="bnomics-progress-bar-container">
    	                      <div class="bnomics-progress-bar" style="width: {literal}{{alt_progress}}{/literal}%;"></div>
    	                    </div>
    	                  </div>
    	                  <span class="ng-cloak bnomics-time-left" ng-hide="order.altstatus != 0">{literal}{{alt_clock*1000 | date:'mm:ss' : 'UTC'}}{/literal} min left to pay your order</span>
                      </div>
                      <div class="bnomics-altcoin-cancel"><a href="" ng-click="altcoin_waiting=false"> {l s='Click here' mod='blockonomics' }</a> {l s='to go back' mod='blockonomics' }
                      </div>
                      <!-- Blockonomics Credit -->
                      <div class="bnomics-powered-by">
                        {l s='Powered by ' mod='blockonomics' }Blockonomics
                      </div>
                    </div>
               	  </div>
               	  <div class="bnomics-altcoin-bg-color" ng-show="order.altstatus == 1 && altemail == false" ng-init=
									{if $uuid != "" }
						     		"altemail=true"
						      {else}
						    		"altemail=false"
									{/if}
									 ng-cloak>
               	  	<h4>Received</h4>
               	  	<h4><i class="material-icons bnomics-alt-icon">check_circle</i></h4>
               	  	{l s='Your payment has been received. You can track your order using the link sent to your email.' mod='blockonomics' }</div>
               	  <!-- Alt status  DEPOSIT_RECEIVED -->
              	  <div class="bnomics-status-flex bnomics-altcoin-bg-color" ng-show="order.altstatus == 1 && altemail == true" ng-cloak >
              	  	<h4>Processing</h4>
                	<h4><i class="cf bnomics-alt-icon" ng-hide="altcoinselect!='Ethereum'" ng-class={literal}{'cf-eth':'{{altcoinselect}}'!=''}{/literal} ></i><i class="cf bnomics-alt-icon" ng-hide="altcoinselect!='Litecoin'" ng-class={literal}{'cf-ltc':'{{altcoinselect}}'!=''}{/literal} ></i></h4>
                	<a href="{literal}{{order.altaddress_link}}{/literal}"><p>{literal}{{altcoinselect}}{/literal} Deposit Confirmation</p></a>
                	<p>{l s='This will take a while for the network to confirm your payment.' mod='blockonomics' }</p>
            	  </div>
            	  <!-- Alt status DEPOSIT_CONFIRMED -->
              	  <div class="bnomics-status-flex bnomics-altcoin-bg-color" ng-show="order.altstatus == 2" ng-cloak >
              	  	<h4>Processing</h4>
                	<h4><i class="cf bnomics-alt-icon" ng-hide="altcoinselect!='Ethereum'" ng-class={literal}{'cf-eth':'{{altcoinselect}}'!=''}{/literal} ></i><i class="cf bnomics-alt-icon" ng-hide="altcoinselect!='Litecoin'" ng-class={literal}{'cf-ltc':'{{altcoinselect}}'!=''}{/literal} ></i></h4>
                	<a href="{literal}{{order.altaddress_link}}{/literal}"><p>{literal}{{altcoinselect}}{/literal} Deposit Confirmation</p></a>
                	<p>{l s='This will take a while for the network to confirm your payment.' mod='blockonomics' }</p>
            	  </div>
            	  <!-- Alt status EXECUTED -->
              	  <div class="bnomics-status-flex bnomics-altcoin-bg-color" ng-show="order.altstatus == 3" ng-cloak >
              	  	<h4>Completed</h4>
	              	<h4><i class="material-icons bnomics-alt-icon">receipt</i></h4>
	                <a href="{literal}{{finish_order_url()}}{/literal}"><p>View Order Confirmation</p></a>
            	  </div>
            	  <!-- Alt status REFUNDED -->
              	  <div class="bnomics-status-flex bnomics-altcoin-bg-color" ng-show="order.altstatus == -1" ng-cloak >
              	  	<h4>Refunded</h4>
	              	<h4><i class="material-icons bnomics-alt-icon">cached</i></h4>
	                <p>{l s='This payment has been refunded.' mod='blockonomics' }</p>
            	  </div>
            	  <!-- Alt status CANCELED -->
              	  <div class="bnomics-status-flex bnomics-altcoin-bg-color" ng-show="order.altstatus == -2" ng-cloak >
              	  	<h4>Canceled</h4>
	              	<h4><i class="material-icons bnomics-alt-icon">cancel</i></h4>
	                <p>{l s='This probably happened because you paid less than the expected amount.<br>Please contact <a href="mailto:hello@flyp.me">hello@flyp.me</a> with below order id for refund:' mod='blockonomics' }</p>
            	  </div>
            	  <!-- Alt status EXPIRED -->
              	  <div class="bnomics-status-flex bnomics-altcoin-bg-color" ng-show="order.altstatus == -3" ng-cloak >
              	  	<h4>Expired</h4>
	              	<h4><i class="material-icons bnomics-alt-icon">timer</i></h4>
	                <p>{l s='Payment Expired (Use the browser back button and try again)' mod='blockonomics' }</p>
            	  </div>
                <!-- Alt Error Low/High -->
                  <div class="bnomics-status-flex bnomics-altcoin-bg-color" ng-show="order.altstatus == -4" ng-cloak >
                    <h4>Error</h4>
                  <h4><i class="material-icons bnomics-alt-icon">error</i></h4>
                  <p>{l s='Order amount too ' mod='blockonomics'} <strong>{literal}{{lowhigh}}{/literal}</strong> {l s='for ' mode='blockonomics'} {literal}{{order.altsymbol}}{/literal} {l s=' payment.' mod='blockonomics' }</p>
                  <p><a href="" ng-click="altcoin_waiting=false"> {l s='Click here' mod='blockonomics' }</a> {l s='to go back and use BTC to complete the payment.' mod='blockonomics' }</p>
                </div>
            	  <!-- Contact Flyp -->
            	  <div class="bnomics-altcoin-bg-color"  ng-show="order.altstatus == -1 || order.altstatus == -2 || order.altstatus == -3" ng-cloak>
	            		<p>uuid: {literal}{{altuuid}}{/literal}</p>
            	  </div>
            	  <!-- Alt Link -->
            	  <div class="bnomics-altcoin-bg-color" ng-show="order.altstatus == 5" ng-cloak>
	                  <div class="bnomics-address">
	                    <input ng-click="page_link_click()" id="bnomics-page-link-input" class="bnomics-page-link-input" type="text" ng-value="order.pagelink" readonly="readonly">
	                    <span ng-click="page_link_click()" class="dashicons dashicons-admin-page bnomics-copy-icon"></span>
	                  </div>
	                  	<div class="bnomics-copy-text" ng-show="copyshow" ng-cloak>{l s='Copied to clipboard' mod='blockonomics' }</div>
	            		{l s='To get back to this page, copy and use the above link.' mod='blockonomics' }
            	  </div>
                </div>
      			</div>
            <!-- Blockonomics Credit -->
            <div class="bnomics-powered-by" ng-hide="order.altstatus == 0">
              {l s='Powered by ' mod='blockonomics' }Blockonomics
            </div>
          </div>
          {/if}

        </div>
      </div>
    </div>
  </div>
</div>
