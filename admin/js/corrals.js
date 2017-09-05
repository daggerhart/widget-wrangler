(function($){
    $(document).ready(function(){
        $('#ww-corrals-sort-list').sortable({
            update: function(event,ui){
                var all_corrals = $('#ww-corrals-sort-list').children('.ww-item');

                $.each(all_corrals, function(i){
                    var weight_input = $(this).children('input[type=hidden]');
                    var count = i+1;
                    $(weight_input).attr("name", "weight["+count+"]");
                });
            }
        }).disableSelection();
    });
})(jQuery);
