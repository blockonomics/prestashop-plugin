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

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_7_9($object, $install = false)
{
    $object = $object;
    $install = $install;
    updateDatabase();
    return true; //if there were no errors
}

//function used to upgrade the module table
function updateDatabase()
{
    $query = 'ALTER TABLE `'._DB_PREFIX_.'blockonomics_bitcoin_orders'.'` MODIFY `addr` varchar(191) NOT NULL';
    if (!Db::getInstance()->execute($query)) {
        return false;
    }
    $query = 'ALTER TABLE `'._DB_PREFIX_.'blockonomics_bitcoin_orders'.'` MODIFY `uuid` varchar(191) NOT NULL';
    if (!Db::getInstance()->execute($query)) {
        return false;
    }
    $query = 'ALTER TABLE `'._DB_PREFIX_.'blockonomics_bitcoin_orders'.'` MODIFY `txid` varchar(191) NOT NULL';
    if (!Db::getInstance()->execute($query)) {
        return false;
    }
    $sql = 'DESCRIBE '._DB_PREFIX_.'blockonomics_bitcoin_orders';
    $columns = Db::getInstance()->executeS($sql);
    $found = false;
    foreach ($columns as $col) {
        if ($col['Field']=='id_cart') {
            $found = true;
            break;
        }
    }
    if (!$found) {
        $query = 'ALTER TABLE `'._DB_PREFIX_.'blockonomics_bitcoin_orders'.'` ADD `id_cart` INT UNSIGNED NOT NULL';
        if (!Db::getInstance()->execute($query)) {
            return false;
        }
    }
    return true;
}
