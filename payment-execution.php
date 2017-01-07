<?php

include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/../../header.php');
include(dirname(__FILE__).'/blockonomics.php');

$blockonomics = new Blockonomics();
if (!isset($cart->id) OR $cart->id_customer == 0 OR $cart->id_address_delivery == 0 OR $cart->id_address_invoice == 0 OR !$blockonomics->active)
	Tools::redirectLink(__PS_BASE_URI__.'order.php?step=1');

$customer = new Customer((int)$cart->id_customer);

if (!Validate::isLoadedObject($customer))
	Tools::redirectLink(__PS_BASE_URI__.'order.php?step=1');

$verif = Db::getInstance()->ExecuteS("SELECT * FROM "._DB_PREFIX_."blockonomics_bitcoin_orders WHERE `id_order` = ".$cart->id." LIMIT 1");

if (isset ($verif[0]["id_order"])){
  echo 'Basket Already Register';
    die();
}

$currency = new Currency((int)(isset($_POST['currency_payement']) ? $_POST['currency_payement'] : $cookie->id_currency));
$total = (float)($cart->getOrderTotal(true, Cart::BOTH));
$new_address = $blockonomics->getNewAddress();

/*
print_r($new_address);
echo "<br/><br/><br/><br/>";
print_r($cart);
 */

if (!isset($new_address)){
    echo 'Not able to generate new bitcoin address at this time.';
    die();
}

$current_time = time();
$price = $blockonomics->getBTCPrice($currency->id);

if(!$price)
	Tools::redirectLink(__PS_BASE_URI__.'order.php?step=1');

$bits = intval(1.0e8*$total/$price);

$mailVars =	array(
	'{bitcoin_address}' => $new_address,
	'{bits}' => $bits/1.0e8,
	'{track_url}' => Tools::getHttpHost(true, true) . __PS_BASE_URI__.'index.php?controller=order-confirmation?id_cart='.(int)($cart->id).'&id_module='.(int)($blockonomics->id).'&id_order='.$blockonomics->currentOrder.'&key='.$customer->secure_key
        );

/*
print_r($mailVars);
 */

$mes = "Adr BTC : " .$new_address;
$blockonomics->validateOrder((int)($cart->id), Configuration::get('BLOCKONOMICS_ORDER_STATE_WAIT'), $total, $blockonomics->displayName, $mes, $mailVars, (int)($currency->id), false, $customer->secure_key);


Db::getInstance()->Execute("INSERT INTO "._DB_PREFIX_."blockonomics_bitcoin_orders (id_order, timestamp,  addr, txid, status,value, bits, bits_payed) VALUES
    ('".$blockonomics->currentOrder."','".$current_time."','".$new_address."', '', -1,'".$total."','".$bits."', 0)");

Tools::redirectLink(Tools::getHttpHost(true, true) . __PS_BASE_URI__ .'index.php?controller=order-confirmation?id_cart='.(int)($cart->id).'&id_module='.(int)($blockonomics->id).'&id_order='.$blockonomics->currentOrder.'&key='.$customer->secure_key);
?>
