<h2>{$name}</h2>
<form action="{$request_uri}" method="post">
<div class="clear"></div>
<br>
<!--Call back url -->
<h3>{l s='Call Back URL :' mod='blockonomics'}</h3> 
<input readonly name="callBackUrl" type="text" value="{$callback_url}">
<br>
<div class="clear"></div>
<br>
<button name="updateCallback" value="Update" type="submit">Update Callback With New Secret</button>
<div class="clear"></div>
<br>
</form>

<form action="{$request_uri}" method="post">
<div class="clear"></div>
<br>

<!-- API Key configuration -->
<h3>{l s='API key :' mod='blockonomics'}</h3> 
<input name="apiKey" type="text" value="{$api_key}">
<br>
<div class="clear"></div>
<br>

<button name="updateApiKey" value="Update" type="submit">Update</button>
<div class="clear"></div>
<br>
</form>
