<?php
/**
 * @package Wecoder Sort 
 * @version 1.0.0
 */
/*
Plugin Name: Wecoder Sort 
Plugin URI: #
Description: This is a plugin for woocommerce product sorting depend on product discount percentage.
Author: Tarikul Islam
Version: 1.7.2
Author URI: #
*/


// Check if WooCommerce is active before allowing the plugin to be activated
add_action('admin_init', 'check_dependencies');
function check_dependencies()
{
	if (!is_plugin_active('woocommerce/woocommerce.php')) {
		add_action('admin_notices', 'woocommerce_missing_notice');
		deactivate_plugins(plugin_basename(__FILE__));
		if (isset($_GET['activate'])) {
			unset($_GET['activate']);
		}
	}
}

// Show notice if WooCommerce is not active
function woocommerce_missing_notice()
{
	echo '<div class="error"><p>' . __('Wecoder Sort plugin requires WooCommerce to be installed and active.', 'text-domain') . '</p></div>';
}

// Add custom column to product table
add_filter('manage_product_posts_columns', 'add_custom_product_column');
function add_custom_product_column($columns)
{
	$columns['discount_percentage'] = __('Discount Percentage', 'text-domain');
	return $columns;
}

// Display custom column data
add_action('manage_product_posts_custom_column', 'display_custom_product_column_data', 10, 2);
function display_custom_product_column_data($column, $post_id)
{
	global $product;
	if ('discount_percentage' === $column) {
		$product_id = $product->id;
		$regular_price = (float) $product->get_regular_price();
		$sale_price = (float) $product->get_sale_price();
		if ($regular_price && $sale_price && $regular_price > $sale_price) {
			$discount_percentage = round((($regular_price - $sale_price) / $regular_price) * 100);
			update_post_meta($product_id, 'discount_percentage', $discount_percentage);
			echo esc_html($discount_percentage) . '%';
		} else {
			echo '-';
		}
	}
}

// Make custom column sortable
add_filter('manage_edit-product_sortable_columns', 'make_custom_product_column_sortable');
function make_custom_product_column_sortable($columns)
{
	$columns['discount_percentage'] = 'discount_percentage';
	return $columns;
}

// Modify product query to enable sorting by custom column
add_action('pre_get_posts', 'custom_product_column_orderby');
function custom_product_column_orderby($query)
{
	if (!is_admin() || !$query->is_main_query()) {
		return;
	}
	if ('discount_percentage' === $query->get('orderby')) {
		$query->set('meta_key', 'discount_percentage');
		$query->set('orderby', 'meta_value_num');
	}
}

// Add custom sorting option
add_filter('woocommerce_catalog_orderby', 'add_custom_sorting_option');
function add_custom_sorting_option($sorting_options)
{
	$sorting_options['discount_percentage_asc'] = 'Sort by Discount Percentage (Low to High)';
	$sorting_options['discount_percentage_desc'] = 'Sort by Discount Percentage (High to Low)';
	return $sorting_options;
}

// Modify product query to enable sorting by custom column and direction
add_filter('woocommerce_get_catalog_ordering_args', 'custom_sort_by_discount_percentage');
function custom_sort_by_discount_percentage($args)
{
	if (isset($_GET['orderby']) && 'discount_percentage_asc' == $_GET['orderby']) {
		$args['meta_key'] = 'discount_percentage';
		$args['orderby'] = 'meta_value_num';
		$args['order'] = 'asc';
	} elseif (isset($_GET['orderby']) && 'discount_percentage_desc' == $_GET['orderby']) {
		$args['meta_key'] = 'discount_percentage';
		$args['orderby'] = 'meta_value_num';
		$args['order'] = 'desc';
	}
	return $args;
}


/**
 * We can see product percentage on product 
 */

function wecoder_woocommerce_sale_flash($content)
{
	global $product;
	if (empty($product->sale_price)) {
		return $content;
	}
	$product_id = $product->id;
	$discount = get_post_meta($product_id, 'discount_percentage', true);
	$content = <<<EOD
	<span class="onsale">$discount%</span>
EOD;

	return $content;
}
add_filter('woocommerce_sale_flash', 'wecoder_woocommerce_sale_flash');
