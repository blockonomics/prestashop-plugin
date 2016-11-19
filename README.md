Features
--------
- Accept bitcoin payments on your website with ease
- Payments go directly in your own bitcoin wallet
- All HD wallet like trezor, blockchain.info, mycelium supported
- No approvals of API key/documentation required
- Uses [blockonomics API](https://www.blockonomics.co/views/api.html)


Prestashop Setup
-----------------
- Upload the zip using Modules and Services > Add new Module
- Locate the module in the modules list (search author blockonomics) and click on Install


Blockonomics Setup
-----------------
- Complete [blockonomics merchant wizard](https://www.blockonomics.co/views/merchants_get_started.html) 
- Get API key from Wallet Watcher > Settings
- Put this API key in prestashop module 
- Copy callback url from prestashop module and set it in blockonomics merchant tab


Try checkout product , and you will see pay with bitcoin option.
Use bitcoin to pay and enjoy !

Note that if you are running prestashop on localhost, you need Dynamic DNS/public IP pointing to your localhost.
This is because blockonomics.co will requires the callback to be a public url.
