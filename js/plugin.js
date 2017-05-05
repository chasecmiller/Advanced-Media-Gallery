//alert('a');
jQuery(document).ready(function ($) {

    var plotRectangle = function (x, y, w, h, parent) {

        var rect = document.createElement('div');
        rect.style.width = w + 'px';
        rect.style.height = h + 'px';
        rect.style.left = x + 'px';
        rect.style.top = y + 'px';
        rect.style.position = 'absolute';
        console.log(rect);
        parent.appendChild(rect);
//        console.log(parent);
//        jQuery('.media-frame-content .details-image').parent()[0].appendChild(rect);

    };



    var showData = function () {
        // Tags.
        var tags = jQuery('.compat-field-post_tag input');
        tags = tags.length == 1 ? tags.val().toString().split(',').map(Function.prototype.call, String.prototype.trim) : [];

        // People
        var people = jQuery('.compat-field-person input');
        people = people.length == 1 ? people.val().toString().split(',').map(Function.prototype.call, String.prototype.trim) : [];


        var img = jQuery('.media-frame-content .details-image')[0];
        var tracker = new tracking.ObjectTracker('face');
        tracking.track(img, tracker);
        tracker.on('track', function (event) {
            event.data.forEach(function (rect) {
                console.log(rect);
                plotRectangle(rect.x, rect.y, rect.width, rect.height);
            });
        });


        // Try to locate the people.
        console.log(tags, people);
    }

    var $w = $('.wrap');
    $w.delegate('.media-frame-content .thumbnail', "click", function () {

        setTimeout(function () {
            showData();
        }, 500);

//        $( this ).toggleClass( "chosen" );
//        console.log(jQuery('.edit-media-header button'));
        jQuery(".edit-media-header button").on('click', function (e) {
            console.log('go');
            setTimeout(function () {
                showData();
            }, 500);

        });
    });

    $w.delegate('.media-model-content', 'click', function () {
        alert('a');
    })


    $w.delegate('.edit-media-header *', 'click', function (e) {
        alert('a');
    })
    $w.delegate('.edit-media-header', 'click', function (e) {
        alert('b');
    })

//    console.log(wp.Uploader.prototype);
    if (typeof wp.media !== 'undefined') {
        var frame = wp.media();
        frame.on('open', function () {
            console.log("Open");
        });

        frame.on('close', function () {
            console.log("Close");
        });

        frame.on('select', function () {
            console.log("Select");

            var selection = frame.state().get('selection');

            selection.each(function (attachment) {
                console.log(attachment.id);
            });
        });
    }
    if (typeof wp.Uploader !== 'undefined') {
        $.extend(wp.Uploader.prototype, {
            init: function () {
                console.log('initialized');
                // Is there a popup open?
                setTimeout(function () {
                    if (jQuery('.media-modal-content').length) {
                        console.log('already open');
                        showData();
                    }

                    // Already open
                    jQuery(".edit-media-header button").on('click', function (e) {
                        console.log('go2');
                        setTimeout(function () {
                            showData();
                        }, 500);
                    });
                }, 1000);//
            },
            success: function (e) {
                // Submit an auto tag request here.
                if (e.attributes.mime.indexOf('image/') === 0) {
                    jQuery.ajax({
                        type: 'POST',
                        url: ajaxurl,
                        data: {
                            'action': 'amgtag',
                            'method': 'process',
                            'id': e.attributes.id
                        },
                        success: function (e) {
                            console.log(e)
                        }
                    });
                }
            }
        });
    }

    if (
        typeof wp.media == 'undefined'
        &&
        typeof wp.Uploader == 'undefined'
    ) {
        console.log('initialized2');
        var img = jQuery('.wp_attachment_image .thumbnail');
        if (img.length != 1) {
            return;
        }

        // Tags.
        var tags = jQuery('.compat-field-post_tag input');
        tags = tags.length == 1 ? tags.val().toString().split(',').map(Function.prototype.call, String.prototype.trim) : [];

        // People
        var people = jQuery('.compat-field-person input');
        people = people.length == 1 ? people.val().toString().split(',').map(Function.prototype.call, String.prototype.trim) : [];

        var tracker = new tracking.ObjectTracker('face');
        tracking.track(img[0], tracker);
        tracker.on('track', function (event) {
            console.log(event);
            event.data.forEach(function (rect) {
//console.log(rect);
//                console.log(rect,img[0]);
                plotRectangle(rect.x, rect.y, rect.width, rect.height, img.closest('.wp_attachment_image')[0]);
            });
        });
        console.log('initialized3');
        return;

    }
});