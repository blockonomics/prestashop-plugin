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



  $scope.init = function(invoice_status, invoice_addr, invoice_timestamp){
    if(invoice_status == -1){
      $scope.tick_interval  = $interval($scope.tick, 1000);

      var ws = new WebSocket("ws://blockonomics.co/payment/" + invoice_addr + "?timestamp=" + invoice_timestamp);

      ws.onmessage = function (evt) {
        console.log(evt);
        $interval(function(){
          if ($scope.tick_interval)
            $interval.cancel($scope.tick_interval);

          $window.location.reload();
        }, 5000, 1);
      }
    }
  }
});
