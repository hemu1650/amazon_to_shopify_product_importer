'use strict';

app.directive('select2',function(){
    return {
        restrict:'A',
        link:function(scope,element,attr,ngModel){
            element.select2({
                allowClear : true,
                width : '100%'
            });
        }
    }
})