"use strict";

var app = angular.module('ng-laravel',['ngCkeditor','ui.tinymce']);
app.controller('wysiwyg-editorCtrl',function($scope){
    $scope.summernoteOption ={
        height: 140,   //set editable area's height
        codemirror: { // codemirror options
            theme: 'monokai'
        }
    }

    $scope.ckeditorOption ={
        skinName  : 'office2013'
    }

    $scope.ckeditorModel = 'Golabi Admin';

    $scope.tinymceModel = '<b>Golabi Admin</b>';
    $scope.tinymceOptions = {
        menubar:false,
        skin: 'light',
        plugins: [
            "advlist autolink link image lists charmap print preview hr anchor pagebreak code  ",
            "searchreplace wordcount visualblocks visualchars insertdatetime media nonbreaking spellchecker",
            "fullscreen table contextmenu directionality emoticons paste textcolor  "
        ],
        image_advtab: true,
        toolbar1: "undo redo | bold italic underline | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | insertdatetime nonbreaking spellchecker contextmenu directionality emoticons paste textcolor codemirror | image | media | link unlink anchor | print preview |  forecolor backcolor | hr anchor pagebreak searchreplace wordcount visualblocks visualchars | code | fullscreen |  styleselect | fontselect fontsizeselect | table | cut copy paste",
    };
});