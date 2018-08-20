{*
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
 *}

<h2>{$name|escape:'htmlall':'UTF-8'}</h2>
<!--Call back url -->

<form action="{$request_uri|escape:'htmlall':'UTF-8'}" method="post">
<div class="clear"></div>
<br>
{if !$api_key}
<h3>{l s='You are few clicks away from accepting bitcoin payments'
mod='blockonomics'}</h3> 
<h3>Click on <b>Get Started for Free</b> on <a
href="https://wwww.blockonomics.co/merchants">Blockonomics Merchants</a>, complete the
wizard and copy the API Key when shown here
</h3>
{/if}
<div class="clear"></div>
<!-- API Key configuration -->
<h3>{l s='API key ' mod='blockonomics'}:</h3>
<input name="apiKey" type="text" value="{$api_key|escape:'htmlall':'UTF-8'}">
<br>
<div class="clear"></div>
<br>

<h3>{l s='HTTP CallBack URL' mod='blockonomics'} ⟳ :</h3>
<h4>{$callback_url|escape:'htmlall':'UTF-8'}</h4>
<div class="clear"></div>
<br>

<!-- Altcoin configuration -->
<h3>{l s='Altcoins ' mod='blockonomics'}:</h3>
<h4>{l s='Accept Altcoin Payments (Using Shapeshift) ' mod='blockonomics'}</h4>
<input name="altcoins" type="checkbox" value="altcoins" {$altcoins|escape:'htmlall':'UTF-8'}>
<br>
<div class="clear"></div>
<br>

<button name="updateSettings" value="Save" type="submit">{l s='Save' mod='blockonomics'}</button>
<button name="testSetup" value="Test Setup" type="submit">{l s='Test Setup' mod='blockonomics'}</button>
<div class="clear"></div>
<br>
</form>
