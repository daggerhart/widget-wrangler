
jQuery(function(){
  function toggle_no_widgets()
  {
    var lists = jQuery("ul.ww-sortable");
    jQuery.each(lists, function(){
      var num_items = jQuery(this).children('.ww-item');
      if(num_items.length > 0)
      {
        jQuery(this).children('.ww-no-widgets').hide();
      }
      else
      {
        jQuery(this).children('.ww-no-widgets').show();
      }
          //console.log(num_items.length);
    });
  }
  function update_widget_weights()
  {
    var lists = jQuery("ul.ww-sortable");
    jQuery.each(lists, function(){
      var this_list = jQuery(this).children(".ww-item");
        jQuery.each(this_list, function(i){
          jQuery(this).children(".ww-widget-weight").val(i+1);// stop working in 3.2:  .attr("disabled","");
        });
    });
  }
  function refresh_all()
  {
    // Auto change sort order when drag and drop
    var sortable_lists = jQuery("ul.ww-sortable");
    jQuery("ul.ww-sortable").sortable({
      connectWith: '.ww-sortable',
      update: function(event,ui){
        var active_widgets = jQuery(this).children(".ww-item");
        var sidebar_name = jQuery(this).attr("name");
        //console.log(active_widgets);
         jQuery.each(active_widgets, function(i){
            jQuery(this).children("select").val(sidebar_name);
            //console.log((i+1)+" - "+jQuery(this).attr("id"));
        });
        toggle_no_widgets();
        update_widget_weights();
      }
    }).disableSelection();
    
    
    var selects = jQuery("#widget-wrangler-form .nojs select");
    jQuery.each(selects, function(){
      jQuery(this).parent('.ww-item').removeClass('nojs');
      jQuery(this).change(function(){
        var select_val = jQuery(this).val();
        var select_name = jQuery(this).attr("name");
        
        //console.log(select_val);
        //console.log(select_name);
        if ( select_val != 'disabled')
        {
          //console.log(jQuery(this).val());
          //console.log(jQuery(this));
          jQuery(this).parent('.ww-item').clone()
            .addClass('nojs').prependTo("#ww-sidebar-"+select_val+"-items").removeClass("disabled");
          jQuery(this).parent('.ww-item').remove();
          jQuery("#ww-sidebar-"+select_val+"-items select[name='"+select_name+"']").val(select_val);
          
          var this_list = jQuery("#ww-sidebar-"+select_val+"-items").children(".ww-item");
          //console.log(this_list);
        }
        else
        {
          jQuery(this).siblings('.ww-widget-weight').val('').parent('.ww-item').clone().addClass('nojs disabled').appendTo("#ww-disabled-items").children(".ww-widget-weight").attr("disabled","disabled");
          jQuery(this).parent('.ww-item').remove();
          jQuery("#ww-disabled-items select[name='"+select_name+"']").val(select_val);
        }
        update_widget_weights();
        toggle_no_widgets();
        refresh_all();
      });
    });
  }
  
  refresh_all();
  
});
jQuery(document).ready(function(){
  // open and close widget menu
  jQuery('.widget-action').click(function(){
    jQuery(this).parent('div').parent('div').next('.widget-inside').slideToggle();
  });
  // handle advanced parsing template description/option
  if(jQuery('#ww-adv-parse-toggle').is(':checked')){
    jQuery('#ww-advanced-template').show();
  } else {
    jQuery('#ww-advanced-template').hide();
  }
  jQuery('#ww-adv-parse-toggle').click(function(){
    jQuery('#ww-advanced-template').toggle();
  });
});