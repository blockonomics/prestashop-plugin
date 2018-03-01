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
        $this->context->controller->addJS(_PS_MODULE_DIR_.$this->module->name.'/views/js/angular.js');
        $this->context->controller->addJS(_PS_MODULE_DIR_.$this->module->name.'/views/js/vendors.min.js');
        $this->context->controller->addJS(_PS_MODULE_DIR_.$this->module->name.'/views/js/angular-qrcode.js');
        $this->context->controller->addJS(_PS_MODULE_DIR_.$this->module->name.'/views/js/prestashop-ui-kit.js');
        $this->context->controller->addJS(_PS_MODULE_DIR_.$this->module->name.'/views/js/app.js');
        $this->context->controller->addCSS(_PS_MODULE_DIR_.$this->module->name.'/views/css/style.css');
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
            $error_str = 'API Key not set. Please login to Admin and go to Blockonomics module configuration to set you API Key.';
            $this->displayError($error_str);
        }

        $responseObj = $blockonomics->getNewAddress();

        if(isset($responseObj->status)) {
            if($responseObj->status == 500) {
                // New address gen has thrown an error
                $error_code = $responseObj->message;

                switch ($error_code) {
                    case "Could not find matching xpub":
                        $error_str = 'There is a problem in the Callback URL. Make sure that you have set your Callback URL from the PrestaShop admin Blockonomics module configuration to your Merchants > Settings.';
                        break;
                    case "This require you to add an xpub in your wallet watcher":
                        $error_str = 'There is a problem in the XPUB. Make sure that the you have added an address to Wallet Wathcer > Address Wathcer. If you have added an address make sure that it is an XPUB address and not a Bitcoin address.';
                        break;

                    default:
                        $error_str = $responseObj->message;
                }

                if(isset($error_str)) {
                    $this->displayError($error_str);
                }
            }
        } elseif(!ini_get('allow_url_fopen')) {
            // allow_url_fopen not enabled
            $error_str = 'The allow_url_fopen is not enabled, please enable this option to allow address generation.';
            $this->displayError($error_str);
  
        } elseif (!isset($responseObj)) {
            // Response empty / 401: Incorrect API Key
            $error_str = 'API Key is incorrect. Make sure that the API key set in PrestaShop admin  Blockonomics module configuration is correct.';
            $this->displayError($error_str);
        }

        $new_address = $responseObj->address;

        if (!isset($new_address)) {
            $error_str = 'Your webhost is blocking outgoing HTTPS connections. Blockonomics requires an outgoing HTTPS POST (port 443) to generate new address. Check with your webhosting provider to allow this.';
            $this->displayError($error_str);
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
            'bits' => rtrim(sprintf('%.8f', $bits/1.0e8), '0'),
            'value' => (float)$total,
            'base_url' => Configuration::get('BLOCKONOMICS_BASE_URL'),
            'base_websocket_url' => Configuration::get('BLOCKONOMICS_WEBSOCKET_URL'),
            'timestamp' => $current_time,
            'currency_iso_code' => $currency->iso_code,
            'bits_payed' => 0,
            'redirect_link' => $redirect_link
            )
        );



        $this->setTemplate('payment.tpl');
        //Tools::redirect($this->context->link->getModuleLink($blockonomics->name, 'payment', array(), true));
        //Tools::redirectLink(Tools::getHttpHost(true, true) . __PS_BASE_URI__ .'index.php?controller=order-confirmation?id_cart='.(int)($cart->id).'&id_module='.(int)($blockonomics->id).'&id_order='.$blockonomics->currentOrder.'&key='.$customer->secure_key);
    }

    private function displayError($error_str) {

        $unable_to_generate = '<h4>Unable to generate bitcoin address.</h4><p> Note for site webmaster: ';
        
        $troubleshooting_guide = '</p><p> If problem persists, please consult this troubleshooting article: <a href="https://blockonomics.freshdesk.com/support/solutions/articles/33000215104-troubleshooting-unable-to-generate-new-address" target="_blank">https://blockonomics.freshdesk.com/support/solutions/articles/33000215104-troubleshooting-unable-to-generate-new-address</a></p>';

        $error_message = $unable_to_generate . $error_str . $troubleshooting_guide;

        echo $error_message;
        die();
    }
}
