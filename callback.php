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


$secret = Tools::getValue('secret');
$txid = Tools::getValue('txid');
$value = Tools::getValue('value');
$status = Tools::getValue('status');
$addr = Tools::getValue('addr');

// Check if the multistore feature is activated
if (Shop::isFeatureActive()) {
    Shop::setContext(Shop::CONTEXT_ALL);
}
//Match secret for callback
if ($secret == Configuration::get('BLOCKONOMICS_CALLBACK_SECRET')) {
     // Update kernel initialization for Prestashop 1.7.6.1
    require_once _PS_ROOT_DIR_.'/app/AppKernel.php';
    $kernel = new \AppKernel('prod', false);
    $kernel->boot();
    
    //Update status and txid for transaction
    $query =
        "UPDATE " .
        _DB_PREFIX_ .
        "blockonomics_bitcoin_orders SET status='" .
        (int) $status .
        "',txid='" .
        pSQL($txid) .
        "',bits_payed=" .
        (int) $value .
        " WHERE addr='" .
        pSQL($addr) .
        "'";
    $result = Db::getInstance()->Execute($query);

    if ($status >= 0) {
        $order = Db::getInstance()->ExecuteS(
            "SELECT * FROM " .
                _DB_PREFIX_ .
                "blockonomics_bitcoin_orders WHERE `addr` = '" .
                pSQL($addr) .
                "' LIMIT 1"
        );
        if ($order) {
            if ($order[0]['id_cart']) {
                    //Delete backup cart
                    $delete_cart =
                    "DELETE FROM " .
                    _DB_PREFIX_ .
                    "cart WHERE id_cart = '" .
                    $order[0]['id_cart'] . "'";
                    Db::getInstance()->Execute($delete_cart);
                    //Remove id_cart from order
                    $remove_cart =
                  "UPDATE " .
                  _DB_PREFIX_ .
                  "blockonomics_bitcoin_orders SET id_cart=''" .
                  " WHERE addr='" .
                  pSQL($addr) .
                  "'";
                  Db::getInstance()->Execute($remove_cart);
            }
            //Update order status
            $o = new Order($order[0]['id_order']);
            $linked_orders = $o->getByReference($o->reference);
            $new_order_state = null;
            // Get underpayment slack
            $underpayment_slack = Configuration::get('BLOCKONOMICS_UNDERPAYMENT_SLACK')/100 * $order[0]['bits'];
            
            if ($status == 2) {
                if ($order[0]['bits'] - $underpayment_slack > $order[0]['bits_payed']) {
                    $new_order_state = Configuration::get('PS_OS_ERROR');
                } else {
                    Context::getContext()->currency = new Currency($o->id_currency);
                    $new_order_state = Configuration::get('PS_OS_PAYMENT');
                }
            }
            
            if (isset($new_order_state)) {
                foreach ($linked_orders as $linked_order) {
                    $linked_order->setCurrentState($new_order_state);
                }
                insertTXIDIntoPaymentDetails($o, $order[0]['txid'], $order[0]);
            }
        } else {
            echo 'Order not found';
        }
    }
} else {
    echo 'Secret not matching';
}

function insertTXIDIntoPaymentDetails($presta_order, $txid, $blockonomics_order)
{
    $paid_ratio = $blockonomics_order['bits_payed'] / $blockonomics_order['bits'];
    $amount = round($paid_ratio * $blockonomics_order['value'], 2);

    $presta_order = new Order($blockonomics_order['id_order']);
    $payments = $presta_order->getOrderPayments();
    if (!$payments) {
        //Too small amount was used in the payment, so the order was not set to PS_OS_PAYMENT and no payment created
        $payment_method = "Blockonomics - " . Tools::strtoupper($blockonomics_order['crypto']);
        $presta_order->addOrderPayment($amount, $payment_method, $txid);
    } elseif ($payments[0]->payment_method == "Bitcoin - Blockonomics" && !$payments[0]->transaction_id) {
        //Payment created, but the txid still hasn't been recorded
        $payments[0]->transaction_id = $txid;
        $payments[0]->payment_method = "Blockonomics - " . Tools::strtoupper($blockonomics_order['crypto']);
        $payments[0]->save();
    }
}
