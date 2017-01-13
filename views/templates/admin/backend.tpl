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
<form action="{$request_uri|escape:'htmlall':'UTF-8'}" method="post">
<div class="clear"></div>
<br>
<!--Call back url -->
<h3>{l s='Call Back URL :' mod='blockonomics'}</h3> 
<input readonly name="callBackUrl" type="text" value="{$callback_url|escape:'htmlall':'UTF-8'}">
<br>
<div class="clear"></div>
<br>
<button name="updateCallback" value="Update" type="submit">Update Callback With New Secret</button>
<div class="clear"></div>
<br>
</form>

<form action="{$request_uri|escape:'htmlall':'UTF-8'}" method="post">
<div class="clear"></div>
<br>

<!-- API Key configuration -->
<h3>{l s='API key :' mod='blockonomics'}</h3> 
<input name="apiKey" type="text" value="{$api_key|escape:'htmlall':'UTF-8'}">
<br>
<div class="clear"></div>
<br>

<button name="updateApiKey" value="Update" type="submit">Update</button>
<div class="clear"></div>
<br>
</form>
