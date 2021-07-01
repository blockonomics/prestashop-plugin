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

class BlockonomicsRedirectModuleFrontController extends ModuleFrontController
{
    public function postProcess()
    {
        $id_module = Tools::getValue('id_module');
        $id_order = Tools::getValue('id_order');
        $key = Tools::getValue('key');
        $id_cart = Tools::getValue('id_cart');

        Tools::redirect(
            'index.php?controller=order-confirmation&id_cart='.
            $id_cart.
            '&id_module='.
            $id_module.
            '&id_order='.
            $id_order.
            '&key='.
            $key
        );
    }
}
