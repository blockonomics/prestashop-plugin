<?php
include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/../../header.php');

$secret = $_GET['secret'];
$txid = $_GET['txid'];
$value = $_GET['value'];
$status = $_GET['status'];
$addr = $_GET['addr'];

//Match secret for callback
if($secret == Configuration::get('BLOCKONOMICS_CALLBACK_SECRET')){
  //Update status and txid for transaction
  $query="UPDATE "._DB_PREFIX_."blockonomics_bitcoin_orders SET status='".$status."',txid='".$txid."',bits_payed=".$value." WHERE addr='".$addr."'";
  $result = Db::getInstance()->Execute($query);

  if($status == 0 || $status == 2){
    $order = Db::getInstance()->ExecuteS("SELECT * FROM "._DB_PREFIX_."blockonomics_bitcoin_orders WHERE `addr` = '".$addr."' LIMIT 1");

    //Update order status
    $o = new Order($order[0]['id_order']);

    if($status == 0){
      $o->setCurrentState(Configuration::get('BLOCKONOMICS_ORDER_STATUS_0'));
    } else if ($status == 2){
      $o->setCurrentState(Configuration::get('BLOCKONOMICS_ORDER_STATUS_2'));
      $o->setCurrentState(Configuration::get('PS_OS_PAYMENT'));
    }
  }
} 
