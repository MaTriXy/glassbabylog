'use strict';

angular.module('glassbabylogApp')
  .controller('MainCtrl', function ($scope) {
    var babies =[
        {name:"Xander", dob:"4/9/2013", interval:"", remind_interval:"", id:""}
    ];

    $scope.babies = babies;
  });
