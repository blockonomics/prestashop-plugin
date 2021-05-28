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

class BlockonomicsValidationModuleFrontController extends ModuleFrontController
{
    public function postProcess()
    {
        $blockonomics = $this->module;
        if (!extension_loaded('intl')) {
            $this->displayExtError($blockonomics);
        }
        $active_cryptos = $blockonomics->getActiveCurrencies();
        // Check how many crypto currencies are activated
        if (count($active_cryptos) > 1) {
            Tools::redirect($this->context->link->getModuleLink($blockonomics->name, 'select', array(), true));
        } elseif (count($active_cryptos) === 1) {
            $crypto = array("crypto"=>array_keys($active_cryptos)[0]);
            Tools::redirect($this->context->link->getModuleLink($blockonomics->name, 'payment', $crypto, true));
        } elseif (count($active_cryptos) === 0) {
            $this->setTemplate('module:blockonomics/views/templates/front/no_crypto.tpl');
        }
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
