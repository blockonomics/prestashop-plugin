<?php
if (!defined('_PS_VERSION_') or !defined('_CAN_LOAD_FILES_'))
  exit;


class Blockonomics extends PaymentModule
{
  private $_html = '';
  private $_postErrors = array();

  public function __construct()
  {
    $this->name = 'blockonomics';
    $this->tab = 'payments_gateways';
    $this->version = '1.0.0';
    $this->author = 'Blockonomics';
    $this->need_instance = 1;
    $this->bootstrap = true;

    parent::__construct();

    $this->displayName = $this->l('Bitcoin - Blockonomics');
    $this->description = $this->l('Module for accepting payments by bitcoin.');
    $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');

    //Include configuration from the local file.
    include(dirname(__FILE__).'/blockonomics_config.php');
    Configuration::updateValue('BLOCKONOMICS_BASE_URL', $BLOCKONOMICS_BASE_URL);
    Configuration::updateValue('BLOCKONOMICS_PRICE_URL', $BLOCKONOMICS_PRICE_URL);
    Configuration::updateValue('BLOCKONOMICS_NEW_ADDRESS_URL', $BLOCKONOMICS_NEW_ADDRESS_URL);
    Configuration::updateValue('BLOCKONOMICS_WEBSOCKET_URL', $BLOCKONOMICS_WEBSOCKET_URL);

    if (!Configuration::get('BLOCKONOMICS_API_KEY'))
      $this->warning = $this->l('API Key is not provided to communicate with '.Configuration::get('BLOCKONOMICS_BASE_URL'));
  }

  public function install()
  {
    if (!parent::install() OR 
      !$this->installOrder('BLOCKONOMICS_ORDER_STATE_WAIT', 'Awaiting Bitcoin Payment', 'bitcoin_waiting') OR 
      !$this->installOrder('BLOCKONOMICS_ORDER_STATE_STATUS_0', 'Waiting for 2 Confirmations', NULL) OR 
      !$this->installOrder('BLOCKONOMICS_ORDER_STATE_STATUS_2', 'Bitcoin Payment Confirmed', NULL) OR 
      !$this->installDB() OR 
      !$this->registerHook('payment') OR 
      !$this->registerHook('paymentReturn') OR 
      !$this->registerHook('displayPDFInvoice') OR 
      !$this->registerHook('invoice'))
    {
      return false;
    }

    $this->active = true;
    return true;
  }

  public function uninstall()
  {
    if (!parent::uninstall() OR 
      !$this->uninstallOrder('BLOCKONOMICS_ORDER_STATE_WAIT') OR 
      !$this->uninstallOrder('BLOCKONOMICS_ORDER_STATE_STATUS_0') OR 
      !$this->uninstallOrder('BLOCKONOMICS_ORDER_STATE_STATUS_2') OR 
      !$this->uninstallDB())
      return false;
    return true;
  }

  function installOrder($key, $title, $template) {
    $orderState = new OrderState();
    $orderState->name = array_fill(0,10,$title);
    $orderState->color = '#add8e6';
    $orderState->send_email = isset($template);
    $orderState->template = array_fill(0,10,$template);
    $orderState->hidden = false;
    $orderState->delivery = false;
    $orderState->logable = false;
    $orderState->invoice = false;

    if (!$orderState->add())
      return false;

    if(isset($template)){
      copy(dirname(__FILE__) . '/logo.gif', dirname(__FILE__) . '/../../img/os/' . (int) $orderState->id . '.gif');

      copy(dirname(__FILE__) . '/mail/en/'.$template.'.txt', dirname(__FILE__) . '/../../mails/en/'.$template.'.txt');
      copy(dirname(__FILE__) . '/mail/en/'.$template.'.html', dirname(__FILE__) . '/../../mails/en/'.$template.'.html');

      copy(dirname(__FILE__) . '/mail/fr/'.$template.'.txt', dirname(__FILE__) . '/../../mails/fr/'.$template.'.txt');
      copy(dirname(__FILE__) . '/mail/fr/'.$template.'.html', dirname(__FILE__) . '/../../mails/fr/'.$template.'.html');
    }

    Configuration::updateValue($key, (int) $orderState->id);
    return true;
  }

  function uninstallOrder($key) {
    $orderState = new OrderState();
    $orderState->id = (int) Configuration::get($key);
    $orderState->delete();
    Configuration::deleteByName($key);

    return true;
  }

  function installDB()
  {
    Db::getInstance()->Execute("CREATE TABLE IF NOT EXISTS "._DB_PREFIX_."blockonomics_bitcoin_orders (
      id INT UNSIGNED NOT NULL AUTO_INCREMENT,
      id_order INT UNSIGNED NOT NULL,
      timestamp INT(8) NOT NULL,
      addr varchar(255) NOT NULL,
      txid varchar(255) NOT NULL,
      status int(8) NOT NULL,
      value double(10,2) NOT NULL,
      bits int(8) NOT NULL,
      bits_payed int(8) NOT NULL,
      PRIMARY KEY (id),
      UNIQUE KEY order_table (addr))");

    //Blockonimcs basic configuration
    Configuration::updateValue('BLOCKONOMICS_API_KEY', '');

    //Generate callback secret
    $secret = md5(uniqid(rand(), true));
    Configuration::updateValue('BLOCKONOMICS_CALLBACK_SECRET', $secret);
    Configuration::updateValue('BLOCKONOMICS_CALLBACK_URL', 
    Tools::getShopDomainSsl(true, true) . __PS_BASE_URI__ . 'modules/' . $this->name . '/callback.php?secret='.$secret);

    return true;
  }

  function uninstallDB()
  {
    Db::getInstance()->Execute('DROP TABLE  `' . _DB_PREFIX_ . 'blockonomics_bitcoin_orders`;');
    Configuration::deleteByName('BLOCKONOMICS_API_KEY');
    Configuration::deleteByName('BLOCKONOMICS_CALLBACK_SECRET');
    Configuration::deleteByName('BLOCKONOMICS_CALLBACK_URL');

    Configuration::deleteByName('BLOCKONOMICS_BASE_URL');
    Configuration::deleteByName('BLOCKONOMICS_PRICE_URL');
    Configuration::deleteByName('BLOCKONOMICS_NEW_ADDRESS_URL');
    Configuration::deleteByName('BLOCKONOMICS_WEBSOCKET_URL');
    return true;
  }

  // Display payment
  public function hookPayment($params)
  {
    if (!$this->active)
      return;

    if (!$this->checkCurrency($params['cart']))
      return;

    global $smarty;

    $smarty->assign(array(
      'this_path' => $this->_path,
      'this_path_ssl' => Tools::getShopDomainSsl(true, true) . __PS_BASE_URI__ . 'modules/' . $this->name . '/'
    ));

    return $this->display(__FILE__, 'views/templates/payment-selection.tpl');
  }

  function getBTCPrice($id_currency){
    //Getting price
    $currency = new Currency((int) $id_currency);
    $options = array( 'http' => array( 'method'  => 'GET') );
    $context = stream_context_create($options);
    $contents = file_get_contents(Configuration::get('BLOCKONOMICS_PRICE_URL').$currency->iso_code, false, $context);
    $priceObj = json_decode($contents);
    return $priceObj->price;
  }


  function getNewAddress(){
    $options = array(
      'http' => array(
        'header'  => array('Authorization: Bearer '.Configuration::get('BLOCKONOMICS_API_KEY'),'Content-type: application/x-www-form-urlencoded'),
        'method'  => 'POST',
        'content' => ''
      )
    );

    //Generate new address for this invoice
    $context = stream_context_create($options);
    $contents = file_get_contents(Configuration::get('BLOCKONOMICS_NEW_ADDRESS_URL'), false, $context);
    $addressObj = json_decode($contents);
    return $addressObj->address;
  }

  function checkCurrency($cart){
    $currency_order = new Currency((int) ($cart->id_currency));
    $currencies_module = $this->getCurrency((int) $cart->id_currency);
    $currency_default = Configuration::get('PS_CURRENCY_DEFAULT');

    if (is_array($currencies_module))
      foreach ($currencies_module AS $currency_module)
        if ($currency_order->id == $currency_module['id_currency'])
          return true;
  }

  public function showConfirmationPage($cart) {
    if (!$this->active)
      return;

    if (!$this->checkCurrency($cart))
      Tools::redirectLink(__PS_BASE_URI__ . 'order.php');

    global $cookie, $smarty;

    $price = $this->getBTCPrice($cart->id_currency);

    //Redirect to order page if the price is zero
    if(!$price)
      Tools::redirectLink(__PS_BASE_URI__ . 'order.php');

    //Total Cart value in bits
    $total_cost = $cart->getOrderTotal(true, Cart::BOTH);
    $bits = intval(1.0e8*$total_cost/$price);

    $smarty->assign(array(
      'nbProducts' => $cart->nbProducts(),
      'total_bits' => $bits,
      'total_btc' => $bits/1.0e8,
      'this_path_ssl' => Tools::getShopDomainSsl(true, true) . __PS_BASE_URI__ . 'modules/' . $this->name . '/'
    ));

    return $this->display(__FILE__, 'views/templates/payment-confirmation.tpl');
  }

  // Display Payment Return
  public function hookPaymentReturn($params) {
    if (!$this->active)
      return;

    /*
    print_r("Order ID<br>");
    print_r($params['objOrder']->id);
    print_r("<br>");
    print_r($params);
     */


    $state = $params['objOrder']->getCurrentState();
    if ($state == Configuration::get('BLOCKONOMICS_ORDER_STATE_WAIT') OR 
      $state == Configuration::get('BLOCKONOMICS_ORDER_STATE_STATUS_0') OR 
      $state == _PS_OS_OUTOFSTOCK_) {

      //Render invoice template
      $this->context->controller->addJS($this->_path.'views/js/bootstrap.js');
      $this->context->controller->addJS($this->_path.'views/js/angular.js');
      $this->context->controller->addJS($this->_path.'views/js/app.js');
      $this->context->controller->addJS($this->_path.'views/js/vendors.min.js');
      $this->context->controller->addJS($this->_path.'views/js/angular-qrcode.js');
      $this->context->controller->addCSS($this->_path.'views/css/style.css');

      $b_order = Db::getInstance()->ExecuteS('SELECT * FROM ' . _DB_PREFIX_ . 'blockonomics_bitcoin_orders WHERE `id_order` = ' . $params['objOrder']->id. '  LIMIT 1');
    /*
    print_r($b_order);
     */

      global $smarty;

      $smarty->assign(array(
        'total_to_pay' => Tools::displayPrice($params['total_to_pay'], $params['currencyObj'], false),
        'id_order' => $params['objOrder']->id,
        'status' => intval($b_order[0]['status']),
        'addr' => $b_order[0]['addr'],
        'txid' => $b_order[0]['txid'],
        'bits' => $b_order[0]['bits'],
        'value' => $b_order[0]['value'],
        'base_url' => Configuration::get('BLOCKONOMICS_BASE_URL'),
        'base_websocket_url' => Configuration::get('BLOCKONOMICS_WEBSOCKET_URL'),
        'timestamp' => $b_order[0]['timestamp'],
        'currency_iso_code' => $params['currencyObj']->iso_code,
        'bits_payed' => $b_order[0]['bits_payed']
      ));

      return $this->display(__FILE__, 'views/templates/payment-return.tpl');
    }
  }

  //Add Bitcoin invoice to pdf invoice
  public function hookDisplayPDFInvoice($params){
    if (!$this->active)
      return;

    $b_order = Db::getInstance()->ExecuteS('SELECT * FROM ' . _DB_PREFIX_ . 'blockonomics_bitcoin_orders WHERE `id_order` = ' .$params['object']->id_order. '  LIMIT 1');

    global $smarty;

    $smarty->assign(array(
      'status' => intval($b_order[0]['status']),
      'addr' => $b_order[0]['addr'],
      'txid' => $b_order[0]['txid'],
      'base_url' => Configuration::get('BLOCKONOMICS_BASE_URL'),
      'bits_payed' => $b_order[0]['bits_payed']
    ));

    return $this->display(__FILE__, 'views/templates/invoice_pdf.tpl');
 
  }

  //Display Invoice
  public function hookInvoice($params){

    $b_order = Db::getInstance()->ExecuteS('SELECT * FROM ' . _DB_PREFIX_ . 'blockonomics_bitcoin_orders WHERE `id_order` = ' . $params['id_order']. '  LIMIT 1');

    /*
    print_r($b_order);
     */

    global $smarty;

    $tx_status = intval($b_order[0]['status']);

    if($tx_status == -1){
      $status = 'Payment Not Received.';
    } else if($tx_status == 0){
      $status = 'Waiting for 2 Confirmations.';
    } else {
      $status = 'Payment Confirmed.';
    }

    $smarty->assign(array(
      'status' => $status,
      'addr' => $b_order[0]['addr'],
      'txid' => $b_order[0]['txid'],
      'bits' => $b_order[0]['bits'],
      'base_url' => Configuration::get('BLOCKONOMICS_BASE_URL'),
      'bits_payed' => $b_order[0]['bits_payed']
    ));

    return $this->display(__FILE__, 'views/templates/invoice.tpl');
  }

  public function getContent()
  {
    if (isset($_POST['updateApiKey'])) {
      Configuration::updateValue('BLOCKONOMICS_API_KEY', $_POST['apiKey']);
    } else if (isset($_POST['updateCallback'])) {
      //Generate new callback secret
      $secret = md5(uniqid(rand(), true));
      Configuration::updateValue('BLOCKONOMICS_CALLBACK_SECRET', $secret);
      Configuration::updateValue('BLOCKONOMICS_CALLBACK_URL', 
        Tools::getShopDomainSsl(true, true) . __PS_BASE_URI__ . 'modules/' . $this->name . '/callback.php?secret='.$secret);
    }

    global $smarty;

    $smarty->assign(array(
      'name' => $this->displayName.$this->id,
      'request_uri' => $_SERVER['REQUEST_URI'],
      'api_key' => Configuration::get('BLOCKONOMICS_API_KEY'),
      'callback_url' => Configuration::get('BLOCKONOMICS_CALLBACK_URL'),
      'token' => Tools::getAdminTokenLite("AdminOrders"),
      'this_path_ssl' => Tools::getShopDomainSsl(true, true) . __PS_BASE_URI__ . 'modules/' . $this->name . '/'
    ));


    return $this->display(__FILE__, 'views/templates/backend.tpl');
  }
}
