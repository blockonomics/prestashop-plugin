<?php
/**
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
 */

if (!defined('_PS_VERSION_') or !defined('_CAN_LOAD_FILES_')) {
    exit();
}

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

class Blockonomics extends PaymentModule
{
    private $html = '';
    private $postErrors = array();

    public function __construct()
    {
        $this->name = 'blockonomics';
        $this->tab = 'payments_gateways';
        $this->version = '1.7.91';
        $this->author = 'Blockonomics';
        $this->need_instance = 1;
        $this->bootstrap = true;
        $this->ps_versions_compliancy = array(
            'min' => '1.7',
            'max' => _PS_VERSION_
        );
        $this->controllers = array('validation');
        $this->module_key = '454392b952b7d0cfc55a656b3cdebb12';

        parent::__construct();

        $this->displayName = $this->l('Bitcoin - Blockonomics');
        $this->description = $this->l(
            'Module for accepting payments by bitcoin'
        );
        $this->confirmUninstall = $this->l(
            'Are you sure you want to uninstall?'
        );

        //Include configuration from the local file.
        $BLOCKONOMICS_BASE_URL = 'https://www.blockonomics.co';
        $BLOCKONOMICS_WEBSOCKET_URL = 'wss://www.blockonomics.co';
        $BLOCKONOMICS_NEW_ADDRESS_URL =
            $BLOCKONOMICS_BASE_URL . '/api/new_address';
        $BLOCKONOMICS_PRICE_URL =
            $BLOCKONOMICS_BASE_URL . '/api/price?currency=';
        $BLOCKONOMICS_GET_CALLBACKS_URL =
            $BLOCKONOMICS_BASE_URL .
            '/api/address?&no_balance=true&only_xpub=true&get_callback=true';
        $BLOCKONOMICS_SET_CALLBACK_URL =
            $BLOCKONOMICS_BASE_URL . '/api/update_callback';

        $BLOCKONOMICS_BCH_BASE_URL = 'https://bch.blockonomics.co';
        $BLOCKONOMICS_BCH_NEW_ADDRESS_URL =
            $BLOCKONOMICS_BCH_BASE_URL . '/api/new_address';
        $BLOCKONOMICS_BCH_PRICE_URL =
            $BLOCKONOMICS_BCH_BASE_URL . '/api/price?currency=';
        $BLOCKONOMICS_BCH_GET_CALLBACKS_URL =
            $BLOCKONOMICS_BCH_BASE_URL .
            '/api/address?&no_balance=true&only_xpub=true&get_callback=true';
        $BLOCKONOMICS_BCH_SET_CALLBACK_URL =
            $BLOCKONOMICS_BCH_BASE_URL . '/api/update_callback';

        Configuration::updateValue(
            'BLOCKONOMICS_BASE_URL',
            $BLOCKONOMICS_BASE_URL
        );
        Configuration::updateValue(
            'BLOCKONOMICS_PRICE_URL',
            $BLOCKONOMICS_PRICE_URL
        );
        Configuration::updateValue(
            'BLOCKONOMICS_NEW_ADDRESS_URL',
            $BLOCKONOMICS_NEW_ADDRESS_URL
        );
        Configuration::updateValue(
            'BLOCKONOMICS_WEBSOCKET_URL',
            $BLOCKONOMICS_WEBSOCKET_URL
        );
        Configuration::updateValue(
            'BLOCKONOMICS_GET_CALLBACKS_URL',
            $BLOCKONOMICS_GET_CALLBACKS_URL
        );
        Configuration::updateValue(
            'BLOCKONOMICS_SET_CALLBACK_URL',
            $BLOCKONOMICS_SET_CALLBACK_URL
        );

        Configuration::updateValue(
            'BLOCKONOMICS_BCH_BASE_URL',
            $BLOCKONOMICS_BCH_BASE_URL
        );
        Configuration::updateValue(
            'BLOCKONOMICS_BCH_PRICE_URL',
            $BLOCKONOMICS_BCH_PRICE_URL
        );
        Configuration::updateValue(
            'BLOCKONOMICS_BCH_NEW_ADDRESS_URL',
            $BLOCKONOMICS_BCH_NEW_ADDRESS_URL
        );
        Configuration::updateValue(
            'BLOCKONOMICS_BCH_GET_CALLBACKS_URL',
            $BLOCKONOMICS_BCH_GET_CALLBACKS_URL
        );
        Configuration::updateValue(
            'BLOCKONOMICS_BCH_SET_CALLBACK_URL',
            $BLOCKONOMICS_BCH_SET_CALLBACK_URL
        );

        if (!Configuration::get('BLOCKONOMICS_API_KEY')) {
            $this->warning = $this->l(
                'API Key is not provided to communicate with Blockonomics'
            );
        }
    }

    public function install()
    {
        if (!parent::install() or
            !$this->installDB() or
            !$this->installOrder('BLOCKONOMICS_ORDER_STATE_WAIT', 'Awaiting Bitcoin Payment', null) or
            !$this->registerHook('paymentOptions') or
            !$this->registerHook('actionValidateOrder')
        ) {
            return false;
        }

        $this->active = true;
        return true;
    }

    public function uninstall()
    {
        if (!parent::uninstall() or
            !$this->uninstallDB()
        ) {
            return false;
        }
        return true;
    }

    public function installOrder($key, $title, $template)
    {
        //Already existing from previous install(ignore)
        if (Configuration::get($key) > 0) {
            return true;
        }
        $orderState = new OrderState();
        $orderState->name = array_fill(0, 10, $title);
        $orderState->color = '#add8e6';
        $orderState->send_email = isset($template);
        $orderState->template = array_fill(0, 10, $template);
        $orderState->hidden = false;
        $orderState->delivery = false;
        $orderState->logable = false;
        $orderState->invoice = false;

        if (!$orderState->add()) {
            return false;
        }

        Configuration::updateValue($key, (int) $orderState->id);
        return true;
    }

    public function uninstallOrder($key)
    {
        $orderState = new OrderState();
        $orderState->id = (int) Configuration::get($key);
        $orderState->delete();
        Configuration::deleteByName($key);

        return true;
    }

    public function installDB()
    {
        Db::getInstance()->Execute(
            "CREATE TABLE IF NOT EXISTS " .
                _DB_PREFIX_ .
                "blockonomics_bitcoin_orders (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            id_order INT UNSIGNED NOT NULL,
            timestamp INT(8) NOT NULL,
            addr varchar(191) NOT NULL,
            txid varchar(191) NOT NULL,
            status int(8) NOT NULL,
            value double(10,2) NOT NULL,
            bits int(8) NOT NULL,
            bits_payed int(8) NOT NULL,
            id_cart INT UNSIGNED NOT NULL,
            PRIMARY KEY (id),
        UNIQUE KEY order_table (addr))"
        );

        //Blockonomics basic configuration
        Configuration::updateValue('BLOCKONOMICS_API_KEY', '');
        Configuration::updateValue('BLOCKONOMICS_TIMEPERIOD', 10);
        Configuration::updateValue('BLOCKONOMICS_BTC', true);
        Configuration::updateValue('BLOCKONOMICS_BCH', false);

        //Generate callback secret
        $secret = md5(uniqid(rand(), true));
        Configuration::updateValue('BLOCKONOMICS_CALLBACK_SECRET', $secret);
        return true;
    }

    public function uninstallDB()
    {
        Db::getInstance()->Execute(
            'DROP TABLE IF EXISTS `' .
                _DB_PREFIX_ .
                'blockonomics_bitcoin_orders`;'
        );
        Configuration::deleteByName('BLOCKONOMICS_API_KEY');
        Configuration::deleteByName('BLOCKONOMICS_CALLBACK_SECRET');
        Configuration::deleteByName('BLOCKONOMICS_TIMEPERIOD');
        Configuration::deleteByName('BLOCKONOMICS_BTC');
        Configuration::deleteByName('BLOCKONOMICS_BCH');

        Configuration::deleteByName('BLOCKONOMICS_BASE_URL');
        Configuration::deleteByName('BLOCKONOMICS_PRICE_URL');
        Configuration::deleteByName('BLOCKONOMICS_NEW_ADDRESS_URL');
        Configuration::deleteByName('BLOCKONOMICS_WEBSOCKET_URL');
        Configuration::deleteByName('BLOCKONOMICS_GET_CALLBACKS_URL');
        Configuration::deleteByName('BLOCKONOMICS_SET_CALLBACK_URL');

        Configuration::deleteByName('BLOCKONOMICS_BCH_BASE_URL');
        Configuration::deleteByName('BLOCKONOMICS_BCH_PRICE_URL');
        Configuration::deleteByName('BLOCKONOMICS_BCH_NEW_ADDRESS_URL');
        Configuration::deleteByName('BLOCKONOMICS_BCH_GET_CALLBACKS_URL');
        Configuration::deleteByName('BLOCKONOMICS_BCH_SET_CALLBACK_URL');
        return true;
    }

    // Display payment
    public function hookPaymentOptions($params)
    {
        if (!$this->active) {
            return;
        }

        if (!$this->checkCurrency($params['cart'])) {
            return;
        }
        $payment_options = array($this->getPaymentOption());
        return $payment_options;
    }

    public function getPaymentOption()
    {
        $offlineOption = new PaymentOption();
        $offlineOption
            ->setCallToActionText($this->l('Pay by bitcoin'))
            ->setAction(
                $this->context->link->getModuleLink(
                    $this->name,
                    'validation',
                    array(),
                    true
                )
            );
        return $offlineOption;
    }

    public function getPrice($id_currency)
    {
        //Getting price
        $currency = new Currency((int) $id_currency);
        $url =
            Configuration::get('BLOCKONOMICS_PRICE_URL') . $currency->iso_code;
        return $this->doCurlCall($url)->data->price;
    }

    /*
     * Get new address; default crypto is btc
     */
    public function getNewAddress($crypto = 'btc', $test_mode = false)
    {
        if ($crypto == 'btc') {
            $new_address_url = Configuration::get('BLOCKONOMICS_NEW_ADDRESS_URL');
        } else {
            $new_address_url = Configuration::get('BLOCKONOMICS_BCH_NEW_ADDRESS_URL');
        }
        $url = $new_address_url . "?match_callback=" . Configuration::get('BLOCKONOMICS_CALLBACK_SECRET');
        if ($test_mode) {
            $url = $url . "&reset=1";
        }
        return $this->doCurlCall($url, 'dummy');
    }

    public function doCurlCall($url, $post_content = '')
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if ($post_content) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_content);
        }
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization: Bearer ' .
                Configuration::get('BLOCKONOMICS_API_KEY'),
            'Content-type: application/x-www-form-urlencoded'
        ));

        $data = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $responseObj = new stdClass();
        $responseObj->data = Tools::jsonDecode($data);
        $responseObj->response_code = $httpcode;

        return $responseObj;
    }

    public function testSetup()
    {
        $test_results = array();
        $active_cryptos = $this->getActiveCurrencies();
        foreach (array_keys($active_cryptos) as $code) {
            $test_results[$code] = $this->testOneCrypto($code);
        }
        return $test_results;
    }

    /*
     * Get list of active crypto currencies
     */
    public function getActiveCurrencies()
    {
        $active_currencies = array();
        $blockonomics_currencies = $this->getSupportedCurrencies();
        foreach ($blockonomics_currencies as $code => $currency) {
            $enabled = Configuration::get('BLOCKONOMICS_' . Tools::strtoupper($code));
            if ($enabled) {
                $active_currencies[$code] = $currency;
            }
        }
        return $active_currencies;
    }

    /*
     * Get list of crypto currencies supported by Blockonomics
     */
    public function getSupportedCurrencies()
    {
        return array(
              'btc' => array(
                    'code' => 'btc',
                    'name' => 'Bitcoin',
                    'uri' => 'bitcoin'
              ),
              'bch' => array(
                    'code' => 'bch',
                    'name' => 'Bitcoin Cash',
                    'uri' => 'bitcoincash'
              )
          );
    }

    public function testOneCrypto($crypto)
    {
        $error_str = '';
        $response = $this->getCallbacks($crypto);
        $error_str = $this->checkCallbackUrlsOrSetOne($crypto, $response);
        if (!$error_str) {
            //Everything OK ! Test address generation
            $response = $this->getNewAddress($crypto, true);
            if ($response->response_code != 200) {
                $error_str = $response->data->message;
            }
        }

        return $error_str;
    }

    public function checkCallbackUrlsOrSetOne($crypto, $response)
    {
        //check the current callback and detect any potential errors
        $error_str = $this->checkGetCallbacksResponseCode($response);
        if (!$error_str) {
            //check callback responsebody and if needed, set the callback.
            $error_str = $this->checkGetCallbacksResponseBody($response, $crypto);
        }
        return $error_str;
    }
    
    public function checkGetCallbacksResponseCode($response)
    {
        $error_str = '';
        //TODO: Check This: WE should actually check code for timeout
        if (!isset($response->response_code)) {
            $error_str = $this->l(
                'Your server is blocking outgoing HTTPS calls'
            );
        } elseif ($response->response_code == 401) {
            $error_str = $this->l('API Key is incorrect');
        } elseif ($response->response_code != 200) {
            $error_str = $response->data;
        }
        return $error_str;
    }

    public function checkGetCallbacksResponseBody($response, $crypto)
    {
        $error_str = '';

        if (!isset($response->data) || count($response->data) == 0) {
            $error_str = $this->l("Please add a new store on blockonomics' website");
        } elseif (count($response->data) >= 1) {
            $error_str = $this->examineServerCallbackUrls($response->data, $crypto);
        }
        return $error_str;
    }

    // checks each existing xpub callback URL to update and/or use
    public function examineServerCallbackUrls($response_body, $crypto)
    {
        $callback_secret = Configuration::get('BLOCKONOMICS_CALLBACK_SECRET');
        $api_url = Context::getContext()->shop->getBaseURL(true) . 'modules/' . $this->name;
        $presta_callback_url = $api_url . '/callback.php?secret=' . $callback_secret;
        $base_url = preg_replace('/https?:\/\//', '', $api_url);
        $available_xpub = '';
        $partial_match = '';
        //Go through all xpubs on the server and examine their callback url
        foreach ($response_body as $one_response) {
            $server_callback_url = isset($one_response->callback) ? $one_response->callback : '';
            $server_base_url = preg_replace('/https?:\/\//', '', $server_callback_url);
            $xpub = isset($one_response->address) ? $one_response->address : '';
            if (!$server_callback_url) {
                // No callback
                $available_xpub = $xpub;
            } elseif ($server_callback_url == $presta_callback_url) {
                // Exact match
                return '';
            } elseif (strpos($server_base_url, $base_url) === 0) {
                // Partial Match - Only secret or protocol differ
                $partial_match = $xpub;
            }
        }
        // Use the available xpub
        if ($partial_match || $available_xpub) {
            $update_xpub = $partial_match ? $partial_match : $available_xpub;
            $this->updateCallback($presta_callback_url, $crypto, $update_xpub);
            return '';
        }
        // No match and no empty callback
        $error_str = $this->l("Please add a new store on blockonomics' website");
        return $error_str;
    }

    public function updateCallback($callback_url, $crypto, $xpub)
    {
        if ($crypto == 'btc') {
            $set_callback_url = Configuration::get('BLOCKONOMICS_SET_CALLBACK_URL');
        } else {
            $set_callback_url = Configuration::get('BLOCKONOMICS_BCH_SET_CALLBACK_URL');
        }
        $post_content =
        '{"callback": "' .
        $callback_url .
        '", "xpub": "' .
        $xpub .
        '"}';
        $this->doCurlCall($set_callback_url, $post_content);
    }

    public function getCallbacks($crypto)
    {
        if ($crypto == 'btc') {
            $url = Configuration::get('BLOCKONOMICS_GET_CALLBACKS_URL');
        } else {
            $url = Configuration::get('BLOCKONOMICS_BCH_GET_CALLBACKS_URL');
        }
        $response = $this->doCurlCall($url);
        return $response;
    }

    public function getContext()
    {
        return $this->context;
    }

    public function checkCurrency($cart)
    {
        $currency_order = new Currency((int) $cart->id_currency);
        $currencies_module = $this->getCurrency((int) $cart->id_currency);

        if (is_array($currencies_module)) {
            foreach ($currencies_module as $currency_module) {
                if ($currency_order->id == $currency_module['id_currency']) {
                    return true;
                }
            }
        }
    }

    //Add invoice to order after it's validated
    public function hookActionValidateOrder($params)
    {
        $order_object = $params['order'];
        if ($order_object->module == 'blockonomics') {
            $order_object->setInvoice(true);
        }
    }

    public function getContent()
    {
        $output = '';
        if (Tools::isSubmit("testSetup")) {
            $this->updateSettings();
            $api_key = Configuration::get('BLOCKONOMICS_API_KEY');
            //if there's no API key, give error immediately
            if (!$api_key) {
                $error_str = $this->l('API Key is not provided to communicate with Blockonomics');
                $output = $output . $this->displayError($error_str);
            //otherwise, test active cryptos
            } else {
                $error_strings = $this->testSetup();
                foreach ($error_strings as $crypto => $error_str) {
                    if ($error_str) {
                        $article_url = 'https://blockonomics.freshdesk.com/solution/articles/';
                        $article_url .= '33000215104-troubleshooting-unable-to-generate-new-address';
                        $error_str = Tools::strtoupper($crypto) .
                            ': ' . $error_str .
                            "</br>" .
                            $this->l('For more information please consult this ') .
                            "<a target='_blank' href='" .
                            $article_url. "'>" .
                            $this->l('troubleshooting article') .
                            "</a>";
                        $output = $output . $this->displayError($error_str);
                    } else {
                        $output = $output . $this->displayConfirmation(
                            Tools::strtoupper($crypto) . ': ' . $this->l('Setup is all done!')
                        );
                    }
                }
            }
        } elseif (Tools::isSubmit('updateSettings')) {
            $this->updateSettings();
            $output = $this->displayConfirmation(
                $this->l(
                    'Settings Saved, click on Test Setup to verify installation'
                )
            );
        } elseif (Tools::isSubmit('generateNewSecret')) {
            $this->generatenewCallbackSecret();
        }

        if (!Configuration::get('BLOCKONOMICS_API_KEY')) {
            $output =
                $output .
                $this->display(__FILE__, 'views/templates/admin/backend.tpl');
        }

        return $output . $this->displayForm();
    }

    public function displayForm()
    {
        $fields_form = array();
        // Init Settings Fields form array; a.k.a. Settings section
        $fields_form[0]['form'] = array(
            'legend' => array(
                'title' => $this->l('Settings')
            ),
            'input' => array(
                array(
                    'type' => 'text',
                    'label' => $this->l('API Key'),
                    'name' => 'BLOCKONOMICS_API_KEY',
                    'size' => 10,
                    'required' => true
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('HTTP CALLBACK URL ') .
                    '<input style="display: none" type="submit" name="generateNewSecret">
                        <a style="display: inline;
                        font-size: 20px;
                        cursor: pointer;
                        text-decoration: none;"
                        class="process-icon-refresh"></a>
                    </input>',
                    'name' => 'callbackURL',
                    'disabled' => 'disabled'
                ),
                array(
                    'type' => 'select',
                    'label' => $this->l('Time Period'),
                    'name' => 'BLOCKONOMICS_TIMEPERIOD',
                    'desc' => $this->l('Countdown timer on payment page'),
                    'required' => false,
                    'options' => array(
                    'query' => array(
                        array('key' => '10', 'name' => $this->l('10 minutes')),
                        array('key' => '15', 'name' => $this->l('15 minutes')),
                        array('key' => '20', 'name' => $this->l('20 minutes')),
                        array('key' => '25', 'name' => $this->l('25 minutes')),
                        array('key' => '30', 'name' => $this->l('30 minutes')),
                    ),
                        'id' => 'key',
                        'name' => 'name'
                    )
                )
            ),
            'submit' => array(
                'title' => $this->l('Save'),
                'name' => 'updateSettings',
                'class' => 'btn btn-default pull-right'
            ),
        );

        // Init Currencies Fields form array; a.k.a. Currencies section
        $desc = $this->l('To configure, click ') .
        '<b>'. $this->l('Get Started for Free'). '</b>' .
        $this->l(' on ');

        $fields_form[1]['form'] = array(
            'legend' => array(
                'title' => $this->l('Currencies')
            ),
            'input' => array(
                array(
                    'type' => 'checkbox',
                    'label'     => $this->l('Bitcoin (BTC)'),
                    'desc'      => $desc .
                    '<a href="https://blockonomics.co/merchants" target="_blank">
                    https://blockonomics.co/merchants</a>',
                    'name' => 'BLOCKONOMICS',
                    'values' => array(
                        'query' => array(
                            array(
                                'id' => 'BTC',
                                'name' => '',
                            ),
                        ),
                        'id' => 'id',
                        'name' => 'name'
                    )
                ),
                array(
                    'type'      => 'checkbox',
                    'label'     => $this->l('Bitcoin Cash (BCH)'),
                    'desc'      => $desc .
                    '<a href="https://bch.blockonomics.co/merchants" target="_blank">
                    https://bch.blockonomics.co/merchants</a>',
                    'name'      => 'BLOCKONOMICS',
                    'values' => array(
                        'query' => array(
                            array(
                                'id' => 'BCH',
                                'name' => '',
                            ),
                        ),
                        'id' => 'id',
                        'name' => 'name'
                    )
                ),
            ),
            'buttons' => array(
                'test-setup' => array(
                    'title' => $this->l('Test Setup'),
                    'name' => 'testSetup',
                    'type' => 'submit',
                    'class' => 'btn btn-default pull-right',
                    'icon' => 'process-icon-cogs',
                    ),
                ),
        );
        
        $helper = $this->generateHelper();
        return $helper->generateForm($fields_form);
    }

    public function generateHelper()
    {
        // Get default language
        $default_lang = (int) Configuration::get('PS_LANG_DEFAULT');
        $helper = new HelperForm();
        // Module, token and currentIndex
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex =
            AdminController::$currentIndex . '&configure=' . $this->name;

        // Language
        $helper->default_form_language = $default_lang;
        $helper->allow_employee_form_lang = $default_lang;

        // Title and toolbar
        $helper->title = $this->displayName;
        $helper->show_toolbar = true; // false -> remove toolbar
        $helper->toolbar_scroll = true; // yes - > Toolbar is always visible on the top of the screen.
        $helper->submit_action = 'submit' . $this->name;
        $helper->toolbar_btn = array(
            'save' => array(
                'desc' => $this->l('Save'),
                'href' =>
                    AdminController::$currentIndex .
                    '&configure=' .
                    $this->name .
                    '&save' .
                    $this->name .
                    '&token=' .
                    Tools::getAdminTokenLite('AdminModules')
            ),
            'back' => array(
                'href' =>
                    AdminController::$currentIndex .
                    '&token=' .
                    Tools::getAdminTokenLite('AdminModules'),
                'desc' => $this->l('Back to list')
            )
        );

        // Load current values for the different fields in Settings and Currencies section
        $helper->fields_value['BLOCKONOMICS_API_KEY'] = Configuration::get(
            'BLOCKONOMICS_API_KEY'
        );
        $helper->fields_value['BLOCKONOMICS_TIMEPERIOD'] = Configuration::get(
            'BLOCKONOMICS_TIMEPERIOD'
        );
        $helper->fields_value['BLOCKONOMICS_BTC'] = Configuration::get(
            'BLOCKONOMICS_BTC'
        );
        $helper->fields_value['BLOCKONOMICS_BCH'] = Configuration::get(
            'BLOCKONOMICS_BCH'
        );
        $callback_secret = Configuration::get('BLOCKONOMICS_CALLBACK_SECRET');
        if (!$callback_secret) {
            $this->generatenewCallback();
            $callback_secret = Configuration::get('BLOCKONOMICS_CALLBACK_SECRET');
        }
        $helper->fields_value['callbackURL'] = Context::getContext()->shop->getBaseURL(true).
        'modules/' .
        $this->name .
        '/callback.php?secret=' .
        $callback_secret;
        return $helper;
    }

    public function updateSettings()
    {
        Configuration::updateValue(
            'BLOCKONOMICS_API_KEY',
            Tools::getValue('BLOCKONOMICS_API_KEY')
        );
        Configuration::updateValue(
            'BLOCKONOMICS_TIMEPERIOD',
            Tools::getValue('BLOCKONOMICS_TIMEPERIOD')
        );
        Configuration::updateValue(
            'BLOCKONOMICS_BTC',
            Tools::getValue('BLOCKONOMICS_BTC')
        );
        Configuration::updateValue(
            'BLOCKONOMICS_BCH',
            Tools::getValue('BLOCKONOMICS_BCH')
        );
    }

    public function generateNewCallbackSecret()
    {
        $secret = md5(uniqid(rand(), true));
        Configuration::updateValue('BLOCKONOMICS_CALLBACK_SECRET', $secret);
    }
}
