"use strict";

var app = angular.module('ng-laravel');
app.controller('ForgetPassCtrl',function($scope,$auth,hotkeys,$state,Restangular,SweetAlert,$stateParams){
    /*
     * Define initial value
     */
    $scope.resetemail = '';
    $scope.send_text = "Send";


    /*
     * Send email request
     */
    $scope.forget = function(resetemail){
        $scope.send_text = "Sending...";
        $scope.isDisabled = true;
        Restangular.all('password').customPOST('Email','email',{email:resetemail}).then(function(data) {
            SweetAlert.swal({ title: "Success", text: data, type: "success"});
            $scope.send_text = "Send";
            $scope.isDisabled = false;
            $scope.resetemail = '';
        }, function(response) {
            SweetAlert.swal({ title: "Error", text: response.data.email, type: "error"});
            $scope.send_text = "Send";
            $scope.isDisabled = false;
        });
    };


    /*
     * Reset password
     */
    $scope.reset = function(user){
        $scope.send_text = "Sending...";
        $scope.isDisabled = true;
        user.token = $stateParams.token;
        // password/reset/
        Restangular.one('password').customPOST('user','reset',user).then(function(data) {
            // if reset password successfully
            SweetAlert.swal({ title: "Success", text: data, type: "success"});
            $scope.send_text = "Send";
            $scope.isDisabled = false;
            $state.go('login');
        }, function(response) {  // if if reset password failed
            if(response.data.error){
                // if error related to invalid data
                SweetAlert.swal({ title: "Error", text: response.data.error, type: "error"});
            }
            else if(response.data.validation){
                // if error related to validations
                var tmp = [];
                angular.forEach(response.data.validation,function(item,key){
                    angular.forEach(item,function(value,key){
                        tmp += value+'</br>';
                    })
                })
                SweetAlert.swal({ title: "Error Validation", text: tmp, type: "error",html: true});
            }
            $scope.send_text = "Send";
            $scope.isDisabled = false;
        })

    }

});