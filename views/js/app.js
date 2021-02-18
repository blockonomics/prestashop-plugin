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
 * @copyright 2011 Blockonomics
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * International Registered Trademark & Property of Blockonomics
 */
service = angular.module("shoppingcart.services", ["ngResource"]);

app = angular.module("blockonomics-invoice", ["monospaced.qrcode",  "shoppingcart.services"]);

app.config(function($compileProvider) {
    $compileProvider.aHrefSanitizationWhitelist(/^\s*(https?|ftp|mailto|data|chrome-extension|bitcoin):/);
    // Angular before v1.2 uses $compileProvider.urlSanitizationWhitelist(...)
});

app.config(function ($interpolateProvider) {
    $interpolateProvider.startSymbol('[[');
    $interpolateProvider.endSymbol(']]');
})

app.controller("CheckoutController", function($window, $scope, $location, $interval, $rootScope, $httpParamSerializer, $timeout) {
    var totalProgress = 100;
    var totalTime = 0;
    $scope.progress = totalProgress;
    
    $scope.copyshow = false;

    //Create url when the order is received 
    $scope.finish_order_url = function() {
        return final_url;
    }

    //Increment bitcoin timer
    $scope.tick = function() {
        $scope.clock = $scope.clock-1;
        $scope.progress = Math.floor($scope.clock*totalProgress/totalTime);
        if($scope.progress == 0){
            //Refresh invoice page
            $window.location.reload();
        }
    };

    //Check if the bitcoin address is present
    $scope.init = function(invoice_status, invoice_addr, invoice_timestamp, base_websocket_url, final_url, invoice_satoshi, order_id, timeperiod){
        $scope.address = invoice_addr;
        $scope.satoshi = invoice_satoshi;
        $scope.id_order = order_id;
        totalTime = timeperiod*60; //10m
        $scope.clock = totalTime;

        if(invoice_status == -1){
            $scope.tick_interval  = $interval($scope.tick, 1000);
            var ws = new WebSocket(base_websocket_url+"/payment/" + invoice_addr + "?timestamp=" + invoice_timestamp);
            ws.onmessage = function (evt) {
                $window.location = final_url;
            }
          }
    }

    //Copy bitcoin address to clipboard
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
    
});
