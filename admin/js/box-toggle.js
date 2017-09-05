(function($){
    $(document).ready(function(){
        $('.ww-box-toggle-content').hide();

        // open and close widget menu
        $('.ww-box-toggle h3').click(function(){
            $(this).parent().find('.ww-box-toggle-content').slideToggle('fast');
        });

        // next content toggle
        $('body').on('click', '.toggle-next-content', function(){
            $(this).next('.togglable-content').slideToggle('fast');
        })
    });
})(jQuery);
