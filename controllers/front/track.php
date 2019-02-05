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
class BlockonomicsTrackModuleFrontController extends ModuleFrontController
{
    public function setMedia()
    {
        parent::setMedia();
        $this->context->controller->addJS(_PS_MODULE_DIR_.$this->module->name.'/views/js/bootstrap.js');
        $this->context->controller->addJS(_PS_MODULE_DIR_.$this->module->name.'/views/js/angular.min.js');
        $this->context->controller->addJS(_PS_MODULE_DIR_.$this->module->name.'/views/js/angular-resource.min.js');
        $this->context->controller->addJS(_PS_MODULE_DIR_.$this->module->name.'/views/js/vendors.min.js');
        $this->context->controller->addJS(_PS_MODULE_DIR_.$this->module->name.'/views/js/angular-qrcode.js');
        // $this->context->controller->addJS(_PS_MODULE_DIR_.$this->module->name.'/views/js/prestashop-ui-kit.js');
        $this->context->controller->addJS(_PS_MODULE_DIR_.$this->module->name.'/views/js/reconnecting-websocket.min.js');
        $this->context->controller->addJS(_PS_MODULE_DIR_.$this->module->name.'/views/js/app.js');
        $this->context->controller->addCSS(_PS_MODULE_DIR_.$this->module->name.'/views/css/order.css');
        $this->context->controller->addCSS(_PS_MODULE_DIR_.$this->module->name.'/views/css/cryptofont/cryptofont.min.css');
        $this->context->controller->addCSS(_PS_MODULE_DIR_.$this->module->name.'/views/css/icons/icons.css');
        // $this->context->controller->addCSS(_PS_MODULE_DIR_.$this->module->name.'/views/css/bootstrap-prestashop-ui-kit.css');
    }
    public function postProcess()
    {
        $cart = $this->context->cart;
        $this->display_column_left = false;
        $blockonomics = $this->module;

        if(Tools::getValue('uuid')){
            $this->context->smarty->assign(
                array(
                'uuid' => Tools::getValue('uuid'),
                'altcoin_ctrl_url' => $this->context->link->getModuleLink($blockonomics->name, 'altcoin', array(), true)
                )
            );

            $this->setTemplate('altcoin.tpl');
        }
    }

}
