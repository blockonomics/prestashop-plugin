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

<div class="form" ng-app="blockonomics-invoice">
<div class="col-md-7 col-md-offset-3 invoice" ng-controller="CheckoutController">
<!-- heading row -->
<div class="row">
<div class="invoice-heading" ng-init="invoice_status = {$status|escape:'htmlall':'UTF-8'}"> 
<span class="ng-binding"> Order# {$id_order|escape:'htmlall':'UTF-8'}</span>
<span ng-show="{$status|escape:'htmlall':'UTF-8'} == -1" class="invoice-heading-right" >//clock*1000 | date:'mm:ss' : 'UTC'//</span>
</div>
<div class="progress" ng-hide="{$status|escape:'htmlall':'UTF-8'} != -1">
<div class="progress-bar" role="progressbar" aria-valuenow="70" aria-valuemin="0" aria-valuemax="100" style="width://progress//%">
</div>
</div>
</div>
<!-- Amount row -->
<div class="row">

<div class="col-xs-12">
<!-- Status -->
<label ng-init="init({$status|escape:'htmlall':'UTF-8'}, '{$addr|escape:'htmlall':'UTF-8'}', {$timestamp|escape:'htmlall':'UTF-8'}, '{$base_websocket_url|escape:'htmlall':'UTF-8'}' )" ng-show="{$status|escape:'htmlall':'UTF-8'} >= 0" for="invoice-amount" style="margin-top:15px;" >Status</label>
<div class="value ng-binding" style="margin-bottom:10px;" >
<strong style="color: #956431;" ng-show="{$status|escape:'htmlall':'UTF-8'} == -1" >To
pay, send exact amount of BTC to the given address</strong>
<strong style="color: #956431;" ng-show="{$status|escape:'htmlall':'UTF-8'} == 0"> Unconfirmed</strong>
<strong style="color: #956431;" ng-show="{$status|escape:'htmlall':'UTF-8'} == 1"> Partially Confirmed</strong>
<strong style="color: #956431;" ng-show="{$status|escape:'htmlall':'UTF-8'} >= 2" >Confirmed</strong>
</div>
</div>

<div class="col-xs-6 invoice-amount"  style="border-right:#ccc 1px solid;">
<!-- address-->
<div class="row">
<label class="col-xs-6" style="margin-bottom:15px;margin-top:15px;" for="btn-address">Bitcoin Address</label>
</div>

<!-- QR Code -->
<div class="row qr-code-box">
<div class="col-xs-5 qr-code">
<div class="qr-enclosure">
<qrcode data="bitcoin:{$addr|escape:'htmlall':'UTF-8'}?amount={math equation="x/y" x=$bits y=100000000}" size="250">
<canvas class="qrcode"></canvas>
</qrcode>
</div>
</div>
</div>
</div>

<div class="col-xs-6 invoice-status" style="margin-top:15px;">
<!-- Amount -->
<label for="invoice-amount">Amount</label>
<div class="value ng-binding">
<strong style="color: #956431;">{math equation="x/y" x=$bits y=100000000}</strong>
<small>BTC</small> ⇌
<strong style="color: #956431;">{$value|escape:'htmlall':'UTF-8'}</strong> 
<small>{$currency_iso_code|escape:'htmlall':'UTF-8'}</small>
</div>

<!-- Payment Details -->
<label style="margin-top:15px;" ng-hide="{$status|escape:'htmlall':'UTF-8'} == -1" for="invoice-amount" >Payment Details</label>
<div ng-hide="{$status|escape:'htmlall':'UTF-8'} == -1" class="value ng-binding">
Received : <strong style="color: #956431;">{math equation="x/y" x=$bits_payed y=100000000}</strong>
<small>BTC</small> 
</div>

<!-- Transaction Details -->
<div ng-show="{$status|escape:'htmlall':'UTF-8'} >=0" class="value ng-binding" style="margin-bottom:10px;" >
Transaction : <a style="font-weight:bold;color: #956431;"
href="{$base_url|escape:'htmlall':'UTF-8'}/api/tx?txid={$txid|escape:'htmlall':'UTF-8'}&addr={$addr|escape:'htmlall':'UTF-8'}">{$txid|escape:'htmlall':'UTF-8'|truncate:20:""}</a>
</div>
</div>
</div>
<div class="row">
<input type="text" class="invoice-address" value="{$addr|escape:'htmlall':'UTF-8'}" readonly="readonly"> 
</div>
</div>
</div>
