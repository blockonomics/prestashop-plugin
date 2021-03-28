Features
--------
- Accept bitcoin payments on your website with ease
- Payments go directly in your own bitcoin wallet
- All HD wallet like trezor, blockchain.info, mycelium supported
- No approvals of API key/documentation required
- Uses [blockonomics API](https://www.blockonomics.co/views/api.html)


Installation
-----------------
- [Installation Video Tutorial](https://www.youtube.com/watch?v=zgbgVOcjw0c)
- Upload blockonomics.zip from [releases](https://github.com/blockonomics/prestashop-plugin/releases) using Modules and Services > Add new Module.
- Locate the module in Admin > modules list (search author blockonomics) and click on Install
- Complete [blockonomics merchant wizard](https://www.blockonomics.co/merchants) to get API Key
- Put shown API key in prestashop module and *Save* settings
- Click on *Test Setup* to make sure everything is working 
- Try checkout product , and you will see pay with bitcoin option. Use bitcoin to pay and enjoy !

Languages
-------------
- Currently available in English, French, German and Spanish
- Contribution for other lanugages are welcome. Feel free to send us files from your backoffice or create a pull request


Troubleshooting
-----------------
- Not able to generate new bitcoin address: Your webhost is blocking outgoing HTTP connections. Blockonomics requires to do an outgoing POST to generate new address. Check with your webhost to allow this.
- Order page repeatedly refreshing on payment: Your webhost is blocking incoming callbacks from bots, our you have a DDOS protection in place that is causing this. Blockonomics.co uses curl to callback your server with payment notification and cannot emulate a browser accessing your website.
- Note that if you are running prestashop on localhost, you need Dynamic DNS/public IP pointing to your localhost.
This is because blockonomics.co will requires the callback to be a public url.

Screenshots
-----------------
#### Checkout Page 
![checkout page](views/img/screenshot-3.png)
#### Order Details 
![order details](views/img/screenshot-2.png)
#### Admin Orders Page 
![admin orders page](views/img/screenshot-1.png)
