'use strict';

angular.module('Request').directive('knob',function(){
    return {
        restrict:'A',
        require:'ngModel',
        link:function(scope,element,attr,ngModel){
            element.knob({
                min:-50,
                max:50,
                skin:'tron',
                fgColor:attr.fgColor,
                change : function (value) {
                    scope.$apply(function(){
                        ngModel.$setViewValue(value)
                    })
                }
            });
        }
    }
})