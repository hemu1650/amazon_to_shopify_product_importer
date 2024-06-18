'use strict';

app.directive('blueimpgallery',function() {
    return{
        restrict:'A',
        link:function(scope,element,attrs){
            var blueimpGallery = element.find('#blueimp-gallery');

            // show image with borderless & border option
            var borderlessCheckbox = element.find('#borderless-checkbox');
            borderlessCheckbox.on('change', function () {
                var borderless = $(this).is(':checked');
                blueimpGallery.data('useBootstrapModal', !borderless);
                blueimpGallery.toggleClass('blueimp-gallery-controls', borderless);
            });

            // fullScreen option
            var fullscreenCheckbox = element.find('#fullscreen-checkbox');
            fullscreenCheckbox.on('change', function () {
                blueimpGallery.data('fullScreen', $(this).is(':checked'));
            });


            // launch image gallery slideshow option
            var imageGalleryButton = element.find('#image-gallery-button');
            imageGalleryButton.on('click', function (event) {
                event.preventDefault();
                blueimp.Gallery($('#links a'), $('#blueimp-gallery').data());
            });

            // launch video gallery slide show
            var videoGalleryButton = element.find('#video-gallery-button');
            videoGalleryButton
                .on('click', function (event) {
                    event.preventDefault();
                    blueimp.Gallery([
                        {
                            title: 'Sintel',
                            href: 'http://media.w3.org/2010/05/sintel/trailer.mp4',
                            type: 'video/mp4',
                            poster: 'http://media.w3.org/2010/05/sintel/poster.png'
                        },
                        {
                            title: 'Big Buck Bunny',
                            href: 'http://upload.wikimedia.org/wikipedia/commons/7/75/' +
                            'Big_Buck_Bunny_Trailer_400p.ogg',
                            type: 'video/ogg',
                            poster: 'http://upload.wikimedia.org/wikipedia/commons/thumb/7/70/' +
                            'Big.Buck.Bunny.-.Opening.Screen.png/' +
                            '800px-Big.Buck.Bunny.-.Opening.Screen.png'
                        },
                        {
                            title: 'Elephants Dream',
                            href: 'http://upload.wikimedia.org/wikipedia/commons/transcoded/8/83/' +
                            'Elephants_Dream_%28high_quality%29.ogv/' +
                            'Elephants_Dream_%28high_quality%29.ogv.360p.webm',
                            type: 'video/webm',
                            poster: 'http://upload.wikimedia.org/wikipedia/commons/thumb/9/90/' +
                            'Elephants_Dream_s1_proog.jpg/800px-Elephants_Dream_s1_proog.jpg'
                        }
                    ], blueimpGallery.data());
                });

        }
    }
});