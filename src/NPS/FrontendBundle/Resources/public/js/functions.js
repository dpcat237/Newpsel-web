$(document).ready(function(){
    /*Create label*/
    addLabelAction.init();

    /*Create label*/
    createLabel.init();

    /*Custom scrollbar*/
    navScrollbar.init();

    /*Read action*/
    readAction.init();

    /*Show external link*/
    showExternalLink.init();

    /*Show / hide submenu*/
    showSubmenu.init();

    /*Stared action*/
    staredAction.init();

    /*Subscribe to feed*/
    subscribeFeed.init();

    /*Subscribe*/
    validGenericForm.init();

    /*Login*/
    $('input').iCheck({
        checkboxClass: 'icheckbox_polaris',
        radioClass: 'iradio_polaris',
        increaseArea: '-10' // optional
    });
});