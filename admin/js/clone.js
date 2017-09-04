(function($){
    $(document).ready(function(){
        // open and close widget menu
        $('.widget-top').click(function(){
            $(this).closest('.widgets-holder-wrap').find('.widget-inside').slideToggle('fast');
        });
    });
})(jQuery);