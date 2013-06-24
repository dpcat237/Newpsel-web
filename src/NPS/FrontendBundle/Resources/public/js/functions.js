$(document).ready(function(){
    /*Subscribe*/
    validGenericForm.init();

    /*Subscribe to feed*/
    subscribeFeed.init();

    /*Login*/
    $('input').iCheck({
        checkboxClass: 'icheckbox_polaris',
        radioClass: 'iradio_polaris',
        increaseArea: '-10' // optional
    });
});


/*Angularjs*/
function FetchCtrl() {
    alert('tut: ');
}