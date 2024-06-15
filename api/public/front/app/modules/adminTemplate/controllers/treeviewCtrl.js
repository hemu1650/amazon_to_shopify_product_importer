'use strict';

var app = angular.module('ng-laravel');
app.controller('treeviewCtrl',function($scope){
    $scope.jtOption1 = {
        plugins: ["wholerow", "checkbox"],
        core: {
            data: [{
                text: "Software",
                children: [{
                    text: "Antivirus",
                    state: {
                        selected: true
                    }
                }, {
                    text: "Animation",
                    icon: "../assets/img/jstree-icon/tree_icon.png"
                }, {
                    text: "Office",
                    state: {
                        opened: true
                    },
                    children: ["MS Word"]
                }, {
                    text: "Graphic",
                    icon: "glyphicon glyphicon-leaf"
                }]
            },
                "Hardware"
            ],
            themes: {
                name: 'proton',
                responsive: true
            }
        }
    };
    $scope.jtOption2 ={
        'core': {
            'data': [{
                "text": "Wholerow with checkboxes",
                "children": [{
                    "text": "initially selected",
                    "state": {
                        "selected": true
                    }
                }, {
                    "text": "custom icon URL",
                    "icon": "../assets/img/jstree-icon/tree_icon.png"
                }, {
                    "text": "initially open",
                    "state": {
                        "opened": true
                    },
                    "children": ["Another node"]
                }, {
                    "text": "custom icon class",
                    "icon": "glyphicon glyphicon-leaf"
                }]
            },
                "And wholerow selection"
            ],
            'themes': {
                'name': 'proton',
                'responsive': true
            }
        }
    }
})