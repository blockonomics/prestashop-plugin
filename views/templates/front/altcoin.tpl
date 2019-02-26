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
  <script>var get_uuid = "{$uuid|escape:'htmlall':'UTF-8'}";</script>
  <div ng-controller="AltcoinController">
    <div class="bnomics-order-container" style="max-width: 700px;">
      <!-- Heading row -->
      <div class="bnomics-order-heading">
        <div class="bnomics-order-heading-wrapper">
          <div class="bnomics-order-id">
            <span class="bnomics-order-number" ng-cloak> {l s='Order #' mod='blockonomics'} //id_order//</span>
          </div>
        </div>
      </div>
      <div class="bnomics-order-panel">
            <div class="bnomics-order-info" ng-init="altcoin_waiting=true">
              <div class="bnomics-altcoin-pane">
                <div class="bnomics-altcoin-waiting" ng-show="altcoin_waiting" ng-init="altcoin_waiting=true" ng-cloak>
                  <!-- WAITING_FOR_DEPOSIT -->
                  <div class="bnomics-btc-info" style="display: flex;flex-wrap: wrap" ng-show="altstatus == 'waiting'" ng-cloak>
                    <div style="flex: 1">
                      <!-- QR Code -->
                      <div class="bnomics-qr-code">
                            <div class="bnomics-qr">
                                      <a href="//altcoinselect//://altaddress//?amount=//altamount//&value=//altamount//">
                                        <qrcode data="//altcoinselect//://altaddress//?amount=//altamount//&value=//altamount//" size="160" version="6">
                                          <canvas class="qrcode"></canvas>
                                        </qrcode>
                                      </a>
                            </div>
                        <div class="bnomics-qr-code-hint">{l s='Click on the QR code to open in the wallet' mod='blockonomics'}</div>
                      </div>
                    </div>
                    <div style="flex: 2">
                      <div class="bnomics-altcoin-bg-color">
                        <!-- Payment Text -->
                        <div class="bnomics-order-status-wrapper">
                          <span class="bnomics-order-status-title" ng-show="altstatus == 'waiting'" ng-cloak >
                            {l s='To confirm your order, please send the exact amount of ' mod='blockonomics'} 
         <strong>//altcoinselect//</strong> {l s=' to the given address' mod='blockonomics' }
                          </span>
                        </div>
                        <h4 class="bnomics-amount-title" for="invoice-amount">
                          //altamount// //altsymbol//
                        </h4>
                        <!-- Altcoin Address -->
                        <div class="bnomics-address">
                          <input ng-click="alt_address_click()" id="bnomics-alt-address-input" class="bnomics-address-input" type="text" ng-value="altaddress" readonly="readonly">
                          <i ng-click="alt_address_click()" class="material-icons bnomics-copy-icon">file_copy</i>
                        </div>
                        <div class="bnomics-copy-text" ng-show="copyshow" ng-cloak>
                          {l s='Copied to clipboard' mod='blockonomics' }
                        </div>
                        <!-- Countdown Timer -->
                        <div ng-cloak ng-hide="altstatus != 'waiting'  || alt_clock <= 0" class="bnomics-progress-bar-wrapper">
                          <div class="bnomics-progress-bar-container">
                            <div class="bnomics-progress-bar" style="width: //alt_progress//%">
                            </div>
                          </div>
                        </div>
                        <span class="ng-cloak bnomics-time-left" ng-hide="altstatus != 'waiting' || alt_clock <= 0">//alt_clock*1000 | date:'mm:ss' : 'UTC'// {l s='min left to pay your order' mod='blockonomics' }
                        </span>
                      </div>
                      <div class="bnomics-altcoin-cancel">
                        <a href="" ng-click="go_back()">{l s='Click here' mod='blockonomics' }</a> {l s='to go back' mod='blockonomics' }
                      </div>
                      <!-- Blockonomics Credit -->
                      <div class="bnomics-powered-by">
                        {l s='Powered by' mod='blockonomics' } Blockonomics
                      </div>
                    </div>
                  </div>
                  <!-- RECEIVED -->
                  <div class="bnomics-altcoin-bg-color" ng-show="altstatus == 'received'" ng-cloak>
                    <h4>{l s='Received' mod='blockonomics' }</h4>
                    <h4><i class="material-icons bnomics-alt-icon">check_circle</i></h4>
                    {l s='Your payment has been received and your order will be processed shortly.' mod='blockonomics' }
                  </div>
                  <!-- ADD_REFUND -->
                  <div class="bnomics-status-flex bnomics-altcoin-bg-color" ng-show="altstatus == 'add_refund'" ng-cloak >
                    <h4>{l s='Refund Required' mod='blockonomics' }</h4>
                    <p>{l s='Your order couldn\'t be processed as you didn\'t pay the exact expected amount.' mod='blockonomics' }<br>{l s='The amount you paid will be refunded.' mod='blockonomics' }</p>
                    <h4><i class="material-icons bnomics-alt-icon">error</i></h4>
                    <p>{l s='Enter your refund address and click the button below to recieve your refund.' mod='blockonomics' }</p>
                    <input type="text" id="bnomics-refund-input" placeholder="//altsymbol// Address">
                    <br>
                    <button id="alt-refund-button" ng-click="add_refund_click()">Refund</button>
                  </div>
                  <!-- REFUNDED no txid-->
                  <div class="bnomics-status-flex bnomics-altcoin-bg-color" ng-show="altstatus == 'refunded'" ng-cloak >
                    <h4>{l s='Refund Submitted' mod='blockonomics' }</h4>
                    <p>{l s='Your refund details have been submitted. You should recieve your refund shortly.' mod='blockonomics' }</p>
                    <h4><i class="material-icons bnomics-alt-icon">autorenew</i></h4>
                    <p>{l s='If you don\'t get refunded in a few hours, contact' mod='blockonomics' } <a href="mailto:hello@flyp.me">hello@flyp.me</a> {l s='with the following uuid:' mod='blockonomics' }<br><span id="alt-uuid">//altuuid//</span></p>
                  </div>
                  <!-- REFUNDED with txid-->
                  <div class="bnomics-status-flex bnomics-altcoin-bg-color" ng-show="altstatus == 'refunded-txid'" ng-cloak >
                    <h4>{l s='Refunded' mod='blockonomics' }</h4>
                    <h4><i class="material-icons bnomics-alt-icon">autorenew</i></h4>
                    <p>{l s='This payment has been refunded.' mod='blockonomics' }</p>
                    <div>
                      {l s='Refund Details:' mod='blockonomics' }
                      <div class="bnomics-small bnomics-bold bnomics-left">{l s='Transaction ID:' mod='blockonomics' }</div> 
                      <div class="bnomics-small bnomics-left" id="alt-refund-txid">//alttxid//</div>
                      <div class="bnomics-small bnomics-bold bnomics-left">{l s='Transaction URL:' mod='blockonomics' }</div>
                      <div class="bnomics-small bnomics-left" id="alt-refund-url"><a href="//alturl//" target="_blank">//alturl//</a></div>
                    </div>
                  </div>
                  <!-- EXPIRED -->
                  <div class="bnomics-status-flex bnomics-altcoin-bg-color" ng-show="altstatus == 'expired'" ng-cloak >
                    <h4>{l s='Expired' mod='blockonomics' }</h4>
                    <h4><i class="material-icons bnomics-alt-icon">timer</i></h4>
                    <p>{l s='Payment Expired. Use the browser back button and try again.' mod='blockonomics' }</p>
                  </div>
                  <!-- LOW/HIGH -->
                  <div class="bnomics-status-flex bnomics-altcoin-bg-color" ng-show="altstatus == 'low_high'" ng-cloak >
                    <h4>{l s='Error' mod='blockonomics' }</h4>
                    <h4><i class="material-icons bnomics-alt-icon">error</i></h4>
                    <p>{l s='Order amount too' mod='blockonomics' } <strong>//lowhigh//</strong> {l s='for' mod='blockonomics' } //altsymbol// {l s='payment.' mod='blockonomics' }</p>
                    <p><a href="" ng-click="go_back()">{l s='Click here' mod='blockonomics' }</a> {l s='to go back and use BTC to complete the payment.' mod='blockonomics' }</p>
                  </div>
                </div>
              <!-- Blockonomics Credit -->
              <div class="bnomics-powered-by" ng-hide="altstatus == 'waiting'">{l s='Powered by ' mod='blockonomics' }Blockonomics</div>
            </div>
          </div>
        </div>
    </div>
  </div>
</div>
{/block}
