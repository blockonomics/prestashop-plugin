<?php
/*
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
 *  @author PrestaShop SA <contact@prestashop.com>
 *  @copyright  2007-2015 PrestaShop SA
 *  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
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
        $this->registerStylesheet(
      'mystyle',
      'modules/blockonomics/views/css/bootstrap-prestashop-ui-kit.css',
      [
        'postion' => 'head'
      ]
    );
        $this->registerStylesheet(
      'mystyle1',
      'modules/blockonomics/views/css/style.css',
      [
        'postion' => 'head',
      ]
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
      'app',
      'modules/blockonomics/views/js/app.js'
    );
    //this->context->controller->addJS('/view/js/bootstrap.js');*/
        /*$this->context->controller->addJS('module:blockonomics/views/js/angular.js');
        $this->context->controller->addJS('module:blockonomics/views/js/app.js');
        $this->context->controller->addJS('module:blockonomics/views/js/vendors.min.js');
        $this->context->controller->addJS('module:blockonomics/views/js/angular-qrcode.js');
        $this->context->controller->addJS('module:blockonomics/views/js/prestashop-ui-kit.js');
        $this->context->controller->addCSS('module:blockonomics/views/css/style.css');
    $this->context->controller->addCSS('module:blockonomics/views/css/bootstrap-prestashop-ui-kit.css');*/
    }
    public function postProcess()
    {
        $cart = $this->context->cart;
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
        $new_address = $blockonomics->getNewAddress();


        if (!isset($new_address)) {
            $result = '<h4>'.$blockonomics->l('Unable to generate bitcoin address.').'</h4>'.$blockonomics->l('Note for site webmaster: Your webhost is blocking outgoing HTTP connections. Blockonomics requires an outgoing HTTP POST to generate new address. Check with your webhosting provider to allow this. If problem still persists contact').' webmaster at blockonomics.co';
            echo($result);
            die();
        }

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


        Db::getInstance()->Execute("INSERT INTO "._DB_PREFIX_."blockonomics_bitcoin_orders (id_order, timestamp,  addr, txid, status,value, bits, bits_payed) VALUES
      ('".(int)$blockonomics->currentOrder."','".(int)$current_time."','".pSQL($new_address)."', '', -1,'".(float)$total."','".(int)$bits."', 0)");

        $redirect_link = '/index.php?controller=order-confirmation?id_cart='.(int)($cart->id).'&id_module='.(int)($blockonomics->id).'&id_order='.$blockonomics->currentOrder.'&key='.$customer->secure_key;

        $this->context->smarty->assign(array(
            'id_order' => (int)($blockonomics->currentOrder),
            'status' => -1,
            'addr' => $new_address,
            'txid' => "",
            'bits' => (int)$bits,
            'value' => (float)$total,
            'base_url' => Configuration::get('BLOCKONOMICS_BASE_URL'),
            'base_websocket_url' => Configuration::get('BLOCKONOMICS_WEBSOCKET_URL'),
            'timestamp' => $current_time,
            'currency_iso_code' => $currency->iso_code,
      'bits_payed' => 0,
      'redirect_link' => $redirect_link
        ));



        $this->setTemplate('module:blockonomics/views/templates/front/payment-return.tpl');
        //Tools::redirect($this->context->link->getModuleLink($blockonomics->name, 'payment', array(), true));
        //Tools::redirectLink(Tools::getHttpHost(true, true) . __PS_BASE_URI__ .'index.php?controller=order-confirmation?id_cart='.(int)($cart->id).'&id_module='.(int)($blockonomics->id).'&id_order='.$blockonomics->currentOrder.'&key='.$customer->secure_key);
    }
}
