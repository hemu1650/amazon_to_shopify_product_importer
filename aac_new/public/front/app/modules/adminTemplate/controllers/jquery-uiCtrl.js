'use strict';

var app = angular.module('ng-laravel');
app.controller('jquery-uiCtrl',function($scope){
    $scope.datepickerOption= {
        showOtherMonths: true,
        selectOtherMonths: false
    };

    $scope.availableTags = [
        "ActionScript",
        "AppleScript",
        "Asp",
        "BASIC",
        "C",
        "C++",
        "Clojure",
        "COBOL",
        "ColdFusion",
        "Erlang",
        "Fortran",
        "Groovy",
        "Haskell",
        "Java",
        "JavaScript",
        "Lisp",
        "Perl",
        "PHP",
        "Python",
        "Ruby",
        "Scala",
        "Scheme"
    ];
    $scope.autocompleteOption={
        source: $scope.availableTags
    };

    $scope.spinnerOption = {
        create: function( event, ui ) {
            //add custom classes and icons
            $(this)
                .next().addClass('btn btn-success').html('<i class=" fa fa-plus"></i>')
                .next().addClass('btn btn-danger').html('<i class=" fa fa-minus"></i>')

            //larger buttons on touch devices
            if('touchstart' in document.documentElement)
                $(this).closest('.ui-spinner').addClass('ui-spinner-touch');
        }
    };

    $scope.sliderOption = {
        range: true,
        min: 0,
        max: 500,
        values: [ 75, 300 ]
    };

    $scope.accordionOption ={
        collapsible: true ,
        heightStyle: "content",
        animate: 250,
        header: ".accordion-header"
    };

    $scope.progressbarOption ={
        value: 37,
        create: function( event, ui ) {
            $(this).addClass('progress progress-striped active')
                .children(0).addClass('progress-bar progress-bar-success');
        }
    };

    $scope.dialogOption = {
        autoOpen : false,
        width : 600,
        resizable : false,
        modal : true,
        buttons : [{
            html : "<i class='fa fa-times'></i>&nbsp; Cancel",
            "class" : "btn btn-default",
            click : function() {
                $(this).dialog("close");

            }
        }, {

            html : "<i class='fa fa-plus'></i>&nbsp; Add",
            "class" : "btn btn-danger",
            click : function() {
                addTab();
                $(this).dialog("close");
            }
        }]
    };



}).directive('jqueryuicontainer',function(){
    return{
        restrict:'A',
        link: function(scope,element,attrs){

            //override dialog's title function to allow for HTML titles
            $.widget("ui.dialog", $.extend({}, $.ui.dialog.prototype, {
                _title: function(title) {
                    var $title = this.options.title || '&nbsp;'
                    if( ("title_html" in this.options) && this.options.title_html == true )
                        title.html($title);
                    else title.text($title);
                }
            }));

            //custom autocomplete (category selection)
            $.widget( "custom.catcomplete", $.ui.autocomplete, {
                _renderMenu: function( ul, items ) {
                    var that = this,
                        currentCategory = "";
                    $.each( items, function( index, item ) {
                        if ( item.category != currentCategory ) {
                            ul.append( "<li class='ui-autocomplete-category'>" + item.category + "</li>" );
                            currentCategory = item.category;
                        }
                        that._renderItemData( ul, item );
                    });
                }
            });

            // id-btn-dialog1 dialog box
            var btnDialog1 = element.find('#id-btn-dialog1');
            var dialog = element.find('#dialog-message');
            btnDialog1.on('click', function() {
                    dialog.removeClass('hide').dialog({
                    modal: true,
                    title: "<div class='widget-header widget-header-small'><h4 class='smaller'><i class=' fa fa-check'></i> jQuery UI Dialog</h4></div>",
                    title_html: true,
                    buttons: [
                        {
                            text: "Cancel",
                            "class" : "btn btn-xs",
                            click: function() {
                                $( this ).dialog( "close" );
                            }
                        },
                        {
                            text: "OK",
                            "class" : "btn btn-primary btn-xs",
                            click: function() {
                                $( this ).dialog( "close" );
                            }
                        }
                    ]
                });
            });

            // btnDialog2 dialog box
            var btnDialog2 = element.find('#id-btn-dialog2');
            var dialogConfirm = element.find('#dialog-confirm');
            btnDialog2.on('click', function() {
                dialogConfirm.removeClass('hide').dialog({
                    resizable: false,
                    modal: true,
                    title: "<div class='widget-header'><h4 class='smaller'><i class='fa fa-exclamation-triangle red'></i> Empty the recycle bin?</h4></div>",
                    title_html: true,
                    buttons: [
                        {
                            html: "<i class=' fa fa-trash-o bigger-110'></i>&nbsp; Delete all items",
                            "class" : "btn btn-danger btn-xs",
                            click: function() {
                                $(this).dialog( "close" );
                            }
                        }
                        ,
                        {
                            html: "<i class=' fa fa-times bigger-110'></i>&nbsp; Cancel",
                            "class" : "btn btn-xs",
                            click: function() {
                                $(this).dialog( "close" );
                            }
                        }
                    ]
                });
            });


            /*
             * ADD TABs
             */
            var tabTitle = element.find("#tab_title"),
                tabContent = element.find("#tab_content"),
                tabTemplate = "<li style='position:relative;'> <span class='air air-top-left delete-tab' style='top:7px; left:7px;'><button class='btn btn-xs font-xs btn-default hover-transparent'><i class='fa fa-times'></i></button></span></span><a href='#{href}'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; #{label}</a></li>",
                tabCounter = 2;

            var tabs = element.find("#tabs2").tabs();

            // modal dialog init: custom buttons and a "close" callback reseting the form inside
            var dialog = element.find("#addtab").dialog({
                autoOpen : false,
                width : 600,
                resizable : false,
                modal : true,
                buttons : [{
                    html : "<i class='fa fa-times'></i>&nbsp; Cancel",
                    "class" : "btn btn-default",
                    click : function() {
                        $(this).dialog("close");

                    }
                }, {

                    html : "<i class='fa fa-plus'></i>&nbsp; Add",
                    "class" : "btn btn-danger",
                    click : function() {
                        addTab();
                        $(this).dialog("close");
                    }
                }]
            });

            // actual addTab function: adds new tab using the input from the form above
            var addTab = function() {
                var label = tabTitle.val() || "Tab " + tabCounter, id = "tabs-" + tabCounter, li = $(tabTemplate.replace(/#\{href\}/g, "#" + id).replace(/#\{label\}/g, label)), tabContentHtml = tabContent.val() || "Tab " + tabCounter + " content.";

                tabs.find(".ui-tabs-nav").append(li);
                tabs.append("<div id='" + id + "'><p>" + tabContentHtml + "</p></div>");
                tabs.tabs("refresh");
                tabCounter++;

                // clear fields
                $("#tab_title").val("");
                $("#tab_content").val("");
            };

            // addTab form: calls addTab function on submit and closes the dialog
            var form = dialog.find("form").submit(function(event) {
                addTab();
                dialog.dialog("close");
                event.preventDefault();
            });

            // addTab button: just opens the dialog
            element.find("#add_tab").button().click(function() {
                dialog.dialog("open");
            });

            // close icon: removing the tab on click
            element.find("#tabs2").on("click", 'span.delete-tab', function() {
                var panelId = $(this).closest("li").remove().attr("aria-controls");
                $("#" + panelId).remove();
                tabs.tabs("refresh");
            });


        }
    }
});