jQuery(window).load(function() {
    apikey = jQuery('#woocommerce_enviaya_api_key_production').val();

    if (!apikey) {
        jQuery('#thickbox').click();
    }
});

jQuery(document).ready(function() {
    apikey = jQuery('input[name=apikey]').val();
    account = jQuery('input[name=account]').val();

    jQuery.each(jQuery('.view.shipment'), function (shipment, attr) {

        ship_num = attr.getAttribute('ship_num');
        carrier = attr.getAttribute('carrier');

        var data = {
            action: 'tracking_order',
            enviaya_account: account,
            api_key: apikey,
            shipment_number: ship_num,
            carrier: carrier
        };

        var elem = jQuery(this);
        jQuery.post(EnviayaAjax.ajaxurl, data, function(response) {

            var res = response.split('|||');
            elem.find('.status_image#status-'+res[0]).show();
        });

    });

    jQuery(document).on('blur','#woocommerce_enviaya_api_key_production, #woocommerce_enviaya_api_key_test', function(){
        getAccounts();
        getOriginAddress();
    });

    // jQuery('#select2-woocommerce_enviaya_enviaya_account-container').hover(function() {
    //     getAccounts();
    // });
    //
    // jQuery('#select2-woocommerce_enviaya_origin_address-container').hover(function() {
    //     getOriginAddress();
    // });

    jQuery('#ship').off('click').on('click', function(){
        url = window.location.search;
        url = url.replace('?post=','');
        url2 = url.split('&');

        jQuery('#loader').css("z-index", "1");

        var rate_id = jQuery('#carrier_list option:selected').attr('rate_id');

        var data = {
            action: 'create_shipment',
            order_id: url2[0],
            rate_id: rate_id
        };

        jQuery.post(EnviayaAjax.ajaxurl, data, function(response) {
            jQuery('#meta-box-ship').show();
            jQuery('#meta-box-ship h7').hide();

            var res = response.split('|||');

            jQuery('#meta-box-ship .inside').prepend(res[0]);

            jQuery('#loader').css("z-index", "-1");
            jQuery('#download_label button').trigger('click');

            jQuery("#meta_box_new").hide();
        });
    });

    jQuery('.refresh-shipment-status').click(function(){
        var data = {
            action: 'refresh_shipment_status',
            shipment_id: jQuery(this).parents('ul').data('shipment-id')
        };

        var selector = jQuery(this).parents('li').find('#text-entry-status');
        selector.html('Loading...');

        jQuery.post(EnviayaAjax.ajaxurl, data, function(response) {
            selector.html(response);
        });
    });

    jQuery(document).on('click', '#shipment-status-edit', function() {
        jQuery('#shipment-status-edit').hide();
        jQuery('#text-entry-status').hide();
        jQuery('#edit-status-form').show();
        jQuery('#edit-status-field').show();
        jQuery('#edit-status-submit').show();
    });
    jQuery(document).on('click', '#edit-status-submit', function(){
        jQuery('#shipment-status-edit').show();
        jQuery('#edit-status-form').hide();
        jQuery('#edit-status-field').hide();
        jQuery('#edit-status-submit').hide();
        var status = jQuery('#edit-status-field');
        status.show();
        jQuery('#edit-status-submit').show();
        var v = jQuery('#order_id_form')[0].value;
        status = status[0].value;
        jQuery('#text-entry-status')[0].value=status;
        data={
            action: 'edit_envia_status',
            id: v,
            status_entry : status
        }
        jQuery.post(EnviayaAjax.ajaxurl, data, function(response) {
        });
    });
    jQuery(document).on('click', '#optain_button', function(){
        var country_code = jQuery('#country_code').val();
        var postal_code = jQuery('#postal_code').val();
        var state_code = jQuery('#state_code').val();
        jQuery('#loader').css("z-index", "1");
        url = window.location.search;
        url = url.replace('?post=','');
        url2 = url.split('&');

        var data = {
            action: 'optain_service',
            order_id: url2[0],
            country_code: country_code,
            postal_code: postal_code,
            state_code: state_code,
        };
        jQuery.post(EnviayaAjax.ajaxurl, data, function(response) {

            var res = response.split('|||');

            if (res[0] === 'false') {
                jQuery('#optain_ship > h4').show();
                jQuery('#loader').css("z-index", "-1");
                return;
            }
            jQuery('#carrier_list').prepend(res[0]);

            var obj = jQuery('#carrier_list')[0];
            var item = obj.children[0];
            var rate = item.getAttribute('rate_id');
            document.cookie =  'rate_id=' + rate;
            // jQuery.cookie("rate_id", item);
            // var rate_id = item.getAttribute('rate_id');
            // document.cookie = 'rate_id='+rate_id+';';

            var carrier_header = jQuery('#carrier_header').text();

            if (carrier_header !== 'Free shipping') {
                jQuery('#download_label').hide();
            }

            jQuery('#loader').css("z-index", "-1");
        });
    });

    jQuery(document).on('click', '#down_label', function(){
        url = window.location.search;
        url = url.replace('?post=','');
        url2 = url.split('&');

        jQuery('#loader').css("z-index", "1");

        var data = {
            action: 'download_shipment',
            order_id: url2[0]
        };

        jQuery.post(EnviayaAjax.ajaxurl, data, function(response) {
            jQuery('#meta-box-ship').show();
            jQuery('#meta-box-ship h7').hide();

            var res = response.split('|||');

            jQuery('#meta-box-ship .inside').prepend(res[0]);

            jQuery('#loader').css("z-index", "-1");
        });
    });

});

function getAccounts()
{
    var api_key;
    if (jQuery('#woocommerce_enviaya_enabled_test_mode:checked').length == 0) {
        api_key = jQuery('#woocommerce_enviaya_api_key_production').val();
    } else {
        api_key = jQuery('#woocommerce_enviaya_api_key_test').val();
    }
    var data = {
        'action': 'enviaya_ajax_get_accounts',
        'api_key': api_key
    };

    jQuery.post(ajaxurl, data, function(response) {
        var count = response.split('<option').length - 1;
        console.log(response);
        jQuery('#woocommerce_enviaya_enviaya_account option').remove();
        jQuery('#woocommerce_enviaya_enviaya_account').append(response);

        jQuery('#instruction_billing_account option').remove();
        jQuery('#instruction_billing_account').append(response);

        if (count == 1)
            jQuery('#countinue1').attr('var1', true);
        else
            jQuery('#countinue1').attr('var1', false);

        if (response.includes('<option value="none"')) {
            jQuery('#countinue1').attr('var1', false);
        }
    });
}

function getOriginAddress()
{

    var api_key;
    if (jQuery('#woocommerce_enviaya_enabled_test_mode:checked').length == 0) {
        api_key = jQuery('#woocommerce_enviaya_api_key_production').val();
    } else {
        api_key = jQuery('#woocommerce_enviaya_api_key_test').val();
    }
    var data = {
        'action': 'enviaya_ajax_get_origin_address',
        'api_key': api_key
    };

    jQuery.post(ajaxurl, data, function(response) {
        var count = response.split('<option').length - 1;
        console.log(response);

        jQuery('#woocommerce_enviaya_origin_address option').remove();
        jQuery('#woocommerce_enviaya_origin_address').append(response);
        jQuery('#instruction_origin_address option').remove();
        jQuery('#instruction_origin_address').append(response);

        if (count == 1)
            jQuery('#countinue1').attr('var2', true);
        else
            jQuery('#countinue1').attr('var2', false);

        if (response.includes('<option value="none"')) {
            jQuery('#countinue1').attr('var2', false);
        }

        ord_adr_func();
    });
}


// admin

if(document.getElementById("enviaya_admin")) {
    lang = lang();
    jQuery('#countinue1').on('click', function () {
        var api = jQuery('#instruction_api').val();
        var test_api = jQuery('#instruction_test_api').val();
        if (api != '') {
            jQuery('#woocommerce_enviaya_api_key_production').val(api);
            jQuery('#woocommerce_enviaya_api_key_production').blur();

            jQuery('#instruction_api').removeClass("error");

            if (test_api != '') {
                jQuery('#woocommerce_enviaya_api_key_test').val(test_api);
                jQuery('#woocommerce_enviaya_api_key_test').blur()
            }

            var i = 0;

            var timer = setInterval(function () {
                if (jQuery('#countinue1').attr('var1') != '' && jQuery('#countinue1').attr('var2') != '') {
                    clearInterval(timer);
                }

                if (jQuery('#countinue1').attr('var1') == 'false') {
                    jQuery('#slide1').hide();
                    jQuery("#instruction_billing_account").select2({
                        minimumResultsForSearch: -1,
                        width: 400
                    }).on('change', function (e) {
                        jQuery("#woocommerce_enviaya_enviaya_account").val(this.value);
                        jQuery("#woocommerce_enviaya_enviaya_account").trigger('change');
                    });

                    if (jQuery('#instruction_billing_account option').val() == 'none') {
                        jQuery('#select1_error').show()
                    }
                    // console.log(jQuery('#instruction_billing_account option').val());

                    jQuery('#slide2').show();
                } else if (jQuery('#countinue1').attr('var1') == 'true') {
                    if (jQuery('#countinue1').attr('var2') == 'false') {
                        jQuery('#slide1').hide();
                        jQuery("#instruction_origin_address").select2({
                            minimumResultsForSearch: -1,
                            width: 400
                        }).on('change', function (e) {
                            jQuery("#woocommerce_enviaya_origin_address").val(this.value);
                            jQuery("#woocommerce_enviaya_origin_address").trigger('change');
                        });

                        if (jQuery('#instruction_origin_address option').val() == 'none') {
                            jQuery('#select2_error').show()
                        }

                        jQuery('#slide3').show();
                    } else if (jQuery('#countinue1').attr('var2') == 'true') {
                        jQuery('#slide1').hide();
                        jQuery('#slide4').show();
                    }

                }
                console.log(jQuery('#countinue1').attr('var1'));
                console.log(jQuery('#countinue1').attr('var2'));
                i++;
                if (i > 40) {
                    alert('stop');
                    clearInterval(timer);
                }
            }, 500);
        } else {
            jQuery('#instruction_api').addClass("error");
        }
    });

    jQuery('#countinue2').on('click', function () {
        jQuery('#slide2').hide();
        jQuery("#instruction_origin_address").select2({
            minimumResultsForSearch: -1,
            width: 400
        }).on('change', function (e) {
            jQuery("#woocommerce_enviaya_origin_address").val(this.value);
            jQuery("#woocommerce_enviaya_origin_address").trigger('change');
        });

        if (jQuery('#instruction_origin_address option').val() == 'none') {
            jQuery('#select2_error').show()
        }

        jQuery('#slide3').show();
    });

    jQuery('#countinue3').on('click', function () {
        jQuery('#slide3').hide();
        jQuery('#slide4').show();
    });

    jQuery('#countinue4').on('click', function () {
        jQuery('#slide4').hide();
        jQuery('#slide5').show();
    });

    jQuery('#close_setup').on('click', function () {
        jQuery('#TB_closeWindowButton').click();
        jQuery('.woocommerce-save-button').click();
        // jQuery('#slide5').hide();
        // jQuery('#slide1').show();
    });


    jQuery('#thickbox').click();

    function changeSelectedAddress(address) {
        var addressBlock = document.getElementById('full_address');
        var addressKeys = ['full_name', 'company', 'phone', 'email', 'direction_1', 'direction_2', 'neighborhood', 'district', 'postal_code', 'city', 'state_code', 'country_code'];
        var addressInfo = '';

        if(address){
            addressKeys.forEach(function (key) {
                if (address[key]) {
                    addressInfo += address[key];
                    if (key == 'postal_code') {
                        addressInfo += ' ';
                    } else if (key == 'city') {
                        addressInfo += ', ';
                    } else {
                        addressInfo += '<br/>';
                    }
                }
                if (key == 'email') {
                    addressInfo += '<br/>';
                }
            });
        }
        addressBlock.innerHTML = addressInfo;
    }

    if (document.getElementById('adrhid_postcode'))
        var adrhid_postcode = document.getElementById('adrhid_postcode').value;
    if (document.getElementById('adrhid_fullname'))
        var adrhid_fullname = document.getElementById('adrhid_fullname').value;
    if (document.getElementById('adrhid_phone'))
        var adrhid_phone = document.getElementById('adrhid_phone').value;

    if (adrhid_postcode && adrhid_phone && adrhid_fullname) {
        selectedAddressSender(adrhid_postcode, adrhid_phone, adrhid_fullname);

        var ord_adr = document.getElementById('woocommerce_enviaya_origin_address').value;
        if (ord_adr != "") {
            var ard = ord_adr.split('||');
            ord_adr = ard.join('"');

            changeSelectedAddress(JSON.parse(ord_adr));
        }
    }

    function copyToStringSupport() {
        var copyText = document.getElementById("stringSupport");
        copyText.select();
        document.execCommand("Copy");
    }

    jQuery(document).ready(function () {
        /* Excluded zones part */
        var exZonesHelper = {
            add: function (zone) {
                jQuery('.ex-zones-table').append('<tr class="ex-zones-row" data-name="' + zone.name + '" data-regions="' + zone.regions + '" data-zips="' + zone.zips + '"><td>' + zone.name + '</td><td>' + (zone.regions === '' ? 0 : (Array.isArray(zone.regions) ? zone.regions.length : zone.regions.split(',').length)) + '</td><td><a class="delete-ex-zone"><span class="dashicons dashicons-no-alt"></span></a></td></tr>');
                jQuery('.delete-ex-zone').click(function (e) {
                    exZonesHelper.remove(jQuery(this));
                    e.preventDefault();
                });
                exZonesHelper.updateData();
            },
            remove: function (el) {
                jQuery(el).parents('tr.ex-zones-row').remove();
                exZonesHelper.updateData();
            },
            updateData: function () {
                var rows = [];

                jQuery('.ex-zones-row').each(function (i, el) {
                    rows.push({
                        name: jQuery(el).data('name') || '',
                        regions: jQuery(el).data('regions') || '',
                        zips: jQuery(el).data('zips') || ''
                    })
                });

                jQuery('#woocommerce_enviaya_excluded_zones_data').val(JSON.stringify(rows));
            }
        };

        try {
            var tableData = jQuery.parseJSON(jQuery('#woocommerce_enviaya_excluded_zones_data').val());
            jQuery.each(tableData, function (i, row) {
                var exObj = {
                    name: row.name,
                    regions: row.regions,
                    zips: row.zips
                };

                exZonesHelper.add(exObj);
            });
        } catch (e) {
        }

        jQuery('.delete-ex-zone').click(function (e) {
            exZonesHelper.remove(jQuery(this));
            e.preventDefault();

            jQuery('.woocommerce-save-button').val('excluded_zones');
            jQuery('.woocommerce-save-button').click();
        });

        jQuery('.add-zone-btn').click(function (e) {
            var exObj = {
                name: jQuery('#excluded_zone_name').val(),
                regions: jQuery('#excluded_zone_locations').val(),
                zips: jQuery('#excluded_zone_postcodes').val()
            };

            exZonesHelper.add(exObj);

            e.preventDefault();

            jQuery('.woocommerce-save-button').val('excluded_zones');
            jQuery('.woocommerce-save-button').click();

        });

        /* End excluded zones part */

        if (jQuery('#woocommerce_enviaya_rate_on_add_to_cart option:selected').val() == '0') {
            jQuery('#woocommerce_enviaya_rate_on_add_to_cart + .description').hide();
        } else {
            jQuery('#woocommerce_enviaya_rate_on_add_to_cart + .description').show();
        }

        jQuery('#woocommerce_enviaya_rate_on_add_to_cart').change(function () {
            if (jQuery('#woocommerce_enviaya_rate_on_add_to_cart option:selected').val() == '0') {
                jQuery('#woocommerce_enviaya_rate_on_add_to_cart + .description').hide();
            } else {
                jQuery('#woocommerce_enviaya_rate_on_add_to_cart + .description').show();
            }
        });

        if (jQuery('#woocommerce_enviaya_default_or_advanced_design option:selected').val() == '0') {

            jQuery('#woocommerce_enviaya_display_carrier_logo').closest('tr').hide();
            jQuery('#woocommerce_enviaya_shipping_service_design').closest('tr').hide();
            jQuery('#woocommerce_enviaya_group_by_carrier').closest('tr').hide();
            jQuery('#default_or_advanced_design_example').html('<span id="span-example">' + lang['shipping_services_design_example'] + '</span><span id="example_carrier_name">Redpack - </span><span id="block_delivery_time">' + lang['express'] + '</span><span id="delivery_time_1"> - 14/11/2017</span><span id="delivery_time_2"> - 1 ' + lang['day'] + '</span><span> ($ 156.43)</span>');
        } else {
            jQuery('#default_or_advanced_design_example').addClass('danger')
            jQuery('#woocommerce_enviaya_display_carrier_logo').closest('tr').show();
            jQuery('#woocommerce_enviaya_shipping_service_design').closest('tr').show();
            jQuery('#woocommerce_enviaya_group_by_carrier').closest('tr').show();
            jQuery('#default_or_advanced_design_example').html(lang['advanced_shipping_services_design_warning']);
        }

        if (jQuery('#woocommerce_enviaya_enable_currency_support').is(':checked')) {
            jQuery('#enable_currency_support_description').show();
            jQuery('#woocommerce_enviaya_default_currency').closest('tr').hide();
        } else {
            jQuery('#enable_currency_support_description').hide();
            jQuery('#woocommerce_enviaya_default_currency').closest('tr').show();
        }

        if (jQuery('#woocommerce_enviaya_enable_contingency_shipping option:selected').val() == '1') {
            if (!jQuery('#woocommerce_enviaya_enable_standard_flat_rate').is(':checked') && !jQuery('#woocommerce_enviaya_enable_express_flat_rate').is(':checked')) {
                jQuery('#woocommerce_enviaya_enable_standard_flat_rate').prop("checked", true);
                jQuery('#woocommerce_enviaya_enable_express_flat_rate').prop("checked", true);
            }
        }

        if (jQuery('#woocommerce_enviaya_enable_contingency_shipping option:selected').val() == '1') {
            jQuery('#woocommerce_enviaya_enable_standard_flat_rate').closest('tr').show();
            jQuery('#woocommerce_enviaya_enable_express_flat_rate').closest('tr').show();

            if (jQuery('#woocommerce_enviaya_enable_standard_flat_rate').is(':checked')) {
                jQuery('#woocommerce_enviaya_standard_flat_rate').closest('tr').show();
            } else {
                jQuery('#woocommerce_enviaya_standard_flat_rate').closest('tr').hide();
            }

            if (jQuery('#woocommerce_enviaya_enable_express_flat_rate').is(':checked')) {
                jQuery('#woocommerce_enviaya_express_flat_rate').closest('tr').show();
            } else {
                jQuery('#woocommerce_enviaya_express_flat_rate').closest('tr').hide();
            }
        } else {
            jQuery('#woocommerce_enviaya_enable_standard_flat_rate').closest('tr').hide();
            jQuery('#woocommerce_enviaya_enable_express_flat_rate').closest('tr').hide();
            jQuery('#woocommerce_enviaya_standard_flat_rate').closest('tr').hide();
            jQuery('#woocommerce_enviaya_express_flat_rate').closest('tr').hide();
        }

        jQuery('#woocommerce_enviaya_enable_currency_support').change(function () {
            if (jQuery('#woocommerce_enviaya_enable_currency_support').is(':checked')) {
                jQuery('#enable_currency_support_description').show();
                jQuery('#woocommerce_enviaya_default_currency').closest('tr').hide();
            } else {
                jQuery('#enable_currency_support_description').hide();
                jQuery('#woocommerce_enviaya_default_currency').closest('tr').show();
            }
        });

        jQuery('#woocommerce_enviaya_enable_contingency_shipping').change(function () {
            if (jQuery('#woocommerce_enviaya_enable_contingency_shipping option:selected').val() == '1') {
                jQuery('#woocommerce_enviaya_enable_standard_flat_rate').closest('tr').show();
                jQuery('#woocommerce_enviaya_enable_express_flat_rate').closest('tr').show();

                if (!jQuery('#woocommerce_enviaya_enable_standard_flat_rate').is(':checked') && !jQuery('#woocommerce_enviaya_enable_express_flat_rate').is(':checked')) {
                    jQuery('#woocommerce_enviaya_enable_standard_flat_rate').prop("checked", true);
                    jQuery('#woocommerce_enviaya_enable_express_flat_rate').prop("checked", true);
                }

                if (jQuery('#woocommerce_enviaya_enable_standard_flat_rate').is(':checked')) {
                    jQuery('#woocommerce_enviaya_standard_flat_rate').closest('tr').show();
                }

                if (jQuery('#woocommerce_enviaya_enable_express_flat_rate').is(':checked')) {
                    jQuery('#woocommerce_enviaya_express_flat_rate').closest('tr').show();
                }
            } else {
                jQuery('#woocommerce_enviaya_enable_standard_flat_rate').closest('tr').hide();
                jQuery('#woocommerce_enviaya_enable_express_flat_rate').closest('tr').hide();
                jQuery('#woocommerce_enviaya_standard_flat_rate').closest('tr').hide();
                jQuery('#woocommerce_enviaya_express_flat_rate').closest('tr').hide();
            }
        });

        jQuery('#woocommerce_enviaya_enable_standard_flat_rate').change(function () {
            if (jQuery('#woocommerce_enviaya_enable_standard_flat_rate').is(':checked')) {
                jQuery('#woocommerce_enviaya_standard_flat_rate').closest('tr').show();
            } else {
                jQuery('#woocommerce_enviaya_standard_flat_rate').closest('tr').hide();
            }
        });

        jQuery('#woocommerce_enviaya_enable_express_flat_rate').change(function () {
            if (jQuery('#woocommerce_enviaya_enable_express_flat_rate').is(':checked')) {
                jQuery('#woocommerce_enviaya_express_flat_rate').closest('tr').show();
            } else {
                jQuery('#woocommerce_enviaya_express_flat_rate').closest('tr').hide();
            }
        });

        jQuery('#woocommerce_enviaya_default_or_advanced_design').change(function () {
            if (jQuery('#woocommerce_enviaya_default_or_advanced_design option:selected').val() == '0') {
                jQuery('#default_or_advanced_design_example').removeClass('danger')
                jQuery('#woocommerce_enviaya_display_carrier_logo').closest('tr').hide();
                jQuery('#woocommerce_enviaya_shipping_service_design').closest('tr').hide();
                jQuery('#woocommerce_enviaya_group_by_carrier').closest('tr').hide();
                jQuery('#default_or_advanced_design_example').html('<span id="span-example">' + lang['shipping_services_design_example'] + '</span><span id="example_carrier_name">Redpack - </span><span id="block_delivery_time">' + lang['express'] + '</span><span id="delivery_time_1"> - 14/11/2017</span><span id="delivery_time_2"> - 1 ' + lang['day'] + '</span><span> ($ 156.43)</span>');
            } else {
                jQuery('#default_or_advanced_design_example').addClass('danger')
                jQuery('#woocommerce_enviaya_display_carrier_logo').closest('tr').show();
                jQuery('#woocommerce_enviaya_shipping_service_design').closest('tr').show();
                jQuery('#woocommerce_enviaya_group_by_carrier').closest('tr').show();
                jQuery('#default_or_advanced_design_example').html(lang['advanced_shipping_services_design_warning']);
            }

            var initValTime = jQuery('#woocommerce_enviaya_shipping_delivery_time').val();
            if (initValTime === '0') jQuery('#delivery_time_1').show();
            if (initValTime === '1') jQuery('#delivery_time_2').show();

            var initValCarrier = jQuery('#woocommerce_enviaya_shipping_carrier_name').val();
            if (initValCarrier === '1') jQuery('#example_carrier_name').show();

        });

        var val = parseInt(jQuery('#woocommerce_enviaya_display_carrier_logo option:selected').val());
        if (val) {
            var pict = parseInt(localStorage.getItem('pict'));
            if (localStorage.getItem('pict') !== null) {
                console.log(pict);
                if (pict >= 4) {
                    localStorage.setItem('pict', 0);
                    jQuery('#pic0').show();

                } else {
                    pict = pict + 1;
                    localStorage.setItem('pict', pict);
                    jQuery('#pic' + pict).show();

                    console.log(parseInt(localStorage.getItem('pict')));
                }
            } else {
                localStorage.setItem('pict', 0);
                jQuery('#pic0').show();
            }
        }

        jQuery('#woocommerce_enviaya_display_carrier_logo').on('change', function (e) {
            if (this.value == '1') {
                var pict = parseInt(localStorage.getItem('pict'));
                if (localStorage.getItem('pict') !== null) {
                    jQuery('#pic' + pict).show();
                } else {
                    localStorage.setItem('pict', 0);
                    jQuery('#pic0').show();

                }
            } else {
                jQuery('#pic0, #pic1, #pic2, #pic3, #pic4').hide();
            }
        });

        var par = jQuery('#woocommerce_enviaya_enable_rating').parent();
        var child = par.children()[2];
        if (jQuery('#woocommerce_enviaya_enable_rating option:selected').val() == 1) {
            console.log(child.style.display = 'block');
        } else {
            console.log(child.style.display = 'none');
        }

        jQuery('#woocommerce_enviaya_enable_rating').on('change', function () {
            var par = jQuery('#woocommerce_enviaya_enable_rating').parent();
            var child = par.children()[2];
            if (jQuery('#woocommerce_enviaya_enable_rating option:selected').val() == 1) {
                console.log(child.style.display = 'block');
            } else {
                console.log(child.style.display = 'none');
            }
        });



        var ind = jQuery('#woocommerce_enviaya_shipping_service_design option:selected').val();
        jQuery('#block_design span').hide();
        jQuery('span#ds' + ind).show();

        jQuery('#woocommerce_enviaya_shipping_service_design').on('change', function (e) {
            jQuery('#block_design span').hide();
            jQuery('span#ds' + this.value).show();
        });

        var initValTime = jQuery('#woocommerce_enviaya_shipping_delivery_time').val();
        if (initValTime === '0') jQuery('#delivery_time_1').show();
        if (initValTime === '1') jQuery('#delivery_time_2').show();

        var initValCarrier = jQuery('#woocommerce_enviaya_shipping_carrier_name').val();
        if (initValCarrier === '1') jQuery('#example_carrier_name').show();

        jQuery('#woocommerce_enviaya_shipping_service_design_advanced').on('change', function () {
            var val = jQuery(this).val();
            if (val === '0') jQuery('.srvc-name').html('Entrega en 2 días');
            if (val === '1') jQuery('.srvc-name').html('Económico');
        });

        var initVal = jQuery('#woocommerce_enviaya_shipping_service_design_advanced').val();
        if (initVal === '0') jQuery('.srvc-name').html('Entrega en 2 días');
        if (initVal === '1') jQuery('.srvc-name').html('Económico');


        jQuery('#woocommerce_enviaya_shipping_delivery_time').on('change', function () {
            var initValTime = jQuery(this).val();

            if (initValTime === '0') {
                jQuery('#delivery_time_1').show();
            } else {
                jQuery('#delivery_time_1').hide();
            }

            if (initValTime === '1') {
                jQuery('#delivery_time_2').show();
            } else {
                jQuery('#delivery_time_2').hide();
            }
        });

        jQuery('#woocommerce_enviaya_shipping_carrier_name').on('change', function () {
            var initValCarrier = jQuery(this).val();

            if (initValCarrier === '1') {
                jQuery('#example_carrier_name').show();
            } else {
                jQuery('#example_carrier_name').hide();
            }
        });


        jQuery('#woocommerce_enviaya_shipping_service_design_advanced').on('change', function () {
            var val = jQuery(this).val();
            if (val === '0') jQuery('.srvc-name').html('Entrega en 2 días');
            if (val === '1') jQuery('.srvc-name').html('Económico');
            if (val === '2') jQuery('.srvc-name').html(lang['express']);
        });

        jQuery('#woocommerce_enviaya_shipping_carrier_name').on('change', function () {
            var val = jQuery(this).val();

        });

        jQuery('button.downloadZip').on('load click', function (e) {
            e.preventDefault();

            window.open('/wp-content/debug.log');
        });
    });

    function ord_adr_func(){
        var ord_adr = jQuery('#woocommerce_enviaya_origin_address').val();
        if(ord_adr != 'none'){
            var ard = ord_adr.split('||');
            ord_adr = ard.join('"');
            console.log(ord_adr);
            changeSelectedAddress(JSON.parse(ord_adr));
        } else{
            changeSelectedAddress(null);
        }
    }
    ord_adr_func();

    function selectedAddressSender(postcode, email, fullname) {
        var select = document.getElementById('woocommerce_enviaya_origin_address').children;
        for (var i = 0; i < select.length; i++) {
            var ord_adr = select[i].value.split('||').join('"');
            var address = JSON.parse(ord_adr);
            if (address.postal_code === postcode && address.full_name === fullname && address.email === email) {
                console.log(address);
            }
        }
    }


    function openTab(evt, tabActive) {
        var i, tabcontent, tablinks, saveButton;

        tabcontent = document.getElementsByClassName("tabcontent");
        for (i = 0; i < tabcontent.length; i++) {
            tabcontent[i].style.display = "none";
        }

        tablinks = document.getElementsByClassName("tablinks");
        for (i = 0; i < tablinks.length; i++) {
            tablinks[i].className = tablinks[i].className.replace("nav-tab-active", "");
        }


        document.getElementById(tabActive).style.display = "block";
        evt.currentTarget.className += " nav-tab-active";
    }

    if (document.getElementById("defaultOpen")) {
        document.getElementById("defaultOpen").click();
    }

    jQuery('#download-api-logs-button').on('click', function (e) {
        e.preventDefault();
        window.location.href = ajaxurl + '?action=ey_download_api_logs';
    });
    jQuery('#delete-api-logs-button').on('click', function (e) {
        e.preventDefault();
        let data = {action: 'ey_delete_api_logs'};
        jQuery.post(ajaxurl, data, function (response) {
            console.log('deleting logs response:');
            console.log(response);
        });
    });
}
