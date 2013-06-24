/*List of urls*/
var jsUrl = function () {
    var URL_HOMEPAGE = "";
    var URL_SUBSCRIBE_NEWSLETTER = "";
    var URL_SUBSCRIBE_FEED = "";

    return {
        URL_HOMEPAGE : URL_HOMEPAGE,
        URL_SUBSCRIBE_NEWSLETTER : URL_SUBSCRIBE_NEWSLETTER,
        URL_SUBSCRIBE_FEED : URL_SUBSCRIBE_FEED
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
    }

    return{
        init : accessInit
    }
}();