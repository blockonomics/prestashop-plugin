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

class BlockonomicsValidationModuleFrontController extends ModuleFrontController
{
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

        if (!extension_loaded('intl')) {
            $this->displayExtError($blockonomics);
        }

        $sql = 'SELECT * FROM '._DB_PREFIX_."blockonomics_bitcoin_orders WHERE id_cart = $cart->id";
        $order = Db::getInstance()->getRow($sql);

        if (!$order || $order['value'] != $total) {
            $current_time = time();
            $bits = $this->getBits($blockonomics, $currency, $total);
            $time_remaining = Configuration::get('BLOCKONOMICS_TIMEPERIOD');
            $responseObj = $blockonomics->getNewAddress();
            if (!$responseObj->data || !$responseObj->data->address) {
                $this->displayError($blockonomics);
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
            
            $invoice_note = "<b>Bitcoin Address: </b>$address";
            $sql = "UPDATE " . _DB_PREFIX_ .
            "order_invoice SET `note` = '" . $invoice_note .
            "' WHERE `id_order` = " . (int) $id_order;
            Db::getInstance()->Execute($sql);

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
                    "blockonomics_bitcoin_orders (id_order, id_cart, timestamp,  ".
                    "addr, txid, status,value, bits, bits_payed) VALUES
                    ('" .
                    (int) $blockonomics->currentOrder .
                    "','" .
                    (int) $id_cart .
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
        } else {
            $address = $order['addr'];
            $id_order = $order['id_order'];
            $current_time = $order['timestamp'];
            $time_remaining = $this->getTimeRemaining($order);
            if (!$time_remaining) {
                $bits = $this->getBits($blockonomics, $currency, $total);
                $query = "UPDATE "._DB_PREFIX_."blockonomics_bitcoin_orders SET timestamp="
                .time().", bits=$bits WHERE id_cart = $cart->id";
                Db::getInstance()->Execute($query);
                $time_remaining = Configuration::get('BLOCKONOMICS_TIMEPERIOD');
            } else {
                $total = $order['value'];
                $bits = $order['bits'];
            }
        }

        $redirect_link = __PS_BASE_URI__ .
            'index.php?controller=order-confirmation?id_cart=' .
            (int) $cart->id .
            '&id_module=' .
            (int) $blockonomics->id .
            '&id_order=' .
            $id_order .
            '&key=' .
            $customer->secure_key;
            
        $this->context->smarty->assign(array(
            'id_order' => (int) $id_order,
            'status' => -1,
            'addr' => $address,
            'txid' => "",
            'bits' => rtrim(sprintf('%.8f', $bits / 1.0e8), '0'),
            'value' => (float) $total,
            'base_url' => Configuration::get('BLOCKONOMICS_BASE_URL'),
            'base_websocket_url' => Configuration::get(
                'BLOCKONOMICS_WEBSOCKET_URL'
            ),
            'timestamp' => $current_time,
            'currency_iso_code' => $currency->iso_code,
            'bits_payed' => 0,
            'redirect_link' => $redirect_link,
            'timeperiod' => Configuration::get('BLOCKONOMICS_TIMEPERIOD'),
            'time_remaining' => $time_remaining
        ));

        $this->setTemplate(
            'module:blockonomics/views/templates/front/payment.tpl'
        );
        //Tools::redirect($this->context->link->getModuleLink($blockonomics->name, 'payment', array(), true));
        //Tools::redirectLink(Tools::getHttpHost(true, true) . __PS_BASE_URI__ .'index.php?controller=order-confirmation?id_cart='.(int)($cart->id).'&id_module='.(int)($blockonomics->id).'&id_order='.$blockonomics->currentOrder.'&key='.$customer->secure_key);
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

    private function getBits($blockonomics, $currency, $total)
    {
        $price = $blockonomics->getBTCPrice($currency->id);
        if (!$price) {
            Tools::redirectLink(__PS_BASE_URI__ . 'order.php?step=1');
        }
        $bits =(int) ((1.0e8 * $total) / $price);
        return $bits;
    }

    private function displayError($blockonomics)
    {
        $unable_to_generate =
            '<h4>' .
            $blockonomics->l(
                'Unable to generate bitcoin address.',
                'validation'
            ) .
            '</h4><p>' .
            $blockonomics->l(
                'Please use Test Setup button in configuration to diagnose the error ',
                'validation'
            );

        echo $unable_to_generate;
        die();
    }

    private function displayExtError($blockonomics)
    {
        $missing_extension =
            '<h4>' .
            $blockonomics->l(
                'Missing PHP Extension.',
                'validation'
            ) .
            '</h4><p>' .
            $blockonomics->l(
                'Please install the missing php-intl extension',
                'validation'
            );

        echo $missing_extension;
        die();
    }
}
