$(document).ready(function(){
    /*Subscribe*/
    validGenericForm.init();

    /*Subscribe to feed*/
    subscribeFeed.init();

    /*Show / hide submenu*/
    showSubmenu.init();

    /*Custom scrollbar*/
    navScrollbar.init();

    /*Stared action*/
    staredAction.init();

    /*Read action*/
    readAction.init();

    /*Create label*/
    createLabel.init();

    /*Show external link*/
    showExternalLink.init();

    /*Login*/
    $('input').iCheck({
        checkboxClass: 'icheckbox_polaris',
        radioClass: 'iradio_polaris',
        increaseArea: '-10' // optional
    });
});