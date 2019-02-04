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
class BlockonomicsAltcoinModuleFrontController extends ModuleFrontController
{
  public function postProcess()
  {
    $action = Tools::getValue('action');

    if($action == 'fetch_order_id'){
      $this->fetch_order_id();
    } else if ($action == 'save_uuid') {
      $this->save_uuid();
    } else if ($action == 'send_email') {
      $this->send_email();
    }
    die();
  }

  function fetch_order_id(){
    $addr = Tools::getValue('address');
    $order = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS("SELECT * FROM "._DB_PREFIX_."blockonomics_bitcoin_orders WHERE `addr` = '".pSQL($addr)."' LIMIT 1");
    $responseObj = new stdClass;
    $responseObj->id = $order[0]['id_order'];
    echo Tools::jsonEncode($responseObj);
  }

  function save_uuid(){
    $addr = Tools::getValue('address');
    $uuid = Tools::getValue('uuid');
    $order = Db::getInstance(_PS_USE_SQL_SLAVE_)->execute("UPDATE "._DB_PREFIX_."blockonomics_bitcoin_orders SET `uuid` = '".$uuid."' WHERE `addr` = '".pSQL($addr)."'");
  }

  function send_email(){

    $blockonomics = $this->module;
    $order_id = Tools::getValue('order_id');
    $order_link = Tools::getValue('order_link');
    $order_coin = Tools::getValue('order_coin');
    $order_coin_sym = Tools::getValue('order_coin_sym');
    $order = new Order($order_id);
    $subject = $order_coin . $blockonomics->l(' Payment Received', (int)$order->id_lang );
    $message = $blockonomics->l('Your payment has been received. It will take a while for the network to confirm your order.<br>To view your payment status, copy and use the link below.<br>').'<a href="'.$order_link.'">'.$order_link.'</a>';

    $id_customer=$order->id_customer;
    $customer= new Customer((int)$id_customer);

    if(Validate::isEmail($customer->email))
    {
      Mail::Send(
        (int)$order->id_lang,
        'contact',
        $subject,
        array('{message}'=>$message,
        '{email}'=>Configuration::get('PS_SHOP_EMAIL'),
        '{order_name}'=>$order_id,
        '{attached_file}'=>'None'
      ),
        $customer->email,
        null,
        null,
        null
      );
    }
  }
}