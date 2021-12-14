<?php
/**
 * 2007-2015 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 *  @author    PrestaShop SA <contact@prestashop.com>
 *  @copyright 2007-2015 PrestaShop SA
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */

class BlockonomicsPaymentModuleFrontController extends ModuleFrontController
{
    const BLOCKONOMICS_WEBSOCKET_URL = "wss://www.blockonomics.co";
    const BLOCKONOMICS_BCH_WEBSOCKET_URL = "wss://bch.blockonomics.co";
    
    public function setMedia()
    {
        parent::setMedia();
        $this->registerStylesheet(
            'mystyle',
            'modules/blockonomics/views/css/bootstrap-prestashop-ui-kit.css',
            array('postion' => 'head')
        );
        $this->registerStylesheet(
            'mystyle2',
            'modules/blockonomics/views/css/order.css',
            array('postion' => 'head')
        );
        $this->registerJavascript(
            'bootstrap',
            'modules/blockonomics/views/js/bootstrap.js'
        );
        $this->registerJavascript(
            'angular',
            'modules/blockonomics/views/js/angular.js'
        );
        $this->registerJavascript(
            'vendor',
            'modules/blockonomics/views/js/vendors.min.js'
        );
        $this->registerJavascript(
            'qrcode',
            'modules/blockonomics/views/js/angular-qrcode.js'
        );
        $this->registerJavascript(
            'angular-resource',
            'modules/blockonomics/views/js/angular-resource.min.js'
        );
        $this->registerJavascript(
            'app',
            'modules/blockonomics/views/js/app.js'
        );
    }
    public function postProcess()
    {
        $cart = $this->context->cart;
        $this->display_column_left = false;
        $blockonomics = $this->module;
        $crypto = $blockonomics->getActiveCurrencies()[Tools::getValue('crypto')];

        if (!isset($cart->id) or
            $cart->id_customer == 0 or
            $cart->id_address_delivery == 0 or
            $cart->id_address_invoice == 0 or
            !$blockonomics->active
        ) {
            Tools::redirectLink(__PS_BASE_URI__ . 'order.php?step=1');
        }

        $customer = new Customer((int) $cart->id_customer);

        if (!Validate::isLoadedObject($customer)) {
            Tools::redirectLink(__PS_BASE_URI__ . 'order.php?step=1');
        }

        $cookie = $blockonomics->getContext()->cookie;
        $currency = new Currency(
            (int) (Tools::getIsset(Tools::getValue('currency_payement'))
                ? Tools::getValue('currency_payement')
                : $cookie->id_currency)
        );
        $total = (float) $cart->getOrderTotal(true, Cart::BOTH);

        // API Key not set
        if (!Configuration::get('BLOCKONOMICS_API_KEY')) {
            $this->displayError($blockonomics);
        }

        $sql = 'SELECT * FROM '._DB_PREFIX_ .
        "blockonomics_bitcoin_orders WHERE id_cart = $cart->id";
        $order = Db::getInstance()->getRow($sql);

        $sql = 'SELECT * FROM '._DB_PREFIX_ .
        "blockonomics_bitcoin_orders WHERE id_cart = $cart->id AND crypto = '" . $crypto['code'] . "'";
        $order_in_crypto = Db::getInstance()->getRow($sql);

        //if no order, or the fiat value of the cart has changed => create a new order
        if (!$order || $order['value'] != $total) {
            $current_time = time();
            $bits = $this->getBits($blockonomics, $crypto['code'], $currency, $total);
            $time_remaining = Configuration::get('BLOCKONOMICS_TIMEPERIOD');
            $responseObj = $blockonomics->getNewAddress($crypto['code']);
            if (!$responseObj->data || !isset($responseObj->data->address)) {
                $this->displayError($blockonomics, $responseObj);
            }
            $address = $responseObj->data->address;

            // Create backup cart
            $old_cart_secure_key = $cart->secure_key;
            $old_cart_customer_id = (int)$cart->id_customer;
            $cart_products = $cart->getProducts();
            $new_cart = new Cart();
            $new_cart->id_lang = $this->context->language->id;
            $new_cart->id_currency = $this->context->currency->id;
            $new_cart->add();
            foreach ($cart_products as $product) {
                $new_cart->
                    updateQty(
                        (int) $product['quantity'],
                        (int) $product['id_product'],
                        (int) $product['id_product_attribute']
                    );
            }
            if ($this->context->cookie->id_guest) {
                $guest = new Guest($this->context->cookie->id_guest);
                $new_cart->mobile_theme = $guest->mobile_theme;
            }

            // Validate the order
            $mailVars = array(
                '{bitcoin_address}' => $address,
                '{bits}' => $bits / 1.0e8,
            );

            $mes = "Adr BTC : " . $address;
            $blockonomics->validateOrder(
                (int) $cart->id,
                (int) Configuration::get('BLOCKONOMICS_ORDER_STATE_WAIT'),
                $total,
                $blockonomics->displayName,
                $mes,
                $mailVars,
                (int) $currency->id,
                false,
                $customer->secure_key
            );

            $id_order = $blockonomics->currentOrder;

            $this->addInvoiceNote($id_order, $crypto['code'], $address);
            
            // Add the backup cart to user
            $new_cart->id_customer = $old_cart_customer_id;
            $new_cart->save();
            if ($new_cart->id) {
                $this->context->cookie->id_cart = (int) $new_cart->id;
                $this->context->cookie->write();
            }
            $id_cart = (int) $new_cart->id;
            $new_cart->secure_key = $old_cart_secure_key;

            Db::getInstance()->Execute(
                "INSERT INTO " .
                    _DB_PREFIX_ .
                    "blockonomics_bitcoin_orders (id_order, id_cart, crypto, timestamp,  ".
                    "addr, txid, status,value, bits, bits_payed) VALUES
                    ('" .
                    (int) $blockonomics->currentOrder .
                    "','" .
                    (int) $id_cart .
                    "','" .
                    (string) $crypto['code'] .
                    "','" .
                    (int) $current_time .
                    "','" .
                    pSQL($address) .
                    "', '', -1,'" .
                    (float) $total .
                    "','" .
                    (int) $bits .
                    "', 0)"
            );
        //We have an order, but not in this crypto
        } elseif ($order && !$order_in_crypto) {
            $id_order = $order['id_order'];
            $id_cart = $order['id_cart'];
            $current_time = time();
            $bits = $this->getBits($blockonomics, $crypto['code'], $currency, $total);
            $time_remaining = Configuration::get('BLOCKONOMICS_TIMEPERIOD');
            $responseObj = $blockonomics->getNewAddress($crypto['code']);
            if (!$responseObj->data || !isset($responseObj->data->address)) {
                $this->displayError($blockonomics, $responseObj);
            }
            $address = $responseObj->data->address;

            $this->addInvoiceNote($id_order, $crypto['code'], $address);

            Db::getInstance()->Execute(
                "INSERT INTO " .
                    _DB_PREFIX_ .
                    "blockonomics_bitcoin_orders (id_order, id_cart, crypto, timestamp,  ".
                    "addr, txid, status,value, bits, bits_payed) VALUES
                    ('" .
                    (int) $id_order .
                    "','" .
                    (int) $id_cart .
                    "','" .
                    (string) $crypto['code'] .
                    "','" .
                    (int) $current_time .
                    "','" .
                    pSQL($address) .
                    "', '', -1,'" .
                    (float) $total .
                    "','" .
                    (int) $bits .
                    "', 0)"
            );
        //else, reuse the order we have
        } else {
            $address = $order_in_crypto['addr'];
            $id_order = $order_in_crypto['id_order'];
            $current_time = $order_in_crypto['timestamp'];
            $time_remaining = $this->getTimeRemaining($order_in_crypto);
            //if time runs out, restart the timer and fetch new crypto price
            //store prices in database so that they are "frozen" until the end of the next time period
            if (!$time_remaining) {
                $bits = $this->getBits($blockonomics, $crypto['code'], $currency, $total);
                $query = "UPDATE "._DB_PREFIX_."blockonomics_bitcoin_orders SET timestamp="
                .time().", bits=$bits WHERE id_cart = $cart->id";
                Db::getInstance()->Execute($query);
                $time_remaining = Configuration::get('BLOCKONOMICS_TIMEPERIOD');
            } else {
                $total = $order_in_crypto['value'];
                $bits = $order_in_crypto['bits'];
            }
        }

        $redirect_link =  $this->context->link->getModuleLink(
            $blockonomics->name,
            'redirect',
            array(
                'id_module' => (int)$blockonomics->id,
                'id_order' => $id_order,
                'key' => $customer->secure_key,
                'id_cart' => (int)$cart->id
                ),
            true
        );

        $base_websocket_url = ($crypto['code']  == 'bch') ?
        BlockonomicsPaymentModuleFrontController::BLOCKONOMICS_BCH_WEBSOCKET_URL :
        BlockonomicsPaymentModuleFrontController::BLOCKONOMICS_WEBSOCKET_URL;

        //Make $crypto['code'] caps before sending it to the payment.tpl
        $crypto['code'] = Tools::strtoupper($crypto['code']);

        $this->context->smarty->assign(array(
            'id_order' => (int) $id_order,
            'status' => -1,
            'addr' => $address,
            'txid' => "",
            'bits' => rtrim(sprintf('%.8f', $bits / 1.0e8), '0'),
            'value' => (float) $total,
            'base_url' => Configuration::get('BLOCKONOMICS_BASE_URL'),
            'base_websocket_url' => $base_websocket_url,
            'timestamp' => $current_time,
            'currency_iso_code' => $currency->iso_code,
            'bits_payed' => 0,
            'redirect_link' => $redirect_link,
            'timeperiod' => Configuration::get('BLOCKONOMICS_TIMEPERIOD'),
            'time_remaining' => $time_remaining,
            'crypto' => $crypto,
        ));

        $this->setTemplate(
            'module:blockonomics/views/templates/front/payment.tpl'
        );
        //Tools::redirect($this->context->link->getModuleLink($blockonomics->name, 'payment', array(), true));
        //Tools::redirectLink(Tools::getHttpHost(true, true) . __PS_BASE_URI__ .'index.php?controller=order-confirmation?id_cart='.(int)($cart->id).'&id_module='.(int)($blockonomics->id).'&id_order='.$blockonomics->currentOrder.'&key='.$customer->secure_key);
    }

    private function addInvoiceNote($id_order, $crypto, $address)
    {
        // Get invoice and add address as a note
        $presta_order = new Order($id_order);
        $invoices = $presta_order->getInvoicesCollection();
        foreach ($invoices as $invoice) {
            // Leave a space after $address since html tags don't work and perhaps two addresses will be saved
            $invoice_note = Tools::strtoupper($crypto) . " Address: $address ";
            $invoice->note = $invoice->note . "\r\n" . $invoice_note;
            $invoice->save();
        }
    }


    private function getTimeRemaining($order)
    {
        if ($order) {
            $time_remaining = ($order['timestamp'] +
            (Configuration::get('BLOCKONOMICS_TIMEPERIOD') * 60) - time()) / 60;
            if ($time_remaining > 0) {
                return $time_remaining;
            }
        }
        return false;
    }

    private function getBits($blockonomics, $crypto, $currency, $total)
    {
        $price = $blockonomics->getPrice($crypto, $currency->id);
        if (!$price) {
            Tools::redirectLink(__PS_BASE_URI__ . 'order.php?step=1');
        }
        $bits = (int) ((1.0e8 * $total) / $price);
        return $bits;
    }

    private function displayError($blockonomics, $responseObj = null)
    {
        
        $unable_to_generate = '<h3>' . $blockonomics->l(
            'Could not generate new address',
            'payment'
        ) . '</h3><p>';
        
        if (isset($responseObj)
            && isset($responseObj->data)
            && isset($responseObj->data->message)
            && (strpos(Tools::strtolower($responseObj->data->message), 'gap limit') !== false
                || strpos(Tools::strtolower($responseObj->data->message), 'temporary') !== false
            )
        ) {
            $unable_to_generate .= $responseObj->data->message;
        } else {
            $unable_to_generate .= $blockonomics->l(
                'Please use Test Setup button in configuration to diagnose the error ',
                'payment'
            );
        }

        $unable_to_generate .= "</p>";

        echo $unable_to_generate;
        die();
    }
}
