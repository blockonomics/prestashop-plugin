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
    //Include configuration from the local file.
    const BASE_URL = 'https://www.blockonomics.co';
    const BCH_BASE_URL = 'https://bch.blockonomics.co';

    const NEW_ADDRESS_PATH = '/api/new_address';
    const PRICE_PATH = '/api/price?currency=';
    const GET_CALLBACKS_PATH = '/api/address?&no_balance=true&only_xpub=true&get_callback=true';
    const SET_CALLBACK_PATH = '/api/update_callback';

    public function __construct()
    {
        $this->name = 'blockonomics';
        $this->tab = 'payments_gateways';
        $this->version = '1.7.96';
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

        $this->setShopContextAll();
        if (!Configuration::get('BLOCKONOMICS_API_KEY')) {
            $this->warning = $this->l(
                'Please specify an API Key'
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
        $this->setShopContextAll();
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
        $this->setShopContextAll();
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
            crypto varchar(3) NOT NULL,
            value double(10,2) NOT NULL,
            bits int(8) NOT NULL,
            bits_payed int(8) NOT NULL,
            id_cart INT UNSIGNED NOT NULL,
            PRIMARY KEY (id),
        UNIQUE KEY order_table (addr))"
        );

        $this->setShopContextAll();
        //Blockonomics basic configuration
        Configuration::updateValue('BLOCKONOMICS_API_KEY', '');
        Configuration::updateValue('BLOCKONOMICS_TIMEPERIOD', 10);
        Configuration::updateValue('BLOCKONOMICS_UNDERPAYMENT_SLACK', 0);
        Configuration::updateValue('BLOCKONOMICS_BTC', true);
        Configuration::updateValue('BLOCKONOMICS_BCH', false);
        Configuration::updateValue('BLOCKONOMICS_LOGO_HEIGHT', "0");

        /* Sets up shop secret */
        $secret = md5(uniqid(rand(), true));
        Configuration::updateValue('BLOCKONOMICS_CALLBACK_SECRET', $secret, false);

        return true;
    }

    public function uninstallDB()
    {
        Db::getInstance()->Execute(
            'DROP TABLE IF EXISTS `' .
                _DB_PREFIX_ .
                'blockonomics_bitcoin_orders`;'
        );

        $this->setShopContextAll();
        Configuration::deleteByName('BLOCKONOMICS_API_KEY');
        Configuration::deleteByName('BLOCKONOMICS_CALLBACK_SECRET');
        Configuration::deleteByName('BLOCKONOMICS_TIMEPERIOD');
        Configuration::deleteByName('BLOCKONOMICS_LOGO_HEIGHT');
        Configuration::deleteByName('BLOCKONOMICS_UNDERPAYMENT_SLACK');

        //We should still delete these values since older versions had them
        Configuration::deleteByName('BLOCKONOMICS_BASE_URL');
        Configuration::deleteByName('BLOCKONOMICS_WEBSOCKET_URL');
        Configuration::deleteByName('BLOCKONOMICS_PRICE_URL');
        Configuration::deleteByName('BLOCKONOMICS_NEW_ADDRESS_URL');
        Configuration::deleteByName('BLOCKONOMICS_GET_CALLBACKS_URL');
        Configuration::deleteByName('BLOCKONOMICS_SET_CALLBACK_URL');

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
        $this->setShopContextAll();
        $offlineOption = new PaymentOption();
        $active_cryptos = $this->getActiveCurrencies();
        $logoHeight = Configuration::get('BLOCKONOMICS_LOGO_HEIGHT');

        $this->context->smarty->assign('blockonomicsLogoHeight', $logoHeight);
        
        $cryptos = array();
        $logo_icons = array();
        foreach ($active_cryptos as $crypto) {
            array_push($cryptos, $crypto['name']);
            array_push(
                $logo_icons,
                _MODULE_DIR_.'blockonomics/views/img/'.$crypto['code'].'-icon.svg'
            );
        }

        $this->context->smarty->assign('blockonomicsEnabledLogos', $logo_icons);
        
        $offlineOption
            ->setModuleName('blockonomics')
            ->setCallToActionText($this->l('Pay by ' . join(' or ', $cryptos)))
            ->setLogo(_MODULE_DIR_.'blockonomics/views/img/btc-icon.svg')
            ->setAdditionalInformation(
                $this->context->smarty->fetch('module:blockonomics/views/templates/hook/logo_height.tpl')
            )
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

    public function getPrice($crypto, $id_currency)
    {
        //Getting price
        $currency = new Currency((int) $id_currency);
        $url = $this->getServerAPIURL($crypto, Blockonomics::PRICE_PATH . $currency->iso_code);
        return $this->doCurlCall($url)->data->price;
    }

    /*
     * Get new address; default crypto is btc
     */
    public function getNewAddress($crypto = 'btc', $test_mode = false)
    {
        $this->setShopContextAll();
        $new_address_url = $this->getServerAPIURL($crypto, Blockonomics::NEW_ADDRESS_PATH);
        $url = $new_address_url . "?match_callback=" . Configuration::get('BLOCKONOMICS_CALLBACK_SECRET');
        if ($test_mode) {
            $url = $url . "&reset=1";
        }
        return $this->doCurlCall($url, 'dummy');
    }

    public function getServerAPIURL($crypto, $path)
    {
        $domain = ($crypto == 'btc') ? Blockonomics::BASE_URL : Blockonomics::BCH_BASE_URL;
        return $domain . $path;
    }

    public function doCurlCall($url, $post_content = '')
    {
        $this->setShopContextAll();
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
        $this->setShopContextAll();
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
            $error_str = $this->l('Please add a new store on blockonomics\' website');
        } elseif (count($response->data) >= 1) {
            $error_str = $this->examineServerCallbackUrls($response->data, $crypto);
        }
        return $error_str;
    }

    // checks each existing xpub callback URL to update and/or use
    public function examineServerCallbackUrls($response_body, $crypto)
    {
        $this->setShopContextAll();
        $callback_secret = Configuration::get('BLOCKONOMICS_CALLBACK_SECRET');
        
        $api_url = Tools::getHttpHost(true).__PS_BASE_URI__ . 'modules/' . $this->name;
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
        $error_str = $this->l('Please add a new store on blockonomics\' website');
        return $error_str;
    }

    public function updateCallback($callback_url, $crypto, $xpub)
    {
        $set_callback_url = $this->getServerAPIURL($crypto, Blockonomics::SET_CALLBACK_PATH);
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
        $get_callback_url = $this->getServerAPIURL($crypto, Blockonomics::GET_CALLBACKS_PATH);
        $response = $this->doCurlCall($get_callback_url);
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
            $output = $this->updateSettings();

            if (!$output) {
                $error_strings = $this->testSetup();
                foreach ($error_strings as $crypto => $error_str) {
                    if ($error_str) {
                        $article_url = 'https://help.blockonomics.co/support/solutions/articles/';
                        $article_url .= '33000215104-unable-to-generate-new-address';
                        $error_str = Tools::strtoupper($crypto) .
                        ': ' . $error_str .
                        "</br>" .
                        $this->l('For more information please consult this') .
                        " <a target='_blank' href='" .
                        $article_url . "'>" .
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
            $output = $this->updateSettings();
            if (!$output) {
                $output = $this->displayConfirmation(
                    $this->l(
                        'Settings Saved'
                    )
                );
            }
        } elseif (Tools::isSubmit('generateNewSecret')) {
            $this->generateNewCallbackSecret();
        }
        return $output . $this->displayForm();
    }

    public function displayForm()
    {
        $slack_value = Configuration::get('BLOCKONOMICS_UNDERPAYMENT_SLACK');
        $fields_form = array();
        // Init Settings Fields form array; a.k.a. Settings section
        $fields_form[0]['form'] = array(
            'legend' => array(
                'title' => $this->l('Settings'),
            ),
            'input' => array(
                array(
                    'type' => 'text',
                    'label' => $this->l('API Key'),
                    'name' => 'BLOCKONOMICS_API_KEY',
                    'size' => 10,
                    'required' => true,
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('HTTP CALLBACK URL') .
                    ' <input style="display: none" type="submit" name="generateNewSecret">
                        <a style="display: inline;
                        font-size: 20px;
                        cursor: pointer;
                        text-decoration: none;"
                        class="process-icon-refresh"></a>
                    </input>',
                    'name' => 'callbackURL',
                    'disabled' => 'disabled',
                ),
                // Inserting JQuery into HelperForm for advance setting toggle
                array(
                  'type' => 'html',
                  'name' => 'settingToggle',
                  'class' => 'btn btn-default',
                  'html_content' => '<script>
                                        $(document).ready(function() {
                                          $("#advanced_title").click(function(){
                                            $("#advanced_title_1").parent().parent().toggleClass("hide");
                                            $("#advanced_title_2").parent().parent().toggleClass("hide");
                                            $("#advanced_title_3").parent().parent().toggleClass("hide");
                                            if ($("#advanced_title").text().trim() === "Advanced Settings ▼"){
                                                $("#advanced_title").text("Advanced Settings ▲");
                                            } 
                                            else {
                                                $("#advanced_title").text("Advanced Settings ▼");
                                            }
                                          });
                                        });
                                      </script>
                                      <a id="advanced_title" style="cursor: pointer; font-weight: bold">
                                        Advanced Settings &#9660
                                      </a>',
              ),                
              array(
                  'type' => 'select',
                  'label' => $this->l('Time Period'),
                  'name' => 'BLOCKONOMICS_TIMEPERIOD',
                  'desc' => $this->l('Countdown timer on payment page'),
                  'required' => false,
                  'id' => 'advanced_title_1',
                  'form_group_class' => 'hide',
                  'options' => array(
                      'query' => array(
                          array('key' => '10', 'name' => $this->l('10 minutes')),
                          array('key' => '15', 'name' => $this->l('15 minutes')),
                          array('key' => '20', 'name' => $this->l('20 minutes')),
                          array('key' => '25', 'name' => $this->l('25 minutes')),
                          array('key' => '30', 'name' => $this->l('30 minutes')),
                      ),
                      'id' => 'key',
                      'name' => 'name',
                  ),
              ),
              array(
                  'type' => 'text',
                  'label' => $this->l('Pay by bitcoin icon size'),
                  'desc' => $this->l(
                      'Size in pixels.
                       Set 0 to disable icon'
                  ),
                  'name' => 'BLOCKONOMICS_LOGO_HEIGHT',
                  'required' => false,
                  'class' => 'fixed-width-xl',
                  'form_group_class' => 'hide',
                  'id' => 'advanced_title_2',
              ),
              array(
                  'type' => 'html',
                  'name' => 'BLOCKONOMICS_UNDERPAYMENT_SLACK',
                  'label' => $this->l('Underpayment Slack %'),
                  'desc' => $this->l('Allow payments that are off by a small percentage'),
                  'required' => false,
                  'form_group_class' => 'hide',
                  'html_content' => '<input type="number" class="fixed-width-xl" id="advanced_title_3" min=0 max=20 step=0.01 name="BLOCKONOMICS_UNDERPAYMENT_SLACK" value=' . strval($slack_value) . '>',
              ),
            ),
            'submit' => array(
                'title' => $this->l('Save'),
                'name' => 'updateSettings',
                'class' => 'btn btn-default pull-right',
            ),
        );

        // Init Currencies Fields form array; a.k.a. Currencies section
        $desc = $this->l('To configure, click') .
        ' <b>'. $this->l('Get Started for Free'). '</b> ' .
        $this->l('on');

        $fields_form[1]['form'] = array(
            'legend' => array(
                'title' => $this->l('Currencies')
            ),
            'input' => array(
                array(
                    'type' => 'checkbox',
                    'label'     => $this->l('Bitcoin (BTC)'),
                    'desc'      => $desc .
                    ' <a href="https://blockonomics.co/merchants" target="_blank">
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
                    ' <a href="https://bch.blockonomics.co/merchants" target="_blank">
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
        $this->setShopContextAll();
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
            )
        );

        // Load current values for the different fields in Settings and Currencies section
        $helper->fields_value['BLOCKONOMICS_API_KEY'] = Configuration::get(
            'BLOCKONOMICS_API_KEY'
        );
        $helper->fields_value['BLOCKONOMICS_TIMEPERIOD'] = Configuration::get(
            'BLOCKONOMICS_TIMEPERIOD'
        );
        $helper->fields_value['BLOCKONOMICS_UNDERPAYMENT_SLACK'] = Configuration::get(
            'BLOCKONOMICS_UNDERPAYMENT_SLACK'
        );
        $helper->fields_value['BLOCKONOMICS_BTC'] = Configuration::get(
            'BLOCKONOMICS_BTC'
        );
        $helper->fields_value['BLOCKONOMICS_BCH'] = Configuration::get(
            'BLOCKONOMICS_BCH'
        );
        $helper->fields_value['BLOCKONOMICS_LOGO_HEIGHT'] = Configuration::get(
            'BLOCKONOMICS_LOGO_HEIGHT'
        );
        $callback_secret = Configuration::get('BLOCKONOMICS_CALLBACK_SECRET');
        if (!$callback_secret) {
            $this->generateNewCallbackSecret();
            $callback_secret = Configuration::get('BLOCKONOMICS_CALLBACK_SECRET');
        }
        $helper->fields_value['callbackURL'] = Tools::getHttpHost(true).__PS_BASE_URI__.
        'modules/' .
        $this->name .
        '/callback.php?secret=' .
        $callback_secret;
        return $helper;
    }

    public function updateSettings()
    {
        $this->setShopContextAll();
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

        $logoHeight = Tools::getValue('BLOCKONOMICS_LOGO_HEIGHT');
        if ($logoHeight) {
            $logoHeight = preg_replace("/[^0-9]/", "", $logoHeight);
        }
    
        Configuration::updateValue(
            'BLOCKONOMICS_LOGO_HEIGHT',
            $logoHeight
        );
        if (!Configuration::get('BLOCKONOMICS_API_KEY')) {
            return $this->displayError($this->l('Please specify an API Key'));
        }
        
        $underpayment_slack = Tools::getValue('BLOCKONOMICS_UNDERPAYMENT_SLACK');        
        if (0 <= $underpayment_slack && $underpayment_slack <= 20){
            Configuration::updateValue(
                'BLOCKONOMICS_UNDERPAYMENT_SLACK',
                Tools::getValue('BLOCKONOMICS_UNDERPAYMENT_SLACK')
            );
        }
        else {
            return $this->displayError($this->l('Invalid Underpayment Slack. Enter a value between 0 to 20'));
        }
    }

    public function generateNewCallbackSecret()
    {
        $this->setShopContextAll();
        $secret = md5(uniqid(rand(), true));
        Configuration::updateValue('BLOCKONOMICS_CALLBACK_SECRET', $secret);
    }

    /*
     * Ensures module settings apply to all stores
     * Fixes PS multistore issues by using single
     * Blockonomics settings for all stores
     */
    public function setShopContextAll()
    {
        // Check if the multistore feature is activated
        if (Shop::isFeatureActive()) {
            Shop::setContext(Shop::CONTEXT_ALL);
        }
    }
}
