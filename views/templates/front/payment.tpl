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
{extends $layout}

{block name="content"}
<div class="form" ng-app="blockonomics-invoice">
  <script>var ajax_url = "{$ajax_url|escape:'htmlall':'UTF-8'}";</script>
  <div class="invoice" ng-controller="CheckoutController" ng-init="init({$status|escape:'htmlall':'UTF-8'},
  '{$addr|escape:'htmlall':'UTF-8'}', {$timestamp|escape:'htmlall':'UTF-8'},
  '{$base_websocket_url|escape:'htmlall':'UTF-8'}' ,'{$redirect_link|escape:'htmlall':'UTF-8'}', '{$bits|escape:'htmlall':'UTF-8'}')">
  <div class="bnomics-order-container" style="max-width: 700px;">
    {if $accept_altcoin }
      <div class="bnomics-payment-option" ng-hide="altcoin_waiting == 1 || order.altstatus == 1 || order.altstatus == 2 || order.altstatus == 3">
        <span class="bnomics-paywith-label" ng-cloak> {l s='Pay with' mod='blockonomics'}</span>
        <span>
          <span class="bnomics-paywith-option bnomics-paywith-btc bnomics-paywith-selected" ng-click="show_altcoin=0">BTC</span>
          <span class="bnomics-paywith-option bnomics-paywith-altcoin" ng-click="show_altcoin=1">Altcoins</span>     
        </span>
      </div><br>
    {/if}
    <div class="bnomics-order-id">
      <span class="bnomics-order-number" ng-cloak> {l s='Order#' mod='blockonomics'} {$id_order|escape:'htmlall':'UTF-8'}</span>
    </div>
    <div class="bnomics-bitcoin-pane" ng-hide="show_altcoin != 0" ng-init="show_altcoin=0">
            <div class="bnomics-btc-info">
              <!-- QR and Amount -->
              <div class="bnomics-qr-code">
                <div class="bnomics-qr">
                          <a href="bitcoin:{$addr|escape:'htmlall':'UTF-8'}?amount={$bits|escape:'htmlall':'UTF-8'}">
                            <qrcode data="bitcoin:{$addr|escape:'htmlall':'UTF-8'}?amount={$bits|escape:'htmlall':'UTF-8'}" size="160" version="6">
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
                    <div class="bnomics-amount-wrapper">
                     {$bits|escape:'htmlall':'UTF-8'} BTC
                    </div>
                    <label>
                       ≈ <span ng-cloak>{$value|escape:'htmlall':'UTF-8'}</span>
                      <small ng-cloak>{$currency_iso_code|escape:'htmlall':'UTF-8'}</small>
                    </label>
              <!-- Bitcoin Address -->
                <div class="bnomics-address">
                  <input ng-click="btc_address_click()" id="bnomics-address-input" class="bnomics-address-input" type="text" value="{$addr|escape:'htmlall':'UTF-8'}" readonly="readonly">
                  <i ng-click="btc_address_click()" class="material-icons bnomics-copy-icon">file_copy</i>
                </div>
                <div class="bnomics-copy-text" ng-show="copyshow" ng-cloak>Copied to clipboard</div>
            <!-- Countdown Timer -->
                <div ng-cloak ng-hide="order.status != -1" class="bnomics-progress-bar-wrapper">
                  <div class="bnomics-progress-bar-container">
                    <div class="bnomics-progress-bar" style="width://progress//%"></div>
                  </div>
                </div>
                <span class="ng-cloak bnomics-time-left" ng-hide="order.status != -1">//clock*1000 | date:'mm:ss' : 'UTC'// min left to pay your order</span>
              </div>
        <!-- Blockonomics Credit -->
            <div class="bnomics-powered-by">
              {l s='Powered by ' mod='blockonomics' }Blockonomics
            </div>
              </div>
            </div>
    </div>
          {if $accept_altcoin }
          <div class="bnomics-altcoin-pane" ng-hide="show_altcoin != 1">
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
                    <a ng-click="pay_altcoins()" href=""><button><i class="cf cf-eth" ></i> {l s='Pay with' mod='blockonomics' } //altcoinselect//</button></a>
                   </div>
                </div>
            </div>
          </div>
          {/if}
    </div>
  </div>
</div>
{/block}
