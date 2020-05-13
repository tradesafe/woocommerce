jQuery(function ($) {
    $('div.more-text').toggle();

    $('a.show-more').on('click', function (e) {
        $('div.more-text').toggle();
    });

    $('form.woocommerce-form-register').prepend('<a href="#" id="tradesafe-login">Already have a TradeSafe account?</a>');

    $('#tradesafe-login').on('click', function (e) {
        e.preventDefault();

        var data = {
            'action': 'woocommerce_tradesafe_ajax_login',
            'page_url': document.location.protocol + "//" + document.location.hostname + document.location.pathname
        };

        jQuery.post(woocommerce_params.ajax_url, data, function(response) {
            var auth_data = $.parseJSON(response);
            var url = tradesafe_params.api_url + '/authorize';
            var form_html = '<form action="' + url + '" method="POST">';
            $.each(auth_data, function (i,v) {
                form_html += '<input type="hidden" name="' + i + '" value="' + v + '">';
            });
            form_html += '</form>';

            var form = $(form_html);

            $('body').append(form);
            form.submit();
        });
    });
});
