/*
 * autor: Miller Augusto S. Martins
 * e-mail: miller.augusto@gmail.com
 * github: miamarti
 * */
(function(window, document) {
    "use strict";
    (angular.module('ng.daterange', [ 'ng' ])).directive('ngDateRange', [ function() {
	var container = function(scope, element, attrs) {
	    var html = '';
	    html += '<div id="reportrange" class="pull-right daterange hidden-xs" >';
	    html += '<i class="glyphicon glyphicon-calendar fa fa-calendar"></i>&nbsp;';
	    html += '<span> ' + moment().subtract(29,'days').format('D MMMM, YYYY') + ' - ' + moment().format('D MMMM, YYYY') + ' </span> <b class="caret"></b>';
	    html += '</div>';
	    $(element).html(html);
	    var divContainer = $(element).find('div').get(0);

	    var config = {
			startDate : moment().subtract(29,'days'),
			endDate : moment(),
			minDate : scope[attrs.min],
			maxDate : scope[attrs.max],
			dateLimit : {
				days : scope[attrs.limit]
			},
			showDropdowns : true,
			showWeekNumbers : false,
			timePicker : false,
			timePickerIncrement : 1,
			timePicker12Hour : true,
			ranges : {
				'Today' : [ moment(), moment() ],
				'Yesterday' : [ moment().subtract(1,'days'), moment().subtract(1,'days') ],
				'Last 7 Days' : [ moment().subtract(6,'days'), moment() ],
				'Last 30 Days' : [ moment().subtract(29,'days'), moment() ],
				'This Month' : [ moment().startOf('month'), moment().endOf('month') ],
				'Last Month' : [ moment().subtract(1,'month').startOf('month'), moment().subtract(1,'month').endOf('month') ]
			},
			buttonClasses : [ 'btn' ],
			applyClass : 'btn-success',
			cancelClass : 'btn-default',
			format : 'MM/DD/YYYY',
	    };

	    var callback = function(start, end) {
			$($(divContainer).find('span').get(0)).html(start.format('MMMM D, YYYY') + ' - ' + end.format('MMMM D, YYYY'));
	    };

	    $(divContainer).daterangepicker(config, callback);
	};
	return {
	    restrict : 'E',
	    link : container
	};
    } ]);
})(window, document);
