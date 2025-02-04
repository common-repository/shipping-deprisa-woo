<?php
/**
 * Plugin Name: Shipping Deprisa Woo
 * Description: Shipping Deprisa Woo is available for Colombia
 * Version: 2.0.2
 * Author: Saul Morales Pacheco
 * Author URI: https://saulmoralespa.com
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * WC tested up to: 8.5
 * WC requires at least: 4.0
 *
 * @package ShippingCoordinadora
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if(!defined('SHIPPING_DEPRISA_WC_SD_VERSION')){
    define('SHIPPING_DEPRISA_WC_SD_VERSION', '2.0.2');
}

add_action( 'plugins_loaded', 'shipping_deprisa_wc_sd_init');
add_action(
    'before_woocommerce_init',
    function () {
        if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__ );
        }
    }
);

function shipping_deprisa_wc_sd_init(): void
{
    if(!shipping_deprisa_wc_sd_requirements())
        return;
    shipping_deprisa_wc_sd()->run_deprisa_wc();
}

function shipping_deprisa_wc_sd_notices( $notice ): void
{
    ?>
    <div class="error notice is-dismissible">
        <p><?php echo $notice; ?></p>
    </div>
    <?php
}

function shipping_deprisa_wc_sd_requirements(): bool
{

    if ( ! function_exists( 'is_plugin_active' ) ) require_once( ABSPATH . '/wp-admin/includes/plugin.php' );

    if (!extension_loaded('simplexml')){
        if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
            add_action(
                'admin_notices',
                function() {
                    shipping_deprisa_wc_sd_notices( 'Shipping Deprisa Woo requiere que se encuentre habilitada la extensión de PHP simplexml' );
                }
            );
        }
        return false;
    }

    if ( ! is_plugin_active(
        'woocommerce/woocommerce.php'
    ) ) {
        if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
            add_action(
                'admin_notices',
                function() {
                    shipping_deprisa_wc_sd_notices( 'Shipping Deprisa Woo requiere que se encuentre instalado y activo el plugin: Woocommerce' );
                }
            );
        }
        return false;
    }

    if ( ! is_plugin_active(
        'departamentos-y-ciudades-de-colombia-para-woocommerce/departamentos-y-ciudades-de-colombia-para-woocommerce.php'
    ) ) {
        if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
            add_action(
                'admin_notices',
                function() {
                    $action = 'install-plugin';
                    $slug = 'departamentos-y-ciudades-de-colombia-para-woocommerce';
                    $plugin_install_url = wp_nonce_url(
                        add_query_arg(
                            array(
                                'action' => $action,
                                'plugin' => $slug
                            ),
                            admin_url( 'update.php' )
                        ),
                        $action.'_'.$slug
                    );
                    $plugin = 'Shipping Deprisa Woo requiere que se encuentre instalado y activo el plugin: '  .
                        sprintf(
                            '%s',
                            "<a class='button button-primary' href='$plugin_install_url'>Departamentos y ciudades de Colombia para Woocommerce</a>" );
                    shipping_deprisa_wc_sd_notices( $plugin );
                }
            );
        }
        return false;
    }

    $woo_countries   = new WC_Countries();
    $default_country = $woo_countries->get_base_country();

    if ($default_country !== 'CO') {
        if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
            add_action(
                'admin_notices',
                function() {
                    $country = 'Shipping Deprisa Woo requiere que el país donde se encuentra ubicada la tienda sea Colombia '  .
                        sprintf(
                            '%s',
                            '<a href="' . admin_url() .
                            'admin.php?page=wc-settings&tab=general#s2id_woocommerce_currency">' .
                            'Click para establecer</a>' );
                    shipping_deprisa_wc_sd_notices( $country );
                }
            );
        }
        return false;
    }

    return true;
}

function  shipping_deprisa_wc_sd(){
    static $plugin;
    if(!isset($plugin)){
        require_once ("includes/class-shipping-deprisa-wc-plugin.php");
        $plugin = new Shipping_Deprisa_WC_plugin(__FILE__, SHIPPING_DEPRISA_WC_SD_VERSION);
    }
    return $plugin;
}

add_action( 'woocommerce_product_after_variable_attributes', array('Shipping_Deprisa_WC_plugin', 'variation_settings_fields'), 10, 3 );
add_action( 'woocommerce_product_options_shipping', array('Shipping_Deprisa_WC_plugin', 'add_custom_shipping_option_to_products'), 10);