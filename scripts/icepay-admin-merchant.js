(function($){
    $('#edit-uc-icepay-secret-code').hideShowPassword(false);
    $('#icepay-merchant-showhide-secret').on('click', function(e) {
        e.preventDefault();
        $('#edit-uc-icepay-secret-code').hideShowPassword('toggle');
    });
})(jQuery);;
