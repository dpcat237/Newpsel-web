/*List of urls*/
var jsUrl = function () {
    var URL_HOMEPAGE = "";
    var URL_SUBSCRIBE_NEWSLETTER = "";
    var URL_SUBSCRIBE_FEED = "";
    var URL_CREATE_LABEL = "";

    return {
        URL_HOMEPAGE : URL_HOMEPAGE,
        URL_SUBSCRIBE_NEWSLETTER : URL_SUBSCRIBE_NEWSLETTER,
        URL_SUBSCRIBE_FEED : URL_SUBSCRIBE_FEED,
        URL_CREATE_LABEL : URL_CREATE_LABEL
    };
}();

// ********************************************
// Object subscribeNewsletter
// Subscribe to newsletter
// ********************************************
var subscribeNewsletter = function () {
    function accessInit() {
        $('#subscribeNewsletterSubmit').click(function(){
            if($('#subscribeNewsletter').parsley('validate')) {
                $('#modalSubscribe').modal('hide');
                var email = $('#subscribe_email').val();
                $('#subscribe_email').val('');

                $.ajax({
                    type: "POST",
                    url: jsUrl.URL_SUBSCRIBE_NEWSLETTER,
                    data: {email: email},
                    dataType: 'json'
                });
            }
        });
    }

    return{
        init : accessInit
    }
}();

// ********************************************
// Object subscribeFeed
// Subscribe to feed
// ********************************************
var subscribeFeed = function () {
    function accessInit() {
        $('#subscribeSubmit').click(function(){
            if($('#subscribe-form').parsley('validate')) {
                $('#modalAddFeed').modal('hide');
                var feed = $('#subscribe_feed').val();
                $('#subscribe_feed').val('');

                $.ajax({
                    type: "POST",
                    url: jsUrl.URL_SUBSCRIBE_FEED,
                    data: {feed: feed},
                    dataType: 'json',
                    success: function (result) {
                        if (result['result'] == "100") {
                            window.location = result['url'];
                        } else {
                            window.location = jsUrl.URL_HOMEPAGE;
                        }
                    }
                });
            }
        });
    }

    return{
        init : accessInit
    }
}();

// ********************************************
// Object validGenericForm
// Validate and submit generic form
// ********************************************
var validGenericForm = function () {
    function accessInit() {
        $('#sign-form-valid').click(function(){
            if ($('#sign-form').parsley('validate')){
                $('#sign-form').submit();
            }
        });

        $(".show-tip").tooltip();
    }

    return{
        init : accessInit
    }
}();

// ********************************************
// Object showSubmenu
// Show and hide submenus
// ********************************************
var showSubmenu = function () {
    function accessInit() {
        $('.mOptAction').click(function(){
            var submenu = $(this).data('sebmenu');
            if (submenu.length > 0) {
                if ($("#"+submenu).is(":visible")) {
                    $("#"+submenu).hide(500);
                } else {
                    $("#"+submenu).show(500);
                }
            }
        });
    }

    return{
        init : accessInit
    }
}();


// ********************************************
// Object navScrollbar
// Custom scrollbar
// ********************************************
var navScrollbar = function () {
    function accessInit() {
        $("ul.nav-list").mCustomScrollbar({
            set_width:false, /*optional element width: boolean, pixels, percentage*/
            set_height:false, /*optional element height: boolean, pixels, percentage*/
            horizontalScroll:false, /*scroll horizontally: boolean*/
            scrollInertia:950, /*scrolling inertia: integer (milliseconds)*/
            mouseWheel:true, /*mousewheel support: boolean*/
            mouseWheelPixels:"auto", /*mousewheel pixels amount: integer, "auto"*/
            autoDraggerLength:true, /*auto-adjust scrollbar dragger length: boolean*/
            autoHideScrollbar:false, /*auto-hide scrollbar when idle*/
            scrollButtons:{ /*scroll buttons*/
                enable:false, /*scroll buttons support: boolean*/
                scrollType:"continuous", /*scroll buttons scrolling type: "continuous", "pixels"*/
                scrollSpeed:"auto", /*scroll buttons continuous scrolling speed: integer, "auto"*/
                scrollAmount:40 /*scroll buttons pixels scroll amount: integer (pixels)*/
            },
            advanced:{
                updateOnBrowserResize:true, /*update scrollbars on browser resize (for layouts based on percentages): boolean*/
                updateOnContentResize:false, /*auto-update scrollbars on content resize (for dynamic content): boolean*/
                autoExpandHorizontalScroll:false, /*auto-expand width for horizontal scrolling: boolean*/
                autoScrollOnFocus:true, /*auto-scroll on focused elements: boolean*/
                normalizeMouseWheelDelta:false /*normalize mouse-wheel delta (-1/1)*/
            },
            contentTouchScroll:true, /*scrolling by touch-swipe content: boolean*/
            callbacks:{
                onScrollStart:function(){}, /*user custom callback function on scroll start event*/
                onScroll:function(){}, /*user custom callback function on scroll event*/
                onTotalScroll:function(){}, /*user custom callback function on scroll end reached event*/
                onTotalScrollBack:function(){}, /*user custom callback function on scroll begin reached event*/
                onTotalScrollOffset:0, /*scroll end reached offset: integer (pixels)*/
                onTotalScrollBackOffset:0, /*scroll begin reached offset: integer (pixels)*/
                whileScrolling:function(){} /*user custom callback function on scrolling event*/
            },
            theme:"light" /*"light", "dark", "light-2", "dark-2", "light-thick", "dark-thick", "light-thin", "dark-thin"*/
        });
    }

    return{
        init : accessInit
    }
}();

// ********************************************
// Object readAction
// Read and not read item
// ********************************************
var readAction = function () {
    function accessInit() {
        $('.readAction').click(function(){
            var elem = $(this);
            var url = elem.data('url');
            var elemText = $('#itemText-id-'+elem.data('id'));

            $.ajax({
                type: "POST",
                url: url,
                dataType: 'json',
                success: function (result) {
                    if (result['result'] == "110") {
                        elem.addClass('icon-check');
                        elem.removeClass('icon-check-empty');
                        elemText.removeClass('bold');
                    } else if (result['result'] == "111") {
                        elem.addClass('icon-check-empty');
                        elem.removeClass('icon-check');
                        elemText.addClass('bold');
                    }
                }
            });
        });
    }

    return{
        init : accessInit
    }
}();

// ********************************************
// Object staredAction
// Star and unstar item
// ********************************************
var staredAction = function () {
    function accessInit() {
        $('.starAction').click(function(){
            var elem = $(this);
            var url = elem.data('url');

            $.ajax({
                type: "POST",
                url: url,
                dataType: 'json',
                success: function (result) {
                    if (result['result'] == "112") {
                        elem.addClass('icon-star-empty');
                        elem.removeClass('icon-star');
                    } else if (result['result'] == "113") {
                        elem.addClass('icon-star');
                        elem.removeClass('icon-star-empty');
                    }
                }
            });
        });
    }

    return{
        init : accessInit
    }
}();

// ********************************************
// Object createLabel
// Create label
// ********************************************
var createLabel = function () {
    function accessInit() {
        $(".add-label").popover();
    }

    return{
        init : accessInit
    }
}();

// ********************************************
// Object showExternalLink
// Show external link
// ********************************************
var showExternalLink = function () {
    function accessInit() {
        $('.showLink').click(function(){
            var elem = $(this);
            var url = elem.data('url');
            var externalLink = elem.data('externallink');
            var elemText = $('#itemText-id-'+elem.data('id'));

            window.open(externalLink);
            if (elemText.hasClass('bold')) {
                $.ajax({
                    type: "POST",
                    url: url,
                    dataType: 'json',
                    success: function (result) {
                        if (result['result'] == "110") {
                            elemText.removeClass('bold');
                        }
                    }
                });
            }

        });
    }

    return{
        init : accessInit
    }
}();