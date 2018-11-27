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

    if($action == 'check_order'){
      $this->check_order();
    } else if ($action == 'send_email') {
      $this->send_email();
    } else if ($action == 'fetch_limit') {
      $this->fetch_limit();
    } else if ($action == 'create_order') {
      $this->create_order();
    } else if ($action == 'info_order') {
      $this->info_order();
    }

    die();
  }

  function fetch_limit(){
    include_once dirname(__FILE__) . '/../../php' . DIRECTORY_SEPARATOR . 'Flyp.php';
    $flypFrom           = Tools::getValue('altcoin');
    $flypTo             = "BTC";
    $flypme = new FlypMe();
    $limits = $flypme->orderLimits($flypFrom, $flypTo);
    if(isset($limits)){
      echo Tools::jsonEncode($limits);
    }
  }

  function create_order(){
    include_once dirname(__FILE__) . '/../../php' . DIRECTORY_SEPARATOR . 'Flyp.php';
    $flypFrom           = Tools::getValue('altcoin');
    $flypAmount         = Tools::getValue('amount');
    $flypDestination    = Tools::getValue('address');
    $flypTo             = "BTC";
    $prestashop_order_id = Tools::getValue('order_id');
    $flypme = new FlypMe();
    $order = $flypme->orderNew($flypFrom, $flypTo, $flypAmount, $flypDestination);

    if(isset($order->order->uuid)){
      $order = $flypme->orderAccept($order->order->uuid);
      if(isset($order->deposit_address)){
        echo Tools::jsonEncode($order);
      }
    }
  }

  function check_order(){
    include_once dirname(__FILE__) . '/../../php' . DIRECTORY_SEPARATOR . 'Flyp.php';
    $flypID             = Tools::getValue('uuid');
    $flypme = new FlypMe();
    $order = $flypme->orderCheck($flypID);
    if(isset($order)){
      echo Tools::jsonEncode($order);
    }
  }

  function info_order(){
    include_once dirname(__FILE__) . '/../../php' . DIRECTORY_SEPARATOR . 'Flyp.php';
    $flypID             = Tools::getValue('uuid');
    $flypme = new FlypMe();
    $order = $flypme->orderInfo($flypID);
    if(isset($order)){
      echo Tools::jsonEncode($order);
    }
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
        'order_conf',
        $subject,
        $message,
        $customer->email,
        $customer->firstname.' '.$customer->lastname,
        null,
        null,
        null,
        null, _PS_MAIL_DIR_, false, (int)$order->id_shop
      );
    }
  }
}
