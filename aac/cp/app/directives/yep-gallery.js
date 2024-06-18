'use strict';

app.directive('yepgallery',function() {
    return {
        restrict: 'A',
        link: function ($scope, element, attrs) {

            function closest(e, className) {
                if (e[0].nodeName == "HTML") {
                    return null;
                } else if (e.hasClass(className)) {
                    return e;
                } else {
                    return closest(e.parent(), className);
                }
            };

            var cell =$('.image__cell');
            element.find('.image--basic').click(function() {
                var thisCell = closest($(this),'image__cell');
                if (thisCell.hasClass('is-collapsed')) {
                    cell.not(thisCell).removeClass('is-expanded').addClass('is-collapsed');
                    thisCell.removeClass('is-collapsed').addClass('is-expanded');
                } else {
                    thisCell.removeClass('is-expanded').addClass('is-collapsed');
                }
            });

            element.find('.expand__close').click(function() {
                var thisCell =  closest($(this),'image__cell');
                thisCell.removeClass('is-expanded').addClass('is-collapsed');
            });
        }
    }
});