'use strict';

app.directive('notification',function() {
    return {
        restrict: 'EA',
        template: '<div class="alert alert-{{alertData.type}} fade in"  role="alert" >\
                        <a href="" data-target="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>\
                        <ul class="list-unstyled" ng-repeat="msg in alertData.message">\
                            <li ng-repeat="item in msg">\
                                {{item}}\
                            </li>\
                        </ul>\
                    </div>',
        scope:{
            alertData:"="
        },
        replace:true
    }
});
