jQuery(function ($) {
    $('div.more-text').toggle();

    $('a.show-more').on('click', function (e) {
        $('div.more-text').toggle();
    })
});