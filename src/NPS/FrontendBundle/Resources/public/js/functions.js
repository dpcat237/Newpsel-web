(function($){

    //cache nav
    var nav = $("#topNav");

    //add indicator and hovers to submenu parents
    nav.find("li").each(function() {
        if ($(this).find("ul").length > 0) {
            $("<span>").text("^").appendTo($(this).children(":first"));

            //show subnav on hover
            $(this).mouseenter(function() {
                $(this).find("ul").stop(true, true).slideDown();
            });

            //hide submenus on exit
            $(this).mouseleave(function() {
                $(this).find("ul").stop(true, true).slideUp();
            });
        }
    });
})(jQuery);