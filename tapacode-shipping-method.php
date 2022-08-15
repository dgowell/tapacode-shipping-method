<?php
/*
Plugin Name: TapaCode Local Shipping Method
Plugin URI: https://woocommerce.com/
Description: Local Shipping Method
Version: 1.0.1
Author: TapaCode
Author URI: https://tapacode.com/
*/
if ( ! defined( 'WPINC' ) ) {
    die;
}

/*
* Function that simply multiplies the quantity and price
*/
function sum_items( $quantity, $price ) {
    $price *= $quantity;
    return $price;
}

/*
* Function that tries to work out how to fit the medium
* sized items into the boxes of the larger items
*/
function calc_medium( $mediumQuan, $mediumPrice, $largeQuan, $largePrice, $toiletQuan) {
    if ($toiletQuan >= $mediumQuan || $largeQuan >= $mediumQuan) {
        return 0;
    } elseif ($largeQuan > 0){
        if (($mediumQuan / $largeQuan) > 2 ) {
            return $mediumPrice + ((($mediumPrice * $mediumQuan) - $mediumPrice)/2);
        }
    } elseif ($mediumQuan > 1) {
        return $mediumPrice + ((($mediumPrice * $mediumQuan) - $mediumPrice)/2);
    } elseif ($mediumQuan == 1) {
        return $mediumPrice;
    } else {
        return 0;
    }
}

/*
* Function that tries to work out how to fit the small
* sized items into the boxes of the all the larger items
*/
function calc_small( $smallQuan, $smallPrice, $mediumQuan, $mediumPrice, $largeQuan, $largePrice, $toiletQuan) {
    if ($toiletQuan >= $smallQuan || $largeQuan >= $smallQuan || $mediumQuan >= $smallQuan) {
        return 0;
    } elseif ($smallQuan > 1) {
        return $smallPrice + ((($smallPrice * $smallQuan) - $smallPrice)/2);
    } elseif ($smallQuan == 1) {
        return $smallPrice;
    } else {
        return 0;
    }
}

/**
 * Check if WooCommerce is active
 */
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

    function tapacode_shipping_method_init() {
        if ( ! class_exists( 'Tapacode_Shipping_Method' ) ) {
            class Tapacode_Shipping_Method extends WC_Shipping_Method {
                /**
                 * Constructor for your shipping class
                 *
                 * @access public
                 * @return void
                 */
                public function __construct() {
                    $this->id                 = 'tapacode_shipping_method'; // Id for your shipping method. Should be uunique.
                    $this->method_title = __( 'Standardfrakt' ); // Title shown in admin
                    $this->method_description = __( 'Standardfrakt' ); // Description shown in admin

                    $this->enabled = 'yes';
                    $this->title = __( 'Standardfrakt', 'tapacode' );

                    $this->availability = 'including';
                    $this->countries = array(
                        'DK', //Denmark
                        'SE', //Sweden
                        'NO', //Norway
                        'FI', //Finland
                    );
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
                public function calculate_shipping( $package = array() ) {
                    $cost = 0;
                    $toiletQuan = 0;
                    $toiletPrice = 0;
                    $largeQuan = 0;
                    $largePrice = 0;
                    $mediumQuan = 0;
                    $mediumPrice = 0;
                    $smallQuan = 0;
                    $smallPrice = 0;
                    $country = $package['destination']['country'];

                    /*
                    * Shipping classes key
                    ----------------------
                    * 228 = toilets
                    * 225 = large-items
                    * 227 = medium-items
                    * 226 = small-items
                    */

                    $shippingClasses = array(
                        228 => "toilet",
                        225 => "large-item",
                        227 => "medium-item",
                        226 => "small-item"
                    );

                    $countryZones = array(
                        'DK' => 'zone_one',
                        'SE' => 'zone_one',
                        'NO' => 'zone_two',
                        'FI' => 'zone_three'
                    );

                    $itemPrices = array(
                        "zone_one" => array(
                            "toilet" => 400,
                            "large-item" => 100,
                            "medium-item" => 60,
                            "small-item" => 50
                        ),
                        "zone_two" => array(
                            "toilet" => 500,
                            "large-item" => 150,
                            "medium-item" => 100,
                            "small-item" => 100
                        ),
                        "zone_three" => array(
                            "toilet" => 950,
                            "large-item" => 200,
                            "medium-item" => 100,
                            "small-item" => 100
                        )
                    );

                    $zoneFromCountry = $countryZones[ $country ];

                    //for each item work out the shipping class
                    //combining the class with the zone we can work out the price

                    //error_log(print_r($package, true));

                    foreach ( $package['contents'] as $item_id => $values ) {
                        $quantity = $values['quantity'];
                        $_product = $values['data'];
                        $class = $_product->get_shipping_class_id();
                        $shippingClass = $shippingClasses[ $class ];
                        $itemPrice = $itemPrices[$zoneFromCountry][$shippingClass];

                        switch ($shippingClass) {
                            case "toilet":
                                $toiletQuan = $quantity;
                                $toiletPrice = $itemPrice;
                                break;
                            case "large-item":
                                $largeQuan += $quantity;
                                $largePrice = $itemPrice;
                                break;
                            case "medium-item":
                                $mediumQuan += $quantity;
                                $mediumPrice = $itemPrice;
                                break;
                            default:
                                $smallQuan += $quantity;
                                $smallPrice = $itemPrice;
                        }
                    }

                    //add the items to the cost of the shipping
                    $cost = sum_items($toiletQuan, $toiletPrice);
                    $cost += sum_items($largeQuan, $largePrice);
                    $cost += calc_medium($mediumQuan, $mediumPrice, $largeQuan, $largePrice, $toiletQuan);
                    $cost += calc_small($smallQuan, $smallPrice, $mediumQuan, $mediumPrice, $largeQuan, $largePrice, $toiletQuan);

                    $rate = array(
                        'id' => $this->id,
                        'label' => $this->title,
                        'cost' => $cost,
                        'calc_tax' => 'per_item'
                    );

                    // Register the rate
                    $this->add_rate( $rate );
                }
            }
        }
    }

    add_action( 'woocommerce_shipping_init', 'tapacode_shipping_method_init' );

    function add_tapacode_shipping_method( $methods ) {
        $methods[] = 'Tapacode_Shipping_Method';
        return $methods;
    }

    add_filter( 'woocommerce_shipping_methods', 'add_tapacode_shipping_method' );
}