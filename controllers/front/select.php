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

class BlockonomicsSelectModuleFrontController extends ModuleFrontController
{
    public function setMedia()
    {
        parent::setMedia();
        $this->registerStylesheet(
            'mystyle',
            'modules/blockonomics/views/css/bootstrap-prestashop-ui-kit.css',
            array('postion' => 'head')
        );
        $this->registerStylesheet(
            'mystyle2',
            'modules/blockonomics/views/css/order.css',
            array('postion' => 'head')
        );
        $this->registerJavascript(
            'bootstrap',
            'modules/blockonomics/views/js/bootstrap.js'
        );
        $this->registerJavascript(
            'angular',
            'modules/blockonomics/views/js/angular.js'
        );
        $this->registerJavascript(
            'vendor',
            'modules/blockonomics/views/js/vendors.min.js'
        );
        $this->registerJavascript(
            'qrcode',
            'modules/blockonomics/views/js/angular-qrcode.js'
        );
        $this->registerJavascript(
            'angular-resource',
            'modules/blockonomics/views/js/angular-resource.min.js'
        );
        $this->registerJavascript(
            'app',
            'modules/blockonomics/views/js/app.js'
        );
    }

    public function postProcess()
    {
        $blockonomics = $this->module;
        $active_cryptos = $blockonomics->getActiveCurrencies();
        $this->context->smarty->assign('active_cryptos' , $active_cryptos);
        $this->setTemplate(
            'module:blockonomics/views/templates/front/select.tpl'
        );
    }

    private function displayExtError($blockonomics)
    {
        $missing_extension =
            '<h4>' .
            $blockonomics->l(
                'Missing PHP Extension.',
                'validation'
            ) .
            '</h4><p>' .
            $blockonomics->l(
                'Please install the missing php-intl extension',
                'validation'
            );

        echo $missing_extension;
        die();
    }
}
