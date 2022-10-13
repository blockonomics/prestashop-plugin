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

require dirname(__FILE__).'/../../config/config.inc.php';
require dirname(__FILE__).'/../../header.php';

$secret = Tools::getValue('secret');
$txid = Tools::getValue('txid');
$value = Tools::getValue('value');
$status = Tools::getValue('status');
$addr = Tools::getValue('addr');

//Match secret for callback
if ($secret == Configuration::get('BLOCKONOMICS_CALLBACK_SECRET')) {
    //Update status and txid for transaction
    $query="UPDATE "._DB_PREFIX_."blockonomics_bitcoin_orders SET status='".(int)$status."',txid='".pSQL($txid)."',bits_payed=".(int)$value." WHERE addr='".pSQL($addr)."'";
    $result = Db::getInstance()->Execute($query);

    if ($status >= 0) {
        $order = Db::getInstance()->ExecuteS("SELECT * FROM "._DB_PREFIX_."blockonomics_bitcoin_orders WHERE `addr` = '".pSQL($addr)."' LIMIT 1");
        if ($order) {
            if($order[0]['id_cart']){
                //Delete backup cart
                $delete_cart =
                "DELETE FROM " .
                _DB_PREFIX_ .
                "cart WHERE id_cart = '" .
                $order[0]['id_cart'] . "'";
          Db::getInstance()->Execute($delete_cart);
            );
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
                $o->setCurrentState(Configuration::get('BLOCKONOMICS_ORDER_STATUS_0'));
            } elseif ($status == 2) {
                $o->setCurrentState(Configuration::get('BLOCKONOMICS_ORDER_STATUS_2'));
                if ($order[0]['bits'] != $order[0]['bits_payed']) {
                    $o->setCurrentState(Configuration::get('PS_OS_ERROR'));
                } else {
                    $o->setCurrentState(Configuration::get('PS_OS_PAYMENT'));
                }
            }
        }
    }
}
