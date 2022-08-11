<?php
/*
Plugin Name: TapaCode Pallet Shipping Method
Plugin URI: https://woocommerce.com/
Description: Pallet Shipping Method
Version: 1.0.0
Author: TapaCode
Author URI: https://tapacode.com/
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
                    $this->method_title       = __( 'Pallet shipping delivery' );  // Title shown in admin
                    $this->method_description = __( 'FREE kerbside pallet shipping delivery (2-5 Days)' ); // Description shown in admin

                    $this->enabled = 'yes';
                    $this->title =  __( 'FREE kerbside pallet delivery (2-5 working days)', 'tapacode' );

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
                    $country = $package['destination']['country'];
                    $match = false;
                    if ($country == 'GB')
                        foreach ( $package['contents'] as $item_id => $values ) {
                            $_product = $values['data'];
                            $shipping_id = $_product->get_shipping_class_id();
                            //shipping class id test 246
                            //shipping class id staging 163
                            //shipping class id production 164
                            if ($shipping_id == '164') {
                                $match = true;
                            }
                        }
                        if ($match) {
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
    }

    add_action( 'woocommerce_shipping_init', 'tapacode_pallet_shipping_method_init' );

    function add_tapacode_pallet_shipping_method( $methods ) {
        $methods['tapacode_pallet_shipping_method'] = 'Tapacode_Pallet_Shipping_Method';
        return $methods;
    }

    add_filter( 'woocommerce_shipping_methods', 'add_tapacode_pallet_shipping_method' );


    /**
    * Hide other shipping rates when pallet shipping method is available.
    * Updated to support WooCommerce 2.6 Shipping Zones.
    *
    * @param array $rates Array of rates found for the package.
    * @return array
    */
    function tapacode_hide_shipping_when_pallet_is_available( $rates ) {
        $pallet = array();
        foreach ( $rates as $rate_id => $rate ) {
            if ( 'tapacode_pallet_shipping_method' === $rate->method_id ) {
                $pallet[ $rate_id ] = $rate;
                break;
            }
        }
        return ! empty( $pallet ) ? $pallet : $rates;
    }
    add_filter( 'woocommerce_package_rates', 'tapacode_hide_shipping_when_pallet_is_available', 100 );
}