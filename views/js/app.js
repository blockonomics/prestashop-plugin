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
service = angular.module("shoppingcart.services", ["ngResource"]);

var flyp_base = 'https://flyp.me/api/v1';

service.factory('AltcoinNew', function($resource) {
    var rsc = $resource(flyp_base + '/order/new');
    return rsc;
});

service.factory('AltcoinAccept', function($resource) {
    var rsc = $resource(flyp_base + '/order/accept');
    return rsc;
});

service.factory('AltcoinCheck', function($resource) {
    var rsc = $resource(flyp_base + '/order/check');
    return rsc;
});

service.factory('AltcoinInfo', function($resource) {
    var rsc = $resource(flyp_base + '/order/info');
    return rsc;
});

service.factory('AltcoinAddRefund', function($resource) {
    var rsc = $resource(flyp_base + '/order/addrefund');
    return rsc;
});

service.factory('AltcoinLimits', function($resource) {
    var rsc = $resource(flyp_base + '/order/limits/:coin/BTC', {
        coin: '@coin'
    });
    return rsc;
});

service.factory('Ajax', function($resource) {
    var rsc = $resource(ajax_url);
    return rsc;
});

app = angular.module("blockonomics-invoice", ["monospaced.qrcode",  "shoppingcart.services"]);

app.config(function ($interpolateProvider) {

    $interpolateProvider.startSymbol('//');
    $interpolateProvider.endSymbol('//');
})

app.controller("CheckoutController", function($window, $scope, $location, $interval, $rootScope, $httpParamSerializer, $timeout, Ajax, AltcoinLimits, AltcoinNew, AltcoinAccept) {
    var totalProgress = 100;
    var totalTime = 10*60; //10m
    $scope.progress = totalProgress;
    $scope.clock = totalTime;

  $scope.alt_track_url = function(uuid) {
      params = {};
      params.uuid = uuid;
      url = track_url;
      var serializedParams = $httpParamSerializer(params);
      if (serializedParams.length > 0) {
          url += ((url.indexOf('?') === -1) ? '?' : '&') + serializedParams;
      }
      return url;
  }

    $scope.tick = function() {
        $scope.clock = $scope.clock-1;
        $scope.progress = Math.floor($scope.clock*totalProgress/totalTime);

        if($scope.progress == 0){
            //Refresh invoice page
            $window.location.reload();
        }
    };

  var interval;
  var send_email = false;

    $scope.pay_altcoins = function() {
        $interval.cancel(interval);
        $interval.cancel($scope.alt_tick_interval);
        $scope.altaddress = '';
        $scope.altamount = '';
        $scope.altcoin_waiting = true;
        $scope.alt_clock = 600;
        send_email = true;
        var altcoin = getAltKeyByValue($scope.altcoins, $scope.altcoinselect);
        $scope.order.altsymbol = getAltKeyByValue($scope.altcoins, $scope.altcoinselect);
        var amount = $scope.satoshi;
        var address = $scope.address;
        var order_id = $scope.id_order;
        create_order(altcoin, amount, address, order_id);
    }

    //Create the altcoin order
    function create_order(altcoin, amount, address, order_id) {
        (function(promises) {
            return new Promise((resolve, reject) => {
                //Wait for both the altcoin limits and new altcoin order uuid
                Promise.all(promises)
                    .then(values => {
                        var alt_minimum = values[0].min;
                        var alt_maximum = values[0].max;
                        //Compare the min/max limits for altcoin payments with the order amount
                        if(amount <= alt_minimum) {
                            //Order amount too low for altcoin payment
                            window.location = $scope.alt_track_url('low');
                        }else if(amount >= alt_maximum) {
                            //Order amount too high for altcoin payment
                            window.location = $scope.alt_track_url('high');
                        }else{
                            var uuid = values[1].order.uuid;
                            //Save the altcoin uuid to database
                            Ajax.get({
                                action: 'save_uuid',
                                address: values[1]['order']['destination'],
                                uuid: values[1]['order']['uuid']
                            });
                            //Accept the altcoin order using the uuid
                            AltcoinAccept.save({
                                    "uuid": uuid
                                },function(order_accept) {
                                    window.location = $scope.alt_track_url(values[1]['order']['uuid']);
                                });
                        }
                    })
                    .catch(err => {
                        console.dir(err);
                        throw err;
                    });
            });
        })([
            new Promise((resolve, reject) => {
                //Fetch altcoin min/max limits
                AltcoinLimits.get({coin: altcoin},function(order_limits) {
                    resolve(order_limits);
                });
            }),
            new Promise((resolve, reject) => {
                //Create the new altcoin order
                AltcoinNew.save({
                        "order": {
                            "from_currency": altcoin,
                            "to_currency": "BTC",
                            "ordered_amount": amount,
                            "destination": address
                        }
                    },function(order_new) {
                        //Resolve the new altcoin order uuid
                        resolve(order_new);
                    });
            })
        ]);
    }

    $scope.init = function(invoice_status, invoice_addr, invoice_timestamp, base_websocket_url, final_url, invoice_satoshi){

    $scope.address = invoice_addr;
    $scope.satoshi = invoice_satoshi;

    if(invoice_status == -1){
        $scope.tick_interval  = $interval($scope.tick, 1000);
        var ws = new WebSocket(base_websocket_url+"/payment/" + invoice_addr + "?timestamp=" + invoice_timestamp);
        ws.onmessage = function (evt) {
            $window.location = final_url;
        }
      }
    }

  function getAltKeyByValue(object, value) {
    return Object.keys(object).find(key => object[key] === value);
  }

  $scope.copyshow = false;
  //Order Form Copy To Clipboard
  $scope.btc_address_click = function() {
      var copyText = document.getElementById("bnomics-address-input");
      copyText.select();
      document.execCommand("copy");
      //Open Message
      $scope.copyshow = true;
      $timeout(function() {
          $scope.copyshow = false;
      }, 2000);
  }

    $scope.altcoins = {
      "ETH": "Ethereum",
      "LTC": "Litecoin"
    };
});

//AltcoinController
app.controller('AltcoinController', function($scope, $interval, AltcoinCheck, AltcoinInfo, AltcoinAddRefund, Ajax, $timeout) {
    var totalProgress = 100;
    var alt_totalTime = 0;
    var interval;
    var send_email = false;
    $scope.altsymbol = 'ETH';
    var address_present = false;

    $scope.alt_tick = function() {
        $scope.alt_clock = $scope.alt_clock - 1;
        $scope.alt_progress = Math.floor($scope.alt_clock * totalProgress / alt_totalTime);
        if ($scope.alt_clock < 0) {
            $scope.alt_clock = 0;
            //Order expired
            $interval.cancel($scope.alt_tick_interval);
        }
        $scope.alt_progress = Math.floor($scope.alt_clock * totalProgress / alt_totalTime);
    };

    function sendEmail() {
        Ajax.get({
            action: 'send_email',
            order_id: $scope.id_order,
            order_link: $scope.pagelink,
            order_coin: $scope.altcoinselect,
            order_coin_sym: $scope.altsymbol
        });
        send_email = false;
    }

    function updateAltcoinStatus(status, cancel_interval = false) {
        $scope.altstatus = status;
        if (cancel_interval == true) {
            $interval.cancel(interval);
        }
    }

    function startCheckOrder(uuid) {
        interval = $interval(function(response) {
            checkOrder(uuid);
        }, 10000);
    }

    function checkOrder(uuid) {
        var response = AltcoinCheck.save({
                'uuid': uuid
            })
            .$promise.then(function successCallback(data) {
                var payment_status = data['payment_status'];
                switch (payment_status) {
                    case "PAYMENT_RECEIVED":
                    case "PAYMENT_CONFIRMED":
                        updateAltcoinStatus('received', true);
                        break;
                    case "OVERPAY_RECEIVED":
                    case "UNDERPAY_RECEIVED":
                    case "OVERPAY_CONFIRMED":
                    case "UNDERPAY_CONFIRMED":
                        var status = data['status'];
                        switch (status) {
                            case "EXPIRED": //Orders not refundable (Extremely Low)
                                updateAltcoinStatus('refunded', true);
                                break;
                            case "REFUNDED":
                                if (data['txid']) {
                                    updateAltcoinStatus('refunded-txid', true);
                                    $scope.alttxid = data['txid'];
                                    $scope.alttxurl = data['txurl'];
                                } else {
                                    updateAltcoinStatus('refunded');
                                }
                                break;
                            default:
                                if (send_email == true) {
                                    sendEmail();
                                }
                                if (address_present == true) {
                                    updateAltcoinStatus('refunded');
                                } else {
                                    updateAltcoinStatus('add_refund', true);
                                }
                                break;
                        }
                    default:
                        var status = data['status'];
                        switch (status) {
                            case "WAITING_FOR_DEPOSIT":
                                updateAltcoinStatus('waiting');
                                break;
                            case "EXPIRED":
                                updateAltcoinStatus('expired', true);
                                break;
                        }
                }
            });
    }
    //Altcoin Info
    function infoOrder(uuid) {
        $scope.altuuid = uuid;
        // $scope.order.pagelink = window.location.href;
        var response = AltcoinInfo.save({
                'uuid': uuid
            })
            .$promise.then(function successCallback(data) {
                // Order.get({
                //     "get_order": data.order.destination
                // }, function(order) {
                    Ajax.get({
                        action: 'fetch_order_id',
                        address: data.order.destination
                    })
                    .$promise.then(function(response) {
                        $scope.id_order = response.id;
                    });
                    $scope.altaddress = data.deposit_address;
                    $scope.altamount = data.order.invoiced_amount;
                    $scope.destination = data.order.destination;
                    var altsymbol = data.order.from_currency;
                    alt_totalTime = data.expires;
                    $scope.alt_clock = data.expires;
                    $scope.alt_tick_interval = $interval($scope.alt_tick, 1000);
                    $scope.altsymbol = altsymbol;
                    $scope.altcoinselect = $scope.altcoins[altsymbol];
                    startCheckOrder(uuid);
                    var refund_address = data.refund_address;
                    if (refund_address) {
                        var txid = data.txid;
                        if (txid) {
                            updateAltcoinStatus('refunded-txid', true);
                            $scope.alttxid = data.txid;
                            $scope.alturl = data.txurl;
                        } else {
                            updateAltcoinStatus('refunded-txid');
                            address_present = true;
                        }
                    } else {
                        var payment_status = data.payment_status;
                        switch (payment_status) {
                            case "PAYMENT_RECEIVED":
                            case "PAYMENT_CONFIRMED":
                                updateAltcoinStatus('received', true);
                                break;
                            case "OVERPAY_RECEIVED":
                            case "UNDERPAY_RECEIVED":
                            case "OVERPAY_CONFIRMED":
                            case "UNDERPAY_CONFIRMED":
                                updateAltcoinStatus('add_refund', true);
                                break;
                            default:
                                var status = data.status;
                                switch (status) {
                                    case "WAITING_FOR_DEPOSIT":
                                        updateAltcoinStatus('waiting');
                                        break;
                                    case "EXPIRED":
                                        updateAltcoinStatus('expired', true);
                                        break;
                                }

                        }
                    }
                // });
            });
    }
    //Check UUID in request
    var given_uuid = get_uuid;
    if (given_uuid != '') {
        if (given_uuid == 'low' || given_uuid == 'high') {
            $scope.altstatus = 'low_high';
            $scope.lowhigh = given_uuid;
        } else {
            infoOrder(given_uuid);
        }
    }
    //Add Refund Address click
    $scope.add_refund_click = function() {
        var refund_address = document.getElementById("bnomics-refund-input").value;
        uuid = $scope.altuuid;
        var response = AltcoinAddRefund.save({
                'uuid': uuid,
                'address': refund_address
            })
            .$promise.then(function successCallback(data) {
                if (data['result'] == 'ok') {
                    address_present = true;
                    $scope.altstatus = 'refunded';
                    startCheckOrder(uuid);
                }
            });
    }
    //Go Back click
    $scope.go_back = function() {
        window.history.back();
    }
    //Altcoin Form Copy To Clipboard
    $scope.copyshow = false;
    $scope.alt_address_click = function() {
        var copyText = document.getElementById("bnomics-alt-address-input");
        copyText.select();
        document.execCommand("copy");
        $scope.copyshow = true;
        $timeout(function() {
            $scope.copyshow = false;
        }, 2000);
    }
    //Altcoin List
    $scope.altcoins = {
        "ETH": "Ethereum",
        "LTC": "Litecoin"
    };
});
