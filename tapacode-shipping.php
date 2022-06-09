<?php
/*
Plugin Name: TapaCode Pallet Shipping Method
Plugin URI: https://woocommerce.com/
Description: Pallet Shipping Method
Version: 1.0.0
Author: WooThemes
Author URI: https://woocommerce.com/
*/

/**
 * Check if WooCommerce is active
 */
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

    function tapacode_pallet_shipping_method_init() {
        if ( ! class_exists( 'Tapacode_Pallet_Shipping_Method' ) ) {
            class Tapacode_Pallet_Shipping_Method extends WC_Shipping_Method {
                /**
                 * Constructor for your shipping class
                 *
                 * @access public
                 * @return void
                 */
                public function __construct() {
                    $this->id                 = 'tapacode_pallet_shipping_method'; // Id for your shipping method. Should be uunique.
                    $this->method_title       = __( 'Pallet Shipping Method' );  // Title shown in admin
                    $this->method_description = __( 'Kerbside PAllet Shipping Method (2-5 Days)' ); // Description shown in admin

                    $this->enabled            = "yes"; // This can be added as an setting but for this example its forced enabled
                    $this->title              = "Pallet Shipping Method"; // This can be added as an setting but for this example its forced.

                    $this->init();
                }

                /**
                 * Init your settings
                 *
                 * @access public
                 * @return void
                 */
                function init() {
                    // Load the settings API
                    $this->init_form_fields(); // This is part of the settings API. Override the method to add your own settings
                    $this->init_settings(); // This is part of the settings API. Loads settings you previously init.

                    // Save settings in admin if you have any defined
                    add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
                }

                /**
                 * calculate_shipping function.
                 *
                 * @access public
                 * @param mixed $package
                 * @return void
                 */
                public function calculate_shipping( $package ) {
                    $rate = array(
                        'label' => $this->title,
                        'cost' => '0',
                        'calc_tax' => 'per_item'
                    );

                    // Register the rate
                    $this->add_rate( $rate );
                }
            }
        }
    }

    add_action( 'woocommerce_shipping_init', 'tapacode_pallet_shipping_method_init' );

    function add_tapacode_pallet_shipping_method( $methods ) {
        $methods['tapacode_pallet_shipping_method'] = 'Tapacode_Pallet_Shipping_Method';
        return $methods;
    }

    add_filter( 'woocommerce_shipping_methods', 'add_tapacode_pallet_shipping_method' );
}