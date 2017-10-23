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


<div class="form" ng-app="blockonomics-invoice">
  <div class="col-md-7 col-md-offset-3 invoice" ng-controller="CheckoutController">
    <!-- heading row -->
    <div class="row">
      <h4> {l s='Order#' mod='blockonomics'} {$id_order|escape:'htmlall':'UTF-8'}</h4>
      <span ng-show="{$status|escape:'htmlall':'UTF-8'} == -1" class="invoice-heading-right" >//clock*1000 | date:'mm:ss' : 'UTC'//</span>
      <div class="progress" ng-hide="{$status|escape:'htmlall':'UTF-8'} != -1">
        <progress class="progress progress-primary" max="100" value="//progress//">
        </progress>
      </div>
    </div>
    <!-- Amount row -->
    <div class="row">

      <div class="col-xs-12">
        <!-- Status -->
        <h4 ng-init="init({$status|escape:'htmlall':'UTF-8'},
'{$addr|escape:'htmlall':'UTF-8'}', {$timestamp|escape:'htmlall':'UTF-8'},
'{$base_websocket_url|escape:'htmlall':'UTF-8'}' ,'{$redirect_link|escape:'htmlall':'UTF-8'}')" ng-show="{$status|escape:'htmlall':'UTF-8'} >= 0" for="invoice-amount" style="margin-top:15px;" >Status</h4>
        <div class="value ng-binding" style="margin-bottom:10px;margin-top:10px" >
          <h3 ng-show="{$status|escape:'htmlall':'UTF-8'} == -1" >{l s='To pay, send exact amount of BTC to the given address' mod='blockonomics'}</h3>
        </div>
      </div>

      <div class="col-xs-6 invoice-amount"  style="border-right:#ccc 1px solid;">
        <!-- address-->
        <div class="row">
          <h4 class="col-xs-6" style="margin-bottom:15px;margin-top:15px;"
for="btn-address">{l s='Bitcoin Address' mod='blockonomics'}</h4>
        </div>

        <!-- QR Code -->
        <div class="row qr-code-box">
          <div class="col-xs-5 qr-code">
            <div class="qr-enclosure">
              <a href="bitcoin:{$addr|escape:'htmlall':'UTF-8'}?amount={math equation="x/y" x=$bits y=100000000}"> 
                <qrcode data="bitcoin:{$addr|escape:'htmlall':'UTF-8'}?amount={math equation="x/y" x=$bits y=100000000}" size="200">
                <canvas class="qrcode"></canvas>
                </qrcode></a>
            </div>
          </div>
        </div>
      </div>

      <div class="col-xs-6 invoice-status" style="margin-top:15px;">
        <!-- Amount -->
        <h4 for="invoice-amount">{l s='Amount' mod='blockonomics'}</h4>
        <div class="value ng-binding">
          <label>{math equation="x/y" x=$bits y=100000000}
            <small>BTC</small></label> ⇌
          <label>{$value|escape:'htmlall':'UTF-8'}
            <small>{$currency_iso_code|escape:'htmlall':'UTF-8'}</small></label>
        </div>
      </div>
    </div>
    <div class="row">
      <div class="input-group">
        <!-- Necessary to apply text transfrom as some styles will capitalize h4 leading to wrong address -->
        <span class="input-group-addon"><h4 style="text-transform:none">{$addr|escape:'htmlall':'UTF-8'}</h4></span>
      </div>
    </div>
    <h3>Powered by blockonomics</h3>
  </div>
</div>
