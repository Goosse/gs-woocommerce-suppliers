
jQuery(document).ready(function(){
  jQuery('.wc_input_stock, .stock').prop( "readonly", true );
  jQuery('.stock').focus(function(){
    jQuery( "<span class='manage_sup_stock' style='padding-left: 5px;color: red;vertical-align: middle;'>Stock managment is only available in full edit mode when tracking suppliers.</span>" ).insertAfter( ".stock" );
    //jQuery("span").css("display", "inline").fadeOut(2000);
  });
  jQuery('#_stock').focus(function(){
    jQuery( "<span class='manage_sup_stock' style='padding-left: 5px;color: red;vertical-align: middle;'>Manage stock by supplier below.</span>" ).insertAfter( "#_stock+span" );
    //jQuery("span").css("display", "inline").fadeOut(2000);
  });
  jQuery('.wc_input_stock, .stock').focusout(function(){
    jQuery(".manage_sup_stock").remove();
  });

  jQuery("#gs_wc_supplier_stock_qty").on("change paste keyup", function() {

    if (jQuery("#gs_wc_supplier_stock_qty").val() < 0){
      jQuery("#gs_wc_supplier_cost").addClass("hidden");
      jQuery('#s2id_gs_wc_add_supplier_id').attr('style', 'display: none !important');
      jQuery('#gs_stock_change_note').css("margin", "0px");
      jQuery('#gs_stock_change_note').css("width", "40%");
    }
    else{
      jQuery("#gs_wc_supplier_cost").removeClass("hidden");
      jQuery('#s2id_gs_wc_add_supplier_id').attr('style', 'display: block;width:30%');
      jQuery('#gs_stock_change_note').css("margin", "8px 5px 0px 0px");
      jQuery('#gs_stock_change_note').css("width", "50%");
    }

  });
});
