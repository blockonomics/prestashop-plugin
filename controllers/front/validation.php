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

/**
 * @since 1.5.0
 */
class BlockonomicsValidationModuleFrontController extends ModuleFrontController
{
    public function setMedia()
    {
        parent::setMedia();
        $this->context->controller->addJS(_PS_MODULE_DIR_.$this->module->name.'/views/js/bootstrap.js');
        $this->context->controller->addJS(_PS_MODULE_DIR_.$this->module->name.'/views/js/angular.min.js');
        $this->context->controller->addJS(_PS_MODULE_DIR_.$this->module->name.'/views/js/angular-resource.min.js');
        $this->context->controller->addJS(_PS_MODULE_DIR_.$this->module->name.'/views/js/vendors.min.js');
        $this->context->controller->addJS(_PS_MODULE_DIR_.$this->module->name.'/views/js/angular-qrcode.js');
        $this->context->controller->addJS(_PS_MODULE_DIR_.$this->module->name.'/views/js/prestashop-ui-kit.js');
        $this->context->controller->addJS(_PS_MODULE_DIR_.$this->module->name.'/views/js/reconnecting-websocket.min.js');
        $this->context->controller->addJS(_PS_MODULE_DIR_.$this->module->name.'/views/js/app.js');
        $this->context->controller->addCSS(_PS_MODULE_DIR_.$this->module->name.'/views/css/order.css');
        $this->context->controller->addCSS(_PS_MODULE_DIR_.$this->module->name.'/views/css/cryptofont/cryptofont.min.css');
        $this->context->controller->addCSS(_PS_MODULE_DIR_.$this->module->name.'/views/css/icons/icons.css');
        $this->context->controller->addCSS(_PS_MODULE_DIR_.$this->module->name.'/views/css/bootstrap-prestashop-ui-kit.css');
    }
    public function postProcess()
    {
        $cart = $this->context->cart;
        $this->display_column_left = false;
        $blockonomics = $this->module;

        if (!isset($cart->id) or $cart->id_customer == 0 or $cart->id_address_delivery == 0 or $cart->id_address_invoice == 0 or !$blockonomics->active) {
            Tools::redirectLink(__PS_BASE_URI__.'order.php?step=1');
        }

        $customer = new Customer((int)$cart->id_customer);

        if (!Validate::isLoadedObject($customer)) {
            Tools::redirectLink(__PS_BASE_URI__.'order.php?step=1');
        }

        $cookie = $blockonomics->getContext()->cookie;
        $currency = new Currency((int)(Tools::getIsset(Tools::getValue('currency_payement')) ? Tools::getValue('currency_payement') : $cookie->id_currency));
        $total = (float)($cart->getOrderTotal(true, Cart::BOTH));

        // API Key not set
        if (!Configuration::get('BLOCKONOMICS_API_KEY')) {
            $this->displayError($blockonomics);
        }

        $responseObj = $blockonomics->getNewAddress();

        if (!$responseObj->data || !$responseObj->data->address)
            $this->displayError($blockonomics);

        $new_address = $responseObj->data->address;

        $current_time = time();
        $price = $blockonomics->getBTCPrice($currency->id);

        if (!$price) {
            Tools::redirectLink(__PS_BASE_URI__.'order.php?step=1');
        }

        $bits = (int)(1.0e8*$total/$price);

        $mailVars =    array(
            '{bitcoin_address}' => $new_address,
            '{bits}' => $bits/1.0e8,
            '{track_url}' => Tools::getHttpHost(true, true) . __PS_BASE_URI__.'index.php?controller=order-confirmation?id_cart='.(int)($cart->id).'&id_module='.(int)($blockonomics->id).'&id_order='.$blockonomics->currentOrder.'&key='.$customer->secure_key
        );


        $mes = "Adr BTC : " .$new_address;
        $blockonomics->validateOrder((int)($cart->id), Configuration::get('BLOCKONOMICS_ORDER_STATE_WAIT'), $total, $blockonomics->displayName, $mes, $mailVars, (int)($currency->id), false, $customer->secure_key);


        Db::getInstance()->Execute(
            "INSERT INTO "._DB_PREFIX_."blockonomics_bitcoin_orders (id_order, timestamp,  addr, txid, status,value, bits, bits_payed) VALUES
      ('".(int)$blockonomics->currentOrder."','".(int)$current_time."','".pSQL($new_address)."', '', -1,'".(float)$total."','".(int)$bits."', 0)"
        );

        $redirect_link = '/index.php?controller=order-confirmation?id_cart='.(int)($cart->id).'&id_module='.(int)($blockonomics->id).'&id_order='.$blockonomics->currentOrder.'&key='.$customer->secure_key;

        $this->context->smarty->assign(
            array(
            'id_order' => (int)($blockonomics->currentOrder),
            'status' => -1,
            'addr' => $new_address,
            'txid' => "",
            'uuid' => Tools::getValue('uuid'),
            'bits' => rtrim(sprintf('%.8f', $bits/1.0e8), '0'),
            'value' => (float)$total,
            'base_url' => Configuration::get('BLOCKONOMICS_BASE_URL'),
            'base_websocket_url' => Configuration::get('BLOCKONOMICS_WEBSOCKET_URL'),
            'timestamp' => $current_time,
            'currency_iso_code' => $currency->iso_code,
            'bits_payed' => 0,
            'redirect_link' => $redirect_link,
            'blockonomics_altcoins' => Configuration::get('BLOCKONOMICS_ACCEPT_ALTCOINS'),
            'blockonomics_timeperiod' => 10,
            'altcoin_ctrl_url' => $this->context->link->getModuleLink($blockonomics->name, 'altcoin', array(), true)
            )
        );



        $this->setTemplate('payment.tpl');
        //Tools::redirect($this->context->link->getModuleLink($blockonomics->name, 'payment', array(), true));
        //Tools::redirectLink(Tools::getHttpHost(true, true) . __PS_BASE_URI__ .'index.php?controller=order-confirmation?id_cart='.(int)($cart->id).'&id_module='.(int)($blockonomics->id).'&id_order='.$blockonomics->currentOrder.'&key='.$customer->secure_key);
    }

    private function displayError($blockonomics)
    {
        $unable_to_generate = '<h4>'.$blockonomics->l('Unable to generate bitcoin address.', 'validation').'</h4><p>'.$blockonomics->l('Please use Test Setup button in configuration to diagnose the error ', 'validation');

        echo $unable_to_generate;
        die();
    }

}
