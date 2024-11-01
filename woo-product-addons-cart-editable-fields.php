<?php
/**
* Plugin Name: Woo Product Addons Cart Editable Fields
* Description: Allow the user to edit their addons in the cart
* Version: 1.0.0
* Author: Liam Bailey (Webby Scots)
* Author URI: http://webbyscots.com/
* License: GNU General Public License v3.0
* License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/


class Woo_Editable_Addons {

    private $required_plugins = array('woocommerce','woocommerce-product-addons');
    public static $_instance;

    function have_required_plugins() {
        if ( empty( $this->required_plugins ) ) {
           return true;
        }
        $active_plugins = ( array ) get_option( 'active_plugins', array() );
        if ( is_multisite() ) {
            $active_plugins = array_merge( $active_plugins, get_site_option( 'active_sitewide_plugins', array() ) );
        }
        foreach ($this->required_plugins as $key => $required) {
            $required = (!is_numeric($key)) ? "{$key}/{$required}.php" : "{$required}/{$required}.php";
            if (!in_array($required, $active_plugins) && !array_key_exists($required, $active_plugins)) {
                return false;
            }
        }
        return true;
    }

    function __construct() {
        if ( !$this->have_required_plugins() ) {
            return;
        }
        load_plugin_textdomain(  $this->textdomain, false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
        add_action( 'wp_enqueue_scripts', array( $this, 'scripts' ) );
        add_action( 'wp_ajax_edit_addon_in_cart', array( $this, 'save' ) );
        add_action( 'wp_ajax_nopriv_edit_addon_in_cart', array( $this, 'save' ) );
        add_filter( 'woocommerce_add_cart_item_data', array( $this, 'fill_in_dedication' ),-1, 3 );
    }

    function fill_in_dedication($data, $product_id, $variation_id) {
        $product_addons = get_product_addons( $product_id );
        if ( empty( $product_addons ) ) {
                return $data;
        }
        if ( is_array( $product_addons ) ) {
                foreach ( $product_addons as $addon ) {
                    if ( strstr( $addon['type'], 'custom' ) ) {
                        if ( isset( $_POST['addon-'. $addon['field-name']][0] ) && ! empty( $_POST['addon-'. $addon['field-name']][0] ) ) {
                            continue;
                        }
                        $_POST[ 'addon-' . $addon['field-name'] ][0] = "-";
                    }
                }
        }

    }

    function save() {
        check_ajax_referer( 'wooEditAddonSecure', 'secure' );
        $new_value = wp_filter_post_kses( wp_unslash( $_POST['addon_value'] ) ); 
        $cart_item_key = sanitize_text_field( wp_unslash( $_POST['cart_key'] ) );
        $addon_key = sanitize_text_field( wp_unslash( $_POST['addon_key'] ) );
        $cart_item = WC()->cart->cart_contents[ $cart_item_key ];
        $success = false;
        foreach( $cart_item['addons'] as $key => &$addon ) {
            if ( sanitize_html_class( $addon['name'] ) == $addon_key ) {
                $addon['value'] = $new_value;
                $success = true;
            }

        }
        if ( $success ) {
            WC()->cart->cart_contents[ $cart_item_key ] = $cart_item;
            WC()->session->cart = WC()->cart->get_cart_for_session();
            wp_send_json( array( 'success' => true ) );
        }

        wp_send_json( array( 'success' => false ) );
    }

    function cart_key_in_class( $class, $cart_item, $cart_item_key ) {
        if ( is_cart() ) {
            return $cart_item_key;
        }
        return $class;
    }

    function scripts() {
        if ( ! is_cart() && ! is_checkout() ) {
            return;
        }

        $js_data = array(
            'ajaxurl' => admin_url( 'admin-ajax.php', 'relative' ),
            'page' => ( is_cart() ) ? 'cart' : 'checkout',
            'secure' => wp_create_nonce( 'wooEditAddonSecure' )
        );
       
        foreach( WC()->cart->cart_contents as $cik => $ci ) {
            if ( isset( $ci['addons'] ) ) {
                foreach( $ci['addons'] as $key => $data ) {
                    $js_data['addon_keys'][ $key ]['cik'] = $cik;
                    $js_data['addon_keys'][ $key ]['key'] = sanitize_html_class( $data['name'] );
                    $js_data['addon_keys'][ $key ]['addon'] = $data;
                }
            }
        }
        wp_enqueue_script( 'woo-editable-addons', plugins_url( 'js/scripts.js', __FILE__ ), array( 'jquery' ) );
        wp_localize_script( 'woo-editable-addons','wooEditAddonVars', $js_data );
    }

    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
}

/* Use this function to call up the class from anywahere
like PB()->class_method();
 */
function WooEACC() {
    return Woo_Editable_Addons::instance();
}

WooEACC();