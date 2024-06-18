"use strict";

var app = angular.module('ng-laravel');
app.controller('DashboardCtrl',function($scope){
    $scope.limitRangeDate = 25;
    $scope.maxRangeDate = 20;
    $scope.minRangeDate = 30;

    // custom calendar with ui-jq & ui-options plugin
    $scope.date = new Date();
    $scope.d = $scope.date.getDate();
    $scope.m = $scope.date.getMonth();
    $scope.y = $scope.date.getFullYear();

    // custom calendar with ui-jq & ui-options plugin
    $scope.fullCalendarOption = {
        //isRTL: true,
        buttonHtml: {
            prev: '<i class="fa fa-chevron-left"></i>',
            next: '<i class="fa fa-chevron-right"></i>'
        },

        header: {
            left: 'prev,next today',
            center: 'title',
            right: 'month,agendaWeek,agendaDay'
        },
        events: [
            {
                title: 'All Day Event',
                start: new Date($scope.y, $scope.m, 1),
                className: 'label-important'
            },
            {
                title: 'Long Event',
                start: new Date($scope.y, $scope.m, $scope.d - 5),
                end: new Date($scope.y, $scope.m, $scope.d - 2),
                className: 'label-success'
            },
            {
                title: 'Some Event',
                start: new Date($scope.y, $scope.m, $scope.d - 3, 16, 0),
                allDay: false
            }
        ]
        ,
        editable: true,
        droppable: true, // this allows things to be dropped onto the calendar !!!
        drop: function (date , allDay) { // this function is called when something is dropped

            // retrieve the dropped element's stored Event Object
            var originalevent = $(this).data('event');
            var $extraEventClass = $(this).attr('data-class');


            // we need to copy it, so that multiple events don't have a reference to the same object
            // var copiedevent = $.extend({}, originalevent);

            // assign it the date that was reported
            copiedevent.start = $scope.date;
            copiedevent.allDay = allDay;
            if ($extraEventClass) copiedevent['className'] = [$extraEventClass];

            // render the event on the calendar
            // the last `true` argument determines if the event "sticks" (http://arshaw.com/fullcalendar/docs/event_rendering/renderEvent/)
            $('#calendar').fullCalendar('renderEvent', copiedevent, true);

            // is the "remove after drop" checkbox checked?
            if ($('#drop-remove').is(':checked')) {
                // if so, remove the element from the "Draggable Events" list
                $(this).remove();
            }

        },
        selectable: true,
        selectHelper: true,
        select: function (start, end, allDay) {

            bootbox.prompt("New Event Title:", function (title) {
                if (title !== null) {
                    calendar.fullCalendar('renderEvent',
                        {
                            title: title,
                            start: start,
                            end: end,
                            allDay: allDay
                        },
                        true // make the event "stick"
                    );
                }
            });


            calendar.fullCalendar('unselect');
        }
        ,
        eventClick: function (calEvent, jsEvent, view) {

            //display a modal
            var modal =
                '<div class="modal fade">\
                  <div class="modal-dialog">\
                   <div class="modal-content">\
                     <div class="modal-body">\
                       <button type="button" class="close" data-dismiss="modal" style="margin-top:-10px;">&times;</button>\
                       <form class="no-margin">\
                          <label>Change event name &nbsp;</label>\
                          <input class="middle" autocomplete="off" type="text" value="' + calEvent.title + '" />\
									 <button type="submit" class="btn btn-sm btn-success"><i class="         fa fa-check"></i> Save</button>\
								   </form>\
								 </div>\
								 <div class="modal-footer">\
									<button type="button" class="btn btn-sm btn-danger" data-action="delete"><i class="         fa fa-trash-o"></i> Delete Event</button>\
									<button type="button" class="btn btn-sm" data-dismiss="modal"><i class="         fa fa-times"></i> Cancel</button>\
								 </div>\
							  </div>\
							 </div>\
							</div>';


            var modal = $(modal).appendTo('body');
            modal.find('form').on('submit', function (ev) {
                ev.preventDefault();

                calEvent.title = $(this).find("input[type=text]").val();
                calendar.fullCalendar('updateEvent', calEvent);
                modal.modal("hide");
            });
            modal.find('button[data-action=delete]').on('click', function () {
                calendar.fullCalendar('removeEvents', function (ev) {
                    return (ev._id == calEvent._id);
                })
                modal.modal("hide");
            });

            modal.modal('show').on('hidden', function () {
                modal.remove();
            });


        }

    }

    $scope.chartData = [
        { y: '2014', a: 4, b: 1},
        { y: '2015', a: 8,  b: 0},
        { y: '2016', a: 5,  b: 2},
        { y: '2017', a: 10,  b: 0},
        { y: '2018', a: 4,  b: 1},
        { y: '2019', a: 16,  b: 3},
        { y: '2020', a: 5, b: 1},
        { y: '2021', a: 11, b: 5},
        { y: '2022', a: 6, b: 2},
        { y: '2023', a: 11, b: 3},
        { y: '2024', a: 30, b: 2},
        { y: '2025', a: 13, b: 0},
        { y: '2026', a: 4, b: 2},
        { y: '2027', a: 3, b: 8},
        { y: '2028', a: 3, b: 0},
        { y: '2029', a: 6, b: 0},
    ];

    $scope.chartOptions={
        xkey: 'y',
        ykeys: ['a', 'b'],
        labels: ['Total Income', 'Total Outcome'],
        fillOpacity: 0.6,
        behaveLikeLine: true,
        resize: true,
        pointStrokeColors: ['black'],
        lineColors:['blue'],
        pointFillColors: ['#00ff00'],
        lineWidth:[1],
        pointSize:[.5],
        hideHover:'always'
    }
});
