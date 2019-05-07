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

require dirname(__FILE__) . '/../../config/config.inc.php';
require dirname(__FILE__) . '/blockonomics.php';

$secret = Tools::getValue('secret');
$txid = Tools::getValue('txid');
$value = Tools::getValue('value');
$status = Tools::getValue('status');
$addr = Tools::getValue('addr');

function failOrder($id_cart){
  //Create order and set it to failed status
  if (Tools::version_compare(_PS_VERSION_, '1.7.1.0', '>')) {
    $order = Order::getByCartId($id_cart);
  } else {
    $id_order = Order::getOrderByCartId($id_cart);
    $order = new Order($id_order);
  }

  $order->setCurrentState(Configuration::get('PS_OS_ERROR'));
};

//Match secret for callback
if ($secret == Configuration::get('BLOCKONOMICS_CALLBACK_SECRET')) {
  $blockonomics = new Blockonomics();
  $order_id = -1;

  //Update status and txid for transaction
  if ($status == 0 || $status == 2) {
    $blockonomics_order = Db::getInstance()->ExecuteS(
      "SELECT * FROM " .
      _DB_PREFIX_ .
      "blockonomics_bitcoin_orders WHERE `addr` = '" .
      pSQL($addr) .
      "' LIMIT 1"
    );

    if ($blockonomics_order) {

      if( $blockonomics_order[0]['id_order'] == -1 || 
      $blockonomics_order[0]['id_order'] == 0 )
      {
        $cart = new Cart((int) $blockonomics_order[0]['id_cart']);

        // Store cart in context to avoid multiple instantiations in BO
        if (!Validate::isLoadedObject($context->cart)) {
          $context->cart = $cart;
        }

        $total = (float) $cart->getOrderTotal(true);

        //Match cart value to value in records
        //This is to avoid carts modified during payment process
        if($total != (float) $blockonomics_order[0]['value']){
          failOrder($cart->id);
          die();
        }

        try {
          $confirm_url = Tools::getHttpHost(true, true) . __PS_BASE_URI__ .
            'index.php?controller=order-confirmation?id_cart=' . (int) $cart->id .
            '&id_module=' . (int) $blockonomics->id . '&key=' . $customer->secure_key;

          $mailVars = array(
            '{bitcoin_address}' => $addr,
            '{bits}' => (int)$blockonomics_order[0]['bits'] / 1.0e8,
            '{track_url}' => $confirm_url
          );

          $customer = new Customer((int) $cart->id_customer);
          $mes = "Adr BTC : " . $addr;

          $currency = new Currency(
            (int) (Tools::getIsset(Tools::getValue('currency_payement'))
            ? Tools::getValue('currency_payement')
            : $cookie->id_currency)
          );

          $blockonomics->validateOrder(
            (int) $cart->id,
            Configuration::get('BLOCKONOMICS_ORDER_STATE_WAIT'),
            $total,
            $blockonomics->displayName,
            $mes,
            $mailVars,
            (int) $currency->id,
            false,
            $customer->secure_key
          );

          $order_id = $blockonomics->currentOrder;

        } catch (Exception $e) {
          failOrder($cart->id);
          die();
        }
      }
      else 
      {
        $order_id = $blockonomics_order[0]['id_order'];
      }

      //Update order status
      $order = new Order($order_id);

      if ($status == 0) {
        $order->setCurrentState(
          Configuration::get('BLOCKONOMICS_ORDER_STATUS_0')
        );
      } elseif ($status == 2) {
        $order->setCurrentState(
          Configuration::get('BLOCKONOMICS_ORDER_STATUS_2')
        );
        if ($blockonomics_order[0]['bits'] != $blockonomics_order[0]['bits_payed']) {
          $order->setCurrentState(Configuration::get('PS_OS_ERROR'));
        } else {
          $order->setCurrentState(Configuration::get('PS_OS_PAYMENT'));
        }
      }
    }
  }

  $query =
    "UPDATE " .
    _DB_PREFIX_ .
    "blockonomics_bitcoin_orders SET status='" .
    (int) $status .
    "',txid='" .
    pSQL($txid) .
    "',id_order='" .
    (int) $order_id .
    "',bits_payed=" .
    (int) $value .
    " WHERE addr='" .
    pSQL($addr) .
    "'";
  $result = Db::getInstance()->Execute($query);
}
