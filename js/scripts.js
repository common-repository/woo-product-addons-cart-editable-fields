jQuery(function($) {
    console.log("JS");
    var wooEditValue = function() {
        var addon_value = $(this).val().replace(/([^0-9a-z ])/ig,'');
        var addon_name = $(this).data('addon-name');
        var cart_item_key = $(this).data('cart-key');
        var addon_key = $(this).data('addon-key');
        var __this = $(this);
        var blocked = __this.closest('table').data('blockUI.isBlocked');
        if ( 1 !== blocked ) {
            console.log("blocking");
            __this.closest('table').block({
                message: null,
                overlayCSS: {
                    background: '#8fbbe4',
                    opacity: 0.9
                }
            });
        }
        $.post(wooEditAddonVars.ajaxurl,{action: 'edit_addon_in_cart', secure: wooEditAddonVars.secure, addon_key : addon_key, addon_value: addon_value, cart_key: cart_item_key},function(response) {
            if (response.success) {
                __this.closest('table').unblock();
                var newValue = $("<span>"+addon_value+"</span>");
                __this.closest('dd.variation-'+addon_key).html(newValue);
                var clicker = $("<button type='button' style='margin-left:10px'>Edit "+addon_name+"</button>");
                clicker.on('click',function(e) {
                    e.preventDefault();
                    clicker.off('click');
                    clicker.text('Save');
                    var input = $("<input id='"+cart_item_key+"' />");
                    input.addClass('wooaddon-edit-value').data('cart-key',cart_item_key);
                    input.data('addon-key',addon_key);
                    input.data('addon-name',addon_name);
                    input.val(newValue.text());
                    input.on('change',wooEditValue);
                    input.on('keypress',wooEnterSave);
                    clicker.on('click',function() {
                        input.blur();
                    })
                    newValue.replaceWith(input);
                    input.focus();
                });
                newValue.after(clicker);
            }


        });
    }
    var wooEnterSave = function(e) {
        if(e.which == 13) {
            e.preventDefault();
            $(this).blur();
        }
        return true;
    }
    //if (wooEditAddonVars.page == 'cart') {
        console.log(wooEditAddonVars.addon_keys);
        $.each(wooEditAddonVars.addon_keys, function(key, val) {
            console.log("looking for " + "dd.variation-"+val['key']);
            $("dd.variation-"+val['key']).each(function() {
                var __this = $(this);
                var existingValue = __this.text();
                console.log(existingValue.length);
                var buttonText = existingValue.replace(' ', '') == "-" ? "Add" : "Edit";
                var cart_item_key = val['cik'];
                var clicker = $("<button type='button' style='margin-left:10px'>" + buttonText + ' ' + val['addon']['name'] + "</button>");
                console.log(clicker);
                clicker.on('click',function(e) {
                    e.preventDefault();
                    clicker.off('click');
                    clicker.text('Save');
                    var input = $("<input id='"+cart_item_key+"' />");
                    input.addClass('wooaddon-edit-value').data('cart-key',cart_item_key);
                    input.val(existingValue);
                    input.data('addon-key',val['key']);
                    input.data('addon-name',val['addon']['name']);
                    console.log("addon key " + val['key']);
                    console.log("addon name" + val['addon']['name']);
                    input.on('change',wooEditValue);
                    input.on('keypress',wooEnterSave);
                    clicker.on('click',function() {
                        input.blur();
                    })
                    __this.html(input).append(clicker);
                    input.focus();
                });
                if (existingValue == "") {
                    __this.html(clicker);
                } else {
                    __this.find('p').append(clicker);
                }
            });
        })

    //}
});
