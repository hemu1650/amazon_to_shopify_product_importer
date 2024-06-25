"use strict";

var app = angular.module('ng-laravel',['ui.bootstrap']);
app.controller('SampleListCtrl',function($scope,SampleService,SweetAlert,$translatePartialLoader,trans){

    /*
     * Define initial value
     */
    $scope.query ='';


    /*
     * Get all Samples
     */
    SampleService.list().then(function(data){
        $scope.samples = data;
        $scope.pagination = $scope.samples.metadata;
        $scope.maxSize = 5;
    });


    /*
     * Remove selected samples
     */
    $scope.delete = function(category) {
        SweetAlert.swal($rootScope.areYouSureDelete,
        function(isConfirm){
            if (isConfirm) {
                SweetAlert.swal($rootScope.recordDeleted);
                SampleService.delete(category);
            }
        });
    };


    /*
     * Pagination sample list
     */
    $scope.units = [
        {'id': 10, 'label': 'Show 10 Item Per Page'},
        {'id': 15, 'label': 'Show 15 Item Per Page'},
        {'id': 20, 'label': 'Show 20 Item Per Page'},
        {'id': 30, 'label': 'Show 30 Item Per Page'},
    ]
    $scope.perPage= $scope.units[0];
    $scope.pageChanged = function(per_page) {
        SampleService.pageChange($scope.pagination.current_page,per_page.id).then(function(data){
            $scope.samples = data;
            $scope.pagination = $scope.samples.metadata;
            $scope.maxSize = 5;
        });
    };


    /*
     * Search in samples
     */
    $scope.search = function(per_page) {
        SampleService.search($scope.query,per_page.id).then(function(data){
            $scope.samples = data;
            $scope.pagination = $scope.samples.metadata;
            $scope.maxSize = 5;
        });
    };


    /**********************************************************
     * Event Listener
     **********************************************************/
    // Get list of selected sample to do actions
    $scope.selection=[];
    $scope.toggleSelection = function toggleSelection(sampleId) {
        // toggle selection for a given sample by Id
        var idx = $scope.selection.indexOf(sampleId);
        // is currently selected
        if (idx > -1) {
            $scope.selection.splice(idx, 1);
        }
        // is newly selected
        else {
            $scope.selection.push(sampleId);
        }
    };

    // update list when sample deleted
    $scope.$on('sample.delete', function() {
        SampleService.list().then(function(data){
            $scope.samples =data;
            $scope.selection=[];
        });
    });



});
