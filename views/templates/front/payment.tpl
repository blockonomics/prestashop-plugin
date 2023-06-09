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

{assign var='current_step' value='payment'}

{include file="$tpl_dir./order-steps.tpl"}

<div id="blockonomics_checkout">
    <div class="bnomics-order-container">

        <!-- Spinner -->
        <div class="bnomics-spinner-wrapper">
            <div class="bnomics-spinner"></div>
        </div>
        <div class="bnomics-display-error">
            <h2>{l s='Payment is pending' mod='blockonomics'}</h2>
            <p>{l s='Additional payments to invoice are only allowed after current pending transaction is confirmed.'  mod='blockonomics'}</p>
        </div>

        <!-- Blockonomics Checkout Panel -->    
        <div class="bnomics-order-panel">
            <table>
                <tr>
                    <th class="bnomics-header">
                        <!-- Order Header -->
                        <span class="bnomics-order-id">
                            {l s='Order #'  mod='blockonomics'}
                        </span>

                        <div>
                            <span class="blockonomics-icon-cart"></span>
                            
                        </div>
                    </th>
                </tr>
            </table>
            <table>
                <tr>
                    <th>
                        <!-- Order Address -->
                        <label class="bnomics-address-text">{l s='To pay, send ' mod='blockonomics'}{l s=' to this adress' mod='blockonomics'}</label>
                        <label class="bnomics-copy-address-text">{l s='Copied to clipboard' mod='blockonomics'}</label>
                        <div class="bnomics-copy-container">
                            <input type="text" value="" id="bnomics-address-input" readonly/>
                            <span id="bnomics-address-copy" class="blockonomics-icon-copy"></span>
                            <span id="bnomics-show-qr" class="blockonomics-icon-qr"></span>
                        </div>

                        <div class="bnomics-qr-code">
                            <div class="bnomics-qr">
                                <a href="" target="_blank" class="bnomics-qr-link">
                                    <canvas id="bnomics-qr-code"></canvas>
                                </a>
                            </div>
                            <small class="bnomics-qr-code-hint">
                                <a href="" target="_blank" class="bnomics-qr-link">{l s='Open in wallet' mod='blockonomics'}</a>
                            </small>
                        </div>
                        </th>
                </tr>
            </table>
            <table>
                <tr>
                    <th>
                        <label class="bnomics-amount-text">{l s='Amount of ' mod='blockonomics'}{l s=' to send' mod='blockonomics'}</label>
                        <label class="bnomics-copy-amount-text">{l s='Copied to clipboard' mod='blockonomics'}</label>

                        <div class="bnomics-copy-container" id="bnomics-amount-copy-container">
                            <input type="text" value="{$order_amount}" id="bnomics-amount-input" readonly/>
                            <span id="bnomics-amount-copy" class="blockonomics-icon-copy"></span>
                            <span id="bnomics-refresh" class="blockonomics-icon-refresh"></span>
                        </div>

                        <small class="bnomics-crypto-price-timer">
                            1  = <span id="bnomics-crypto-rate"> </span>  {l s='updates in' mod='blockonomics'} <span class="bnomics-time-left">00:00 min</span>
                        </small>
                    </th>
                </tr>
            </table>
        </div>
    </div>
</div>
