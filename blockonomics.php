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
        $this->version = '1.7.83';
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
        $BLOCKONOMICS_TEMP_API_KEY_URL =
            $BLOCKONOMICS_BASE_URL . '/api/temp_wallet';
        $BLOCKONOMICS_TEMP_WITHDRAW_URL =
            $BLOCKONOMICS_BASE_URL . '/api/temp_withdraw_request';

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
            'BLOCKONOMICS_TEMP_API_KEY_URL',
            $BLOCKONOMICS_TEMP_API_KEY_URL
        );
        Configuration::updateValue(
            'BLOCKONOMICS_TEMP_WITHDRAW_URL',
            $BLOCKONOMICS_TEMP_WITHDRAW_URL
        );

        if (!Configuration::get('BLOCKONOMICS_API_KEY') && !Configuration::get('BLOCKONOMICS_TEMP_API_KEY')) {
            $this->warning = $this->l(
                'API Key is not provided to communicate with Blockonomics'
            );
        }
    }

    public function install()
    {
        if (!parent::install() or
            !$this->installOrder(
                'BLOCKONOMICS_ORDER_STATE_WAIT',
                'Awaiting Bitcoin Payment',
                null
            ) or
            !$this->installOrder(
                'BLOCKONOMICS_ORDER_STATUS_0',
                'Waiting for 2 Confirmations',
                null
            ) or
            !$this->installOrder(
                'BLOCKONOMICS_ORDER_STATUS_2',
                'Bitcoin Payment Confirmed',
                null
            ) or
            !$this->installDB() or
            !$this->registerHook('paymentOptions') or
            !$this->registerHook('displayPDFInvoice') or
            !$this->registerHook('invoice')
        ) {
            return false;
        }

        $this->active = true;
        return true;
    }

    public function uninstall()
    {
        if (!parent::uninstall() or
            !$this->uninstallOrder('BLOCKONOMICS_ORDER_STATE_WAIT') or
            !$this->uninstallOrder('BLOCKONOMICS_ORDER_STATUS_0') or
            !$this->uninstallOrder('BLOCKONOMICS_ORDER_STATUS_2') or
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
            uuid varchar(191) NOT NULL,
            id_cart INT UNSIGNED NOT NULL,
            PRIMARY KEY (id),
        UNIQUE KEY order_table (addr))"
        );

        //Blockonimcs basic configuration
        Configuration::updateValue('BLOCKONOMICS_API_KEY', '');
        Configuration::updateValue('BLOCKONOMICS_TIMEPERIOD', 10);
        Configuration::updateValue('BLOCKONOMICS_TEMP_API_KEY', '');
        Configuration::updateValue('BLOCKONOMICS_TEMP_WITHDRAW_AMOUNT', 0);
        Configuration::updateValue('BLOCKONOMICS_ACCEPT_ALTCOINS', false);

        //Generate callback secret + url
        $this->generatenewCallback();

        // Setup temp wallet
        $this->setupTempWallet();

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
        Configuration::deleteByName('BLOCKONOMICS_CALLBACK_URL');
        Configuration::deleteByName('BLOCKONOMICS_TIMEPERIOD');
        Configuration::deleteByName('BLOCKONOMICS_TEMP_API_KEY');
        Configuration::deleteByName('BLOCKONOMICS_TEMP_WITHDRAW_AMOUNT');

        Configuration::deleteByName('BLOCKONOMICS_BASE_URL');
        Configuration::deleteByName('BLOCKONOMICS_PRICE_URL');
        Configuration::deleteByName('BLOCKONOMICS_NEW_ADDRESS_URL');
        Configuration::deleteByName('BLOCKONOMICS_WEBSOCKET_URL');
        Configuration::deleteByName('BLOCKONOMICS_GET_CALLBACKS_URL');
        Configuration::deleteByName('BLOCKONOMICS_SET_CALLBACK_URL');
        Configuration::deleteByName('BLOCKONOMICS_TEMP_API_KEY_URL');
        Configuration::deleteByName('BLOCKONOMICS_TEMP_WITHDRAW_URL');
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
        $payment_options = array($this->getBTCPaymentOption());
        return $payment_options;
    }

    public function getBTCPaymentOption()
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

    public function getApiKey()
    {
        $api_key = Configuration::get('BLOCKONOMICS_API_KEY');
        if (!$api_key) {
            $api_key = Configuration::get('BLOCKONOMICS_TEMP_API_KEY');
        }
        return $api_key;
    }

    public function getBTCPrice($id_currency)
    {
        //Getting price
        $currency = new Currency((int) $id_currency);
        $url =
            Configuration::get('BLOCKONOMICS_PRICE_URL') . $currency->iso_code;
        return $this->doCurlCall($url)->data->price;
    }

    public function getNewAddress($test_mode = false)
    {
        $url =
            Configuration::get('BLOCKONOMICS_NEW_ADDRESS_URL') .
            "?match_callback=" .
            Configuration::get('BLOCKONOMICS_CALLBACK_SECRET');
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
                $this->getApiKey(),
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
        $error_str = '';
        $url = Configuration::get('BLOCKONOMICS_GET_CALLBACKS_URL');
        $response = $this->doCurlCall($url);

        $callback_url = Configuration::get('BLOCKONOMICS_CALLBACK_URL');

        //TODO: Check This: WE should actually check code for timeout
        if (!isset($response->response_code)) {
            $error_str = $this->l(
                'Your server is blocking outgoing HTTPS calls'
            );
        } elseif ($response->response_code == 401) {
            $error_str = $this->l('API Key is incorrect');
        } elseif ($response->response_code != 200) {
            $error_str = $response->data;
        } elseif (!isset($response->data) || count($response->data) == 0) {
            $error_str = $this->l('You have not entered an xpub');
        } elseif (count($response->data) == 1) {
            if (!$response->data[0]->callback ||
                $response->data[0]->callback == null
            ) {
                //No callback URL set, set one
                $post_content =
                    '{"callback": "' .
                    $callback_url .
                    '", "xpub": "' .
                    $response->data[0]->address .
                    '"}';
                $set_callback_url = Configuration::get(
                    'BLOCKONOMICS_SET_CALLBACK_URL'
                );
                $this->doCurlCall($set_callback_url, $post_content);
            } elseif ($response->data[0]->callback != $callback_url) {
                // Check if only secret differs
                $base_url =
                    Tools::getHttpHost(true, true) .
                    __PS_BASE_URI__ .
                    'modules/' .
                    $this->name .
                    '/callback.php';
                if (strpos($response->data[0]->callback, $base_url) !== false) {
                    //Looks like the user regenrated callback by mistake
                    //Just force Update_callback on server
                    $post_content =
                        '{"callback": "' .
                        $callback_url .
                        '", "xpub": "' .
                        $response->data[0]->address .
                        '"}';
                    $set_callback_url = Configuration::get(
                        'BLOCKONOMICS_SET_CALLBACK_URL'
                    );
                    $this->doCurlCall($set_callback_url, $post_content);
                } else {
                    $error_str = $this->l(
                        "Your have an existing callback URL. Refer instructions on integrating multiple websites"
                    );
                }
            }
        } else {
            // Check if callback url is set
            foreach ($response->data as $resObj) {
                if ($resObj->callback == $callback_url) {
                    return "";
                }
            }
            $error_str = $this->l(
                "Your have an existing callback URL. Refer instructions on integrating multiple websites"
            );
        }
        if (!$error_str) {
            //Everything OK ! Test address generation
            $response = $this->getNewAddress(true);
            if ($response->response_code != 200) {
                $error_str = $response->data->message;
            }
        }

        return $error_str;
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

    //Add Bitcoin invoice to pdf invoice
    public function hookDisplayPDFInvoice($params)
    {
        if (!$this->active) {
            return;
        }

        $b_order = Db::getInstance()->ExecuteS(
            'SELECT * FROM ' .
                _DB_PREFIX_ .
                'blockonomics_bitcoin_orders WHERE `id_order` = ' .
                (int) $params['object']->id_order .
                '  LIMIT 1'
        );

        $this->smarty->assign(array(
            'status' => (int) $b_order[0]['status'],
            'addr' => $b_order[0]['addr'],
            'txid' => $b_order[0]['txid'],
            'base_url' => Configuration::get('BLOCKONOMICS_BASE_URL'),
            'bits_payed' => $b_order[0]['bits_payed']
        ));

        return $this->display(__FILE__, 'views/templates/hook/invoice_pdf.tpl');
    }

    //Display Invoice
    public function hookInvoice($params)
    {
        $b_order = Db::getInstance()->ExecuteS(
            'SELECT * FROM ' .
                _DB_PREFIX_ .
                'blockonomics_bitcoin_orders WHERE `id_order` = ' .
                (int) $params['id_order'] .
                '  LIMIT 1'
        );

        /*
        print_r($b_order);
        */

        if ($b_order) {
            $tx_status = (int) $b_order[0]['status'];

            if ($tx_status == -1) {
                $status = 'Payment Not Received.';
            } elseif ($tx_status == 0 || $tx_status == 1) {
                $status = 'Waiting for 2 Confirmations.';
            } else {
                $status = 'Payment Confirmed.';
            }

            $this->smarty->assign(array(
                'status' => $status,
                'addr' => $b_order[0]['addr'],
                'txid' => $b_order[0]['txid'],
                'bits' => $b_order[0]['bits'],
                'base_url' => Configuration::get('BLOCKONOMICS_BASE_URL'),
                'bits_payed' => $b_order[0]['bits_payed']
            ));

            return $this->display(__FILE__, 'views/templates/hook/invoice.tpl');
        }
    }

    public function getContent()
    {
        $output = '';
        if (Tools::isSubmit("testSetup")) {
            //Save current settings before testing setup
            Configuration::updateValue(
                'BLOCKONOMICS_API_KEY',
                Tools::getValue('BLOCKONOMICS_API_KEY')
            );
            Configuration::updateValue(
                'BLOCKONOMICS_ACCEPT_ALTCOINS',
                Tools::getValue('BLOCKONOMICS_ACCEPT_ALTCOINS')
            );
            $error_str = $this->testSetup();
            if ($error_str) {
                $article_url = 'https://blockonomics.freshdesk.com/solution/articles/';
                $article_url .= '33000215104-troubleshooting-unable-to-generate-new-address';
                $error_str =
                    $error_str .
                    "</br>" .
                    $this->l('For more information please consult this ') .
                    "<a target='_blank' href='" .
                    $article_url. "'>" .
                    $this->l('troubleshooting article') .
                    "</a>";
                $output = $this->displayError($error_str);
            } else {
                $output = $this->displayConfirmation($this->l('Setup is all done!'));
                $withdraw = $this->makeWithdraw();
                if ($withdraw) {
                    $output .= $withdraw;
                };
            }
        } elseif (Tools::isSubmit('updateSettings')) {
            Configuration::updateValue(
                'BLOCKONOMICS_API_KEY',
                Tools::getValue('BLOCKONOMICS_API_KEY')
            );
            Configuration::updateValue(
                'BLOCKONOMICS_ACCEPT_ALTCOINS',
                Tools::getValue('BLOCKONOMICS_ACCEPT_ALTCOINS')
            );
            Configuration::updateValue(
                'BLOCKONOMICS_TIMEPERIOD',
                Tools::getValue('BLOCKONOMICS_TIMEPERIOD')
            );
            $output = $this->displayConfirmation(
                $this->l(
                    'Settings Saved, click on Test Setup to verify installation'
                )
            );
        } elseif (Tools::isSubmit('generateNewURL')) {
            $this->generatenewCallback();
        }

        //$output .= $this->display(__FILE__, 'views/templates/admin/backend.tpl');

        return $output . $this->displayForm();
    }

    public function displayForm()
    {
        // Get default language
        $default_lang = (int) Configuration::get('PS_LANG_DEFAULT');

        // Init Fields form array
        $fields_form = array();
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
                    'type' => 'switch', // This is an <input type="checkbox"> tag.
                    'label' => $this->l('Altcoins Integration'), // The <label> for this <input> tag.
                    'desc' => $this->l('Accept altcoins like ETH, LTC, BCH'), // Displayed next to the <input> tag.
                    'name' => 'BLOCKONOMICS_ACCEPT_ALTCOINS', // The content of the 'id' attribute of the <input> tag.
                    'required' => false, // If set to true, this option must be set.
                    'class' => 't', // The content of the 'class' attribute of the <label> tag for the <input> tag.
                    'is_bool' => true, // If set to true, this means you want to display a yes/no or true/false option.
                    // The CSS will therefore use green mark for the option value '1', and a red mark for value '2'.
                    // If set to false, this means there can be more than two radio buttons,
                    // and the option label text will be displayed instead of marks.
                    'values' => array(
                        // $values contains the data itself.
                        array(
                            'id' => 'active_on', // The content of the 'id' and 'for' attribute of the <input> tag.
                            'value' => 1, // The content of the 'value' attribute of the <input> tag.
                            'label' => $this->l('Enabled') // The <label> for this radio button.
                        ),
                        array(
                            'id' => 'active_off',
                            'value' => 0,
                            'label' => $this->l('Disabled')
                        )
                    )
                ),
                array(
                    'type' => 'select',
                    'label' => $this->l('Time Period'),
                    'name' => 'BLOCKONOMICS_TIMEPERIOD',
                    'desc' => $this->l('Countdown timer on payment page'),
                    'required' => false,
                    'options' => array(
                    'query' => array(
                        array('key' => '10', 'name' => '10 minutes'),
                        array('key' => '15', 'name' => '15 minutes'),
                        array('key' => '20', 'name' => '20 minutes'),
                        array('key' => '25', 'name' => '25 minutes'),
                        array('key' => '30', 'name' => '30 minutes'),
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
            )
        );

        // Init Fields form array
        $fields_form[1]['form'] = array(
            'legend' => array(
                'title' => $this->l('Store Info')
            ),
            'input' => array(
                array(
                    'type' => 'text',
                    'label' => $this->l('HTTP CALLBACK URL'),
                    'name' => 'callbackURL',
                    'readonly' => 'readonly'
                ),
                array(
                    'type' => 'free',
                    'label' => $this->l('Destination BTC wallet for payments'),
                    'name' => 'destinationWallet',
                    'class' => 'readonly'
                )
            ),
            'submit' => array(
                'title' => $this->l('Test Setup'),
                'name' => 'testSetup',
                'class' => 'btn btn-default pull-right'
            )
        );
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

        // Load current value
        $helper->fields_value['BLOCKONOMICS_API_KEY'] = Configuration::get(
            'BLOCKONOMICS_API_KEY'
        );
        $helper->fields_value[
            'BLOCKONOMICS_ACCEPT_ALTCOINS'
        ] = Configuration::get('BLOCKONOMICS_ACCEPT_ALTCOINS');
        $helper->fields_value['BLOCKONOMICS_TIMEPERIOD'] = Configuration::get(
            'BLOCKONOMICS_TIMEPERIOD'
        );
        $callbackurl = Configuration::get('BLOCKONOMICS_CALLBACK_URL');
        if (!$callbackurl) {
            $this->generatenewCallback();
            $callbackurl = Configuration::get('BLOCKONOMICS_CALLBACK_URL');
        }
        $helper->fields_value['callbackURL'] = $callbackurl;
        // Check the linked wallet
        $api_key = $this->getApiKey();
        if (!$api_key)
        {
            $this->setupTempWallet();
        }
        $total_received = Configuration::get(
            'BLOCKONOMICS_TEMP_WITHDRAW_AMOUNT'
        ) / 1.0e8;
        $api_key = Configuration::get(
            'BLOCKONOMICS_API_KEY'
        );
        $temp_api_key = Configuration::get(
            'BLOCKONOMICS_TEMP_API_KEY'
        );
        if ($temp_api_key && !$api_key && !($total_received > 0)){
            $wallet_message = '<p><b>Blockonomics Wallet</b> (Balance: 0 BTC)</p>
                <p>We are using a temporary wallet on Blockonomics to receive your payments.</p>
                <p>
                    To receive payments directly to your wallet (recommended) -> Follow Wizard by clicking on <i>Get Started for Free</i> on <a href="https://www.blockonomics.co/merchants" target="_blank">Merchants</a> and enter the APIKey above [<a href="https://blog.blockonomics.co/how-to-accept-bitcoin-on-prestashop-6b900396c85f">Blog Instructions</a>]
                </p>';
        }elseif ($temp_api_key && $total_received > 0) {
            $wallet_message = '<p><b>Blockonomics Wallet</b> (Balance: '.$total_received.' BTC)</p>';
            if (!$api_key) {
                $wallet_message .= '<p>
                        To withdraw, follow wizard by clicking on <i>Get Started for Free</i> on <a href="https://www.blockonomics.co/merchants" target="_blank">Merchants</a>, then enter the APIKey above [<a href="https://blog.blockonomics.co/how-to-accept-bitcoin-on-prestashop-6b900396c85f">Blog Instructions</a>]
                    </p>';
            }else{
                $wallet_message .= '<p>
                        To withdraw, Click on <b>Test Setup</b>
                    </p>';
            }
        }elseif ($api_key) {
            $wallet_message = '<p><b>Your wallet</b></p>
                <p>
                    Payments will go directly to the wallet which your setup on <a href="https://www.blockonomics.co/merchants" target="_blank">Blockonomics</a>. There is no need for withdraw
                </p>';
        }else{
            $wallet_message = '<p><b>ERROR:</b> No wallet set up</p>';
        }
        $helper->fields_value['destinationWallet'] = $wallet_message;
        return $helper->generateForm($fields_form);
    }

    public function generatenewCallback()
    {
        $secret = md5(uniqid(rand(), true));
        Configuration::updateValue('BLOCKONOMICS_CALLBACK_SECRET', $secret);
        Configuration::updateValue(
            'BLOCKONOMICS_CALLBACK_URL',
            Tools::getHttpHost(true, true) .
                __PS_BASE_URI__ .
                'modules/' .
                $this->name .
                '/callback.php?secret=' .
                $secret
        );
    }

    public function setupTempWallet()
    {
        $response = $this->getTempApiKey();
        if ($response->response_code == 200) {
            Configuration::updateValue('BLOCKONOMICS_TEMP_API_KEY', $response->apikey);
        }
    }

    public function getTempApiKey()
    {
        $callback_url = Configuration::get('BLOCKONOMICS_CALLBACK_URL');
        $url = Configuration::get(
            'BLOCKONOMICS_TEMP_API_KEY_URL'
        );
        $body = json_encode(array('callback' => $callback_url));
        $response = $this->doCurlCall($url, $body);
        $responseObj = $response->data;
        $responseObj->{'response_code'} = $response->response_code;
        return $responseObj;
    }

    public function makeWithdraw()
    {
        $api_key = $this->getApiKey();
        $temp_api_key = Configuration::get(
            'BLOCKONOMICS_TEMP_API_KEY'
        );
        if (!$api_key || !$temp_api_key || $temp_api_key == $api_key) {
            return null;
        }
        $temp_withdraw_amount = Configuration::get(
            'BLOCKONOMICS_TEMP_WITHDRAW_AMOUNT'
        );
        if ($temp_withdraw_amount > 0) {
            $url = Configuration::get(
                'BLOCKONOMICS_TEMP_WITHDRAW_URL'
            ) .'?tempkey='.$temp_api_key;
            $response = $this->doCurlCall($url, 'dummy');
            $response_code = $response->response_code;
            if ($response_code != 200) {
                $error = $this->l('Error while making withdraw: ') .$response->data->message;
                return $this->displayError($error);
            }
            Configuration::updateValue('BLOCKONOMICS_TEMP_API_KEY', null);
            Configuration::updateValue('BLOCKONOMICS_TEMP_WITHDRAW_AMOUNT', 0);
            $message = $this->l('Your funds withdraw request has been submitted. ');
            $message .= $this->l('Please check your Blockonomics registered emailid for details.');
            return $this->displayConfirmation($message);
        }
        //Configuration::updateValue('BLOCKONOMICS_TEMP_API_KEY', null); ??
        return null;
    }
}
