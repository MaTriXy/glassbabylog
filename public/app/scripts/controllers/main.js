'use strict';

angular.module('glassbabylogApp')
  .controller('MainCtrl', function ($scope, $http) {
    var babies =[
        // {name:"Xander", dob:"4/9/2013", interval:"", remind_interval:"", id:""}
    ];

    var baby = {};

    $http.get('/api/babies').success(function(data) {
        // console.log(data);

        $scope.babies = data.babies;
    });

    $scope.babies = babies;
    $scope.baby = baby;

    $scope.selectChild = function(id) {
        // console.log(id);
        for (var i = 0; i < $scope.babies.length; i++) {
            if ($scope.babies[i].id == id) {
                $scope.baby = $scope.babies[i];
                break;
            }
        }
    }

    $scope.newChild = function() {
        $scope.baby = {};
    }

    $scope.saveChild = function() {
        $http.post('/api/save', $scope.baby).success(function(data) {
            $scope.babies.push(data.baby);
        });
    }
  });
