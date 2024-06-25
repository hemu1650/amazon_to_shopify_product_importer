"use strict";

var app = angular.module('ng-laravel');
app.controller('calendarCtrl',function($scope){

});

// fullCalendar custom directive
app.directive('fullCalendar',function(){
    return{
        restrict:'A',
        link: function($scope,element,attrs){
            var date = new Date();
            var d = date.getDate();
            var m = date.getMonth();
            var y = date.getFullYear();

            //$scope.$watch(ngModel,  function(){
            //    var model = ngModel.$modelValue || '';
            //});

            var calendar = element.fullCalendar({
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
                        start: new Date(y, m, 1),
                        className: 'label-important'
                    },
                    {
                        title: 'Long Event',
                        start: new Date(y, m, d-5),
                        end: new Date(y, m, d-2),
                        className: 'label-success'
                    },
                    {
                        title: 'Some Event',
                        start: new Date(y, m, d-3, 16, 0),
                        allDay: false
                    }
                ],
                editable: true,
                droppable: true, // this allows things to be dropped onto the calendar !!!
                drop: function(date, allDay) { // this function is called when something is dropped

                    // retrieve the dropped element's stored Event Object
                    var originalevent = $(this).data('event');
                    var $extraEventClass = $(this).attr('data-class');


                    // we need to copy it, so that multiple events don't have a reference to the same object
                    var copiedevent = $.extend({}, originalevent);

                    // assign it the date that was reported
                    copiedevent.start = date;
                    copiedevent.allDay = allDay;
                    if($extraEventClass) copiedevent['className'] = [$extraEventClass];

                    // render the event on the calendar
                    // the last `true` argument determines if the event "sticks" (http://arshaw.com/fullcalendar/docs/event_rendering/renderEvent/)
                    element.fullCalendar('renderEvent', copiedevent, true);

                    // is the "remove after drop" checkbox checked?
                    if (element.find('#drop-remove').is(':checked')) {
                        // if so, remove the element from the "Draggable Events" list
                        $(this).remove();
                    }

                }
                ,
                selectable: true,
                selectHelper: true,
                select: function(start, end, allDay) {

                    bootbox.prompt("New Event Title:", function(title) {
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
                eventClick: function(calEvent, jsEvent, view) {

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
                    modal.find('form').on('submit', function(){
                        calEvent.title = $(this).find("input[type=text]").val();
                        calendar.fullCalendar('updateEvent', calEvent);
                        modal.modal("hide");
                    });
                    modal.find('button[data-action=delete]').on('click', function() {
                        calendar.fullCalendar('removeEvents' , function(ev){
                            return (ev._id == calEvent._id);
                        })
                        modal.modal("hide");
                    });

                    modal.modal('show').on('hidden', function(){
                        modal.remove();
                    });


                }

            })
        }
    }
});

// fullCalendar custom directive for external events
app.directive('externalEvents',function(){
    return{
        restrict:'A',
        link: function(scope,element,attrs){
            element.find('.fc-event').each(function() {
                // store data so the calendar knows to render an event upon drop
                $(this).data('event', {
                    title: $.trim($(this).text()), // use the element's text as the event title
                    stick: true // maintain when user navigates (see docs on the renderEvent method)
                });


                // make the event draggable using jQuery UI
                $(this).draggable({
                    zIndex: 999,
                    revert: true,      // will cause the event to go back to its
                    revertDuration: 0  //  original position after the drag
                });

            });
        }
    }
});