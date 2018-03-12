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

app = angular.module("blockonomics-invoice", ["monospaced.qrcode"]);

app.config(function ($interpolateProvider) {

    $interpolateProvider.startSymbol('//');
    $interpolateProvider.endSymbol('//');
})

app.controller("CheckoutController", function($window, $scope, $location, $interval, $rootScope) {
    var totalProgress = 100;
    var totalTime = 10*60; //10m
    $scope.progress = totalProgress;
    $scope.clock = totalTime;

    $scope.tick = function() {
        $scope.clock = $scope.clock-1;
        $scope.progress = Math.floor($scope.clock*totalProgress/totalTime);

        if($scope.progress == 0){
            //Refresh invoice page
            $window.location.reload();
        }
    };

    $scope.pay_altcoins = function() {
        $scope.altcoin_waiting = true;
        url = "https://shapeshift.io/shifty.html?destination=" + $scope.address + "&amount=" + $scope.satoshi + "&output=BTC";
        window.open(url, '1418115287605','width=700,height=500,toolbar=0,menubar=0,location=0,status=1,scrollbars=1,resizable=0,left=0,top=0');
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
});
