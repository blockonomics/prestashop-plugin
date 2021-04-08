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

app = angular.module("BlockonomicsApp", ["monospaced.qrcode",  "shoppingcart.services"]);

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

    //Create url when the order is received 
    $scope.finish_order_url = function() {
        return final_url;
    }

    //Increment bitcoin timer
    $scope.tick = function() {
        $scope.clock = $scope.clock-1;
        $scope.progress = Math.floor($scope.clock*totalProgress/totalTime);
        if($scope.progress <= 0){
            //Expired status
            $scope.status = -3; 
        }
    };

    //Check if the bitcoin address is present
    $scope.init = function(invoice_status, invoice_addr, invoice_timestamp, base_websocket_url, final_url, timeperiod, time_remaining){;
        totalTime = timeperiod*60; //10m
        $scope.clock = time_remaining*60;
        $scope.status = invoice_status;
        if($scope.status == -1){
            $scope.tick_interval  = $interval($scope.tick, 1000);
            var ws = new WebSocket(base_websocket_url+"/payment/" + invoice_addr + "?timestamp=" + invoice_timestamp);
            ws.onmessage = function (evt) {
                $window.location = final_url;
            }
          }
    }

    function select_text(divid)
    {
        var selection = window.getSelection();
        var div = document.createRange();

        div.setStartBefore(document.getElementById(divid));
        div.setEndAfter(document.getElementById(divid)) ;
        selection.removeAllRanges();
        selection.addRange(div);
    }

    function copy_to_clipboard(divid)
    {
        var textarea = document.createElement('textarea');
        textarea.id = 'temp_element';
        textarea.style.height = 0;
        document.body.appendChild(textarea);
        textarea.value = document.getElementById(divid).innerText;
        var selector = document.querySelector('#temp_element');
        selector.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);

        select_text(divid);

        if (divid == "bnomics-address-copy") {
            $scope.address_copyshow = true;
            $timeout(function() {
                $scope.address_copyshow = false;
                //Close copy to clipboard message after 2 sec
            }, 2000);
        }else{
            $scope.amount_copyshow = true;
            $timeout(function() {
                $scope.amount_copyshow = false;
                //Close copy to clipboard message after 2 sec
            }, 2000);            
        }
    }

    //Copy bitcoin address to clipboard
    $scope.blockonomics_address_click = function() {
        copy_to_clipboard("bnomics-address-copy");
    }

    //Copy bitcoin amount to clipboard
    $scope.blockonomics_amount_click = function() {
        copy_to_clipboard("bnomics-amount-copy");
    }
    //Reload the page if user clicks try again after the order expires
    $scope.try_again_click = function() {
        location.reload();
    }
    
});
