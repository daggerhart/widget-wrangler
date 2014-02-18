jQuery(document).ready(function(){          

  // open and close widget menu
  jQuery('.widget-action').click(function(){
    jQuery(this).parent('div').parent('div').next('.widget-inside').slideToggle();
  });
  
  // delete confirm
  jQuery('.ww-delete-corral').submit(function(){
      var ask = confirm('Whoa-there partner! Are you sure you want to delete this corral?');			
      return (ask) ? true : false;
  });
  
  // sorting
  jQuery('#ww-corrals-sort-list').sortable({
    update: function(event,ui){
      var all_corrals = jQuery('#ww-corrals-sort-list').children('.ww-corral-sort-item');
      
      jQuery.each(all_corrals, function(i){
          var weight_input = jQuery(this).children('.ww-corral-weight');
          var count = i+1;
          jQuery(weight_input).attr("name", "weight["+count+"]");
      });
    }
  }).disableSelection();
});