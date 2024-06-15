'use strict';

// you can use yepSparkLine directive to render Composite jQuery Sparkline chart
app.directive('yepSparkLine', function () {
    return {
        restrict: 'AE',
        link: function (scope, element, attrs) {

            var composite = attrs.composite;
            var sparkHtmlOptions = scope[attrs.sparkHtmlOptions];
            var sparkValuesOptions = scope[attrs.sparkValuesOptions];
            var sparkValue = scope[attrs.sparkValue];

            // just render composite chart
            if (composite == 'true') {
                element.sparkline('html', sparkHtmlOptions);
                element.sparkline(sparkValue, sparkValuesOptions);
            }

        }
    }
});