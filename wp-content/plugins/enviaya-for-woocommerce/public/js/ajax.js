jQuery( document ).ready( function(){
    if (jQuery('#calc_shipping_postcode').val() == '') {
        jQuery('#shipping_method li').hide();
        var subtotal = jQuery('.cart-subtotal td span.woocommerce-Price-amount.amount').text();
	    jQuery('.order-total td span.woocommerce-Price-amount.amount').text(subtotal);
    }
    // getFrontTitle();
    // getCarriersLogo();
    jQuery(document).on('click','#shipping_method li :not(input)', function(){
        jQuery(this).parent().find('input').click();
    });

    jQuery('body').on('updated_checkout', function(){
        jQuery(".enviaya-info.err").not(":first").hide();
    });

    jQuery('body').on('updated_wc_div', function(){
        jQuery(".enviaya-info.err").not(":first").hide();
    });
});

function getFrontTitle()
{
    var data = {
        'action': 'enviaya_front_title',
    };

    jQuery.post(EnviayaAjax.ajaxurl, data, function(response) {
        if (response != '') {
            jQuery('.shop_table .shipping').before('<tr><td colspan="3" class="shipping_title">' + response + '</td></tr>');
            jQuery('.shop_table .shipping th').text(response);
            jQuery('.shop_table .shipping_title').slideDown();
        }
    });
}

function getCarriersLogo()
{
    var data = {
        'action': 'enviaya_carriers_logo',
    };

    jQuery.post(EnviayaAjax.ajaxurl, data, function(response) {
        if (response != 'error') {
            jQuery('#shipping_method li label').text('');

            response = jQuery.parseJSON(response);

            if (response.show_carrier_logo == 1) {
                jQuery('#shipping_method').addClass('env_show_logo');
            }
            if (response.shipping_service_design != 0) {
                jQuery('#shipping_method').addClass('env_rate_design_' + response.shipping_service_design);
            }
            if (response.group_by_carrier == 1) {
                jQuery('#shipping_method').addClass('env_group');
            }
            jQuery.each(response.rates, function (carrier, rates) {
                var first_key = Object.keys(rates)[0];
                jQuery('#shipping_method li input[value=' + first_key + ']').parent().before('<div class="env_carrier"><img src="' + response.carrier_logos[carrier] + '" alt=""><span>'+carrier+'</span></div>');
                jQuery.each(rates, function (rate_id, rate) {
                    jQuery('#shipping_method li input[value=' + rate_id + ']').parent().find('label')
                        .before('<img src="' + rate.carrier_logo + '" alt="">' +
                            'test1<span class="env_carrier_name">' + rate.carrier_name + '</span>' +
                            '<span class="env_carrier_service_name">' + rate.carrier_service_name + '</span>' +
                            '<span class="env_delivery_cost"><i>' + rate.currency_symbol + '</i>' + rate.delivery_cost + '</span>' +
                            '<span class="env_est_delivery">' + rate.estimated_delivery + '</span>'
                        );
                });

            });
        }
    });
}
