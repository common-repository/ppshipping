jQuery(document).ready(function() {
	
	if (jQuery('body').hasClass('woocommerce-checkout')) {
		
		jQuery('#billing_address_2_field').after('<p class="form-row address-field form-row-wide" id="billing_suburb_field" data-priority="65"></p>');
		jQuery('#shipping_address_2_field').after('<p class="form-row address-field form-row-wide" id="shipping_suburb_field" data-priority="65"></p>');
		
		var suburb_select = jQuery('#pp_suburb_field').html();
		
		jQuery('#pp_suburb_field').remove();
		
		if (jQuery('#ship-to-different-address-checkbox').is(":checked")) {
			jQuery('#shipping_suburb_field').html(suburb_select);
		}
		else {
			jQuery('#billing_suburb_field').html(suburb_select);
		}
		
		jQuery('#pp_suburb').val('');
		jQuery('#pp_suburb').attr('autocomplete','none');
	}
	
	jQuery('body').on('focus','#pp_suburb, .origin_shop_code, .pp_edit_suburb',function() {
		jQuery('html.responsive').addClass('noscroll');
		jQuery('.popupbg').addClass('makepopupscroll');
		jQuery('.popupcontentcontainer #ppshipping_loader').hide();
		jQuery('.popupbg').show();
	});
	
	jQuery('.popupbg a.closeviewoptions').on('click',function(e) {
		e.preventDefault();
		
		jQuery('html.responsive').removeClass('noscroll');
		jQuery('.popupbg').removeClass('makepopupscroll');
		jQuery('.popupcontentcontainer #ppshipping_loader').hide();
		jQuery('.suburb_list').html('');
		jQuery('.popupresult').hide();
		jQuery('.popupinfo').show();
		jQuery('#ppshipping_suburb_finder').val('');
		jQuery('.popupbg').hide();
		
		return false;
	});
	
	jQuery('.popupbg a.refresh_suburb_search').on('click',function(e) {
		e.preventDefault();
		
		jQuery('.suburb_list').html('');
		jQuery('.popupresult').hide();
		jQuery('.popupinfo').show();
		jQuery('#ppshipping_suburb_finder').val('');
		
		return false;
	});
	
	jQuery('.popupresult').on('click','.ppshipping_suburb_result',function() {
		var selected_value = jQuery(this).html();
		
		var placename = selected_value.substring(0, selected_value.length-6);
		var placecode = selected_value.substring(selected_value.length-4, selected_value.length);
		
		jQuery('#pp_suburb_code').val(placecode);
		
		jQuery('html.responsive').removeClass('noscroll');
		jQuery('.popupbg').removeClass('makepopupscroll');
		jQuery('.popupcontentcontainer #ppshipping_loader').hide();
		jQuery('.suburb_list').html('');
		jQuery('.popupresult').hide();
		jQuery('.popupinfo').show();
		jQuery('#ppshipping_suburb_finder').val('');
		jQuery('.popupbg').hide();
		
		if (jQuery('#woocommerce_ppshipping_shop_place_code').length) {
			jQuery('#woocommerce_ppshipping_shop_place_code').val(selected_value);
		}
		
		if (jQuery('#pp_edit_suburb').length) {
			jQuery('#pp_edit_suburb').val(selected_value);
		}
		
		if (jQuery('#pp_suburb').length) {
			jQuery('#pp_suburb').val(placename);
			jQuery( 'body' ).trigger( 'update_checkout' );
		}
	});
	
	jQuery('#ppshipping_generate_waybill').on('click',function(e) {
		e.preventDefault();
		var quote = jQuery(this).attr('data-quote');
		var order = jQuery(this).attr('data-order');
		
		jQuery('#ppshipping_details').hide();
		jQuery('#ppshipping_waybill_loader').show();
		
		jQuery.ajax({
			type : "post",
			url : pp_path.ajax,
			data : {
					action: "ppshipping_generate_waybill",
					quote : quote, 
					order: order
			},
			success: function(response) {
				window.location.assign(location.href);
			}
		});
	});
	
	jQuery('#ppshipping_update_order_suburb').on('click',function(e) {
		e.preventDefault();
		var order = jQuery(this).attr('data-order');
		var suburb = jQuery('#pp_edit_suburb').val();
		var suburb_confirm = jQuery('#pp_edit_suburb_confirm').val();
		
		jQuery('#ppshipping_suburb_details').hide();
		jQuery('#ppshipping_edit_loader').show();
		
		if (suburb != suburb_confirm) {
			jQuery.ajax({
				type : "post",
				url : pp_path.ajax,
				data : {
						action: "ppshipping_update_order_suburb",
						order: order,
						suburb: suburb
				},
				success: function(response) {
					window.location.assign(location.href);
				}
			});
		}
		else {
			window.location.assign(location.href);
		}
	});
	
	jQuery('body').on('change','select.origin_shop_code',function(e) {
		jQuery('#woocommerce_ppshipping_shop_place_code').val(jQuery(this).val() + ' - ' + jQuery( "select.origin_shop_code option:selected" ).text());
	});
	
	jQuery('#ship-to-different-address-checkbox').on('click',function() {
		if (jQuery('#ship-to-different-address-checkbox').is(":checked")) {
			var suburb_select = jQuery('#billing_suburb_field').html();
			
			jQuery('#billing_suburb_field').html('');
			
			jQuery('#shipping_suburb_field').html(suburb_select);
		}
		else {
			var suburb_select = jQuery('#shipping_suburb_field').html();
			
			jQuery('#shipping_suburb_field').html('');
			
			jQuery('#billing_suburb_field').html(suburb_select);
		} 
	});
	
	jQuery('#pp_pack_no_items').on('change',function () {
		var amount = jQuery(this).val();
		jQuery('#pp_package_dim_breakdown div').html('');
		
		jQuery('#ppshipping_loader').show();
		jQuery('#FormContent').hide();
		
		if (amount > 0) {			
			jQuery.ajax({
				type : "post",
				url : pp_path.ajax,
				data : {
					'action': "ppshipping_get_dim_breakdown",
					'amount' : amount
				},
				success: function(response) {
					jQuery('#pp_package_dim_breakdown div').html(response);
					jQuery('#pp_package_dim_breakdown').show();
					
					jQuery('#ppshipping_loader').hide();
					jQuery('#FormContent').show();
				}
			});
		}
	});
	
	jQuery('.ppshipping_autoprint').on('click',function(e) {
		e.preventDefault();
		
		var order = jQuery(this).attr('data-waybill');
		
		jQuery('#ppshipping_waybill_iframe').attr("src", pp_path.theme_path + '/OrderWaybills/'+order+'_Waybill.pdf').load(function(){
			document.getElementById('ppshipping_waybill_iframe').contentWindow.print();
		});
		
		jQuery('#ppshipping_label_iframe').attr("src", pp_path.theme_path + '/OrderLabels/'+order+'_Label.pdf').load(function(){
			document.getElementById('ppshipping_label_iframe').contentWindow.print();
		});
		
		return false;
	});
});

function ppshipping_find_suburb() {
	var searchTerm = jQuery('#ppshipping_suburb_finder').val();
	var searchType = 'name';
	
	jQuery('.suburb_list').html('');
	
	if (searchTerm == '' || searchTerm == null) {
		jQuery('.popupinfo').hide();
		jQuery('.suburb_list').html('<span style="color: red;">Please enter a suburb name or postal code to search for your suburb.</span>');
		jQuery('.popupresult').show();
		return false;
	}
	
	if (isNumeric(searchTerm)) {
		searchType = 'postal_code';
	}
	
	jQuery('.popupinfo').hide();
	jQuery('#ppshipping_loader').show();
	
	jQuery.ajax({
		type : "post",
		url : pp_path.ajax,
		data : {
			'action': "ppshipping_get_places",
			'searchTerm' : searchTerm,
			'searchType' : searchType
		},
		success: function(response) {
			jQuery('.popupresult').show();
			jQuery('#ppshipping_loader').hide();
			
			var results = JSON.parse(response);
			
			jQuery.each(results, function(index, element) {
				jQuery('.suburb_list').append('<div class="ppshipping_suburb_result">'+element.text+' - '+element.id+'</div>');
			});
		}
	});
	
	return false;
}

function isNumeric(str) {
  if (typeof str != "string") return false // we only process strings!  
  return !isNaN(str) &&
         !isNaN(parseFloat(str))
}

function Add_Shipping_Package() {
	var shipping_class = jQuery('#pp_pack_shipping_class').val();
	var shipping_class_name = jQuery( "#pp_pack_shipping_class option:selected" ).text();
	var shipping_class_waybill = jQuery('#pp_pack_ignore').val();
	var label = jQuery("input[name='ppshipping_breakdown_label[]']").map(function(){return jQuery(this).val();}).get();
	var no_items = jQuery('#pp_pack_no_items').val();
	var width = jQuery("input[name='ppshipping_breakdown_width[]']").map(function(){return jQuery(this).val();}).get();
	var length = jQuery("input[name='ppshipping_breakdown_length[]']").map(function(){return jQuery(this).val();}).get();
	var height = jQuery("input[name='ppshipping_breakdown_height[]']").map(function(){return jQuery(this).val();}).get();
	var weight = jQuery("input[name='ppshipping_breakdown_weight[]']").map(function(){return jQuery(this).val();}).get();
	
	if (shipping_class == '' || shipping_class == null) {
		alert('Please select a valid shipping class for this shipping package.  If no options exist, please create a shipping class or delete an existing shipping package.');
		return false;
	}
	
	if (label == '' || label == null) {
		alert('Please enter valid label(s) for this shipping package.');
		return false;
	}
	
	if (no_items == '' || no_items == null || no_items < 1) {
		alert('Please enter a valid number of items for this shipping package.');
		return false;
	}
	
	if (width == '' || width == null) {
		alert('Please enter valid width(s) in cm for this shipping package box.');
		return false;
	}
	
	if (length == '' || length == null) {
		alert('Please enter valid length(s) in cm for this shipping package box.');
		return false;
	}
	
	if (height == '' || height == null) {
		alert('Please enter valid height(s) in cm for this shipping package box.');
		return false;
	}
	
	if (weight == '' || weight == null) {
		alert('Please enter valid weight(s) in kg for this shipping package box.');
		return false;
	}
	
	jQuery('#ppshipping_loader').show();
	jQuery('#FormContent').hide();
	
	jQuery.ajax({
		type : "post",
		url : pp_path.ajax,
		data : {
			'action': 'ppshipping_save_shipping_package',
			'shipping_class' : shipping_class,
			'shipping_class_name' : shipping_class_name,
			'shipping_class_waybill' : shipping_class_waybill,
			'label' : label,
			'no_items' : no_items,
			'width' : width,
			'length' : length,
			'height' : height,
			'weight' : weight
		},
		success: function(response) {
			window.location.assign(pp_path.admin_packages);
		}
	});
	
	return false;
}

function Update_Shipping_Package() {
	var shipping_class = jQuery('#pp_pack_shipping_class').val();
	var shipping_class_name = jQuery( "#pp_pack_shipping_class option:selected" ).text();
	var shipping_class_waybill = jQuery('#pp_pack_ignore').val();
	var label = jQuery("input[name='ppshipping_breakdown_label[]']").map(function(){return jQuery(this).val();}).get();
	var no_items = jQuery('#pp_pack_no_items').val();
	var width = jQuery("input[name='ppshipping_breakdown_width[]']").map(function(){return jQuery(this).val();}).get();
	var length = jQuery("input[name='ppshipping_breakdown_length[]']").map(function(){return jQuery(this).val();}).get();
	var height = jQuery("input[name='ppshipping_breakdown_height[]']").map(function(){return jQuery(this).val();}).get();
	var weight = jQuery("input[name='ppshipping_breakdown_weight[]']").map(function(){return jQuery(this).val();}).get();
	
	var package_id = jQuery('#package_id').val();
	
	if (shipping_class == '' || shipping_class == null) {
		alert('Please select a valid shipping class for this shipping package.  If no options exist, please create a shipping class or delete an existing shipping package.');
		return false;
	}
	
	if (label == '' || label == null) {
		alert('Please enter valid label(s) for this shipping package.');
		return false;
	}
	
	if (no_items == '' || no_items == null || no_items < 1) {
		alert('Please enter a valid number of items for this shipping package.');
		return false;
	}
	
	if (width == '' || width == null) {
		alert('Please enter valid width(s) in cm for this shipping package box.');
		return false;
	}
	
	if (length == '' || length == null) {
		alert('Please enter valid length(s) in cm for this shipping package box.');
		return false;
	}
	
	if (height == '' || height == null) {
		alert('Please enter valid height(s) in cm for this shipping package box.');
		return false;
	}
	
	if (weight == '' || weight == null) {
		alert('Please enter valid weight(s) in kg for this shipping package box.');
		return false;
	}
	
	jQuery('#ppshipping_loader').show();
	jQuery('#FormContent').hide();
	
	jQuery.ajax({
		type : "post",
		url : pp_path.ajax,
		data : {
			'action': 'ppshipping_update_shipping_package',
			'package_id' : package_id,
			'shipping_class' : shipping_class,
			'shipping_class_name' : shipping_class_name,
			'shipping_class_waybill' : shipping_class_waybill,
			'label' : label,
			'no_items' : no_items,
			'width' : width,
			'length' : length,
			'height' : height,
			'weight' : weight
		},
		success: function(response) {
			window.location.assign(pp_path.admin_packages);
		}
	});
	
	return false;
}

function Delete_Shipping_Package() {
	var package_id = jQuery('#package_id').val();
	
	jQuery('#ppshipping_loader').show();
	jQuery('#FormContent').hide();
	
	jQuery.ajax({
		type : "post",
		url : pp_path.ajax,
		data : {
			'action': 'ppshipping_delete_shipping_package',
			'package_id' : package_id
		},
		success: function(response) {
			window.location.assign(pp_path.admin_packages);
		}
	});
	
	return false;
}