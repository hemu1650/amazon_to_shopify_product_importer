'use strict';


app.directive('panelFlat',function(){
    return{
        restrict:'AEC',
        link:function(scope,element,attrs){

            // Define closest function
            function closest(e, className) {
                if (e[0].nodeName == "HTML") {
                    return null;
                } else if (e.hasClass(className)) {
                    return e;
                } else {
                    return closest(e.parent(), className);
                }
            };

            // Define disable/enable textarea
            var placeholder = element.find('.panel-footer > .input-placeholder, .panel-flat-comment > .panel-flat-textarea > button[type="reset"]');
            placeholder.on('click', function() {
                var panel = $(this).closest('.panel-flat');
                var comment = panel.find('.panel-flat-comment');
                console.log(panel);
                comment.find('.btn:first-child').addClass('disabled');
                comment.find('textarea').val('');

                panel.toggleClass('panel-flat-show-comment');

                if (panel.hasClass('panel-flat-show-comment')) {
                    comment.find('textarea').focus();
                }
            });


            // Define function for submit disable when textarea is blank
            var textarea = element.find('.panel-flat-comment > .panel-flat-textarea > textarea');
            textarea.on('keyup', function() {
                var comment = $(this).closest('.panel-flat-comment');

                comment.find('button[type="submit"]').addClass('disabled');
                if ($(this).val().length >= 1) {
                    comment.find('button[type="submit"]').removeClass('disabled');
                }
            });

        }
    }
});
