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

<div ng-app="BlockonomicsApp">
  <div ng-controller="CheckoutController" ng-init="init({$status|escape:'htmlall':'UTF-8'},
  '{$addr|escape:'htmlall':'UTF-8'}', {$timestamp|escape:'htmlall':'UTF-8'},
  '{$base_websocket_url|escape:'htmlall':'UTF-8'}' ,'{$redirect_link|escape:'htmlall':'UTF-8'}', {$timeperiod|escape:'htmlall':'UTF-8'}, {$time_remaining|escape:'htmlall':'UTF-8'})">
    <div class="bnomics-order-container">
      <!-- Heading row -->
      <div class="bnomics-order-heading">
        <div class="bnomics-order-heading-wrapper">
          <div class="bnomics-order-id">
            <span class="bnomics-order-number" ng-cloak>{l s='Order #' mod='blockonomics' } {$id_order|escape:'htmlall':'UTF-8'}</span>
          </div>
        </div>
      </div>
      <!-- Payment Expired -->
      <div class="bnomics-order-expired-wrapper" ng-show="status == -3" ng-cloak>
        <h2>{l s='Payment Expired' mod='blockonomics' }</h2><br>
        <p><a ng-click="try_again_click()">{l s='Click here to try again' mod='blockonomics' }</a></p>
      </div>
      <!-- Payment Error -->
      <div class="bnomics-order-error-wrapper" ng-show="status == -2" ng-cloak>
        <h2>{l s='Paid order BTC amount is less than expected. Contact merchant' mod='blockonomics' }</h2>
      </div>
      <!-- Blockonomics Checkout Panel -->
      <div class="bnomics-order-panel" ng-show="status == -1" ng-cloak>
        <div class="bnomics-order-info">
          <div class="bnomics-bitcoin-pane">
            <div class="bnomics-btc-info">
              <!-- Left Side -->
              <!-- QR and Open in wallet -->
              <div class="bnomics-qr-code">
                <div class="bnomics-qr">
                  <a href="{$crypto.uri|escape:'htmlall':'UTF-8'}:{$addr|escape:'htmlall':'UTF-8'}?amount={$bits|escape:'htmlall':'UTF-8'}" target="_blank">
                    <qrcode data="{$crypto.uri|escape:'htmlall':'UTF-8'}:{$addr|escape:'htmlall':'UTF-8'}?amount={$bits|escape:'htmlall':'UTF-8'}" size="160" version="6">
                      <canvas class="qrcode"></canvas>
                    </qrcode>
                  </a>
                </div>
                <div class="bnomics-qr-code-hint"><a href="{$crypto.uri|escape:'htmlall':'UTF-8'}:{$addr|escape:'htmlall':'UTF-8'}?amount={$bits|escape:'htmlall':'UTF-8'}" target="_blank">{l s='Open in wallet' mod='blockonomics' }</a></div>
              </div>
              <!-- Right Side -->
              <div class="bnomics-amount">
                <div class="bnomics-bg">
                  <!-- Order Amounts -->
                  <div class="bnomics-amount">
                    <div class="bnomics-amount-text" ng-hide="amount_copyshow" ng-cloak>
                      {l s='To pay, send exactly' mod='blockonomics' }
                    </div>
                    <div class="bnomics-copy-amount-text" ng-show="amount_copyshow" ng-cloak>{l s='Copied to clipboard' mod='blockonomics' }</div>
                    <ul ng-click="blockonomics_amount_click()" id="bnomics-amount-input" class="bnomics-amount-input">
                        <li id="bnomics-amount-copy">{$bits|escape:'htmlall':'UTF-8'}</li>
                        <li>{$crypto.code|escape:'htmlall':'UTF-8'}</li>
                        <li class="bnomics-grey"> ≈ </li>
                        <li class="bnomics-grey">{$value|escape:'htmlall':'UTF-8'}</li>
                        <li class="bnomics-grey">{$currency_iso_code|escape:'htmlall':'UTF-8'}</li>
                    </ul>
                  </div>
                  <!-- Order Address -->
                  <div class="bnomics-address">
                    <div class="bnomics-address-text" ng-hide="address_copyshow" ng-cloak>{l s='To this' mod='blockonomics' } {$crypto.name|escape:'htmlall':'UTF-8'} {l s=' address' mod='blockonomics' }</div>
                    <div class="bnomics-copy-address-text" ng-show="address_copyshow" ng-cloak>{l s='Copied to clipboard' mod='blockonomics' }</div>
                    <ul ng-click="blockonomics_address_click()" id="bnomics-address-input" class="bnomics-address-input">
                          <li id="bnomics-address-copy">{$addr|escape:'htmlall':'UTF-8'}</li>
                    </ul>
                  </div>
                  <!-- Order Countdown Timer -->
                  <div class="bnomics-progress-bar-wrapper">
                    <div class="bnomics-progress-bar-container">
                      <div class="bnomics-progress-bar" style="width: [[progress]]%;"></div>
                    </div>
                  </div>
                  <span class="ng-cloak bnomics-time-left">[[clock*1000 | date:'mm:ss' : 'UTC']] {l s=' min' mod='blockonomics' }</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <!-- Blockonomics How to pay + Credit -->
      <div class="bnomics-powered-by">
        <a href="https://blog.blockonomics.co/how-to-pay-a-bitcoin-invoice-abf4a04d041c" target="_blank">{l s='How do I pay? | Check reviews of this shop' mod='blockonomics' }</a><br>
        <div class="bnomics-powered-by-text bnomics-grey">{l s='Powered by Blockonomics' mod='blockonomics' }</div>
      </div>
    </div>
  </div>
</div>

{/block}
