  jQuery(document).ready(function(){          
    jQuery('.ww-sidebar-edit').click(
      function(){
        jQuery(this).siblings('.ww-sidebar-details').slideToggle();
    });
    
    jQuery('.ww-delete-sidebar').submit(
      function(){
        var slug = jQuery(this).children('input[name=ww-delete-slug]').val();
        
        var ask = confirm('Whoa-there partner! Are you sure you want to delete this sidebar?');
        if (ask) {
          return true;
        }
        else
        {
          return false;
        }
      });
    
    jQuery('#ww-sidebars-sort').sortable({
      update: function(event,ui){
        var all_sidebars = jQuery('#ww-sidebars-sort').children('.ww-sidebar-sort-item');  
        //console.log(all_sidebars);
         jQuery.each(all_sidebars, function(i){
            var weight_input = jQuery(this).children('.ww-sidebar-weight');
            var count = i+1;
            //console.log("B "+jQuery(weight_input).attr("name"));
            jQuery(weight_input).attr("name", "weight["+count+"]");
            //console.log("A "+jQuery(weight_input).attr("name"));
            //console.log(count+' - '+jQuery(this).children('.ww-sidebar-weight').val());//.attr('name'));
        });
      }
      
    }).disableSelection();
  });