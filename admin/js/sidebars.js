
(function ($) {
    $(document).ready(function(){
        $('.ww-alter-sidebar-original .content').hide();
        $('.ww-alter-sidebar-original .toggle').click(function(){
            $(this).next('.content').toggle();
        });
    });
})(jQuery);