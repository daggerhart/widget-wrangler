  jQuery(document).ready(function(){          
    jQuery('.ww-corral-edit').click(
      function(){
        jQuery(this).siblings('.ww-corral-details').slideToggle();
    });
    
    jQuery('.ww-delete-corral').submit(
      function(){
        var slug = jQuery(this).children('input[name=ww-delete-slug]').val();
        
        var ask = confirm('Whoa-there partner! Are you sure you want to delete this corral?');
        if (ask) {
          return true;
        }
        else
        {
          return false;
        }
      });
    
    jQuery('#ww-corrals-sort').sortable({
      update: function(event,ui){
        var all_corrals = jQuery('#ww-corrals-sort').children('.ww-corral-sort-item');  
        //console.log(all_corrals);
         jQuery.each(all_corrals, function(i){
            var weight_input = jQuery(this).children('.ww-corral-weight');
            var count = i+1;
            //console.log("B "+jQuery(weight_input).attr("name"));
            jQuery(weight_input).attr("name", "weight["+count+"]");
            //console.log("A "+jQuery(weight_input).attr("name"));
            //console.log(count+' - '+jQuery(this).children('.ww-corral-weight').val());//.attr('name'));
        });
      }
      
    }).disableSelection();
  });