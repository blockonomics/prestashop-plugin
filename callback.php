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

            if ($status == 0 || $status == 1) {
                $o->setCurrentState(
                    Configuration::get('BLOCKONOMICS_ORDER_STATUS_0')
                );
            } elseif ($status == 2) {
                $id_order = $order[0]['id_order'];
                $note = getInvoiceNote($order[0]);
                $sql = "UPDATE " . _DB_PREFIX_ .
                "order_invoice SET `note` = '" . $note .
                "' WHERE `id_order` = " . (int) $id_order;
                Db::getInstance()->Execute($sql);

                $o->setCurrentState(
                    Configuration::get('BLOCKONOMICS_ORDER_STATUS_2')
                );
                if ($order[0]['bits'] > $order[0]['bits_payed']) {
                    $o->setCurrentState(Configuration::get('PS_OS_ERROR'));
                } else {
                    Context::getContext()->currency = new Currency($o->id_currency);
                    $o->setCurrentState(Configuration::get('PS_OS_PAYMENT'));
                }
            }
        } else {
            echo 'Order not found';
        }
    }
} else {
    echo 'Secret not matching';
}

function getInvoiceNote($order)
{
    $addr = $order['addr'];
    $bits = number_format($order['bits']/100000000, 8);
    $bits_payed = number_format($order['bits_payed']/100000000, 8);
    $addr_message = "<b>Bitcoin Address: </b> $addr <br>";
    $cart_value = "<b>Cart value: </b>" . $bits . " BTC <br>";
    $amount_paid = "<b>Amount paid: </b>" . $bits_payed . " BTC <br>";
    $base_url = Configuration::get('BLOCKONOMICS_BASE_URL');
    $txid = $order['txid'];
    $transaction_link = '<b>TXID: </b><a "style=word-wrap: break-word;" href='.$base_url.
    '/api/tx?txid='.$txid.'&addr=$addr> '.$txid.'</a>';
    $payment_error = '';
    if ($bits > $bits_payed) {
        $payment_error = '<br><b>Payment Error</b>: Amount paid less than cart value <br>';
    }
    $note = $addr_message . $cart_value . $amount_paid . $transaction_link . $payment_error;
    return $note;
}
