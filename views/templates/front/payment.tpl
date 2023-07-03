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

<div id="blockonomics_checkout">
    <div class="bnomics-order-container">

        <!-- Spinner -->
        <div class="bnomics-spinner-wrapper">
            <div class="bnomics-spinner"></div>
        </div>

        <!-- Blockonomics Checkout Panel -->    
        <div class="bnomics-order-panel">
            <table>
                <tr>
                    <th class="bnomics-header">
                        <!-- Order Header -->
                        <span class="bnomics-order-id">
                            {l s='Order #'  mod='blockonomics'}{$id_order}
                        </span>

                        <div>
                            <span class="blockonomics-icon-cart"></span>
                            {$value} {$currency_iso_code}
                        </div>
                    </th>
                </tr>
            </table>
            <table>
                <tr>
                    <th>
                        <!-- Order Address -->
                        <label class="bnomics-address-text">{l s='To pay, send' mod='blockonomics'} {strtolower($crypto['name'])} {l s='to this address' mod='blockonomics'}</label>
                        <label class="bnomics-copy-address-text">{l s='Copied to clipboard' mod='blockonomics'}</label>
                        <div class="bnomics-copy-container">
                            <input type="text" value="{$addr}" id="bnomics-address-input" readonly/>
                            <span id="bnomics-address-copy" class="blockonomics-icon-copy"></span>
                            <span id="bnomics-show-qr" class="blockonomics-icon-qr"></span>
                        </div>

                        <div class="bnomics-qr-code">
                            <div class="bnomics-qr">
                                <a href="{$payment_uri}" target="_blank" class="bnomics-qr-link">
                                    <canvas id="bnomics-qr-code"></canvas>
                                </a>
                            </div>
                            <small class="bnomics-qr-code-hint">
                                <a href="{$payment_uri}" target="_blank" class="bnomics-qr-link">{l s='Open in wallet' mod='blockonomics'}</a>
                            </small>
                        </div>
                        </th>
                </tr>
            </table>
            <table>
                <tr>
                    <th>
                        <label class="bnomics-amount-text">{l s='Amount of' mod='blockonomics'} {strtolower($crypto['name'])} ({strtoupper($crypto['code'])}) {l s='to send' mod='blockonomics'}</label>
                        <label class="bnomics-copy-amount-text">{l s='Copied to clipboard' mod='blockonomics'}</label>

                        <div class="bnomics-copy-container" id="bnomics-amount-copy-container">
                            <input type="text" value="{$order_amount}" id="bnomics-amount-input" readonly/>
                            <span id="bnomics-amount-copy" class="blockonomics-icon-copy"></span>
                            <span id="bnomics-refresh" class="blockonomics-icon-refresh"></span>
                        </div>

                        <small class="bnomics-crypto-price-timer">
                            1 {strtoupper($crypto['code'])} = <span id="bnomics-crypto-rate">{$crypto_rate_str}</span> {$currency_iso_code} {l s='updates in' mod='blockonomics'} <span class="bnomics-time-left">00:00 min</span>
                        </small>
                    </th>
                </tr>
            </table>
        </div>
    </div>
</div>
<script>
    var blockonomics_data = JSON.stringify({
        time_period: {$time_period},
        crypto:  JSON.parse('{json_encode($crypto) nofilter}'),
        crypto_address: '{$addr}',
        finish_order_url: '{$redirect_link nofilter}',
        payment_uri: '{$payment_uri}',
    })
</script>
{/block}