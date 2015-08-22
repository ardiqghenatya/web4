<?php
/*
Plugin Name: WooCommerce Product Filter
Plugin URI: http://www.mihajlovicnenad.com/product-filter
Description: Advanced product filter for any Wordpress template! - mihajlovicnenad.com
Author: Mihajlovic Nenad
Version: 2.3.0
Author URI: http://www.mihajlovicnenad.com
*/

/**
 * Check if WooCommerce is installed
 */

/**
 * Product Filter Translation
 */
function prdctfltr_plugin_setup() {

	$locale = apply_filters( 'plugin_locale', get_locale(), 'prdctfltr' );
	$dir    = trailingslashit( WP_LANG_DIR );

	load_textdomain( 'prdctfltr', $dir . 'plugins/prdctfltr-' . $locale . '.mo' );
	load_plugin_textdomain( 'prdctfltr', false, $dir . 'plugins' );

}
add_action('init', 'prdctfltr_plugin_setup');

/**
 * Product Filter Action
 */
function prdctfltr_get_filter() {

	ob_start();

	wc_get_template( 'loop/orderby.php');

	$out = ob_get_clean();
	
	echo $out;
}
add_action('prdctfltr_output', 'prdctfltr_get_filter', 10);

/**
 * Product Filter Basic
 */
$curr_path = dirname( __FILE__ );
$curr_name = basename( $curr_path );
$curr_url = plugins_url( "/$curr_name/" );

define('PRDCTFLTR_URL', $curr_url);
function prdctfltr_path() {
	return untrailingslashit( plugin_dir_path( __FILE__ ) );
}
include_once ( $curr_path.'/lib/pf-attribute-thumbnails.php' );

/*
 * Product Filter Load Scripts
*/
if ( !function_exists('prdctfltr_scripts') ) :
function prdctfltr_scripts() {

	$curr_scripts = get_option( 'wc_settings_prdctfltr_disable_scripts', array() );

	wp_register_style( 'prdctfltr-main-css', PRDCTFLTR_URL .'lib/css/prdctfltr.css', false, '2.3.0' );
	wp_enqueue_style( 'prdctfltr-main-css' );

	if ( !in_array( 'mcustomscroll', $curr_scripts ) ) {
		wp_register_style( 'prdctfltr-scrollbar-css', PRDCTFLTR_URL .'lib/css/jquery.mCustomScrollbar.css', false, '1.0.0' );
		wp_enqueue_style( 'prdctfltr-scrollbar-css' );
		wp_register_script( 'prdctfltr-scrollbar-js', PRDCTFLTR_URL .'lib/js/jquery.mCustomScrollbar.concat.min.js', array( 'jquery' ), '1.0', true );
		wp_enqueue_script( 'prdctfltr-scrollbar-js' );
	}

	if ( !in_array( 'isotope', $curr_scripts ) ) {
		wp_register_script( 'prdctfltr-isotope-js', PRDCTFLTR_URL .'lib/js/isotope.js', array( 'jquery' ), '1.0', true );
		wp_enqueue_script( 'prdctfltr-isotope-js' );
	}

	if ( !in_array( 'ionrange', $curr_scripts ) ) {
		wp_register_style( 'prdctfltr-ionrange-css', PRDCTFLTR_URL .'lib/css/ion.rangeSlider.css', false, '1.0.0' );
		wp_enqueue_style( 'prdctfltr-ionrange-css' );
		wp_register_script( 'prdctfltr-ionrange-js', PRDCTFLTR_URL .'lib/js/ion.rangeSlider.min.js', array( 'jquery' ), '1.0.0', false );
		wp_enqueue_script( 'prdctfltr-ionrange-js' );
	}

	wp_register_script( 'prdctfltr-main-js', PRDCTFLTR_URL .'lib/js/prdctfltr_main.js', array( 'jquery' ), '2.3.0', true );
	wp_enqueue_script( 'prdctfltr-main-js' );

	$curr_args = array(
		'ajax' => admin_url( 'admin-ajax.php' ),
	);

	wp_localize_script( 'prdctfltr-main-js', 'prdctfltr', $curr_args );
}
endif;
add_action( 'wp_enqueue_scripts', 'prdctfltr_scripts' );

function prdctfltr_admin_scripts($hook) {

	if ( isset($_GET['page'], $_GET['tab']) && ($_GET['page'] == 'wc-settings' || $_GET['page'] == 'woocommerce_settings') && $_GET['tab'] == 'settings_products_filter' ) {
		wp_register_style( 'prdctfltr-admin', PRDCTFLTR_URL .'lib/css/admin.css', false, '2.3.0' );
		wp_enqueue_style( 'prdctfltr-admin' );
		wp_register_script( 'prdctfltr-settings', PRDCTFLTR_URL . 'lib/js/admin.js', array( 'jquery', 'jquery-ui-core', 'jquery-ui-sortable' ), '2.3.0', true );
		wp_enqueue_script( 'prdctfltr-settings' );

		$curr_args = array(
			'ajax' => admin_url( 'admin-ajax.php' )
		);
		wp_localize_script( 'prdctfltr-settings', 'prdctfltr', $curr_args );
	}

}
add_action( 'admin_enqueue_scripts', 'prdctfltr_admin_scripts' );

/*
 * Product Filter Pre Get Posts
*/
if ( !function_exists( 'prdctfltr_wc_meta_query' ) ) :
function prdctfltr_wc_meta_query($query) {
	if ( $query->is_post_type_archive() === false ) return;

	if ( ( !is_admin() && isset($query->query['prdctfltr']) && $query->query['prdctfltr'] == 'active' ) !== false ) {
	}
	else if ( !is_admin() && $query->is_main_query() && ( $query->is_post_type_archive( 'product' ) || $query->is_page( wc_get_page_id( 'shop' ) ) ) !== false ) {
	}
	else {
		if ( ( defined('DOING_AJAX') && DOING_AJAX ) === false ) {
			return;
		}
	}

	$curr_args = array();

	if ( !isset($_GET['sale_products']) && isset($query->query['sale_products']) && $query->query['sale_products'] == 'yes' ) {
		$_GET['sale_products'] = 'yes';
	}

	$pf_taxonomies = get_object_taxonomies( 'product', 'object' );
	$pf_not_allowed = array( 'product_cat', 'product_tag', 'characteristics', 'product_type' );
	$pf_next = array();
	$f_attrs = array();
	$f_terms = array();
	$rng_terms = array();
	foreach ( $pf_taxonomies as $y => $u ) {
		if ( !in_array($y, $pf_not_allowed) ) {
			$pf_next[] = $y;
		}
	}

	$pf_not_allowed = array( 'product_cat', 'product_tag', 'characteristics', 'min_price', 'max_price', 'orderby', 'sale_products', 'instock_products', 'widget_search' );

	if ( ( defined('DOING_AJAX') && DOING_AJAX ) && isset($query->query) ) {

		foreach( $query->query as $k => $v ){
			if ( !in_array($k, $pf_not_allowed) ) {
				if ( substr($k, 0, 4) == 'rng_' && $v !== '' ) {
					if ( substr($k, 0, 8) == 'rng_min_' ) {
						$rng_terms[str_replace('rng_min_', '', $k)]['min'] = $v;
					}
					else {
						$rng_terms[str_replace('rng_max_', '', $k)]['max'] = $v;
					}
				}
				else if ( substr($k, 0, 3) == 'pa_' && $v !== '' && !isset( $_GET[$k] ) ) {
					$_GET[$k] = $v;
				}
			}
		}

	}

	foreach( $_GET as $k => $v ){
		if ( !in_array($k, $pf_not_allowed) ) {
			if ( substr($k, 0, 4) == 'rng_' && $v !== '' ) {
				if ( substr($k, 0, 8) == 'rng_min_' ) {
					$rng_terms[str_replace('rng_min_', '', $k)]['min'] = $v;
				}
				else {
					$rng_terms[str_replace('rng_max_', '', $k)]['max'] = $v;
				}
			}
		}
	}

	if ( !empty($rng_terms) ) {
		foreach ( $rng_terms as $rng_name => $rng_inside ) {
			if ( !in_array( $rng_name, array( 'price' ) ) ) {
				$curr_attributes = get_terms( $rng_name );
				$rng_found = false;
				$curr_ranges = array();
				foreach ( $curr_attributes as $c => $s ) {
					if ( $rng_found == true ) {
						$curr_ranges[] = $s->slug;
						if ( $s->slug == $rng_inside['max'] ) {
							$rng_found = false;
							continue;
						}
					}
					if ( $s->slug == $rng_inside['min'] && $rng_found === false ) {
						$rng_found = true;
						$curr_ranges[] = $s->slug;
					}
				}
				$curr_args = array_merge( $curr_args, array(
						$rng_name => implode( $curr_ranges, ',' )
					) );

				$f_attrs[] = '"attribute_' . $rng_name . '"';
				$f_terms_rng = array();
				foreach ( $curr_ranges as $c ) {
					$f_terms_rng[] = '"' . $c . '"';
				}
				$f_terms[] = implode( $f_terms_rng, ',' );
			}
			else if ( !isset($_GET['sale_products']) || $_GET['sale_products'] !== 'yes' ) {
				$curr_args = array_merge( $curr_args, array(
							'meta_key' => '_price',
							'meta_value' => array( floatval($rng_inside['min']), floatval($rng_inside['max'])),
							'meta_type' => 'numeric',
							'meta_compare' => 'BETWEEN'
					) );
			}

		}
	}

	foreach( $_GET as $k => $v ){
		if ( !in_array($k, $pf_not_allowed) ) {
			if ( substr($k, 0, 3) == 'pa_' && $v !== '' ) {
				$curr_args = array_merge( $curr_args, array(
						$k => $v
					) );
				$f_attrs[] = '"attribute_'.$k.'"';
				if ( !strpos($v, ',') ) {
					$f_terms[] = '"'.$v.'"';
				}
				else {
					$v_val = explode(',', $v);
					foreach ( $v_val as $o => $z ) {
						$f_terms[] = '"'.$z.'"';
					}
				}
			}
			if ( in_array($k, $pf_next) ) {
				$curr_args = array_merge( $curr_args, array(
						$k => $v
					) );
			}
		}
	}



	if ( !isset($_GET['orderby']) && isset($query->query['orderby']) && $query->query['orderby'] !== 'date' ) {
		$_GET['orderby'] = $query->query['orderby'];
	}

	if ( isset($_GET['orderby']) ) {
		if ( $_GET['orderby'] == 'price' || $_GET['orderby'] == 'price-desc' ) {
			$orderby = 'meta_value_num';
			$order = ( $_GET['orderby'] == 'price-desc' ? 'DESC' : 'ASC' );
			$curr_args = array_merge( $curr_args, array(
					'meta_key' => '_price',
					'orderby' => $orderby,
					'order' => $order
				) );
		}
		else if ( $_GET['orderby'] == 'rating' ) {
			add_filter( 'posts_clauses', array( WC()->query, 'order_by_rating_post_clauses' ) );
		}
		else if ( $_GET['orderby'] == 'popularity' ) {
			$orderby = 'meta_value_num';
			$order = 'DESC';
			$curr_args = array_merge( $curr_args, array(
					'meta_key' => 'total_sales',
					'orderby' => $orderby,
					'order' => $order
				) );
		}
		else {
			$orderby = $_GET['orderby'];
			$order = ( isset($_GET['order']) ? $_GET['order'] : 'DESC' );
			$curr_args = array_merge( $curr_args, array(
					'orderby' => $orderby,
					'order' => $order
				) );
		}
	}

	if ( isset($_GET['product_cat']) && $_GET['product_cat'] !== '' ) {
		$curr_args = array_merge( $curr_args, array(
					'product_cat' => $_GET['product_cat']
			) );
	}
	else if ( isset($query->query['product_cat']) ) {
		$curr_args = array_merge( $curr_args, array(
					'product_cat' => $query->query['product_cat']
			) );
	}

	if ( isset($_GET['product_tag']) && $_GET['product_tag'] !== '' ) {
		$curr_args = array_merge( $curr_args, array(
					'product_tag' => $_GET['product_tag']
			) );
	}
	else if ( isset($query->query['product_tag']) ) {
		$curr_args = array_merge( $curr_args, array(
					'product_tag' => $query->query['product_tag']
			) );
	}

	if ( isset($_GET['characteristics']) && $_GET['characteristics'] !== '' ) {
		$curr_args = array_merge( $curr_args, array(
					'characteristics' => $_GET['characteristics']
			) );
	}
	else if ( isset($query->query['product_characteristics']) ) {
		$curr_args = array_merge( $curr_args, array(
					'characteristics' => $query->query['product_characteristics']
			) );
	}

	if ( isset($_GET['min_price']) && $_GET['min_price'] !== '' && ( !isset($_GET['sale_products']) || $_GET['sale_products'] !== 'yes' ) !== false ) {

		global $wpdb;

		if ( isset($_GET['max_price']) ) {
			$curr_args = array_merge( $curr_args, array(
						'meta_key' => '_price',
						'meta_value' => array( floatval($_GET['min_price']), floatval($_GET['max_price'])),
						'meta_type' => 'numeric',
						'meta_compare' => 'BETWEEN'
				) );
		}
		else {
			$max = ceil( $wpdb->get_var(
				$wpdb->prepare('
					SELECT max(meta_value + 0)
					FROM %1$s
					LEFT JOIN %2$s ON %1$s.ID = %2$s.post_id
					WHERE ( meta_key = \'%3$s\' OR meta_key = \'%4$s\' )
					AND meta_value != ""
					', $wpdb->posts, $wpdb->postmeta, '_price', '_max_variation_price' )
			) );
			$curr_args = array_merge( $curr_args, array(
						'meta_key' => '_price',
						'meta_value' => array( floatval($_GET['min_price']), floatval($max) ),
						'meta_type' => 'numeric',
						'meta_compare' => 'BETWEEN'
				) );
		}
	}
	else if ( isset($query->query['min_price']) ) {
		if ( !isset($query->query['max_price']) ) {
			global $wpdb;
			$_max_price = ceil( $wpdb->get_var(
				$wpdb->prepare('
					SELECT max(meta_value + 0)
					FROM %1$s
					LEFT JOIN %2$s ON %1$s.ID = %2$s.post_id
					WHERE ( meta_key = \'%3$s\' OR meta_key = \'%4$s\' )
					AND meta_value != ""
					', $wpdb->posts, $wpdb->postmeta, '_price', '_max_variation_price' )
			) );
		} 
		else {
			$_max_price = $query->query['max_price'];
		}
		$curr_args = array_merge( $curr_args, array(
					'meta_key' => '_price',
					'meta_value' => array( floatval($query->query['min_price']), floatval($_max_price) ),
					'meta_type' => 'numeric',
					'meta_compare' => 'BETWEEN'
			) );
	}

	if ( isset($_GET['sale_products']) && $_GET['sale_products'] !== '' ) {
		add_filter( 'posts_join' , 'prdctfltr_join_sale');
		add_filter( 'posts_where' , 'prdctfltr_sale_filter', 10, 2 );
	}

	if ( isset($query->query['http_query']) ) {
		parse_str(html_entity_decode($query->query['http_query']), $curr_http_args);
		$curr_args = array_merge( $curr_args, $curr_http_args );
	}

	if ( !isset($_GET['instock_products']) && isset($query->query['instock_products']) && $query->query['instock_products'] !== '' ) {
		$_GET['instock_products'] = $query->query['instock_products'];
	}

	if ( isset($_GET['instock_products']) && $_GET['instock_products'] !== '' && ( $_GET['instock_products'] == 'in' || $_GET['instock_products'] == 'out' ) ) {

		if ( $_GET['instock_products'] == 'in' ) {
			$i_arr['f_results'] = 'outofstock';
			$i_arr['s_results'] = 'instock';
		}
		else if ( $_GET['instock_products'] == 'out' ) {
			$i_arr['f_results'] = 'instock';
			$i_arr['s_results'] = 'outofstock';
		}

			if ( count($f_terms) == 0 ) {
				foreach($query->query as $k => $v){
					if (substr($k, 0, 3) == 'pa_') {
						$f_attrs[] = '"attribute_'.$k.'"';
						if ( !strpos($v, ',') ) {
							$f_terms[] = '"'.$v.'"';
						}
						else {
							$v_val = explode(',', $v);
							foreach ( $v_val as $o => $z ) {
								$f_terms[] = '"'.$z.'"';
							}
						}
					}
				}
			}

			$curr_atts = join(',', $f_attrs);
			$curr_terms = join(',', $f_terms);
			$curr_count = count($f_attrs)+1;

			if ( $curr_count > 1 ) {

				global $wpdb;

				$pf_exclude_product = $wpdb->get_results( $wpdb->prepare( '
					SELECT DISTINCT(post_parent) FROM %1$s
					INNER JOIN %2$s ON (%1$s.ID = %2$s.post_id)
					WHERE %1$s.post_parent != "0"
					AND %2$s.meta_key IN ("_stock_status",'.$curr_atts.')
					AND %2$s.meta_value IN ("'.$i_arr['f_results'].'",'.$curr_terms.',"")
					GROUP BY %2$s.post_id
					HAVING COUNT(DISTINCT %2$s.meta_value) = ' . $curr_count .'
					ORDER BY %1$s.ID ASC
				', $wpdb->posts, $wpdb->postmeta ) );

				$curr_in = array();
				foreach ( $pf_exclude_product as $p ) {
					$curr_in[] = $p->post_parent;
				}

				$pf_exclude_product_out = $wpdb->get_results( $wpdb->prepare( '
					SELECT DISTINCT(post_parent) FROM %1$s
					INNER JOIN %2$s ON (%1$s.ID = %2$s.post_id)
					WHERE %1$s.post_parent != "0"
					AND %2$s.meta_key IN ("_stock_status",'.$curr_atts.')
					AND %2$s.meta_value IN ("'.$i_arr['s_results'].'",'.$curr_terms.',"")
					GROUP BY %2$s.post_id
					HAVING COUNT(DISTINCT %2$s.meta_value) = ' . $curr_count .'
					ORDER BY %1$s.ID ASC
				', $wpdb->posts, $wpdb->postmeta ) );

				$curr_in_out = array();
				foreach ( $pf_exclude_product_out as $p ) {
					$curr_in_out[] = $p->post_parent;
				}

				if ( $_GET['instock_products'] == 'in' ) {

					foreach ( $curr_in as $q => $w ) {
						if ( in_array( $w, $curr_in_out) ) {
							unset($curr_in[$q]);
						}
					}
					$curr_args = array_merge( $curr_args, array(
								'post__not_in' => $curr_in
						) );

					add_filter( 'posts_join' , 'prdctfltr_join_instock');
					add_filter( 'posts_where' , 'prdctfltr_instock_filter', 999, 2 );


				}
				else if ( $_GET['instock_products'] == 'out' ) {

					foreach ( $curr_in_out as $e => $r ) {
						if ( in_array( $r, $curr_in) ) {
							unset($curr_in_out[$e]);
						}
					}

					$pf_exclude_product_addon = $wpdb->get_results( $wpdb->prepare( '
						SELECT DISTINCT(ID) FROM %1$s
						INNER JOIN %2$s ON (%1$s.ID = %2$s.post_id)
						WHERE %1$s.post_parent = "0"
						AND %2$s.meta_key IN ("_stock_status",'.$curr_atts.')
						AND %2$s.meta_value IN ("outofstock",'.$curr_terms.')
						GROUP BY %2$s.post_id
						ORDER BY %1$s.ID ASC
					', $wpdb->posts, $wpdb->postmeta ) );

					$curr_in_out_addon = array();
					foreach ( $pf_exclude_product_addon as $a ) {
						$curr_in_out_addon[] = $a->ID;
					}

					$curr_in_out = $curr_in_out + $curr_in_out_addon;

					$curr_args = array_merge( $curr_args, array(
								'post__in' => $curr_in_out
						) );

				}

			}
			else {
				if ( $_GET['instock_products'] == 'in' ) {
					add_filter( 'posts_join' , 'prdctfltr_join_instock');
					add_filter( 'posts_where' , 'prdctfltr_instock_filter', 999, 2 );
				}
				else if ( $_GET['instock_products'] == 'out' ) {
					add_filter( 'posts_join' , 'prdctfltr_join_instock');
					add_filter( 'posts_where' , 'prdctfltr_outofstock_filter', 999, 2 );
				}
			}

	}

	$pf_tax_query = array ();

	foreach ( $curr_args as $k => $v ) {
		if ( substr($k, 0, 3) == 'pa_' && $v !== '' ) {

			if ( !strpos($v, ',') ) {
				$pf_tax_query[] = array( 'taxonomy' => $k, 'field' => 'slug', 'terms' => $v );
			}
			else {
				$pf_tax_query[] = array( 'taxonomy' => $k, 'field' => 'slug', 'terms' => explode(',', $v) );
			}

			$query->set( $k, $v );
		}
		else {
			$query->set( $k, $v );
		}
	}

	if ( !empty($pf_tax_query) ) {

		$pf_tax_query['relation'] = 'AND';

		$query->set( 'tax_query', $pf_tax_query );

	}

}
endif;
add_filter('pre_get_posts','prdctfltr_wc_meta_query', 999999, 1);


/*
 * Product Filter Sale Filter
*/
function prdctfltr_sale_filter ( $where, &$wp_query ) {
	global $wpdb;

	if ( isset( $wp_query->query_vars['min_price'] ) ) {
		$_min_price =  $wp_query->query_vars['min_price'];
	}
	else if ( isset( $_GET['min_price'] ) ) {
		$_min_price =  $_GET['min_price'];
	}
	else if ( isset( $wp_query->query_vars['rng_min_price'] ) ) {
		$_min_price = $wp_query->query_vars['rng_min_price'];
	}
	else if ( isset( $_GET['rng_min_price'] ) ) {
		$_min_price = $_GET['rng_min_price'];
	}

	if ( isset( $wp_query->query_vars['max_price'] ) ) {
		$_max_price =  $wp_query->query_vars['max_price'];
	}
	else if ( isset( $_GET['max_price'] ) ) {
		$_max_price =  $_GET['max_price'];
	}
	else if ( isset( $wp_query->query_vars['rng_max_price'] ) ) {
		$_max_price = $wp_query->query_vars['rng_max_price'];
	}
	else if ( isset( $_GET['rng_max_price'] ) ) {
		$_max_price = $_GET['rng_max_price'];
	}

	if ( isset($_min_price) ) {

		$min = $_min_price;

		if ( isset($_max_price) ) {
			$max = $_max_price;
		}
		else {
			$max = ceil( $wpdb->get_var(
				$wpdb->prepare('
					SELECT max(meta_value + 0)
					FROM %1$s
					LEFT JOIN %2$s ON %1$s.ID = %2$s.post_id
					WHERE ( meta_key = \'%3$s\' OR meta_key = \'%4$s\' )
					AND meta_value != ""
					', $wpdb->posts, $wpdb->postmeta, '_sale_price', '_max_variation_sale_price' )
			) );
		}
			$where .= " AND ( pf_sale.meta_key IN ('_sale_price','_min_variation_sale_price') AND pf_sale.meta_value >= $min ) AND ( pf_sale_max.meta_key IN ('_sale_price','_max_variation_sale_price') AND pf_sale_max.meta_value <= $max ) ";
	}
	else {
		$where .= " AND ( pf_sale.meta_key IN (\"_sale_price\",\"_min_variation_sale_price\") AND pf_sale.meta_value > 0 ) ";
	}

	remove_filter( 'posts_where' , 'prdctfltr_sale_filter' );

	return $where;
	
}

/*
 * Product Filter Join Sale Tables
*/
function prdctfltr_join_sale($join){
	global $wpdb;
	$join .= " JOIN $wpdb->postmeta AS pf_sale ON $wpdb->posts.ID = pf_sale.post_id JOIN $wpdb->postmeta AS pf_sale_max ON $wpdb->posts.ID = pf_sale_max.post_id ";
	return $join;
}

/*
 * Product Filter Instock Filter
*/
function prdctfltr_instock_filter ( $where, &$wp_query ) {
	global $wpdb;


	$where = str_replace("AND ( ($wpdb->postmeta.meta_key = '_visibility' AND CAST($wpdb->postmeta.meta_value AS CHAR) IN ('visible','catalog')) )", "", $where);

	if ( isset($_GET['instock_products'] ) ) {
		$where .= " AND ( pf_instock.meta_key LIKE '_stock_status' AND pf_instock.meta_value = 'instock' ) ";
	}

	remove_filter( 'posts_where' , 'prdctfltr_instock_filter' );

	return $where;
	
}

/*
 * Product Filter Outofstock Filter
*/
function prdctfltr_outofstock_filter ( $where, &$wp_query ) {
	global $wpdb;


	$where = str_replace("AND ( ($wpdb->postmeta.meta_key = '_visibility' AND CAST($wpdb->postmeta.meta_value AS CHAR) IN ('visible','catalog')) )", "", $where);

	if ( isset($_GET['instock_products'] ) ) {
		$where .= " AND ( pf_instock.meta_key LIKE '_stock_status' AND pf_instock.meta_value = 'outofstock' ) ";
	}

	remove_filter( 'posts_where' , 'prdctfltr_outofstock_filter' );

	return $where;
	
}

/*
 * Product Filter Join Instock Tables
*/

function prdctfltr_join_instock($join){
	global $wpdb;
	$join .= " JOIN $wpdb->postmeta AS pf_instock ON $wpdb->posts.ID = pf_instock.post_id ";
	return $join;
}

/*
 * Product Filter Register Characteristics
*/
$curr_char = get_option( 'wc_settings_prdctfltr_custom_tax', 'no' );
if ( $curr_char == 'yes' ) {
	function prdctfltr_characteristics() {

		$labels = array(
			'name'                       => _x( 'Characteristics', 'taxonomy general name', 'prdctfltr' ),
			'singular_name'              => _x( 'Characteristics', 'taxonomy singular name', 'prdctfltr' ),
			'search_items'               => __( 'Search Characteristics', 'prdctfltr' ),
			'popular_items'              => __( 'Popular Characteristics', 'prdctfltr' ),
			'all_items'                  => __( 'All Characteristics', 'prdctfltr' ),
			'parent_item'                => null,
			'parent_item_colon'          => null,
			'edit_item'                  => __( 'Edit Characteristics', 'prdctfltr' ),
			'update_item'                => __( 'Update Characteristics', 'prdctfltr' ),
			'add_new_item'               => __( 'Add New Characteristic', 'prdctfltr' ),
			'new_item_name'              => __( 'New Characteristic Name', 'prdctfltr' ),
			'separate_items_with_commas' => __( 'Separate Characteristics with commas', 'prdctfltr' ),
			'add_or_remove_items'        => __( 'Add or remove characteristics', 'prdctfltr' ),
			'choose_from_most_used'      => __( 'Choose from the most used characteristics', 'prdctfltr' ),
			'not_found'                  => __( 'No characteristics found.', 'prdctfltr' ),
			'menu_name'                  => __( 'Characteristics', 'prdctfltr' ),
		);

		$args = array(
			'hierarchical'          => false,
			'labels'                => $labels,
			'show_ui'               => true,
			'show_admin_column'     => true,
			'update_count_callback' => '_update_post_term_count',
			'query_var'             => true,
			'rewrite'               => array( 'slug' => 'characteristics' ),
		);

		register_taxonomy( 'characteristics', array('product'), $args );
	}
	add_action( 'init', 'prdctfltr_characteristics', 0 );
}

$curr_disable = get_option( 'wc_settings_prdctfltr_enable', 'yes' );
$curr_disable_def = get_option( 'wc_settings_prdctfltr_default_templates', 'yes' );

if ( $curr_disable == 'yes') {

	/*
	 * Product Filter Override WooCommerce Template
	*/
	function prdctrfltr_add_filter ( $template, $slug, $name ) {

		if ( $name ) {
			$path = plugin_dir_path( __FILE__ ) . WC()->template_path() . "{$slug}-{$name}.php";
		} else {
			$path = plugin_dir_path( __FILE__ ) . WC()->template_path() . "{$slug}.php";
		}

		return file_exists( $path ) ? $path : $template;

	}
	add_filter( 'wc_get_template_part', 'prdctrfltr_add_filter', 10, 3 );

	function prdctrfltr_add_loop_filter ( $template, $template_name, $template_path ) {

		$path = plugin_dir_path( __FILE__ ) . $template_path . $template_name;
		return file_exists( $path ) ? $path : $template;

	}
	add_filter( 'woocommerce_locate_template', 'prdctrfltr_add_loop_filter', 10, 3 );


}

if ( $curr_disable == 'no' && $curr_disable_def == 'yes' ) {

	/*
	 * Product Filter Override WooCommerce Template
	*/
	function prdctrfltr_add_filter ( $template, $slug, $name ) {

		if ( $name ) {
			$path = plugin_dir_path( __FILE__ ) . 'blank/' . WC()->template_path() . "{$slug}-{$name}.php";
		} else {
			$path = plugin_dir_path( __FILE__ ) . 'blank/' . WC()->template_path() . "{$slug}.php";
		}

		return file_exists( $path ) ? $path : $template;

	}
	add_filter( 'wc_get_template_part', 'prdctrfltr_add_filter', 10, 3 );

	function prdctrfltr_add_loop_filter ( $template, $template_name, $template_path ) {

		$path = plugin_dir_path( __FILE__ ) . 'blank/' . $template_path . $template_name;
		return file_exists( $path ) ? $path : $template;

	}
	add_filter( 'woocommerce_locate_template', 'prdctrfltr_add_loop_filter', 10, 3 );

}


/*
 * Product Filter Search Variable Products
*/
function prdctrfltr_search_array($array, $attrs) {
	$results = array();
	$found = 0;

	foreach ($array as $subarray) {

		if ( isset($subarray['attributes'])) {
			foreach ( $attrs as $k => $v ) {
				if (in_array($v, $subarray['attributes'])) {
					$found++;
				}
			}
		}
		if ( count($attrs) == $found ) {
			$results[] = $subarray;
		}
		$found = 0;

	}

	return $results;
}

/*
 * Product Filter Get Variable Product
*/
$curr_variable = get_option( 'wc_settings_prdctfltr_use_variable_images', 'no' );
if ( $curr_variable == 'yes' ) {

	if ( function_exists('runkit_function_rename') && function_exists( 'woocommerce_get_product_thumbnail' ) ) :
		runkit_function_rename ( 'woocommerce_get_product_thumbnail', 'old_woocommerce_get_product_thumbnail' );
	endif;

	if ( !function_exists( 'woocommerce_get_product_thumbnail' ) ) :
	function woocommerce_get_product_thumbnail( $size = 'shop_catalog', $placeholder_width = 0, $placeholder_height = 0  ) {
		$product = get_product(get_the_ID());

		$attrs = array();
		foreach($_GET as $k => $v){
			if (substr($k, 0, 3) == 'pa_') {
				if ( !strpos($v, ',') ) {
					$v_val = $v;
				}
				else {
					$v_val = explode(',', $v);
					$v_val = $v_val[0];
				}
				$attrs = $attrs + array(
					$k => $v_val
				);
			}
		}

		if ( count($attrs) == 0 ) {
			global $wp_the_query;
			if ( isset($wp_the_query->query ) ) {
				foreach($wp_the_query->query as $k => $v){
					if (substr($k, 0, 3) == 'pa_') {
						if ( !strpos($v, ',') ) {
							$v_val = $v;
						}
						else {
							$v_val = explode(',', $v);
							$v_val = $v_val[0];
						}
						$attrs = $attrs + array(
							$k => $v_val
						);
					}
				}
			}
		}

		if ( count($attrs) == 0 ) {
			global $prdctfltr_global;

			if ( isset($prdctfltr_global['active_filters']) ) {
				foreach($prdctfltr_global['active_filters'] as $k => $v){
					if (substr($k, 0, 3) == 'pa_') {
						if ( !strpos($v, ',') ) {
							$v_val = $v;
						}
						else {
							$v_val = explode(',', $v);
							$v_val = $v_val[0];
						}
						$attrs = $attrs + array(
							$k => $v_val
						);
					}
				}
			}
		}

		if ( is_product_taxonomy() ) {
			$attrs = array_merge( $attrs, array( get_query_var('taxonomy') => get_query_var('term') ) );
		}

		if ( count($attrs) > 0 ) {
			if ( $product->is_type( 'variable' ) ) {
				$curr_var = $product->get_available_variations();
				$si = prdctrfltr_search_array($curr_var, $attrs);
			}
		}

		if ( isset($si[0]) && $si[0]['variation_id'] && has_post_thumbnail( $si[0]['variation_id'] ) ) {
			$image = get_the_post_thumbnail( $si[0]['variation_id'], $size );
		} elseif ( has_post_thumbnail( $product->id ) ) {
			$image = get_the_post_thumbnail( $product->id, $size );
		} elseif ( ( $parent_id = wp_get_post_parent_id( $product->id ) ) && has_post_thumbnail( $parent_id ) ) {
			$image = get_the_post_thumbnail( $product, $size );
		} else {
			$image = wc_placeholder_img( $size );
		}

		return $image;

	}
	endif;
}



/*
 * Product Filter Settings Class
*/
class WC_Settings_Prdctfltr {

	public static function init() {
		add_filter( 'woocommerce_settings_tabs_array', __CLASS__ . '::prdctfltr_add_settings_tab', 50 );
		add_action( 'woocommerce_settings_tabs_settings_products_filter', __CLASS__ . '::prdctfltr_settings_tab' );
		add_action( 'woocommerce_update_options_settings_products_filter', __CLASS__ . '::prdctfltr_update_settings' );
		add_action( 'woocommerce_admin_field_pf_filter', __CLASS__ . '::prdctfltr_pf_filter', 10 );
	}

	public static function prdctfltr_pf_filter($field) {

	global $woocommerce;
?>
	<tr valign="top">
		<th scope="row" class="titledesc">
			<label for="<?php echo esc_attr( $field['id'] ); ?>"><?php echo esc_html( $field['title'] ); ?></label>
			<?php echo '<img class="help_tip" data-tip="' . esc_attr( $field['desc'] ) . '" src="' . $woocommerce->plugin_url() . '/assets/images/help.png" height="16" width="16" />'; ?>
		</th>
		<td class="forminp forminp-<?php echo sanitize_title( $field['type'] ) ?>">
			<?php

				$pf_filters_selected = get_option('wc_settings_prdctfltr_active_filters');
				if ( $pf_filters_selected === false ) {
					$pf_filters_selected = array();
				}
				if ( empty($pf_filters_selected) ) {
					$curr_selected = get_option( 'wc_settings_prdctfltr_selected', array('sort','price','cat') );
					$curr_selected_attr = get_option( 'wc_settings_prdctfltr_attributes', array() );
					$pf_filters_selected = array_merge($curr_selected, $curr_selected_attr);
				}

				$curr_filters = array(
					'sort' => __('Sort By', 'prdctfltr'),
					'price' => __('By Price', 'prdctfltr'),
					'cat' => __('By Categories', 'prdctfltr'),
					'tag' => __('By Tags', 'prdctfltr'),
					'char' => __('By Characteristics', 'prdctfltr'),
					'instock' => __('In Stock Filter', 'prdctfltr')
				);

				if ( $attribute_taxonomies = wc_get_attribute_taxonomies() ) {
				$curr_attr = array();
				foreach ( $attribute_taxonomies as $tax ) {
					$curr_label = ! empty( $tax->attribute_label ) ? $tax->attribute_label : $tax->attribute_name;
					$curr_attr['pa_' . $tax->attribute_name] = ucfirst($curr_label);
					}
				}

				$pf_filters = ( is_array($curr_filters) ? $curr_filters : array() ) + ( is_array($curr_attr) ? $curr_attr : array() );

			?>
			<p class="form-field prdctfltr_customizer_fields">
			<?php
				foreach ( $pf_filters as $k => $v ) {
					if ( in_array($k, $pf_filters_selected) ) {
						$add['class'] = ' pf_active';
						$add['icon'] = '<i class="prdctfltr-eye"></i>';
					}
					else {
						$add['class'] = '';
						$add['icon'] = '<i class="prdctfltr-eye-disabled"></i>';
					}
			?>
				<a href="#" class="prdctfltr_c_add_filter<?php echo $add['class']; ?>" data-filter="<?php echo $k; ?>">
					<?php echo $add['icon']; ?> 
					<span><?php echo $v; ?></span>
				</a>
			<?php
				}
			?>
				<a href="#" class="prdctfltr_c_add pf_advanced"><i class="prdctfltr-plus"></i> <span><?php _e('Add advanced filter', 'prdctfltr'); ?></span></a>
				<a href="#" class="prdctfltr_c_add pf_range"><i class="prdctfltr-plus"></i> <span><?php _e('Add range filter', 'prdctfltr'); ?></span></a>
			</p>

			<p class="form-field prdctfltr_customizer">
			<?php
				$pf_filters_advanced = get_option('wc_settings_prdctfltr_advanced_filters');

				if ( $pf_filters_advanced === false ) {
					$pf_filters_advanced = array();
				}

				$pf_filters_range = get_option('wc_settings_prdctfltr_range_filters');

				if ( $pf_filters_range === false ) {
					$pf_filters_range = array();
				}

				$i=0;$q=0;

				foreach ( $pf_filters_selected as $v ) {
					if ( $v == 'advanced' ) {
				?>
						<span class="pf_element adv" data-filter="advanced" data-id="<?php echo $i; ?>">
							<span><?php _e('Advanced Filter', 'prdctfltr'); ?></span>
							<a href="#" class="prdctfltr_c_delete"><i class="prdctfltr-delete"></i></a>
							<a href="#" class="prdctfltr_c_move"><i class="prdctfltr-move"></i></a>
							<span class="pf_options_holder">
						<?php
							$taxonomies = get_object_taxonomies( 'product', 'object' );

							$html = '';

							$html .= sprintf( '<label><input type="text" name="pfa_title[%1$s]" value="%2$s"/> %3$s</label>', $i, $pf_filters_advanced['pfa_title'][$i], __( 'Override title.', 'prdctfltr' ) );

							$html .= sprintf('<label><select class="prdctfltr_adv_select" name="pfa_taxonomy[%1$s]">', $i);

							foreach ( $taxonomies as $k => $v ) {
								if ( $k == 'product_type' ) {
									continue;
								}
								$html .= '<option value="' . $k . '"' . ( $pf_filters_advanced['pfa_taxonomy'][$i] == $k ? ' selected="selected"' : '' ) .'>' . $v->label . '</option>';
							}
							$html .= '</select></label>';

							$catalog_attrs = get_terms( $pf_filters_advanced['pfa_taxonomy'][$i] );
							$curr_options = '';
							if ( !empty( $catalog_attrs ) && !is_wp_error( $catalog_attrs ) ){
								foreach ( $catalog_attrs as $term ) {
									$curr_options .= sprintf( '<option value="%1$s"%3$s>%2$s</option>', $term->slug, $term->name, ( in_array($term->slug, $pf_filters_advanced['pfa_include'][$i]) ? ' selected="selected"' : '' ) );
								}
							}

							$html .= sprintf( '<label><span>%3$s</span> <select name="pfa_include[%2$s][]" multiple="multiple">%1$s</select></label>', $curr_options, $i, __( 'Include terms.', 'prdctfltr' ) );

							$html .= sprintf( '<label><input type="checkbox" name="pfa_multiselect[%1$s]" value="yes" %2$s /> %3$s</label>', $i, ( $pf_filters_advanced['pfa_multiselect'][$i] == 'yes' ? ' checked="checked"' : '' ), __( 'Use multi select.', 'prdctfltr' ) );

							$html .= sprintf( '<label><input type="checkbox" name="pfa_adoptive[%1$s]" value="yes" %2$s /> %3$s</label>', $i, ( $pf_filters_advanced['pfa_adoptive'][$i] == 'yes' ? ' checked="checked"' : '' ), __( 'Use adoptive filtering.', 'prdctfltr' ) );

							echo $html;
						?>
							</span>
						</span>
					<?php
						$i++;
					}
					else if ( $v == 'range') {
				?>
						<span class="pf_element rng" data-filter="range" data-id="<?php echo $q; ?>">
							<span><?php _e('Range Filter', 'prdctfltr'); ?></span>
							<a href="#" class="prdctfltr_c_delete"><i class="prdctfltr-delete"></i></a>
							<a href="#" class="prdctfltr_c_move"><i class="prdctfltr-move"></i></a>
							<span class="pf_options_holder">
						<?php
							$taxonomies = wc_get_attribute_taxonomies();

							$html = '';

							$html .= sprintf( '<label><span>%3$s</span> <input type="text" name="pfr_title[%1$s]" value="%2$s"/></label>', $q, $pf_filters_range['pfr_title'][$q], __( 'Override title.', 'prdctfltr' ) );

							$html .= sprintf('<label><span>%2$s</span> <select class="prdctfltr_rng_select" name="pfr_taxonomy[%1$s]">', $q, __( 'Select range', 'prdctfltr' ));

							$html .= '<option value="price"' . ( $pf_filters_range['pfr_taxonomy'][$q] == 'price' ? ' selected="selected"' : '' ) . '>' . __( 'Price range', 'prdctfltr' ) . '</option>';

							foreach ( $taxonomies as $k => $v ) {
								$curr_label = ! empty( $v->attribute_label ) ? $v->attribute_label : $v->attribute_name;
								$html .= '<option value="pa_' . $v->attribute_name . '"' . ( $pf_filters_range['pfr_taxonomy'][$q] == 'pa_' . $v->attribute_name ? ' selected="selected"' : '' ) .'>' . $curr_label . '</option>';
							}
							$html .= '</select></label>';

							if ( $pf_filters_range['pfr_taxonomy'][$q] !== 'price' ) {

								$catalog_attrs = get_terms( $pf_filters_range['pfr_taxonomy'][$q] );
								$curr_options = '';
								if ( !empty( $catalog_attrs ) && !is_wp_error( $catalog_attrs ) ){
									foreach ( $catalog_attrs as $term ) {
										$curr_options .= sprintf( '<option value="%1$s"%3$s>%2$s</option>', $term->slug, $term->name, ( in_array($term->slug, $pf_filters_range['pfr_include'][$q]) ? ' selected="selected"' : '' ) );
									}
								}

								$html .= sprintf( '<label><span>%3$s</span> <select name="pfr_include[%2$s][]" multiple="multiple">%1$s</select></label>', $curr_options, $q, __( 'Include terms.', 'prdctfltr' ) );
							}
							else {
								$html .= sprintf( '<label><span>%2$s</span> <select name="pfr_include[%1$s][]" multiple="multiple" disabled></select></label>', $q, __( 'Include terms.', 'prdctfltr' ) );
							}

							$catalog_style = array( 'flat' => __( 'Flat', 'prdctfltr' ), 'modern' => __( 'Modern', 'prdctfltr' ), 'html5' => __( 'HTML5', 'prdctfltr' ), 'white' => __( 'White', 'prdctfltr' ) );
							$curr_options = '';
							foreach ( $catalog_style as $k => $v ) {
								$selected = ( $pf_filters_range['pfr_style'][$q] == $k ? ' selected="selected"' : '' );
								$curr_options .= sprintf( '<option value="%1$s"%3$s>%2$s</option>', $k, $v, $selected );
							}

							$html .= sprintf( '<label><span>%2$s</span> <select name="pfr_style[%3$s]">%1$s</select></label>', $curr_options, __( 'Select style', 'prdctfltr' ), $q );

							$selected = ( $pf_filters_range['pfr_grid'][$q] == 'yes' ? ' checked="checked"' : '' ) ;
							$html .= sprintf( '<label><input type="checkbox" name="pfr_grid[%3$s]" value="yes"%1$s /> %2$s</label>', $selected, __( 'Use grid', 'prdctfltr' ), $q );

							echo $html;
						?>
							</span>
						</span>
					<?php
						$q++;
					}
					else {
					?>
						<span class="pf_element" data-filter="<?php echo $v; ?>">
							<span><?php echo $pf_filters[$v]; ?></span>
							<a href="#" class="prdctfltr_c_delete"><i class="prdctfltr-delete"></i></a>
							<a href="#" class="prdctfltr_c_move"><i class="prdctfltr-move"></i></a>
						</span>
					<?php
					}
				}
			?>
			</p>

			<p class="form-field prdctfltr_hidden">
				<select name="wc_settings_prdctfltr_active_filters[]" id="wc_settings_prdctfltr_active_filters" class="hidden" multiple="multiple">
				<?php
					foreach ( $pf_filters_selected as $v ) {
						if ( $v !== 'advanced') {
					?>
						<option value="<?php echo $v; ?>" selected="selected"><?php echo $pf_filters[$v]; ?></option>
					<?php
						}
						else {
					?>
						<option value="<?php echo $v; ?>" selected="selected"><?php _e('Advanced Filter', 'prdctfltr'); ?></option>
					<?php
						}
					}
				?>
				</select>
			</p>

		</td>
	</tr><?php
	}

	public static function prdctfltr_add_settings_tab( $settings_tabs ) {
		$settings_tabs['settings_products_filter'] = __( 'Product Filter', 'prdctfltr' );
		return $settings_tabs;
	}

	public static function prdctfltr_settings_tab() {
		woocommerce_admin_fields( self::prdctfltr_get_settings( 'get' ) );
	}

	public static function prdctfltr_update_settings() {
		woocommerce_update_options( self::prdctfltr_get_settings( 'update' ) );

		if ( isset($_POST['pfa_taxonomy']) ) {

			$adv_filters = array();

			for($i = 0; $i < count($_POST['pfa_taxonomy']); $i++ ) {
				$adv_filters['pfa_title'][$i] = $_POST['pfa_title'][$i];
				$adv_filters['pfa_taxonomy'][$i] = $_POST['pfa_taxonomy'][$i];
				$adv_filters['pfa_include'][$i] = ( isset($_POST['pfa_include'][$i]) ? $_POST['pfa_include'][$i] : array() );
				$adv_filters['pfa_multiselect'][$i] = ( isset($_POST['pfa_multiselect'][$i]) ? $_POST['pfa_multiselect'][$i] : 'no' );
				$adv_filters['pfa_adoptive'][$i] = ( isset($_POST['pfa_adoptive'][$i]) ? $_POST['pfa_adoptive'][$i] : 'no' );
			}

			update_option('wc_settings_prdctfltr_advanced_filters', $adv_filters);

		}

		if ( isset($_POST['pfr_taxonomy']) ) {

			$rng_filters = array();

			for($i = 0; $i < count($_POST['pfr_taxonomy']); $i++ ) {
				$rng_filters['pfr_title'][$i] = $_POST['pfr_title'][$i];
				$rng_filters['pfr_taxonomy'][$i] = $_POST['pfr_taxonomy'][$i];
				$rng_filters['pfr_include'][$i] = ( isset($_POST['pfr_include'][$i]) ? $_POST['pfr_include'][$i] : array() );
				$rng_filters['pfr_style'][$i] = ( isset($_POST['pfr_style'][$i]) ? $_POST['pfr_style'][$i] : 'flat' );
				$rng_filters['pfr_grid'][$i] = ( isset($_POST['pfr_grid'][$i]) ? $_POST['pfr_grid'][$i] : 'no' );
			}

			update_option('wc_settings_prdctfltr_range_filters', $rng_filters);

		}

	}

	public static function prdctfltr_get_settings( $action = 'get' ) {

		if ( $attribute_taxonomies = wc_get_attribute_taxonomies() ) {
			$curr_attr = array();
			foreach ( $attribute_taxonomies as $tax ) {

				$curr_label = ! empty( $tax->attribute_label ) ? $tax->attribute_label : $tax->attribute_name;

				$curr_attr['pa_' . $tax->attribute_name] = $curr_label;

			}
		}

		$catalog_categories = get_terms( 'product_cat' );
		$curr_cats = array();
		if ( !empty( $catalog_categories ) && !is_wp_error( $catalog_categories ) ){
			foreach ( $catalog_categories as $term ) {
				$curr_cats[$term->slug] = $term->name;
			}
		}

		$catalog_tags = get_terms( 'product_tag' );
		$curr_tags = array();
		if ( !empty( $catalog_tags ) && !is_wp_error( $catalog_tags ) ){
			foreach ( $catalog_tags as $term ) {
				$curr_tags[$term->slug] = $term->name;
			}
		}

		$catalog_chars = ( taxonomy_exists('characteristics') ? get_terms( 'characteristics' ) : array() );
		$curr_chars = array();
		if ( !empty( $catalog_chars ) && !is_wp_error( $catalog_chars ) ){
			foreach ( $catalog_chars as $term ) {
				$curr_chars[$term->slug] = $term->name;
			}
		}

		$attribute_taxonomies = wc_get_attribute_taxonomies();
		$curr_atts = array();
		if ( !empty( $attribute_taxonomies ) && !is_wp_error( $attribute_taxonomies ) ){
			foreach ( $attribute_taxonomies as $term ) {
				$curr_atts['pa_' . $term->attribute_name] = $term->attribute_name;
			}
		}


		if ( $action == 'get' ) {
	?>
	<ul class="subsubsub">
	<?php
		$sections = array(
			'general' => __( 'General Options', 'prdctfltr' ),
			'presets' => __( 'Default Filter and Presets', 'prdctfltr' ),
			'overrides' => __( 'Filter Overrides', 'prdctfltr' ),
		);

		$i=0;
		foreach ( $sections as $k => $v ) {
			if ( ( isset($_GET['section']) && $_GET['section'] == $k ) || ( !isset($_GET['section']) && $k == 'general' ) ) {
				printf( '<li>%3$s<a href="%1$s" class="current">%2$s</a></li>', add_query_arg('section', $k, get_permalink()), $v, ( $i == 0 ? '' : ' | ' ) );
			}
			else {
				printf( '<li>%3$s<a href="%1$s">%2$s</a></li>', add_query_arg('section', $k, get_permalink()), $v, ( $i == 0 ? '' : ' | ' ) );
			}
			$i++;
		}
	?>
	</ul>
	<br class="clear" />
	<?php
		}
		if ( ( isset($_GET['section']) && $_GET['section'] == 'general' ) || ( !isset($_GET['section']) ) ) {
			$curr_theme = wp_get_theme();

			$settings = array(
				'section_general_title' => array(
					'name' => __( 'Product Filter General Settings', 'prdctfltr' ),
					'type' => 'title',
					'desc' => __( 'These settings will affect all filters.', 'prdctfltr' ),
					'id' => 'wc_settings_prdctfltr_general_title'
				),
				'prdctfltr_enable' => array(
					'name' => __( 'Enable/Disable Product Filter Template Overrides', 'prdctfltr' ),
					'type' => 'checkbox',
					'desc' => __( 'Uncheck this option in order to disable the Product Filter template override and use the default WooCommerce or', 'prdctfltr') . ' ' . $curr_theme->get('Name') . ' ' . __('theme filter. This options should be unchecked if you are using the widget version.', 'prdctfltr' ),
					'id'   => 'wc_settings_prdctfltr_enable',
					'default' => 'yes',
				),
				'section_general_end' => array(
					'type' => 'sectionend',
					'id' => 'wc_settings_prdctfltr_general_end'
				),
				'section_advanced_title' => array(
					'name' => __( 'Product Filter Advanced Settings', 'prdctfltr' ),
					'type' => 'title',
					'desc' => __( 'Advanced Settings - These settings will affect all filters.', 'prdctfltr' ),
					'id' => 'wc_settings_prdctfltr_advanced_title'
				),
				'prdctfltr_shop_disable' => array(
					'name' => __( 'Enable/Disable Shop Page Product Filter', 'prdctfltr' ),
					'type' => 'checkbox',
					'desc' => __( 'Check this option in order to disable the Product Filter on shop page. This option can be useful for themes with custom Shop pages, if checked the default WooCommerce or', 'prdctfltr') . ' ' . $curr_theme->get('Name') . ' ' . __('filter template will be overriden only on product archives. CAUTION The above option must be enabled (checked) for this setting to take effect.', 'prdctfltr' ),
					'id'   => 'wc_settings_prdctfltr_shop_disable',
					'default' => 'no',
				),
				'prdctfltr_categories_query' => array(
					'name' => __( 'Enable/Disable Filtering thru Categories', 'prdctfltr' ),
					'type' => 'checkbox',
					'desc' => __( 'Check this option if you are experiencing redirects on shop page when searching the categories. This setting is only for the faulty themes as regular themes will filter these as intended.', 'prdctfltr' ),
					'id'   => 'wc_settings_prdctfltr_categories_query',
					'default' => 'no',
				),
				'prdctfltr_default_templates' => array(
					'name' => __( 'Enable/Disable Default Filter Templates', 'prdctfltr' ),
					'type' => 'checkbox',
					'desc' => __( 'If you have disabled the Product Filter Override Templates option at the top, then your default WooCommerce or', 'prdctfltr') . ' ' . $curr_theme->get('Name') . ' ' . __('filter templates will be shown. If you want do disable those too, check this option.', 'prdctfltr' ),
					'id'   => 'wc_settings_prdctfltr_default_templates',
					'default' => 'no',
				),
				'prdctfltr_use_variable_images' => array(
					'name' => __( 'Use Variable Images', 'prdctfltr' ),
					'type' => 'checkbox',
					'desc' => __( 'Check this option to use variable images override on shop and archive pages. CAUTION This setting does not work on all servers by default. Additional server setup might be needed.', 'prdctfltr' ),
					'id'   => 'wc_settings_prdctfltr_use_variable_images',
					'default' => 'no',
				),
				'prdctfltr_disable_scripts' => array(
					'name' => __( 'Disable JavaScript Libraries', 'prdctfltr' ),
					'type' => 'multiselect',
					'desc' => __( 'Select JavaScript libraries to disable. Use CTRL+Click to select multiple libraries or deselect all. Selected libraries will not be loaded.', 'prdctfltr' ),
					'id'   => 'wc_settings_prdctfltr_disable_scripts',
					'options' => array(
						'ionrange' => __( 'Ion Range Slider', 'prdctfltr' ),
						'isotope' => __( 'Isotope', 'prdctfltr' ),
						'mcustomscroll' => __( 'Malihu jQuery Scrollbar', 'prdctfltr' )
					),
					'default' => '',
					'css' => 'width:300px;margin-right:12px;'
				),
				'section_advanced_end' => array(
					'type' => 'sectionend',
					'id' => 'wc_settings_prdctfltr_advanced_end'
				),
			);
		}
		else if ( isset($_GET['section']) && $_GET['section'] == 'presets' ) {
			if ( $action == 'get' ) {

				printf( '<h3>%1$s</h3><p>%2$s</p><p>', __( 'Product Filter Presets', 'prdctfltr' ), __( 'Manage filter presets. Load, delete and save settings. Once saved as a preset, filters can be used within shortcodes, to generate different filters for different elements or to override filters on shop archive.', 'prdctfltr' ) );
		?>
						<select id="prdctfltr_filter_presets">
							<option value="default"><?php _e('Default', 'wcwar'); ?></option>
							<?php
								$curr_presets = get_option('prdctfltr_templates');
								if ( $curr_presets === false ) {
									$curr_presets = array();
								}
								if ( !empty($curr_presets) ) {
									foreach ( $curr_presets as $k => $v ) {
								?>
										<option value="<?php echo $k; ?>"><?php echo $k; ?></option>
								<?php
									}
								}
							?>
						</select>
		<?php
				printf( '<a href="#" id="prdctfltr_save" class="button-primary">%1$s</a> <a href="#" id="prdctfltr_load" class="button-primary">%2$s</a> <a href="#" id="prdctfltr_delete" class="button-primary">%3$s</a> <a href="#" id="prdctfltr_reset_default" class="button-primary">%4$s</a> <a href="#" id="prdctfltr_save_default" class="button-primary">%5$s</a></p>', __( 'Save as preset', 'prdctfltr' ), __( 'Load', 'prdctfltr' ), __( 'Delete', 'prdctfltr' ), __( 'Reset to default', 'prdctfltr' ), __( 'Save as default preset', 'prdctfltr' ) );
			}

			$settings = array(
				'section_basic_title' => array(
					'name'     => __( 'Product Filter Basic Settings', 'prdctfltr' ),
					'type'     => 'title',
					'desc'     => __( 'Setup you Product Filter appearance.', 'prdctfltr' ),
					'id'       => 'wc_settings_prdctfltr_basic_title'
				),
				'prdctfltr_title' => array(
					'name' => __( 'Override Filter Title', 'prdctfltr' ),
					'type' => 'text',
					'desc' => __( 'Override Filter Products, the default filter title.', 'prdctfltr' ),
					'id'   => 'wc_settings_prdctfltr_title',
					'default' => '',
					'css' => 'width:300px;margin-right:12px;'
				),
				'prdctfltr_disable_bar' => array(
					'name' => __( 'Disable Top Bar', 'prdctfltr' ),
					'type' => 'checkbox',
					'desc' => __( 'Check this option to hide the Product Filter top bar. This option will also make the filter always visible.', 'prdctfltr' ) . ' <em>' . __( '(Does not work with the Arrow presets as these presets are absolutely positioned)', 'prdctfltr' ) . '</em>',
					'id'   => 'wc_settings_prdctfltr_disable_bar',
					'default' => 'no',
				),
				'prdctfltr_always_visible' => array(
					'name' => __( 'Always Visible', 'prdctfltr' ),
					'type' => 'checkbox',
					'desc' => __( 'This option will make Product Filter visible without the slide up/down animation at all times.', 'prdctfltr' ) . ' <em>' . __( '(Doesn\'t work with the Arrow presets as these presets are absolutely positioned)', 'prdctfltr' ) . '</em>',
					'id'   => 'wc_settings_prdctfltr_always_visible',
					'default' => 'no',
				),
				'prdctfltr_click_filter' => array(
					'name' => __( 'Instant Filtering', 'prdctfltr' ),
					'type' => 'checkbox',
					'desc' => __( 'Check this option to disable the filter button and use instant product filtering.', 'prdctfltr' ),
					'id'   => 'wc_settings_prdctfltr_click_filter',
					'default' => 'no',
				),
				'prdctfltr_show_counts' => array(
					'name' => __( 'Show Term Products Count', 'prdctfltr' ),
					'type' => 'checkbox',
					'desc' => __( 'Check this option to show products count with the terms.', 'prdctfltr' ),
					'id'   => 'wc_settings_prdctfltr_show_counts',
					'default' => 'no',
				),
				'prdctfltr_adoptive' => array(
					'name' => __( 'Enable/Disable Adoptive Filtering', 'prdctfltr' ),
					'type' => 'checkbox',
					'desc' => __( 'Check this option to enable the adoptive filtering.', 'prdctfltr' ),
					'id'   => 'wc_settings_prdctfltr_adoptive',
					'default' => 'no',
				),
				'prdctfltr_adoptive_style' => array(
					'name' => __( 'Select Adoptive Filtering Style', 'prdctfltr' ),
					'type' => 'select',
					'desc' => __( 'Select style to use with the filtered terms.', 'prdctfltr' ),
					'id'   => 'wc_settings_prdctfltr_adoptive_style',
					'options' => array(
						'pf_adptv_default' => __( 'Hide Terms', 'prdctfltr' ),
						'pf_adptv_unclick' => __( 'Disabled and Unclickable', 'prdctfltr' ),
						'pf_adptv_click' => __( 'Disabled but Clickable', 'prdctfltr' )
					),
					'default' => 'pf_adptv_default',
					'css' => 'width:300px;margin-right:12px;'
				),
				'prdctfltr_disable_sale' => array(
					'name' => __( 'Disable Sale Button', 'prdctfltr' ),
					'type' => 'checkbox',
					'desc' => __( 'Check this option to hide the Product Filter sale button.', 'prdctfltr' ),
					'id'   => 'wc_settings_prdctfltr_disable_sale',
					'default' => 'no',
				),
				'prdctfltr_disable_instock' => array(
					'name' => __( 'Disable In Stock Button', 'prdctfltr' ),
					'type' => 'checkbox',
					'desc' => __( 'Check this option to hide the Product Filter in stock button.', 'prdctfltr' ),
					'id'   => 'wc_settings_prdctfltr_disable_instock',
					'default' => 'no',
				),
				'prdctfltr_disable_reset' => array(
					'name' => __( 'Disable Reset Button', 'prdctfltr' ),
					'type' => 'checkbox',
					'desc' => __( 'Check this option to hide the Product Filter reset button.', 'prdctfltr' ),
					'id'   => 'wc_settings_prdctfltr_disable_reset',
					'default' => 'no',
				),
				'prdctfltr_disable_showresults' => array(
					'name' => __( 'Disable Show Results Title', 'prdctfltr' ),
					'type' => 'checkbox',
					'desc' => __( 'Check this option to hide the show results text from the filter title.', 'prdctfltr' ),
					'id'   => 'wc_settings_prdctfltr_disable_showresults',
					'default' => 'no',
				),
				'prdctfltr_noproducts' => array(
					'name' => __( 'Override No Products Action', 'prdctfltr' ),
					'type' => 'textarea',
					'desc' => __( 'Input HTML/Shortcode to override the default action when no products are found. Default action means that random products will be shown when there are no products within the filter query.', 'prdctfltr' ),
					'id'   => 'wc_settings_prdctfltr_noproducts',
					'default' => '',
					'css' 		=> 'min-width:600px;margin-top:12px;min-height:150px;',
				),
				'section_basic_end' => array(
					'type' => 'sectionend',
					'id' => 'wc_settings_prdctfltr_enable_end'
				),
				'section_style_title' => array(
					'name'     => __( 'Product Filter Style', 'prdctfltr' ),
					'type'     => 'title',
					'desc'     => __( 'Select style preset to use. Use custom preset for your own style. Use Disable CSS to disable all CSS for product filter.', 'prdctfltr' ),
					'id'       => 'wc_settings_prdctfltr_style_title'
				),
				'prdctfltr_style_preset' => array(
					'name' => __( 'Select Style', 'prdctfltr' ),
					'type' => 'select',
					'desc' => __( 'Select style to use or use Disable CSS option for custom settings.', 'prdctfltr' ),
					'id'   => 'wc_settings_prdctfltr_style_preset',
					'options' => array(
						'pf_disable' => __( 'Disable CSS', 'prdctfltr' ),
						'pf_arrow' => __( 'Arrow', 'prdctfltr' ),
						'pf_arrow_inline' => __( 'Arrow Inline', 'prdctfltr' ),
						'pf_default' => __( 'Default', 'prdctfltr' ),
						'pf_default_inline' => __( 'Default Inline', 'prdctfltr' ),
						'pf_select' => __( 'Use Select Box', 'prdctfltr' ),
					),
					'default' => 'pf_default',
					'css' => 'width:300px;margin-right:12px;'
				),
				'prdctfltr_style_mode' => array(
					'name' => __( 'Select Mode', 'prdctfltr' ),
					'type' => 'select',
					'desc' => __( 'Select mode to use with the filter.', 'prdctfltr' ),
					'id'   => 'wc_settings_prdctfltr_style_mode',
					'options' => array(
						'pf_mod_row' => __( 'One Row', 'prdctfltr' ),
						'pf_mod_multirow' => __( 'Multiple Rows', 'prdctfltr' ),
						'pf_mod_masonry' => __( 'Masonry Filters', 'prdctfltr' )
					),
					'default' => 'pf_mod_multirow',
					'css' => 'width:300px;margin-right:12px;'
				),
				'prdctfltr_max_columns' => array(
					'name' => __( 'Max Columns', 'prdctfltr' ),
					'type' => 'number',
					'desc' => __( 'This option sets the number of columns for the filter. If the Max Height is set to 0 the filters will be added in the next row when the Max Columns number is reached.', 'prdctfltr' ),
					'id'   => 'wc_settings_prdctfltr_max_columns',
					'default' => 6,
					'custom_attributes' => array(
						'min' 	=> 1,
						'max' 	=> 6,
						'step' 	=> 1
					),
					'css' => 'width:100px;margin-right:12px;'
				),
				'prdctfltr_limit_max_height' => array(
					'name' => __( 'Limit Max Height', 'prdctfltr' ),
					'type' => 'checkbox',
					'desc' => __( 'Check this option to limit the Max Height of for the filters.', 'prdctfltr' ),
					'id'   => 'wc_settings_prdctfltr_limit_max_height',
					'default' => 'no',
				),
				'prdctfltr_max_height' => array(
					'name' => __( 'Max Height', 'prdctfltr' ),
					'type' => 'number',
					'desc' => __( 'Set the Max Height value.', 'prdctfltr' ),
					'id'   => 'wc_settings_prdctfltr_max_height',
					'default' => 150,
					'custom_attributes' => array(
						'min' 	=> 100,
						'max' 	=> 300,
						'step' 	=> 1
					),
					'css' => 'width:100px;margin-right:12px;'
				),
				'prdctfltr_custom_scrollbar' => array(
					'name' => __( 'Use Custom Scroll Bars', 'prdctfltr' ),
					'type' => 'checkbox',
					'desc' => __( 'Check this option to override default browser scroll bars with javascrips scrollbars in Max Height mode.', 'prdctfltr' ),
					'id'   => 'wc_settings_prdctfltr_custom_scrollbar',
					'default' => 'yes',
				),
				'prdctfltr_icon' => array(
					'name' => __( 'Override Default Icon', 'prdctfltr' ),
					'type' => 'text',
					'desc' => __( 'Input the icon class in order to override default Product Filter icon.', 'prdctfltr' ),
					'id'   => 'wc_settings_prdctfltr_icon',
					'default' => '',
					'css' => 'width:300px;margin-right:12px;'
				),
				'section_style_end' => array(
					'type' => 'sectionend',
					'id' => 'wc_settings_prdctfltr_style_end'
				),
				'section_title' => array(
					'name'     => __( 'Select Product Filters', 'prdctfltr' ),
					'type'     => 'title',
					'desc'     => __( 'Select product filters to use.', 'prdctfltr' ),
					'id'       => 'wc_settings_prdctfltr_section_title'
				),
				'prdctfltr_filters' => array(
					'name' => __( 'Select Filters', 'prdctfltr' ),
					'type' => 'pf_filter',
					'desc' => __( 'Select filters. Use CTRL+Click to select multiple filters.', 'prdctfltr' ),
					'id'   => 'wc_settings_prdctfltr_active_filters',
				),
				'prdctfltr_selected' => array(
					'name' => __( 'Select Filters', 'prdctfltr' ),
					'type' => 'multiselect',
					'desc' => __( 'Select filters. Use CTRL+Click to select multiple filters.', 'prdctfltr' ),
					'id'   => 'wc_settings_prdctfltr_selected',
					'options' => array(
						'sort' => __('Sort By', 'prdctfltr'),
						'price' => __('By Price', 'prdctfltr'),
						'cat' => __('By Categories', 'prdctfltr'),
						'tag' => __('By Tags', 'prdctfltr'),
						'char' => __('By Characteristics', 'prdctfltr')
					),
					'default' => array('sort','price','cat')
				),
				'prdctfltr_attributes' => array(
					'name' => __( 'Select Attributes', 'prdctfltr' ),
					'type' => 'multiselect',
					'desc' => __( 'Select your attributes. Use CTRL+Click to select multiple attributes.', 'prdctfltr' ),
					'id'   => 'wc_settings_prdctfltr_attributes',
					'options' => $curr_attr,
					'default' => array()
				),
				'section_end' => array(
					'type' => 'sectionend',
					'id' => 'wc_settings_prdctfltr_section_end'
				),

				'section_instock_filter_title' => array(
					'name'     => __( 'In Stock Filter Settings', 'prdctfltr' ),
					'type'     => 'title',
					'desc'     => __( 'Setup in stock filter.', 'prdctfltr' ),
					'id'       => 'wc_settings_prdctfltr_instock_filter_title'
				),
				'prdctfltr_instock_title' => array(
					'name' => __( 'Override In Stock Filter Title', 'prdctfltr' ),
					'type' => 'text',
					'desc' => __( 'Enter title for the in stock filter. If you leave this field blank default will be used.', 'prdctfltr' ),
					'id'   => 'wc_settings_prdctfltr_instock_title',
					'default' => '',
					'css' => 'width:300px;margin-right:12px;'
				),
				'section_instock_filter_end' => array(
					'type' => 'sectionend',
					'id' => 'wc_settings_prdctfltr_instock_filter_end'
				),

				'section_orderby_filter_title' => array(
					'name'     => __( 'Sort By Filter Settings', 'prdctfltr' ),
					'type'     => 'title',
					'desc'     => __( 'Setup sort by filter.', 'prdctfltr' ),
					'id'       => 'wc_settings_prdctfltr_orderby_filter_title'
				),
				'prdctfltr_orderby_title' => array(
					'name' => __( 'Override Sort By Filter Title', 'prdctfltr' ),
					'type' => 'text',
					'desc' => __( 'Enter title for the sort by filter. If you leave this field blank default will be used.', 'prdctfltr' ),
					'id'   => 'wc_settings_prdctfltr_orderby_title',
					'default' => '',
					'css' => 'width:300px;margin-right:12px;'
				),

				'prdctfltr_include_orderby' => array(
					'name' => __( 'Select Sort By Terms', 'prdctfltr' ),
					'type' => 'multiselect',
					'desc' => __( 'Select Sort by terms to include. Use CTRL+Click to select multiple Sort by terms or deselect all to use all Sort by terms.', 'prdctfltr' ),
					'id'   => 'wc_settings_prdctfltr_include_orderby',
					'options' => array(
							'menu_order'    => __( 'Default', 'prdctfltr' ),
							'comment_count' => __( 'Review Count', 'prdctfltr' ),
							'popularity'    => __( 'Popularity', 'prdctfltr' ),
							'rating'        => __( 'Average rating', 'prdctfltr' ),
							'date'          => __( 'Newness', 'prdctfltr' ),
							'price'         => __( 'Price: low to high', 'prdctfltr' ),
							'price-desc'    => __( 'Price: high to low', 'prdctfltr' ),
							'rand'          => __( 'Random Products', 'prdctfltr' ),
							'title'         => __( 'Product Name', 'prdctfltr' )
						),
					'default' => array(),
					'css' => 'width:300px;margin-right:12px;'
				),

				'section_orderby_filter_end' => array(
					'type' => 'sectionend',
					'id' => 'wc_settings_prdctfltr_orderby_filter_end'
				),

				'section_price_filter_title' => array(
					'name'     => __( 'By Price Filter Settings', 'prdctfltr' ),
					'type'     => 'title',
					'desc'     => __( 'Setup by price filter.', 'prdctfltr' ),
					'id'       => 'wc_settings_prdctfltr_price_filter_title'
				),
				'prdctfltr_price_title' => array(
					'name' => __( 'Override Price Filter Title', 'prdctfltr' ),
					'type' => 'text',
					'desc' => __( 'Enter title for the price filter. If you leave this field blank default will be used.', 'prdctfltr' ),
					'id'   => 'wc_settings_prdctfltr_price_title',
					'default' => '',
					'css' => 'width:300px;margin-right:12px;'
				),
				'prdctfltr_price_range' => array(
					'name' => __( 'Price Range Filter Initial', 'prdctfltr' ),
					'type' => 'number',
					'desc' => __( 'Input basic initial price.', 'prdctfltr' ),
					'id'   => 'wc_settings_prdctfltr_price_range',
					'default' => 100,
					'custom_attributes' => array(
						'min' 	=> 0.5,
						'max' 	=> 9999999,
						'step' 	=> 0.1
					),
					'css' => 'width:100px;margin-right:12px;'
				),
				'prdctfltr_price_range_add' => array(
					'name' => __( 'Price Range Filter Price Add', 'prdctfltr' ),
					'type' => 'number',
					'desc' => __( 'Input the price to add. E.G. You have set the initial value to 99.9, and you now wish to add a 100 more on the next price options to achieve filtering from 0-99.9, 99.9-199.9, 199.9- 299.9 and so on...', 'prdctfltr' ),
					'id'   => 'wc_settings_prdctfltr_price_range_add',
					'default' => 100,
					'custom_attributes' => array(
						'min' 	=> 0.5,
						'max' 	=> 9999999,
						'step' 	=> 0.1
					),
					'css' => 'width:100px;margin-right:12px;'
				),
				'prdctfltr_price_range_limit' => array(
					'name' => __( 'Price Range Filter Price Limit', 'prdctfltr' ),
					'type' => 'number',
					'desc' => __( 'Input the number of price intervals you wish to use.', 'prdctfltr' ),
					'id'   => 'wc_settings_prdctfltr_price_range_limit',
					'default' => 6,
					'custom_attributes' => array(
						'min' 	=> 2,
						'max' 	=> 20,
						'step' 	=> 1
					),
					'css' => 'width:100px;margin-right:12px;'
				),
				'prdctfltr_price_adoptive' => array(
					'name' => __( 'Use Adoptive Filtering', 'prdctfltr' ),
					'type' => 'checkbox',
					'desc' => __( 'Check this option to use adoptive filtering on prices.', 'prdctfltr' ),
					'id'   => 'wc_settings_prdctfltr_price_adoptive',
					'default' => 'no',
				),
				'section_price_filter_end' => array(
					'type' => 'sectionend',
					'id' => 'wc_settings_prdctfltr_price_filter_end'
				),
				'section_cat_filter_title' => array(
					'name'     => __( 'By Category Filter Settings', 'prdctfltr' ),
					'type'     => 'title',
					'desc'     => __( 'Setup by category filter.', 'prdctfltr' ),
					'id'       => 'wc_settings_prdctfltr_cat_filter_title'
				),
				'prdctfltr_cat_title' => array(
					'name' => __( 'Override Category Filter Title', 'prdctfltr' ),
					'type' => 'text',
					'desc' => __( 'Enter title for the category filter. If you leave this field blank default will be used.', 'prdctfltr' ),
					'id'   => 'wc_settings_prdctfltr_cat_title',
					'default' => '',
					'css' => 'width:300px;margin-right:12px;'
				),
				'prdctfltr_include_cats' => array(
					'name' => __( 'Select Categories', 'prdctfltr' ),
					'type' => 'multiselect',
					'desc' => __( 'Select categories to include. Use CTRL+Click to select multiple categories or deselect all.', 'prdctfltr' ),
					'id'   => 'wc_settings_prdctfltr_include_cats',
					'options' => $curr_cats,
					'default' => array(),
					'css' => 'width:300px;margin-right:12px;'
				),
				'prdctfltr_cat_limit' => array(
					'name' => __( 'Limit Categories', 'prdctfltr' ),
					'type' => 'number',
					'desc' => __( 'Limit number of categories to be shown. If limit is set categories with most posts will be shown first.', 'prdctfltr' ),
					'id'   => 'wc_settings_prdctfltr_cat_limit',
					'default' => 0,
					'custom_attributes' => array(
						'min' 	=> 0,
						'max' 	=> 100,
						'step' 	=> 1
					),
					'css' => 'width:100px;margin-right:12px;'
				),
				'prdctfltr_cat_hierarchy' => array(
					'name' => __( 'Use Category Hierarchy', 'prdctfltr' ),
					'type' => 'checkbox',
					'desc' => __( 'Check this option to enable category hierarchy.', 'prdctfltr' ),
					'id'   => 'wc_settings_prdctfltr_cat_hierarchy',
					'default' => 'no',
				),
				'prdctfltr_cat_multi' => array(
					'name' => __( 'Use Multi Select', 'prdctfltr' ),
					'type' => 'checkbox',
					'desc' => __( 'Check this option to enable multi-select on categories.', 'prdctfltr' ),
					'id'   => 'wc_settings_prdctfltr_cat_multi',
					'default' => 'no',
				),
				'prdctfltr_cat_adoptive' => array(
					'name' => __( 'Use Adoptive Filtering', 'prdctfltr' ),
					'type' => 'checkbox',
					'desc' => __( 'Check this option to use adoptive filtering on categories.', 'prdctfltr' ),
					'id'   => 'wc_settings_prdctfltr_cat_adoptive',
					'default' => 'no',
				),
				'section_cat_filter_end' => array(
					'type' => 'sectionend',
					'id' => 'wc_settings_prdctfltr_cat_filter_end'
				),
				'section_tag_filter_title' => array(
					'name'     => __( 'By Tag Filter Settings', 'prdctfltr' ),
					'type'     => 'title',
					'desc'     => __( 'Setup by tag filter.', 'prdctfltr' ),
					'id'       => 'wc_settings_prdctfltr_tag_filter_title'
				),
				'prdctfltr_tag_title' => array(
					'name' => __( 'Override Tag Filter Title', 'prdctfltr' ),
					'type' => 'text',
					'desc' => __( 'Enter title for the tag filter. If you leave this field blank default will be used.', 'prdctfltr' ),
					'id'   => 'wc_settings_prdctfltr_tag_title',
					'default' => '',
					'css' => 'width:300px;margin-right:12px;'
				),
				'prdctfltr_include_tags' => array(
					'name' => __( 'Select Tags', 'prdctfltr' ),
					'type' => 'multiselect',
					'desc' => __( 'Select tags to include. Use CTRL+Click to select multiple tags or deselect all.', 'prdctfltr' ),
					'id'   => 'wc_settings_prdctfltr_include_tags',
					'options' => $curr_tags,
					'default' => array(),
					'css' => 'width:300px;margin-right:12px;'
				),
				'prdctfltr_tag_limit' => array(
					'name' => __( 'Limit Tags', 'prdctfltr' ),
					'type' => 'number',
					'desc' => __( 'Limit number of tags to be shown. If limit is set tags with most posts will be shown first.', 'prdctfltr' ),
					'id'   => 'wc_settings_prdctfltr_tag_limit',
					'default' => 0,
					'custom_attributes' => array(
						'min' 	=> 0,
						'max' 	=> 100,
						'step' 	=> 1
					),
					'css' => 'width:100px;margin-right:12px;'
				),
				'prdctfltr_tag_multi' => array(
					'name' => __( 'Use Multi Select', 'prdctfltr' ),
					'type' => 'checkbox',
					'desc' => __( 'Check this option to enable multi-select on tags.', 'prdctfltr' ),
					'id'   => 'wc_settings_prdctfltr_tag_multi',
					'default' => 'no',
				),
				'prdctfltr_tag_adoptive' => array(
					'name' => __( 'Use Adoptive Filtering', 'prdctfltr' ),
					'type' => 'checkbox',
					'desc' => __( 'Check this option to use adoptive filtering on tags.', 'prdctfltr' ),
					'id'   => 'wc_settings_prdctfltr_tag_adoptive',
					'default' => 'no',
				),
				'section_tag_filter_end' => array(
					'type' => 'sectionend',
					'id' => 'wc_settings_prdctfltr_tag_filter_end'
				),
				'section_char_filter_title' => array(
					'name'     => __( 'By Characteristics Filter Settings', 'prdctfltr' ),
					'type'     => 'title',
					'desc'     => __( 'Setup by characteristics filter.', 'prdctfltr' ),
					'id'       => 'wc_settings_prdctfltr_char_filter_title'
				),
				'prdctfltr_custom_tax' => array(
					'name' => __( 'Use Characteristics', 'prdctfltr' ),
					'type' => 'checkbox',
					'desc' => __( 'Enable this option to get custom characteristics product meta box.', 'prdctfltr' ),
					'id'   => 'wc_settings_prdctfltr_custom_tax',
					'default' => 'yes',
				),
				'prdctfltr_custom_tax_title' => array(
					'name' => __( 'Override Characteristics Filter Title', 'prdctfltr' ),
					'type' => 'text',
					'desc' => __( 'Enter title for the characteristics filter. If you leave this field blank default will be used.', 'prdctfltr' ),
					'id'   => 'wc_settings_prdctfltr_custom_tax_title',
					'default' => '',
					'css' => 'width:300px;margin-right:12px;'
				),
				'prdctfltr_include_chars' => array(
					'name' => __( 'Select Characteristics', 'prdctfltr' ),
					'type' => 'multiselect',
					'desc' => __( 'Select characteristics to include. Use CTRL+Click to select multiple characteristics or deselect all.', 'prdctfltr' ),
					'id'   => 'wc_settings_prdctfltr_include_chars',
					'options' => $curr_chars,
					'default' => array(),
					'css' => 'width:300px;margin-right:12px;'
				),
				'prdctfltr_custom_tax_limit' => array(
					'name' => __( 'Limit Characteristics', 'prdctfltr' ),
					'type' => 'number',
					'desc' => __( 'Limit number of characteristics to be shown. If limit is set characteristics with most posts will be shown first.', 'prdctfltr' ),
					'id'   => 'wc_settings_prdctfltr_custom_tax_limit',
					'default' => 0,
					'custom_attributes' => array(
						'min' 	=> 0,
						'max' 	=> 100,
						'step' 	=> 1
					),
					'css' => 'width:100px;margin-right:12px;'
				),
				'prdctfltr_chars_multi' => array(
					'name' => __( 'Use Multi Select', 'prdctfltr' ),
					'type' => 'checkbox',
					'desc' => __( 'Check this option to enable multi-select on characteristics.', 'prdctfltr' ),
					'id'   => 'wc_settings_prdctfltr_chars_multi',
					'default' => 'no',
				),
				'prdctfltr_chars_adoptive' => array(
					'name' => __( 'Use Adoptive Filtering', 'prdctfltr' ),
					'type' => 'checkbox',
					'desc' => __( 'Check this option to use adoptive filtering on characteristics.', 'prdctfltr' ),
					'id'   => 'wc_settings_prdctfltr_chars_adoptive',
					'default' => 'no',
				),
				'section_char_filter_end' => array(
					'type' => 'sectionend',
					'id' => 'wc_settings_prdctfltr_char_filter_end'
				),

			);

			if ($attribute_taxonomies) {
				$settings = $settings + array (
					
				);
				foreach ($attribute_taxonomies as $tax) {

					$catalog_attrs = get_terms( 'pa_' . $tax->attribute_name );
					$curr_attrs = array();
					if ( !empty( $catalog_attrs ) && !is_wp_error( $catalog_attrs ) ){
						foreach ( $catalog_attrs as $term ) {
							$curr_attrs[$term->slug] = $term->name;
						}
					}

					$settings = $settings + array(
						'section_pa_'.$tax->attribute_name.'_title' => array(
							'name'     => __( 'By', 'prdctfltr' ) . ' ' . $tax->attribute_label . ' ' . __( 'Filter Settings', 'prdctfltr' ),
							'type'     => 'title',
							'desc'     => __( 'Select options for the current attribute.', 'prdctfltr' ),
							'id'       => 'wc_settings_prdctfltr_pa_'.$tax->attribute_name.'_title'
						),
						'prdctfltr_pa_'.$tax->attribute_name.'_title' => array(
							'name' => __( 'Override ' . $tax->attribute_label . ' Filter Title', 'prdctfltr' ),
							'type' => 'text',
							'desc' => __( 'Enter title for the characteristics filter. If you leave this field blank default will be used.', 'prdctfltr' ),
							'id'   => 'wc_settings_prdctfltr_pa_'.$tax->attribute_name.'_title',
							'default' => '',
							'css' => 'width:300px;margin-right:12px;'
						),
						'prdctfltr_include_pa_'.$tax->attribute_name => array(
							'name' => __( 'Select Terms', 'prdctfltr' ),
							'type' => 'multiselect',
							'desc' => __( 'Select terms to include. Use CTRL+Click to select multiple terms or deselect all.', 'prdctfltr' ),
							'id'   => 'wc_settings_prdctfltr_include_pa_'.$tax->attribute_name,
							'options' => $curr_attrs,
							'default' => array(),
							'css' => 'width:300px;margin-right:12px;'
						),
						'prdctfltr_pa_'.$tax->attribute_name => array(
							'name' => __( 'Appearance', 'prdctfltr' ),
							'type' => 'select',
							'desc' => __( 'Select style preset to use with the current attribute.', 'prdctfltr' ),
							'id'   => 'wc_settings_prdctfltr_pa_'.$tax->attribute_name,
							'options' => array(
								'pf_attr_text' => __( 'Text', 'prdctfltr' ),
								'pf_attr_imgtext' => __( 'Thumbnails with text', 'prdctfltr' ),
								'pf_attr_img' => __( 'Thumbnails only', 'prdctfltr' )
							),
							'default' => 'pf_attr_text',
							'css' => 'width:300px;margin-right:12px;'
						),
						'prdctfltr_pa_'.$tax->attribute_name.'_multi' => array(
							'name' => __( 'Use Multi Select', 'prdctfltr' ),
							'type' => 'checkbox',
							'desc' => __( 'Check this option to enable multi-select on current attribute.', 'prdctfltr' ),
							'id'   => 'wc_settings_prdctfltr_pa_'.$tax->attribute_name.'_multi',
							'default' => 'no',
						),
						'prdctfltr_pa_'.$tax->attribute_name.'_adoptive' => array(
							'name' => __( 'Use Adoptive Filtering', 'prdctfltr' ),
							'type' => 'checkbox',
							'desc' => __( 'Check this option to use adoptive filtering on current attribute.', 'prdctfltr' ),
							'id'   => 'wc_settings_prdctfltr_pa_'.$tax->attribute_name.'_adoptive',
							'default' => 'no',
						),
						'section_pa_'.$tax->attribute_name.'_end' => array(
							'type' => 'sectionend',
							'id' => 'wc_settings_prdctfltr_pa_'.$tax->attribute_name.'_end'
						),
						
					);
				}
			}

		}
		else if ( isset($_GET['section']) && $_GET['section'] == 'overrides' ) {
			$settings = array();
			if ( $action == 'get' ) {
				$curr_or_settings = get_option( 'prdctfltr_overrides', array() );
			?>
				<h3><?php _e( 'Product Filter Shop Archives Override', 'prdctfltr' ); ?></h3>
				<p><?php _e( 'Override archive filters. Select the term you wish to override and the desired filter preset and click Add Override to enable the new filter preset on this archive page.', 'prdctfltr' ); ?></p>
			<?php
				$curr_overrides = array(
					'product_cat' => array( 'text' => __( 'Product Categories Overrides', 'prdctfltr' ), 'values' => $curr_cats ),
					'product_tag' => array( 'text' => __( 'Product Tags Overrides', 'prdctfltr' ), 'values' => $curr_tags ),
					'characteristics' => array( 'text' => __( 'Product Characteristics Overrides', 'prdctfltr' ), 'values' => $curr_chars ),
					'attributes' => array( 'text' => __( 'Product Attributes Overrides', 'prdctfltr' ), 'values' => $curr_atts )
				);
				foreach ( $curr_overrides as $n => $m ) {
					if ( empty($m['values']) ) {
						continue;
					}
			?>
					<h3><?php echo $m['text']; ?></h3>
					<p class="<?php echo $n; ?>">
					<?php
						if ( isset($curr_or_settings[$n]) ) {
							foreach ( $curr_or_settings[$n] as $k => $v ) {
						?>
						<span class="prdctfltr_override"><input type="checkbox" class="pf_override_checkbox" /> <?php echo __('Term slug', 'prdctfltr') . ' : <span class="slug">' . $k . '</span>'; ?> <?php echo __('Filter Preset', 'prdctfltr') . ' : <span class="preset">' . $v; ?></span> <a href="#" class="button prdctfltr_or_remove"><?php _e('Remove Override', 'prdctfltr'); ?></a><span class="clearfix"></span></span>
						<?php
							}
						}
					?>
						<span class="prdctfltr_override_controls">
							<a href="#" class="button prdctfltr_or_remove_selected"><?php _e('Remove Selected Overrides', 'prdctfltr'); ?></a> <a href="#" class="button prdctfltr_or_remove_all"><?php _e('Remove All Overrides', 'prdctfltr'); ?></a>
						</span>
						<select class="prdctfltr_or_select">
					<?php
						foreach ( $m['values'] as $k => $v ) {
							printf( '<option value="%1$s">%2$s</option>', $k, $v );
						}
					?>
						</select>
						<select class="prdctfltr_filter_presets">
							<option value="default"><?php _e('Default', 'wcwar'); ?></option>
							<?php
								$curr_presets = get_option('prdctfltr_templates');
								if ( $curr_presets === false ) {
									$curr_presets = array();
								}
								if ( !empty($curr_presets) ) {
									foreach ( $curr_presets as $k => $v ) {
								?>
										<option value="<?php echo $k; ?>"><?php echo $k; ?></option>
								<?php
									}
								}
							?>
						</select>
						<a href="#" class="button-primary prdctfltr_or_add"><?php _e( 'Add Override', 'prdctfltr' ); ?></a>
					</p>
			<?php
				}
			}
		}

		return apply_filters( 'wc_settings_products_filter_settings', $settings );
	}

}

add_action( 'init', 'WC_Settings_Prdctfltr::init');

// Sort hierarchicaly
function prdctfltr_sort_terms_hierarchicaly( Array &$cats, Array &$into, $parentId = 0 ) {
	foreach ($cats as $i => $cat) {
		if ($cat->parent == $parentId) {
			$into[$cat->term_id] = $cat;
			unset($cats[$i]);
		}
	}

	foreach ($into as $topCat) {
		$topCat->children = array();
		prdctfltr_sort_terms_hierarchicaly($cats, $topCat->children, $topCat->term_id);
	}
}

// [prdctfltr_sc_products]
function prdctfltr_sc_products( $atts, $content = null ) {
	extract( shortcode_atts( array(
		'preset' => '',
		'rows' => 4,
		'columns' => 4,
		'ajax' => 'no',
		'pagination' => 'yes',
		'use_filter' => 'yes',
		'no_products' => 'no',
		'show_products' => 'yes',
		'min_price' => '',
		'max_price' => '',
		'orderby' => '',
		'order' => '',
		'meta_key'=> '',
		'product_cat'=> '',
		'product_tag'=> '',
		'product_characteristics'=> '',
		'product_attributes'=> '',
		'sale_products' => '',
		'instock_products' => '',
		'http_query' => '',
		'action' => '',
		'bot_margin' => 36,
		'class' => '',
		'shortcode_id' => ''
	), $atts ) );


	global $paged;
	$args = array();
	if ( empty( $paged ) ) $paged = ( get_query_var('paged') ? get_query_var('paged') : 1 );

	if ( $no_products == 'no' ) {
		$args = $args + array (
			'prdctfltr' => 'active'
		);
	}
	else {
		$use_filter = 'no';
		$pagination = 'no';
		$orderby = 'rand';
	}

	global $prdctfltr_global;

	$prdctfltr_global['posts_per_page'] = $columns*$rows;
	if ( $action !== '' ) {
		$prdctfltr_global['action'] = $action;
	}
	if ( $preset !== '' ) {
		$prdctfltr_global['preset'] = $preset;
	}

	$args = $args + array (
		'post_type'				=> 'product',
		'post_status'			=> 'publish',
		'posts_per_page' 		=> $prdctfltr_global['posts_per_page'],
		'paged' 				=> $paged,
		'meta_query' 			=> array(
			array(
				'key' 			=> '_visibility',
				'value' 		=> array('catalog', 'visible'),
				'compare' 		=> 'IN'
			)
		)
	);

	if ( $orderby !== '' ) {
		$args['orderby'] = $orderby;
	}
	if ( $order !== '' ) {
		$args['order'] = $order;
	}
	if ( $order !== '' ) {
		$args['meta_key'] = $meta_key;
	}
	if ( $min_price !== '' ) {
		$args['min_price'] = $min_price;
	}
	if ( $max_price !== '' ) {
		$args['max_price'] = $max_price;
	}
	if ( $product_cat !== '' ) {
		$args['product_cat'] = $product_cat;
	}
	if ( $product_tag !== '' ) {
		$args['product_tag'] = $product_tag;
	}
	if ( $product_characteristics !== '' ) {
		$args['product_characteristics'] = $product_characteristics;
	}
	if ( $product_attributes !== '' ) {
		$args['product_attributes'] = $product_attributes;
	}
	if ( $instock_products !== '' ) {
		$args['instock_products'] = $instock_products;
	}
	if ( $sale_products !== '' ) {
		$args['sale_products'] = $sale_products;
	}
	if ( $http_query !== '' ) {
		$args['http_query'] = $http_query;
	}

	if ( $ajax == 'yes' ) {

		$ajax_params =  array(
			( $preset !== '' ? $preset : 'false' ),
			( $columns !== '' ? $columns : 'false' ),
			( $rows !== '' ? $rows : 'false' ),
			( $pagination !== '' ? $pagination : 'false' ),
			( $no_products !== '' ? $no_products : 'false' ),
			( $show_products !== '' ? $show_products : 'false' ),
			( $use_filter !== '' ? $use_filter : 'false' ),
			( $action !== '' ? $action : 'false' ),
			( $bot_margin !== '' ? $bot_margin : 'false' ),
			( $class !== '' ? $class : 'false' ),
			( $shortcode_id !== '' ? $shortcode_id : 'false' )
		);
		$pf_params = implode( '|', $ajax_params );

		$add_ajax = ' data-query="' . http_build_query( $args ) . '" data-page="' . $paged . '" data-shortcode="' . $pf_params . '"';

	}

	$prdctfltr_global['sc_query'] = $args;

	$bot_margin = (int)$bot_margin;
	$margin = " style='margin-bottom:".$bot_margin."px'";

	$out = '';

	global $woocommerce, $woocommerce_loop;
	
	$woocommerce_loop['columns'] = $columns;

	$products = new WP_Query( $args );

	ob_start();

	if ( $products->have_posts() ) : ?>

		<?php
			if ( $use_filter == 'yes' ) {
				include_once( plugin_dir_path( __FILE__ ) . 'woocommerce/loop/orderby.php' );
			}
		?>
		
		<?php if ( $show_products == 'yes' ) { ?>

		<?php woocommerce_product_loop_start(); ?>

			<?php while ( $products->have_posts() ) : $products->the_post(); ?>

				<?php wc_get_template_part( 'content', 'product' ); ?>

			<?php endwhile; ?>

		<?php woocommerce_product_loop_end(); ?>

		<?php
			}
			else {
				$pagination = 'no';
			}
		?>

	<?php
	
	else :
		wc_get_template( 'loop/no-products-found.php' );
	endif;

	$shortcode = ob_get_clean();

	$out .= '<div' . ( $shortcode_id != '' ? ' id="'.$shortcode_id.'"' : '' ) . ' class="prdctfltr_sc_products woocommerce'.($ajax=='yes'? ' prdctfltr_ajax' : '' ).'' . ( $class != '' ? ' '.$class.'' : '' ) . '"'.$margin.($ajax=='yes' ? $add_ajax : '' ).'>';
	$out .= do_shortcode($shortcode);

	if ( $pagination == 'yes' ) {

		ob_start();
		?>
		<nav class="woocommerce-pagination">
			<?php
				echo paginate_links( apply_filters( 'woocommerce_pagination_args', array(
					'base'         => @add_query_arg('paged','%#%'),
					'format'       => '',
					'current'      => $paged,
					'total'        => $products->max_num_pages,
					'prev_text'    => '&larr;',
					'next_text'    => '&rarr;',
					'type'         => 'list',
					'end_size'     => 3,
					'mid_size'     => 3
				) ) );
			?>
		</nav>
		<?php
		$pagination = ob_get_clean();

		$out .= $pagination;
	}

	$out .= '</div>';

	wp_reset_postdata();
	wp_reset_query();

	return $out;

}
add_shortcode( 'prdctfltr_sc_products', 'prdctfltr_sc_products' );

// [prdctfltr_sc_get_filter]
function prdctfltr_sc_get_filter( $atts, $content = null ) {
	return prdctfltr_get_filter();
}
add_shortcode( 'prdctfltr_sc_get_filter', 'prdctfltr_sc_get_filter' );




class prdctfltr extends WP_Widget {

	function prdctfltr() {
		$widget_ops = array(
			'classname' => 'prdctfltr-widget',
			'description' => __( 'Product Filter widget version.', 'wdgtcstmzr' )
		);
		$this->WP_Widget( 'prdctfltr', '+ Product Filter', $widget_ops );
	}

	function widget( $args, $instance ) {
		extract( $args, EXTR_SKIP );
		$curr_pf = array (
			'preset' => $instance['preset'],
			'template' => $instance['template']
		);

global $prdctfltr_global;

if ( ( isset($prdctfltr_global['active']) && $prdctfltr_global['active'] == 'true') !== true ) {

global $wp;

echo $before_widget;

$prdctfltr_global['active'] = 'true';

$curr_overrides = get_option( 'prdctfltr_overrides', array() );

if ( is_product_category()) {
	$_GET['product_cat'] = get_query_var('product_cat');
	if ( isset($curr_overrides) && is_array($curr_overrides) && isset($curr_overrides['product_cat']) ) {
		if ( array_key_exists($_GET['product_cat'], $curr_overrides['product_cat']) ) {
			$get_options = $curr_overrides['product_cat'][$_GET['product_cat']];
		}
	}
}
else if ( is_product_tag() ) {
	$_GET['product_tag'] = get_query_var('product_tag');
	if ( isset($curr_overrides) && is_array($curr_overrides) && isset($curr_overrides['product_cat']) ) {
		if ( array_key_exists($_GET['product_tag'], $curr_overrides['product_tag']) ) {
			$get_options = $curr_overrides['product_tag'][$_GET['product_tag']];
		}
	}
}
else if ( is_product_taxonomy() ) {
	$_GET[get_query_var('taxonomy')] = get_query_var('term');
}

if ( !isset($get_options) ) {
	if ( isset($curr_pf['template']) ) {
		$get_options = $curr_pf['template'];
	}
}

if ( isset($prdctfltr_global['active_filters']) ) {
	$_GET = array();
	foreach( $prdctfltr_global['active_filters'] as $k => $v ) {
		$_GET[$k] = $v;
	}
	
}

if ( isset($get_options) ) {
	$curr_or_presets = get_option( 'prdctfltr_templates', array() );
	if ( isset($curr_or_presets) && is_array($curr_or_presets) ) {
		if ( array_key_exists($get_options, $curr_or_presets) ) {
			$get_curr_options = json_decode(stripslashes($curr_or_presets[$get_options]), true);
		}
	}
}

$pf_chck_settings = array(
	'wc_settings_prdctfltr_style_preset' => 'pf_default',
	'wc_settings_prdctfltr_always_visible' => 'no',
	'wc_settings_prdctfltr_click_filter' => 'no',
	'wc_settings_prdctfltr_limit_max_height' => 'no',
	'wc_settings_prdctfltr_max_height' => 150,
	'wc_settings_prdctfltr_custom_scrollbar' => 'no',
	'wc_settings_prdctfltr_disable_bar' => 'no',
	'wc_settings_prdctfltr_icon' => '',
	'wc_settings_prdctfltr_max_columns' => 6,
	'wc_settings_prdctfltr_adoptive' => 'no',
	'wc_settings_prdctfltr_cat_adoptive' => 'no',
	'wc_settings_prdctfltr_tag_adoptive' => 'no',
	'wc_settings_prdctfltr_char_adoptive' => 'no',
	'wc_settings_prdctfltr_price_adoptive' => 'no',
	'wc_settings_prdctfltr_orderby_title' => '',
	'wc_settings_prdctfltr_price_title' => '',
	'wc_settings_prdctfltr_price_range' => 100,
	'wc_settings_prdctfltr_price_range_add' => 100,
	'wc_settings_prdctfltr_price_range_limit' => 6,
	'wc_settings_prdctfltr_cat_title' => '',
	'wc_settings_prdctfltr_cat_limit' => 0,
	'wc_settings_prdctfltr_cat_hierarchy' => 'no',
	'wc_settings_prdctfltr_cat_multi' => 'no',
	'wc_settings_prdctfltr_include_cats' => array(),
	'wc_settings_prdctfltr_tag_title' => '',
	'wc_settings_prdctfltr_tag_limit' => 0,
	'wc_settings_prdctfltr_tag_multi' => 'no',
	'wc_settings_prdctfltr_include_tags' => array(),
	'wc_settings_prdctfltr_custom_tax_title' => '',
	'wc_settings_prdctfltr_custom_tax_limit' => 0,
	'wc_settings_prdctfltr_chars_multi' => 'no',
	'wc_settings_prdctfltr_include_chars' => array(),
	'wc_settings_prdctfltr_disable_sale' => 'no',
	'wc_settings_prdctfltr_noproducts' => '',
	'wc_settings_prdctfltr_advanced_filters' => array(),
	'wc_settings_prdctfltr_range_filters' => array(),
	'wc_settings_prdctfltr_disable_instock' => 'no',
	'wc_settings_prdctfltr_title' => '',
	'wc_settings_prdctfltr_style_mode' => 'pf_mod_multirow',
	'wc_settings_prdctfltr_instock_title' => '',
	'wc_settings_prdctfltr_disable_reset' => 'no',
	'wc_settings_prdctfltr_include_orderby' => array('menu_order','popularity','rating','date','price','price-desc'),
	'wc_settings_prdctfltr_adoptive_style' => 'pf_adptv_default',
	'wc_settings_prdctfltr_show_counts' => 'no'
);

if ( isset($get_curr_options) ) {
	$curr_options = $get_curr_options;

	foreach ( $pf_chck_settings as $z => $x) {
		if ( !isset($curr_options[$z]) ) {
			$curr_options[$z] = $x;
		}
	}
}
else {
	$wc_settings_prdctfltr_active_filters = get_option( 'wc_settings_prdctfltr_active_filters' );

	if ( $wc_settings_prdctfltr_active_filters === false ) {
		$wc_settings_prdctfltr_selected = get_option( 'wc_settings_prdctfltr_selected', array('sort','price','cat') );
		$wc_settings_prdctfltr_attributes = get_option( 'wc_settings_prdctfltr_attributes', array() );
		$wc_settings_prdctfltr_active_filters = array();
		$wc_settings_prdctfltr_active_filters = array_merge( $wc_settings_prdctfltr_selected,  $wc_settings_prdctfltr_attributes );
	}
	else if ( is_array($wc_settings_prdctfltr_active_filters) ) {
		$wc_settings_prdctfltr_selected = array();
		$wc_settings_prdctfltr_attributes = array();
		foreach ( $wc_settings_prdctfltr_active_filters as $k ) {
			if (substr($k, 0, 3) == 'pa_') {
				$wc_settings_prdctfltr_attributes[] = $k;
			}
		}
	}

	$curr_attrs = $wc_settings_prdctfltr_attributes;

	$curr_options = array(
		'wc_settings_prdctfltr_selected' => $wc_settings_prdctfltr_selected,
		'wc_settings_prdctfltr_attributes' => $wc_settings_prdctfltr_attributes,
		'wc_settings_prdctfltr_active_filters' => $wc_settings_prdctfltr_active_filters
	);
	
	foreach ( $pf_chck_settings as $z => $x) {
		$curr_z = get_option( $z );
		if ( $curr_z === false ) {
			$curr_options[$z] = $x;
		}
		else {
			$curr_options[$z] = $curr_z;
		}
	}

	foreach ( $curr_attrs as $k => $attr ) {
		$curr_options['wc_settings_prdctfltr_'.$attr.'_adoptive'] = get_option( 'wc_settings_prdctfltr_'.$attr.'_adoptive', 'no' );
		$curr_options['wc_settings_prdctfltr_'.$attr.'_title'] = get_option( 'wc_settings_prdctfltr_'.$attr.'_title', '' );
		$curr_options['wc_settings_prdctfltr_' . $attr] = get_option( 'wc_settings_prdctfltr_' . $attr, 'pf_attr_text' );
		$curr_options['wc_settings_prdctfltr_' . $attr . '_multi'] = get_option( 'wc_settings_prdctfltr_' . $attr . '_multi', 'no' );
		$curr_options['wc_settings_prdctfltr_include_' . $attr] = get_option( 'wc_settings_prdctfltr_include_' . $attr, array() );
	}

}

$curr_elements = ( $curr_options['wc_settings_prdctfltr_active_filters'] !== NULL ? $curr_options['wc_settings_prdctfltr_active_filters'] : array() );

if ( in_array( $curr_options['wc_settings_prdctfltr_style_preset'], array('pf_arrow','pf_arrow_inline') ) !== false ) {
	$curr_options['wc_settings_prdctfltr_always_visible'] = 'no';
	$curr_options['wc_settings_prdctfltr_disable_bar'] = 'no';
}

$curr_styles = array(
	( $curr_pf['preset'] !== 'pf_disable' ? ' ' . $curr_pf['preset'] : '' ),
	( $curr_options['wc_settings_prdctfltr_always_visible'] == 'no' && $curr_options['wc_settings_prdctfltr_disable_bar'] == 'no' ? 'prdctfltr_slide' : 'prdctfltr_always_visible' ),
	( $curr_options['wc_settings_prdctfltr_click_filter'] == 'no' ? 'prdctfltr_click' : 'prdctfltr_click_filter' ),
	( $curr_options['wc_settings_prdctfltr_limit_max_height'] == 'no' ? 'prdctfltr_rows' : 'prdctfltr_maxheight' ),
	( $curr_options['wc_settings_prdctfltr_custom_scrollbar'] == 'no' ? '' : 'prdctfltr_scroll_active' ),
	( $curr_options['wc_settings_prdctfltr_disable_bar'] == 'no' ? '' : 'prdctfltr_disable_bar' ),
	'pf_mod_multirow',
	( $curr_options['wc_settings_prdctfltr_adoptive'] == 'no' ? '' : $curr_options['wc_settings_prdctfltr_adoptive_style'] ),
);

$curr_maxheight = ( $curr_options['wc_settings_prdctfltr_limit_max_height'] == 'yes' ? ' style="max-height:' . $curr_options['wc_settings_prdctfltr_max_height'] . 'px;"' : '' );

$pf_order_default = array(
	''              => __( 'None', 'prdctfltr' ),
	'menu_order'    => __( 'Default', 'prdctfltr' ),
	'comment_count' => __( 'Review Count', 'prdctfltr' ),
	'popularity'    => __( 'Popularity', 'prdctfltr' ),
	'rating'        => __( 'Average rating', 'prdctfltr' ),
	'date'          => __( 'Newness', 'prdctfltr' ),
	'price'         => __( 'Price: low to high', 'prdctfltr' ),
	'price-desc'    => __( 'Price: high to low', 'prdctfltr' ),
	'rand'          => __( 'Random Products', 'prdctfltr' ),
	'title'         => __( 'Product Name', 'prdctfltr' )
);

if ( !empty( $curr_options['wc_settings_prdctfltr_include_orderby'] ) ) {
	foreach ( $pf_order_default as $u => $i ) {
		if ( !in_array( $u, $curr_options['wc_settings_prdctfltr_include_orderby'] ) ) {
			unset( $pf_order_default[$u] );
		}
	}
	$pf_order_default = array_merge( array( '' => __( 'None', 'prdctfltr' ) ), $pf_order_default );
}

$catalog_orderby = apply_filters( 'prdctfltr_catalog_orderby', $pf_order_default );

$catalog_instock = apply_filters( 'prdctfltr_catalog_instock', array(
	''    => __( 'None', 'prdctfltr' ),
	'in'  => __( 'In Stock', 'prdctfltr' ),
	'out' => __( 'Out Of Stock', 'prdctfltr' )
) );

?>
<div id="prdctfltr_woocommerce" class="prdctfltr_woocommerce woocommerce<?php echo implode( $curr_styles, ' ' ); ?>" data-preset="<?php echo $curr_pf['preset']; ?>" data-template="<?php echo $curr_pf['template']; ?>">
<?php

	if ( is_shop() || is_woocommerce() ) {
		global $wp_the_query;

		$paged    = max( 1, $wp_the_query->get( 'paged' ) );
		$per_page = $wp_the_query->get( 'posts_per_page' );
		$total    = $wp_the_query->found_posts;
		$first    = ( $per_page * $paged ) - $per_page + 1;
		$last     = min( $total, $wp_the_query->get( 'posts_per_page' ) * $paged );

	}
	else {
		if ( isset( $prdctfltr_global['sc_query'] ) ) {
			$r_args = $prdctfltr_global['sc_query'];
		}
		else {
			$r_args = array();

			$r_args = $r_args + array(
				'prdctfltr'				=> 'active',
				'post_type'				=> 'product',
				'post_status' 			=> 'publish',
				'posts_per_page' 		=> $prdctfltr_global['posts_per_page'],
				'meta_query' 			=> array(
					array(
						'key' 			=> '_visibility',
						'value' 		=> array('catalog', 'visible'),
						'compare' 		=> 'IN'
					)
				)
			);
		}

		$res_products = new WP_Query( $r_args );

		$paged    = ( isset($prdctfltr_global['ajax_paged']) ? $prdctfltr_global['ajax_paged'] : max( 1, $res_products->get( 'paged' ) ) );
		$per_page = ( isset($prdctfltr_global['posts_per_page']) ? $prdctfltr_global['posts_per_page'] : $res_products->get( 'posts_per_page' ) );
		$total    = $res_products->found_posts;
		$first    = ( $per_page * $paged ) - $per_page + 1;
		$last     = min( $total, $res_products->get( 'posts_per_page' ) * $paged );
	}

	$pf_query = ( isset($res_products) ? $res_products : $wp_the_query );

	if ( isset( $_GET ) ) {

		$supress = array( 'post_type', 'widget_search' );
		$allowed = array( 'orderby', 'min_price', 'max_price', 'instock_products' );

		$rng_terms = array();
		$pf_activated = array();

		foreach( $_GET as $k => $v ){
			if ( !in_array( $k, $supress+$allowed ) ) {
				if ( substr($k, 0, 4) == 'rng_' && $v !== '' ) {
					if ( substr($k, 0, 8) == 'rng_min_' ) {
						$rng_terms[str_replace('rng_min_', '', $k)]['min'] = $v;
					}
					else {
						$rng_terms[str_replace('rng_max_', '', $k)]['max'] = $v;
					}
				}
			}
			if ( !in_array( $k, $supress ) ) {
				if ( in_array( $k, $allowed ) ) {
					$pf_activated = $pf_activated + array( $k => $v );
				}
				else if ( taxonomy_exists( $k ) ) {
					$pf_activated = $pf_activated + array( $k => $v );
				}
			}
		}

	}

	$pf_activated = $pf_activated + $rng_terms;

	$curr_mix_count = ( count($curr_elements) );
	$curr_columns = 1;

	$curr_columns_class = ' prdctfltr_columns_' . $curr_columns;

	if ( $curr_options['wc_settings_prdctfltr_adoptive'] == 'yes' || ( defined('DOING_AJAX') && DOING_AJAX ) ) {

		if ( $pf_query->have_posts() ) {

			$output_terms = array();
			$pf_query->set('posts_per_page', $total);

			$t_pos = strpos($pf_query->request, 'LIMIT');
			if ( $t_pos !== false ) {
				$t_str = substr($pf_query->request, 0, $t_pos);
			}
			else {
				$t_str = $pf_query->request;
			}

			$t_str .= ' LIMIT 0,10000000';

			global $wpdb;
			$pf_products = $wpdb->get_results( $t_str );

			$curr_in = array();
			foreach ( $pf_products as $p ) {
				$curr_in[] = $p->ID ;
			}

			$curr_ins = implode(',',$curr_in);
			$curr_tax = implode(',',$curr_elements);

			$pf_product_terms = $wpdb->get_results( $wpdb->prepare( '
				SELECT slug, taxonomy FROM %1$s
				INNER JOIN %2$s ON (%1$s.ID = %2$s.object_id)
				INNER JOIN %3$s ON (%2$s.term_taxonomy_id = %3$s.term_taxonomy_id )
				INNER JOIN %4$s ON (%3$s.term_id = %4$s.term_id )
				WHERE %1$s.ID IN (' . $curr_ins . ')
				ORDER BY %4$s.name ASC
			', $wpdb->posts, $wpdb->term_relationships, $wpdb->term_taxonomy, $wpdb->terms ) );

			foreach ( $pf_product_terms as $p ) {
				if ( !isset($output_terms[$p->taxonomy]) ) {
					$output_terms[$p->taxonomy] = array();
				}
				if ( !array_key_exists( $p->slug, $output_terms[$p->taxonomy] ) ) {
					$output_terms[$p->taxonomy][$p->slug] = 1;
				}
				else if ( array_key_exists( $p->slug, $output_terms[$p->taxonomy] ) ) {
					$output_terms[$p->taxonomy][$p->slug] = $output_terms[$p->taxonomy][$p->slug] + 1;
				}
			}

		}

	}

	$curr_cat_query = get_option( 'wc_settings_prdctfltr_categories_query', 'no' );
	if ( is_product_taxonomy() || is_product() ) {
		if ( $curr_cat_query == 'no' ) {
			$curr_action = get_permalink( wc_get_page_id( 'shop' ) );
		}
		else {
			if ( get_option( 'permalink_structure' ) == '' ) {
				$curr_action = remove_query_arg( array( 'page', 'paged' ), add_query_arg( $wp->query_string, '', home_url( $wp->request ) ) );
			} else {
				$curr_action = preg_replace( '%\/page/[0-9]+%', '', home_url( $wp->request ) );
			}
		}
	}
	else if ( !isset($prdctfltr_global['action']) ) {
		if ( get_option( 'permalink_structure' ) == '' ) {
			$curr_action = remove_query_arg( array( 'page', 'paged' ), add_query_arg( $wp->query_string, '', home_url( $wp->request ) ) );
		} else {
			$curr_action = preg_replace( '%\/page/[0-9]+%', '', home_url( $wp->request ) );
		}
	}
	else {
		$curr_action = $prdctfltr_global['action'];
	}

?>
<form action="<?php echo $curr_action; ?>" class="prdctfltr_woocommerce_ordering" method="get">
	<div class="prdctfltr_filter_wrapper<?php echo $curr_columns_class; ?>" data-columns="<?php echo $curr_columns; ?>">
		<div class="prdctfltr_filter_inner">
		<?php

			$q = 0;
			$n = 0;
			$p = 0;
			$active_filters = array();
			foreach ( $curr_elements as $k => $v ) :

				if ( $q == $curr_columns && ( $curr_options['wc_settings_prdctfltr_style_mode'] == 'pf_mod_multirow' || $curr_options['wc_settings_prdctfltr_style_preset'] == 'pf_select' ) ) {
			?>
				<div class="prdctfltr_clear"></div>
			<?php
				}

				switch ( $v ) :

				case 'instock' :
					if ( !in_array('instock', $active_filters) ) {
						$active_filters[] = 'instock';
					}
				?>

					<div class="prdctfltr_filter prdctfltr_instock">
						<input name="instock_products" type="hidden"<?php echo ( isset($_GET['instock_products'] ) ? ' value="'.$_GET['instock_products'].'"' : '' );?>>

						<?php echo $before_title; ?>

						<span class="prdctfltr_widget_title">
							<?php
								if ( isset($_GET['instock_products'] ) ) {
									echo '<a href="#" data-key="instock_products"><i class="prdctfltr-delete"></i></a> <span>'.$catalog_instock[$_GET['instock_products']] . '</span> / ';
								}

								if ( $curr_options['wc_settings_prdctfltr_instock_title'] != '' ) {
									echo $curr_options['wc_settings_prdctfltr_instock_title'];
								}
								else {
									_e('Product Availability', 'prdctfltr');
								}
							?>
							<i class="prdctfltr-down"></i>
						</span>

						<?php echo $after_title; ?>
						<div class="prdctfltr_checkboxes"<?php echo $curr_maxheight; ?>>
						<?php

							foreach ( $catalog_instock as $id => $name ) {
								printf('<label%4$s><input type="checkbox" value="%1$s" %2$s /><span>%3$s</span></label>', esc_attr( $id ), ( isset($_GET['instock_products']) && $_GET['instock_products'] == $id ? 'checked' : '' ), esc_attr( $name ), ( isset($_GET['instock_products']) && $_GET['instock_products'] == $id ? ' class="prdctfltr_active"' : '' ) );
							}
						?>
						</div>
					</div>

				<?php break;

				case 'sort' :
					if ( !in_array('orderby', $active_filters) ) {
						$active_filters[] = 'orderby';
					}
				?>

					<div class="prdctfltr_filter prdctfltr_orderby">
						<input name="orderby" type="hidden"<?php echo ( isset($_GET['orderby'] ) ? ' value="'.$_GET['orderby'].'"' : '' );?>>

						<?php echo $before_title; ?>

						<span class="prdctfltr_widget_title">
							<?php
								if ( isset($_GET['orderby'] ) && isset($catalog_orderby[$_GET['orderby']]) ) {
									echo '<a href="#" data-key="orderby"><i class="prdctfltr-delete"></i></a> <span>'.$catalog_orderby[$_GET['orderby']] . '</span> / ';
								}

								if ( $curr_options['wc_settings_prdctfltr_orderby_title'] != '' ) {
									echo $curr_options['wc_settings_prdctfltr_orderby_title'];
								}
								else {
									_e('Sort by', 'prdctfltr');
								}
							?>
							<i class="prdctfltr-down"></i>
						</span>

						<?php echo $after_title; ?>

						<div class="prdctfltr_checkboxes"<?php echo $curr_maxheight; ?>>
						<?php
							if ( get_option( 'woocommerce_enable_review_rating' ) === 'no' )
								unset( $catalog_orderby['rating'] );

							foreach ( $catalog_orderby as $id => $name ) {
								printf('<label%4$s><input type="checkbox" value="%1$s" %2$s /><span>%3$s</span></label>', esc_attr( $id ), ( isset($_GET['orderby']) && $_GET['orderby'] == $id ? 'checked' : '' ), esc_attr( $name ), ( isset($_GET['orderby']) && $_GET['orderby'] == $id ? ' class="prdctfltr_active"' : '' ) );
							}
						?>
						</div>
					</div>

				<?php break;

				case 'price' :
					if ( !in_array('price', $active_filters) ) {
						$active_filters[] = 'price';
					}
				?>

					<div class="prdctfltr_filter prdctfltr_byprice">
					<input name="min_price" type="hidden"<?php echo ( isset($_GET['min_price'] ) ? ' value="'.$_GET['min_price'].'"' : '' );?>>
					<input name="max_price" type="hidden"<?php echo ( isset($_GET['max_price'] ) ? ' value="'.$_GET['max_price'].'"' : '' );?>>

						<?php echo $before_title; ?>
						<span class="prdctfltr_widget_title">
							<?php
								if ( isset($_GET['min_price']) && $_GET['min_price'] !== '' ) {
									$min_price = wc_price($_GET['min_price']);
									if ( isset($_GET['max_price']) && $_GET['max_price'] !== '' ) {
										$curr_max_price = $_GET['max_price'];
										$max_price = wc_price($_GET['max_price']);
									}
									else {
										$max_price = ' +';
									}
									echo '<a href="#" data-key="byprice"><i class="prdctfltr-delete"></i></a> <span>' . $min_price . ' - ' . $max_price . '</span> / ';
								}

								if ( $curr_options['wc_settings_prdctfltr_price_title'] != '' ) {
									echo $curr_options['wc_settings_prdctfltr_price_title'];
								}
								else {
									_e('Price range', 'prdctfltr');
								}
							?>
							<i class="prdctfltr-down"></i>
						</span>
						<?php echo $after_title; ?>

					<?php
						$curr_price = ( isset($_GET['min_price']) ? $_GET['min_price'].'-'.( isset($_GET['max_price']) ? $_GET['max_price'] : '' ) : '' );
						
						$curr_price_set = $curr_options['wc_settings_prdctfltr_price_range'];
						$curr_price_add = $curr_options['wc_settings_prdctfltr_price_range_add'];
						$curr_price_limit = $curr_options['wc_settings_prdctfltr_price_range_limit'];

						$curr_prices = array();
						$curr_prices_currency = array();
						global $wpdb;
						$min = floor( $wpdb->get_var(
							$wpdb->prepare('
								SELECT min(meta_value + 0)
								FROM %1$s
								LEFT JOIN %2$s ON %1$s.ID = %2$s.post_id
								WHERE ( meta_key = \'%3$s\' OR meta_key = \'%4$s\' )
								AND meta_value != ""
								', $wpdb->posts, $wpdb->postmeta, '_price', '_min_variation_price' )
							)
						);

						$catalog_ready_price = array(
							'-' => __( 'None', 'prdctfltr' )
						);

						for ($i = 0; $i < $curr_price_limit; $i++) {

							if ( $i == 0 ) {
								$min_price = $min;
								$max_price = $curr_price_set;
							}
							else {
								$min_price = $curr_price_set+($i-1)*$curr_price_add;
								$max_price = $curr_price_set+$i*$curr_price_add;
							}

							$curr_prices[$i] = $min_price . '-' . ( ($i+1) == $curr_price_limit ? '' : $max_price );

							$curr_prices_currency[$i] = wc_price( $min_price ) . ( $i+1 == $curr_price_limit ? '+' : ' - ' . wc_price( $max_price ) );

							$catalog_ready_price = $catalog_ready_price + array(
								$curr_prices[$i] => $curr_prices_currency[$i]
							);

						}

						$catalog_price = apply_filters( 'prdctfltr_catalog_price', $catalog_ready_price );

						$catalog_price = array(
							'-' => __( 'None', 'prdctfltr' )
						) + $catalog_price;
					?>
					<div class="prdctfltr_checkboxes"<?php echo $curr_maxheight; ?>>
						<?php
							foreach ( $catalog_price as $id => $name ) {
								printf('<label%4$s><input type="checkbox" value="%1$s" %2$s /><span>%3$s</span></label>',
									esc_attr( $id ),
									( $curr_price == $id ? 'checked' : '' ),
									$name,
									( $curr_price == $id ? ' class="prdctfltr_active"' : '' )
								);
							}
						?>
						</div>
					</div>

				<?php break;

				case 'cat' :

					if ( $curr_options['wc_settings_prdctfltr_cat_adoptive'] == 'yes' && isset($output_terms) && ( !isset($output_terms['product_cat']) || empty($output_terms['product_cat']) ) === true && $total !== 0 ) {
						continue;
					}

					if ( !in_array('product_cat', $active_filters) ) {
						$active_filters[] = 'product_cat';
					}

					$curr_limit = intval( $curr_options['wc_settings_prdctfltr_cat_limit'] );
					if ( $curr_limit !== 0 ) {
						$catalog_categories = get_terms( 'product_cat', array('hide_empty' => 1, 'orderby' => 'count', 'order' => 'DESC', 'number' => $curr_limit ) );
					}
					else {
						$catalog_categories = get_terms( 'product_cat', array('hide_empty' => 1 ) );

						if ( $curr_options['wc_settings_prdctfltr_cat_hierarchy'] == 'yes' ) {
							$catalog_categories_sorted = array();
							prdctfltr_sort_terms_hierarchicaly($catalog_categories, $catalog_categories_sorted);
							$catalog_categories = $catalog_categories_sorted;
						}
					}

					if ( !empty( $catalog_categories ) && !is_wp_error( $catalog_categories ) ){
					$curr_term_multi = ( $curr_options['wc_settings_prdctfltr_cat_multi'] == 'yes' ? ' prdctfltr_multi' : ' prdctfltr_single' );
					$curr_term_adoptive = ( $curr_options['wc_settings_prdctfltr_cat_adoptive'] == 'yes' ? ' prdctfltr_adoptive' : '' );
				?>
					<div class="prdctfltr_filter prdctfltr_cat <?php echo $curr_term_multi; ?> <?php echo $curr_term_adoptive; ?>">
						<input name="product_cat" type="hidden"<?php echo ( isset($_GET['product_cat'] ) ? ' value="'.$_GET['product_cat'].'"' : '' );?>>

						<?php echo $before_title; ?>
						<span class="prdctfltr_widget_title">
							<?php
								if ( isset($_GET['product_cat']) ) {
									$curr_selected = ( !is_shop() && is_product_category() ? array($_GET['product_cat']) : explode(',', $pf_query->query_vars['product_cat']) );
									echo '<a href="#" data-key="product_cat"><i class="prdctfltr-delete"></i></a> <span>';
									$i=0;
									foreach( $curr_selected as $selected ) {
										$curr_term = get_term_by('slug', $selected, 'product_cat');
										echo ( $i !== 0 ? ', ' : '' ) . $curr_term->name;
										$i++;
									}
									echo '</span> / ';
								}

								if ( $curr_options['wc_settings_prdctfltr_cat_title'] != '' ) {
									echo $curr_options['wc_settings_prdctfltr_cat_title'];
								}
								else {
									_e('Categories', 'prdctfltr');
								}
							?>
							<i class="prdctfltr-down"></i>
						</span>
						<?php echo $after_title; ?>

						<div class="prdctfltr_checkboxes"<?php echo $curr_maxheight; ?>>
						<?php
							$curr_include = $curr_options['wc_settings_prdctfltr_include_cats'];
							printf('<label><input type="checkbox" value="" /><span>%1$s</span></label>', __('None' , 'prdctfltr') );

							foreach ( $catalog_categories as $term ) {

								if ( isset($term->children) ) {
									$pf_children = $term->children;
								}
								else {
									$pf_children = array();
								}

								if ( !empty($curr_include) && !in_array($term->slug, $curr_include) ) {
									continue;
								}

								$pf_adoptive_class = '';
								if ( $curr_options['wc_settings_prdctfltr_cat_adoptive'] == 'yes' && isset($output_terms['product_cat']) && !empty($output_terms['product_cat']) && !array_key_exists($term->slug, $output_terms['product_cat']) ) {
									$pf_adoptive_class = ' pf_adoptive_hide';
								}

								printf('<label class="%6$s%4$s"><input type="checkbox" value="%1$s" %3$s /><span>%2$s%7$s</span>%5$s</label>', $term->slug, $term->name, ( isset($_GET['product_cat']) && $_GET['product_cat'] == $term->slug ? 'checked' : '' ), ( isset($_GET['product_cat']) && in_array( $term->slug, ( !is_shop() && is_product_category() ? array($_GET['product_cat']) : explode(',', $pf_query->query_vars['product_cat']) ) ) ? ' prdctfltr_active"' : '' ), ( !empty($pf_children) ? '<i class="prdctfltr-plus"></i>' : '' ), $pf_adoptive_class, ( $curr_options['wc_settings_prdctfltr_show_counts'] == 'no' ? '' : ' <span class="prdctfltr_count">' . ( isset($output_terms['product_cat']) && isset($output_terms['product_cat'][$term->slug]) && $output_terms['product_cat'][$term->slug] != $term->count ? $output_terms['product_cat'][$term->slug] . '/' . $term->count : $term->count ) . '</span>' ) );

								if ( $curr_options['wc_settings_prdctfltr_cat_hierarchy'] == 'yes' && !empty($pf_children) ) {

									printf( '<div class="prdctfltr_sub" data-sub="%1$s">', $term->slug );

									foreach( $pf_children as $sub ) {

										$pf_adoptive_class = '';
										if ( $curr_options['wc_settings_prdctfltr_cat_adoptive'] == 'yes' && isset($output_terms['product_cat']) && !empty($output_terms['product_cat']) && !array_key_exists($sub->slug, $output_terms['product_cat']) ) {
											$pf_adoptive_class = ' pf_adoptive_hide';
										}

										printf('<label class="%6$s%4$s"><input type="checkbox" value="%1$s" %3$s /><span>%2$s%7$s</span>%5$s</label>', $sub->slug, $sub->name, ( isset($_GET['product_cat']) && $_GET['product_cat'] == $sub->slug ? 'checked' : '' ), ( isset($_GET['product_cat']) && in_array( $sub->slug, ( !is_shop() && is_product_category() ? array($_GET['product_cat']) : explode(',', $pf_query->query_vars['product_cat']) ) ) ? ' prdctfltr_active' : '' ), ( !empty($sub->children) ? '<i class="prdctfltr-plus"></i>' : '' ), $pf_adoptive_class, ( $curr_options['wc_settings_prdctfltr_show_counts'] == 'no' ? '' : ' <span class="prdctfltr_count">' . ( isset($output_terms['product_cat']) && isset($output_terms['product_cat'][$sub->slug]) && $output_terms['product_cat'][$sub->slug] != $sub->count ? $output_terms['product_cat'][$sub->slug] . '/' . $sub->count : $sub->count ) . '</span>' ) );

										if ( !empty($sub->children) ) {

											printf( '<div class="prdctfltr_sub" data-sub="%1$s">', $sub->slug );

											foreach( $sub->children as $subsub ) {

												$pf_adoptive_class = '';
												if ( $curr_options['wc_settings_prdctfltr_cat_adoptive'] == 'yes' && isset($output_terms['product_cat']) && !empty($output_terms['product_cat']) && !array_key_exists($subsub->slug, $output_terms['product_cat']) ) {
													$pf_adoptive_class = ' pf_adoptive_hide';
												}

												printf('<label class="%6$s%4$s"><input type="checkbox" value="%1$s" %3$s /><span>%2$s%7$s</span>%5$s</label>', $subsub->slug, $subsub->name, ( isset($_GET['product_cat']) && $_GET['product_cat'] == $subsub->slug ? 'checked' : '' ), ( isset($_GET['product_cat']) && in_array( $subsub->slug, ( !is_shop() && is_product_category() ? array($_GET['product_cat']) : explode(',', $pf_query->query_vars['product_cat']) ) ) ? ' prdctfltr_active' : '' ), ( !empty($subsub->children) ? '<i class="prdctfltr-plus"></i>' : '' ), $pf_adoptive_class, ( $curr_options['wc_settings_prdctfltr_show_counts'] == 'no' ? '' : ' <span class="prdctfltr_count">' . ( isset($output_terms['product_cat']) && isset($output_terms['product_cat'][$subsub->slug]) && $output_terms['product_cat'][$subsub->slug] != $subsub->count ? $output_terms['product_cat'][$subsub->slug] . '/' . $subsub->count : $subsub->count ) . '</span>' ) );

												if ( !empty($subsub->children) ) {

													printf( '<div class="prdctfltr_sub" data-sub="%1$s">', $subsub->slug );

													foreach( $subsub->children as $subsubsub ) {

														$pf_adoptive_class = '';
														if ( $curr_options['wc_settings_prdctfltr_cat_adoptive'] == 'yes' && isset($output_terms['product_cat']) && !empty($output_terms['product_cat']) && !array_key_exists($subsubsub->slug, $output_terms['product_cat']) ) {
															$pf_adoptive_class = ' pf_adoptive_hide';
														}

														printf('<label class="%5$s%4$s"><input type="checkbox" value="%1$s" %3$s /><span>%2$s%6$s</span></label>', $subsubsub->slug, $subsubsub->name, ( isset($_GET['product_cat']) && $_GET['product_cat'] == $subsubsub->slug ? 'checked' : '' ), ( isset($_GET['product_cat']) && in_array( $subsubsub->slug, ( !is_shop() && is_product_category() ? array($_GET['product_cat']) : explode(',', $pf_query->query_vars['product_cat']) ) ) ? ' prdctfltr_active' : '' ), $pf_adoptive_class, ( $curr_options['wc_settings_prdctfltr_show_counts'] == 'no' ? '' : ' <span class="prdctfltr_count">' . ( isset($output_terms['product_cat']) && isset($output_terms['product_cat'][$subsubsub->slug]) && $output_terms['product_cat'][$subsubsub->slug] != $subsubsub->count ? $output_terms['product_cat'][$subsubsub->slug] . '/' . $subsubsub->count : $subsubsub->count ) . '</span>' ) );

													}

												echo '</div>';

												}

											}

											echo '</div>';

										}

									}

									echo '</div>';
								}
							}
						?>
						</div>
					</div>
					<?php
					}
					?>

				<?php break;

				case 'tag' :

					if ( $curr_options['wc_settings_prdctfltr_tag_adoptive'] == 'yes' && isset($output_terms) && ( !isset($output_terms['product_tag']) || empty($output_terms['product_tag']) ) === true && $total !== 0 ) {
						continue;
					}

					if ( !in_array('product_tag', $active_filters) ) {
						$active_filters[] = 'product_tag';
					}
				?>

					<?php
						$curr_limit = intval( $curr_options['wc_settings_prdctfltr_tag_limit'] );
						if ( $curr_limit !== 0 ) {
							$catalog_tags = get_terms( 'product_tag', array('hide_empty' => 1, 'orderby' => 'count', 'order' => 'DESC', 'number' => $curr_limit ) );
						}
						else {
							$catalog_tags = get_terms( 'product_tag', array('hide_empty' => 1 ) );
						}

						if ( !empty( $catalog_tags ) && !is_wp_error( $catalog_tags ) ){
						$curr_term_multi = ( $curr_options['wc_settings_prdctfltr_tag_multi'] == 'yes' ? ' prdctfltr_multi' : ' prdctfltr_single' );
						$curr_term_adoptive = ( $curr_options['wc_settings_prdctfltr_tag_adoptive'] == 'yes' ? ' prdctfltr_adoptive' : '' );

					?>
					<div class="prdctfltr_filter prdctfltr_tag <?php echo $curr_term_multi; ?> <?php echo $curr_term_adoptive; ?>">
						<input name="product_tag" type="hidden"<?php echo ( isset($_GET['product_tag'] ) ? ' value="'.$_GET['product_tag'].'"' : '' );?>>

						<?php echo $before_title; ?>

						<span class="prdctfltr_widget_title">
							<?php
								if ( isset($_GET['product_tag']) ) {
									$curr_selected = explode(',', $pf_query->query_vars['product_tag']);
									echo '<a href="#" data-key="product_tag"><i class="prdctfltr-delete"></i></a> <span>';
									$i=0;
									foreach( $curr_selected as $selected ) {
										$curr_term = get_term_by('slug', $selected, 'product_tag');
										echo ( $i !== 0 ? ', ' : '' ) . $curr_term->name;
										$i++;
									}
									echo '</span> / ';
								}

								if ( $curr_options['wc_settings_prdctfltr_tag_title'] != '' ) {
									echo $curr_options['wc_settings_prdctfltr_tag_title'];
								}
								else {
									_e('Tags', 'prdctfltr');
								}
							?>
							<i class="prdctfltr-down"></i>
						</span>

						<?php echo $after_title; ?>

						<div class="prdctfltr_checkboxes"<?php echo $curr_maxheight; ?>>
						<?php
							$curr_include = $curr_options['wc_settings_prdctfltr_include_tags'];
							printf('<label><input type="checkbox" value="" /><span>%1$s</span></label>', __('None' , 'prdctfltr') );
							foreach ( $catalog_tags as $term ) {
								if ( !empty($curr_include) && !in_array($term->slug, $curr_include) ) {
									continue;
								}

								$pf_adoptive_class = '';
								if ( $curr_options['wc_settings_prdctfltr_tag_adoptive'] == 'yes' && isset($output_terms['product_tag']) && !empty($output_terms['product_tag']) && !array_key_exists($term->slug, $output_terms['product_tag']) ) {
									$pf_adoptive_class = ' pf_adoptive_hide';
								}

								printf('<label class="%5$s%4$s"><input type="checkbox" value="%1$s" %3$s /><span>%2$s%6$s</span></label>', $term->slug, $term->name, ( isset($_GET['product_tag']) && $_GET['product_tag'] == $term->slug ? 'checked' : '' ), ( isset($pf_query->query_vars['product_tag']) && in_array( $term->slug, explode(',', $pf_query->query_vars['product_tag']) ) ? ' prdctfltr_active' : '' ), $pf_adoptive_class, ( $curr_options['wc_settings_prdctfltr_show_counts'] == 'no' ? '' : ' <span class="prdctfltr_count">' . ( isset($output_terms['product_tag']) && isset($output_terms['product_tag'][$term->slug]) && $output_terms['product_tag'][$term->slug] != $term->count ? $output_terms['product_tag'][$term->slug] . '/' . $term->count : $term->count ) . '</span>' ) );
							}
						?>
						</div>
					</div>
					<?php
					}
				break;

				case 'char' :

					if ( $curr_options['wc_settings_prdctfltr_chars_adoptive'] == 'yes' && isset($output_terms) && ( !isset($output_terms['characteristics']) || empty($output_terms['characteristics']) ) === true && $total !== 0 ) {
						continue;
					}

					if ( !in_array('characteristics', $active_filters) ) {
						$active_filters[] = 'characteristics';
					}
				?>

					<?php
						$curr_limit = intval( $curr_options['wc_settings_prdctfltr_custom_tax_limit'] );
						if ( $curr_limit !== 0 ) {
							$catalog_characteristics = get_terms( 'characteristics', array('hide_empty' => 1, 'orderby' => 'count', 'order' => 'DESC', 'number' => $curr_limit ) );
						}
						else {
							$catalog_characteristics = get_terms( 'characteristics', array('hide_empty' => 1 ) );
						}

						if ( !empty( $catalog_characteristics ) && !is_wp_error( $catalog_characteristics ) ){
						$curr_term_multi = ( $curr_options['wc_settings_prdctfltr_chars_multi'] == 'yes' ? ' prdctfltr_multi' : ' prdctfltr_single' );
						$curr_term_adoptive = ( $curr_options['wc_settings_prdctfltr_chars_adoptive'] == 'yes' ? ' prdctfltr_adoptive' : '' );

					?>
					<div class="prdctfltr_filter prdctfltr_characteristics <?php echo $curr_term_multi; ?> <?php echo $curr_term_adoptive; ?>">
						<input name="characteristics" type="hidden"<?php echo ( isset($_GET['characteristics'] ) ? ' value="'.$_GET['characteristics'].'"' : '' );?>>

						<?php echo $before_title; ?>

						<span class="prdctfltr_widget_title">
							<?php
								if ( isset($_GET['characteristics']) ) {
									$curr_selected = explode(',', $pf_query->query_vars['characteristics']);
									echo '<a href="#" data-key="characteristics"><i class="prdctfltr-delete"></i></a> <span>';
									$i=0;
									foreach( $curr_selected as $selected ) {
										$curr_term = get_term_by('slug', $selected, 'characteristics');
										echo ( $i !== 0 ? ', ' : '' ) . $curr_term->name;
										$i++;
									}
									echo '</span> / ';
								}


								if ( $curr_options['wc_settings_prdctfltr_custom_tax_title'] != '' ) {
									echo $curr_options['wc_settings_prdctfltr_custom_tax_title'];
								}
								else {
									_e('Characteristics', 'prdctfltr');
								}
							?>
							<i class="prdctfltr-down"></i>
						</span>
						<?php echo $after_title; ?>

						<div class="prdctfltr_checkboxes"<?php echo $curr_maxheight; ?>>
						<?php
							$curr_include = $curr_options['wc_settings_prdctfltr_include_chars'];
							printf('<label><input type="checkbox" value="" /><span>%1$s</span></label>', __('None' , 'prdctfltr') );
							foreach ( $catalog_characteristics as $term ) {
								if ( !empty($curr_include) && !in_array($term->slug, $curr_include) ) {
									continue;
								}

								$pf_adoptive_class = '';
								if ( $curr_options['wc_settings_prdctfltr_chars_adoptive'] == 'yes' && isset($output_terms['characteristics']) && !empty($output_terms['characteristics']) && !array_key_exists($term->slug, $output_terms['characteristics']) ) {
									$pf_adoptive_class = ' pf_adoptive_hide';
								}

								printf('<label class="%5$s%4$s"><input type="checkbox" value="%1$s" %3$s /><span>%2$s%6$s</span></label>', $term->slug, $term->name, ( isset($_GET['characteristics']) && $_GET['characteristics'] == $term->slug ? 'checked' : '' ), ( isset($pf_query->query_vars['characteristics']) && in_array( $term->slug, explode(',', $pf_query->query_vars['characteristics']) ) ? ' prdctfltr_active' : '' ), $pf_adoptive_class, ( $curr_options['wc_settings_prdctfltr_show_counts'] == 'no' ? '' : ' <span class="prdctfltr_count">' . ( isset($output_terms['characteristics']) && isset($output_terms['characteristics'][$term->slug]) && $output_terms['characteristics'][$term->slug] != $term->count ? $output_terms['characteristics'][$term->slug] . '/' . $term->count : $term->count ) . '</span>' ) );
							}
						?>
						</div>
					</div>
					<?php
					}
				break;

				case 'advanced' :

				$attr = $curr_options['wc_settings_prdctfltr_advanced_filters']['pfa_taxonomy'][$n];

					if ( $curr_options['wc_settings_prdctfltr_advanced_filters']['pfa_adoptive'][$n] == 'yes' && isset($output_terms) && ( !isset($output_terms[$attr]) || empty($output_terms[$attr]) ) === true && $total !== 0 ) {
						continue;
					}

					if ( !in_array($attr, $active_filters) ) {
						$active_filters[] = $attr;
					}

					$curr_attributes = get_terms( $attr, array('hide_empty' => 1 ) );

					$curr_term = get_taxonomy( $attr );
					$curr_term_style = 'text';
					$curr_term_multi = ( $curr_options['wc_settings_prdctfltr_advanced_filters']['pfa_multiselect'][$n] == 'yes' ? ' prdctfltr_multi' : ' prdctfltr_single' );
					$curr_term_adoptive = ( $curr_options['wc_settings_prdctfltr_' . $attr . '_adoptive'] == 'yes' ? ' prdctfltr_adoptive' : '' );

		?>
					<div class="prdctfltr_filter prdctfltr_attributes prdctfltr_<?php echo $attr; ?> <?php echo $curr_term_style; ?> <?php echo $curr_term_multi; ?> <?php echo $curr_term_adoptive; ?>">
						<input name="<?php echo $attr; ?>" type="hidden"<?php echo ( isset( $pf_query->query_vars[$attr] ) ? ' value="'.$pf_query->query_vars[$attr].'"' : '' );?>>

					<?php echo $before_title; ?>

					<span class="prdctfltr_widget_title">
							<?php
								if ( isset($_GET[$attr]) ) {
									$curr_selected = explode(',', $pf_query->query_vars[$attr]);
									echo '<a href="#" data-key="' . $attr . '"><i class="prdctfltr-delete"></i></a> <span>';
									$i=0;
									foreach( $curr_selected as $selected ) {
										$curr_sterm = get_term_by('slug', $selected, $attr);
										echo ( $i !== 0 ? ', ' : '' ) . $curr_sterm->name;
										$i++;
									}
									echo '</span> / ';
								}

								if ( $curr_options['wc_settings_prdctfltr_advanced_filters']['pfa_title'][$n] !== '' ) {
									echo $curr_options['wc_settings_prdctfltr_advanced_filters']['pfa_title'][$n];
								}
								else {
									$curr_term->label;
								}
							?>
							<i class="prdctfltr-down"></i>
					</span>
					<?php echo $after_title; ?>

						<div class="prdctfltr_checkboxes"<?php echo $curr_maxheight; ?>>
						<?php
							$curr_include = $curr_options['wc_settings_prdctfltr_advanced_filters']['pfa_include'][$n];
							switch ( $curr_term_style ) {
								case 'pf_attr_text':
									$curr_blank_element = __('None' , 'prdctfltr');
								break;
								case 'pf_attr_imgtext':
									$curr_blank_element = '<img src="' . PRDCTFLTR_URL . '/lib/images/pf-transparent.gif" />';
									$curr_blank_element .= __('None' , 'prdctfltr');
								break;
								case 'pf_attr_img':
									$curr_blank_element = '<img src="' . PRDCTFLTR_URL . '/lib/images/pf-transparent.gif" />';
								break;
								default :
									$curr_blank_element = __('None' , 'prdctfltr');
								break;
							}
							printf('<label><input type="checkbox" value="" /><span>%1$s</span></label>', $curr_blank_element );
							foreach ( $curr_attributes as $attribute ) {
								if ( !empty($curr_include) && !in_array($attribute->slug, $curr_include) ) {
									continue;
								}
								switch ( $curr_term_style ) {
									case 'pf_attr_text':
										$curr_attr_element = $attribute->name . ( $curr_options['wc_settings_prdctfltr_show_counts'] == 'no' ? '' : ' <span class="prdctfltr_count">' . ( isset($output_terms[$attr]) && isset($output_terms[$attr][$attribute->slug]) && $output_terms[$attr][$attribute->slug] != $attribute->count ? $output_terms[$attr][$attribute->slug] . '/' . $attribute->count : $attribute->count ) . '</span>' );
									break;
									case 'pf_attr_imgtext':
										$curr_attr_element = wp_get_attachment_image( get_woocommerce_term_meta($attribute->term_id, $attr.'_thumbnail_id_photo', true), 'shop_thumbnail' );
										$curr_attr_element .= $attribute->name . ( $curr_options['wc_settings_prdctfltr_show_counts'] == 'no' ? '' : ' <span class="prdctfltr_count">' . ( isset($output_terms[$attr]) && isset($output_terms[$attr][$attribute->slug]) && $output_terms[$attr][$attribute->slug] != $attribute->count ? $output_terms[$attr][$attribute->slug] . '/' . $attribute->count : $attribute->count ) . '</span>' );
									break;
									case 'pf_attr_img':
										$curr_attr_element = wp_get_attachment_image( get_woocommerce_term_meta($attribute->term_id, $attr.'_thumbnail_id_photo', true), 'shop_thumbnail' );
									break;
									default :
										$curr_attr_element = $attribute->name;
									break;
								}

								$pf_adoptive_class = '';
								if ( $curr_options['wc_settings_prdctfltr_advanced_filters']['pfa_adoptive'][$n] == 'yes' && isset($output_terms[$attr]) && !empty($output_terms[$attr]) && !array_key_exists($attribute->slug, $output_terms[$attr]) ) {
									$pf_adoptive_class = ' pf_adoptive_hide';
								}

								printf('<label class="%5$s%4$s"><input type="checkbox" value="%1$s" %3$s /><span>%2$s</span></label>', $attribute->slug, $curr_attr_element, ( isset($_GET[$attr]) && $_GET[$attr] == $attribute->slug ? 'checked' : '' ), ( isset($pf_query->query_vars[$attr]) && in_array( $attribute->slug, explode(',', $pf_query->query_vars[$attr]) ) ? ' prdctfltr_active' : '' ), $pf_adoptive_class );
							}
						?>
						</div>
					</div>
					<?php

					$n++;
				break;


				case 'range' :

				$attr = $curr_options['wc_settings_prdctfltr_range_filters']['pfr_taxonomy'][$p];

					if ( !in_array($attr, $active_filters) ) {
						$active_filters[] = $attr;
					}

		?>
					<div class="prdctfltr_filter prdctfltr_range prdctfltr_<?php echo $attr; ?> <?php echo $curr_term_style; ?> <?php echo 'pf_rngstyle_' . $curr_options['wc_settings_prdctfltr_range_filters']['pfr_style'][$p]; ?>">
						<input name="rng_min_<?php echo $attr; ?>" type="hidden"<?php echo ( isset( $_GET['rng_min_' . $attr] ) ? ' value="'.$_GET['rng_min_' . $attr].'"' : '' );?>>
						<input name="rng_max_<?php echo $attr; ?>" type="hidden"<?php echo ( isset( $_GET['rng_max_' . $attr] ) ? ' value="'.$_GET['rng_max_' . $attr].'"' : '' );?>>

						<?php echo $before_title; ?>

						<span class="prdctfltr_widget_title">
							<?php
								if ( isset($_GET['rng_min_' . $attr]) && isset($_GET['rng_max_' . $attr]) ) {
									echo '<a href="#" data-key="rng_' . $attr . '"><i class="prdctfltr-delete"></i></a> <span>';
									if ( $attr == 'price' ) {
										echo wc_price($_GET['rng_min_' . $attr]) . ' - ' . wc_price($_GET['rng_max_' . $attr]);
									}
									else {
										$pf_f_term = get_term_by('slug', $_GET['rng_min_' . $attr], $attr);
										$pf_s_term = get_term_by('slug', $_GET['rng_max_' . $attr], $attr);
										echo $pf_f_term->name . ' - ' . $pf_s_term->name;
									}
									echo '</span> / ';
								}

								if ( $curr_options['wc_settings_prdctfltr_range_filters']['pfr_title'][$p] !== '' ) {
									echo $curr_options['wc_settings_prdctfltr_range_filters']['pfr_title'][$p];
								}
								else {
									if ( !in_array($curr_options['wc_settings_prdctfltr_range_filters']['pfr_taxonomy'][$p], array('price') ) ) {
										$curr_term = get_taxonomy( $attr );
										echo $curr_term->label;
									}
									else {
										_e( 'Price range', 'prdctfltr' );
									}

								}
							?>
							<i class="prdctfltr-down"></i>
						</span>
						<?php echo $after_title; ?>

						<div class="prdctfltr_checkboxes"<?php echo $curr_maxheight; ?>>
						<?php
							$pf_add_settings = '';
							$curr_include = $curr_options['wc_settings_prdctfltr_range_filters']['pfr_include'][$p];
							
							if ( !in_array($curr_options['wc_settings_prdctfltr_range_filters']['pfr_taxonomy'][$p], array('price') ) ) {

								$curr_attributes = get_terms( $attr, array('hide_empty' => 1 ) );
								$pf_add_settings .= 'values:[';

								$c=0;
							
								foreach ( $curr_attributes as $attribute ) {
									if ( !empty($curr_include) && !in_array($attribute->slug, $curr_include) ) {
										continue;
									}
									if ( isset($_GET['rng_min_' . $attr]) && isset($_GET['rng_max_' . $attr]) ) {
										if ( $_GET['rng_min_' . $attr] == $attribute->slug ) {
											$pf_curr_min = $c;
										}
										if ( $_GET['rng_max_' . $attr] == $attribute->slug ) {
											$pf_curr_max = $c;
										}
									}
									$pf_add_settings .= ( $c !== 0 ? ', ' : '' ) . '"' . $attribute->slug . '"';
									$c++;
								}

								$pf_add_settings .= '], decorate_both: false,values_separator: " &rarr; ", min_interval: 1, ';


							}
							else {
								global $wpdb;
								$pf_curr_min = floor( $wpdb->get_var(
									$wpdb->prepare('
										SELECT min(meta_value + 0)
										FROM %1$s
										LEFT JOIN %2$s ON %1$s.ID = %2$s.post_id
										WHERE ( meta_key = \'%3$s\' OR meta_key = \'%4$s\' )
										AND meta_value != ""
										', $wpdb->posts, $wpdb->postmeta, '_price', '_min_variation_price' )
									)
								);
								$pf_curr_max = ceil( $wpdb->get_var(
									$wpdb->prepare('
										SELECT max(meta_value + 0)
										FROM %1$s
										LEFT JOIN %2$s ON %1$s.ID = %2$s.post_id
										WHERE ( meta_key = \'%3$s\' OR meta_key = \'%4$s\' )
										AND meta_value != ""
									', $wpdb->posts, $wpdb->postmeta, '_price', '_max_variation_price' )
								) );

								$pf_add_settings .= 'min:' . $pf_curr_min . ', max:' . $pf_curr_max . ', min_interval: 1, ';

								$currency_pos = get_option( 'woocommerce_currency_pos' );
								$currency = get_woocommerce_currency_symbol();

								switch ( $currency_pos ) {
									case 'left' :
										$pf_add_settings .= 'prefix: "' . $currency . '", ';
									break;
									case 'right' :
										$pf_add_settings .= 'postfix: "' . $currency . '", ';
									break;
									case 'left_space' :
										$pf_add_settings .= 'prefix: "' . $currency . ' ", ';
									break;
									case 'right_space' :
										$pf_add_settings .= 'postfix: " ' . $currency . '", ';
									break;
								}
								if ( ( isset($_GET['rng_min_' . $attr]) && isset($_GET['rng_max_' . $attr]) ) !== false ) {
									$pf_curr_min = ( isset($_GET['rng_min_' . $attr]) ? $_GET['rng_min_' . $attr] : $_GET['min_' . $attr] );
									$pf_curr_max = ( isset($_GET['rng_max_' . $attr]) ? $_GET['rng_max_' . $attr] : $_GET['max_' . $attr] );
								}
							}

							if ( $curr_options['wc_settings_prdctfltr_range_filters']['pfr_grid'][$p] == 'yes' ) {
								$pf_add_settings .= 'grid: true, ';
							}

							if ( ( isset($_GET['rng_min_' . $attr]) && isset($_GET['rng_max_' . $attr]) ) !== false ) {
								$pf_add_settings .= 'from:'.$pf_curr_min.',to:'.$pf_curr_max.', ';
							}

							$pf_add_settings .= 'force_edges: true, ';

							$pf_add_settings .= '
								onFinish: function (data) {
									if ( data.min == data.from && data.max == data.to ) {
										$(\'#prdctfltr_rng_' . $p . '\').closest(\'.prdctfltr_filter\').find(\'input[name^="rng_min_"]:first\').val( \'\' );
										$(\'#prdctfltr_rng_' . $p . '\').closest(\'.prdctfltr_filter\').find(\'input[name^="rng_max_"]:first\').val( \'\' ).trigger(\'change\');
									}
									else {
										$(\'#prdctfltr_rng_' . $p . '\').closest(\'.prdctfltr_filter\').find(\'input[name^="rng_min_"]:first\').val( ( data.from_value == null ? data.from : data.from_value ) );
										$(\'#prdctfltr_rng_' . $p . '\').closest(\'.prdctfltr_filter\').find(\'input[name^="rng_max_"]:first\').val( ( data.to_value == null ? data.to : data.to_value ) ).trigger(\'change\');
									}
								}';

							printf( '<input id="prdctfltr_rng_%1$s" />', $p );
?>
							<script type="text/javascript">
(function($){
"use strict";
	$('#prdctfltr_rng_<?php echo $p; ?>').ionRangeSlider({
		type: 'double',
		<?php echo $pf_add_settings; ?>
	});
})(jQuery);
							</script>
<?php
						?>
						</div>
					</div>
					<?php

					$p++;
				break;

				default :

				$attr = $v;

					if ( $curr_options['wc_settings_prdctfltr_' . $attr . '_adoptive'] == 'yes' && isset($output_terms) && ( !isset($output_terms[$attr]) || empty($output_terms[$attr]) ) === true && $total !== 0 ) {
						continue;
					}

					if ( !in_array($attr, $active_filters) ) {
						$active_filters[] = $attr;
					}

					$curr_attributes = get_terms( $attr, array('hide_empty' => 1 ) );

					$curr_term = get_taxonomy( $attr );
					$curr_term_style = $curr_options['wc_settings_prdctfltr_' . $attr];
					$curr_term_multi = ( $curr_options['wc_settings_prdctfltr_' . $attr . '_multi'] == 'yes' ? ' prdctfltr_multi' : ' prdctfltr_single' );
					$curr_term_adoptive = ( $curr_options['wc_settings_prdctfltr_' . $attr . '_adoptive'] == 'yes' ? ' prdctfltr_adoptive' : '' );

		?>
					<div class="prdctfltr_filter prdctfltr_attributes prdctfltr_<?php echo $attr; ?> <?php echo $curr_term_style; ?> <?php echo $curr_term_multi; ?> <?php echo $curr_term_adoptive; ?>">
					<input name="<?php echo $attr; ?>" type="hidden"<?php echo ( isset( $pf_query->query_vars[$attr] ) ? ' value="'.$pf_query->query_vars[$attr].'"' : '' );?>>

					<?php echo $before_title; ?>

					<span class="prdctfltr_widget_title">
						<?php
							if ( isset($_GET[$attr]) ) {
								$curr_selected = explode(',', $pf_query->query_vars[$attr]);
								echo '<a href="#" data-key="' . $attr . '"><i class="prdctfltr-delete"></i></a> <span>';
								$i=0;
								foreach( $curr_selected as $selected ) {
									$curr_sterm = get_term_by('slug', $selected, $attr);
									echo ( $i !== 0 ? ', ' : '' ) . $curr_sterm->name;
									$i++;
								}
								echo '</span> / ';
							}

							if ( $curr_options['wc_settings_prdctfltr_'.$attr.'_title'] != '' ) {
								echo $curr_options['wc_settings_prdctfltr_'.$attr.'_title'];
							}
							else {
								echo $curr_term->label;
							}
						?>
						<i class="prdctfltr-down"></i>
					</span>
					<?php echo $after_title; ?>
					<div class="prdctfltr_checkboxes"<?php echo $curr_maxheight; ?>>
						<?php
							$curr_include = $curr_options['wc_settings_prdctfltr_include_' . $attr];
							switch ( $curr_term_style ) {
								case 'pf_attr_text':
									$curr_blank_element = __('None' , 'prdctfltr');
								break;
								case 'pf_attr_imgtext':
									$curr_blank_element = '<img src="' . PRDCTFLTR_URL . '/lib/images/pf-transparent.gif" />';
									$curr_blank_element .= __('None' , 'prdctfltr');
								break;
								case 'pf_attr_img':
									$curr_blank_element = '<img src="' . PRDCTFLTR_URL . '/lib/images/pf-transparent.gif" />';
								break;
								default :
									$curr_blank_element = __('None' , 'prdctfltr');
								break;
							}
							printf('<label><input type="checkbox" value="" /><span>%1$s</span></label>', $curr_blank_element );
							foreach ( $curr_attributes as $attribute ) {

								if ( !empty($curr_include) && !in_array($attribute->slug, $curr_include) ) {
									continue;
								}
								switch ( $curr_term_style ) {
									case 'pf_attr_text':
										$curr_attr_element = $attribute->name . ( $curr_options['wc_settings_prdctfltr_show_counts'] == 'no' ? '' : ' <span class="prdctfltr_count">' . ( isset($output_terms[$attr]) && isset($output_terms[$attr][$attribute->slug]) && $output_terms[$attr][$attribute->slug] != $attribute->count ? $output_terms[$attr][$attribute->slug] . '/' . $attribute->count : $attribute->count ) . '</span>' );
									break;
									case 'pf_attr_imgtext':
										$curr_attr_element = wp_get_attachment_image( get_woocommerce_term_meta($attribute->term_id, $attr.'_thumbnail_id_photo', true), 'shop_thumbnail' );
										$curr_attr_element .= $attribute->name . ( $curr_options['wc_settings_prdctfltr_show_counts'] == 'no' ? '' : ' <span class="prdctfltr_count">' . ( isset($output_terms[$attr]) && isset($output_terms[$attr][$attribute->slug]) && $output_terms[$attr][$attribute->slug] != $attribute->count ? $output_terms[$attr][$attribute->slug] . '/' . $attribute->count : $attribute->count ) . '</span>' );
									break;
									case 'pf_attr_img':
										$curr_attr_element = wp_get_attachment_image( get_woocommerce_term_meta($attribute->term_id, $attr.'_thumbnail_id_photo', true), 'shop_thumbnail' );
									break;
									default :
										$curr_attr_element = $attribute->name;
									break;
								}

								$pf_adoptive_class = '';
								if ( $curr_options['wc_settings_prdctfltr_' . $attr . '_adoptive'] == 'yes' && isset($output_terms[$attr]) && !empty($output_terms[$attr]) && !array_key_exists($attribute->slug, $output_terms[$attr]) ) {
									$pf_adoptive_class = ' pf_adoptive_hide';
								}

								printf('<label class="%5$s%4$s"><input type="checkbox" value="%1$s" %3$s /><span>%2$s</span></label>', $attribute->slug, $curr_attr_element, ( isset($_GET[$attr]) && $_GET[$attr] == $attribute->slug ? 'checked' : '' ), ( isset($pf_query->query_vars[$attr]) && in_array( $attribute->slug, explode(',', $pf_query->query_vars[$attr]) ) ? ' prdctfltr_active' : '' ), $pf_adoptive_class );
							}
						?>
						</div>
					</div>
					<?php
				break;

				endswitch;

			$q++;

			endforeach;

		?>
		<div class="prdctfltr_clear"></div>
	</div>
</div>
<?php
	if ( $curr_options['wc_settings_prdctfltr_click_filter'] == 'no' ) {
?>
	<a id="prdctfltr_woocommerce_filter_submit" class="button" href="#"><?php _e('Filter selected', 'prdctfltr'); ?></a>
<?php
	}
	if ( $curr_options['wc_settings_prdctfltr_disable_sale'] == 'no' ) {
?>
<span class="prdctfltr_sale">
	<?php
	printf('<label%2$s><input name="sale_products" type="checkbox"%3$s/><span>%1$s</span></label>', __('Show only products on sale' , 'prdctfltr'), ( isset($_GET['sale_products']) ? ' class="prdctfltr_active"' : '' ), ( isset($_GET['sale_products']) ? ' checked' : '' ) );
	?>
</span>
<?php
	}
	if ( $curr_options['wc_settings_prdctfltr_disable_instock'] == 'no' && !in_array('instock', $curr_elements) ) {
?>
<span class="prdctfltr_instock">
	<?php
	printf('<label%2$s><input name="instock_products" type="checkbox" value="in"%3$s/><span>%1$s</span></label>', __('In stock only' , 'prdctfltr'), ( isset($_GET['instock_products']) ? ' class="prdctfltr_active"' : '' ), ( isset($_GET['instock_products']) ? ' checked' : '' ) );
	?>
</span>
<?php
	}
	if ( $curr_options['wc_settings_prdctfltr_disable_reset'] == 'no' && isset($pf_activated) && !empty($pf_activated) ) {
?>
<span class="prdctfltr_reset">
	<?php
	printf('<label><input name="reset_filter" type="checkbox" /><span>%1$s</span></label>', __('Clear all filters' , 'prdctfltr') );
	?>
</span>
<?php
	}
?>
	<div class="prdctfltr_add_inputs">
		<input type="hidden" name="widget_search" value="yes" />
	<?php
		if ( isset($_GET['s']) ) {
			echo '<input type="hidden" name="s" value="' . $_GET['s'] . '" />';
		}
		if ( isset($_GET['page_id']) ) {
			echo '<input type="hidden" name="page_id" value="' . $_GET['page_id'] . '" />';
		}
		if ( is_woocommerce() ) {
			echo '<input type="hidden" name="post_type" value="product" />';
		}
		if ( $curr_cat_query == 'no' || isset($prdctfltr_global['sc_query']) ) {
			if ( is_product_taxonomy() && !in_array(get_query_var('term'), $pf_activated) ) {
				echo '<input type="hidden" name="' . get_query_var('taxonomy') . '" value="' . get_query_var('term') . '" />';
			}
		}
	?>
	</div>
</form>
</div>
<?php
echo $after_widget;
	wp_reset_query();
	wp_reset_postdata();
}
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		$instance['preset'] =  $new_instance['preset'];
		$instance['template'] =  $new_instance['template'];

		return $instance;
	}

	function form( $instance ) {
		$vars = array( 'preset' => 'pf_default', 'template' => '' );
		$instance = wp_parse_args( (array) $instance, $vars );

		$preset = strip_tags($instance['preset']);
		$template = strip_tags($instance['template']);

?>
		<div>
			<p class="prdctfltr-box">
			<label for="<?php echo $this->get_field_id('preset'); ?>" class="prdctfltr-label"><?php _e('Style', 'prdctfltr'); ?> :</label>
			<select name="<?php echo $this->get_field_name('preset'); ?>" id="<?php echo $this->get_field_id('preset'); ?>" class="widefat" style="width:95%;margin-bottom:10px;">
				<option value="pf_default_inline"<?php echo ( $preset == 'pf_default_inline' ? ' selected="selected"' : '' ); ?>><?php _e('Flat Inline', 'prdctfltr'); ?></option>
				<option value="pf_default"<?php echo ( $preset == 'pf_default' ? ' selected="selected"' : '' ); ?>><?php _e('Flat Block', 'prdctfltr'); ?></option>
				<option value="pf_default_select"<?php echo ( $preset == 'pf_default_select' ? ' selected="selected"' : '' ); ?>><?php _e('Flat Select', 'prdctfltr'); ?></option>
			</select>

			<label for="<?php echo $this->get_field_id('template'); ?>" class="prdctfltr-label"><?php _e('Preset', 'prdctfltr'); ?> :</label>
			<select name="<?php echo $this->get_field_name('template'); ?>" id="<?php echo $this->get_field_id('template'); ?>" class="widefat" style="width:95%;">
				<option value="default"<?php echo ( $template == 'default' ? ' selected="selected"' : '' ); ?>><?php _e('Default', 'prdctfltr'); ?></option>
			<?php
				$curr_templates = get_option( 'prdctfltr_templates', array() );
				foreach ( $curr_templates as $k => $v ) {
			?>
				<option value="<?php echo $k; ?>"<?php echo ( $template == $k ? ' selected="selected"' : '' ); ?>><?php echo $k; ?></option>
			<?php
				}
			?>
			</select>
			</p>
		</div>

<?php
	}
}
add_action( 'widgets_init', create_function('', 'return register_widget("prdctfltr");' ) );

if ( is_admin() ) {

	function prdctfltr_admin_save() {

		$curr_name = $_POST['curr_name'];

		$curr_data = array();
		$curr_data[$curr_name] = $_POST['curr_settings'];

		$curr_presets = get_option('prdctfltr_templates');

		if ( $curr_presets === false ) {
			$curr_presets = array();
		}

		if ( isset($curr_presets) && is_array($curr_presets) ) {
			if ( array_key_exists($curr_name, $curr_presets) ) {
				unset($curr_presets[$curr_name]);
			}
			$curr_presets = $curr_presets + $curr_data;
			update_option('prdctfltr_templates', $curr_presets);
			die('1');
			exit;
		}

		die();
		exit;

	}
	add_action( 'wp_ajax_prdctfltr_admin_save', 'prdctfltr_admin_save' );


	function prdctfltr_admin_load() {

		$curr_name = $_POST['curr_name'];

		$curr_presets = get_option('prdctfltr_templates');
		if ( isset($curr_presets) && !empty($curr_presets) && is_array($curr_presets) ) {
			if ( array_key_exists($curr_name, $curr_presets) ) {
				die(stripslashes($curr_presets[$curr_name]));
				exit;
			}
			die('1');
			exit;
		}

		die();
		exit;

	}
	add_action( 'wp_ajax_prdctfltr_admin_load', 'prdctfltr_admin_load' );



	function prdctfltr_admin_delete() {

		$curr_name = $_POST['curr_name'];

		$curr_presets = get_option('prdctfltr_templates');
		if ( isset($curr_presets) && !empty($curr_presets) && is_array($curr_presets) ) {
			if ( array_key_exists($curr_name, $curr_presets) ) {
				unset($curr_presets[$curr_name]);
				update_option('prdctfltr_templates', $curr_presets);
			}
			die('1');
			exit;
		}

		die();
		exit;

	}
	add_action( 'wp_ajax_prdctfltr_admin_delete', 'prdctfltr_admin_delete' );

	function prdctfltr_or_add() {
		$curr_tax = $_POST['curr_tax'];
		$curr_term = $_POST['curr_term'];
		$curr_override = $_POST['curr_override'];

		$curr_overrides = get_option('prdctfltr_overrides');

		if ( $curr_overrides === false ) {
			$curr_overrides = array();
		}

		$curr_data = array(
			$curr_tax => array( $curr_term => $curr_override )
		);

		if ( isset($curr_overrides) && is_array($curr_overrides) ) {
			if ( isset($curr_overrides[$curr_tax]) && isset($curr_overrides[$curr_tax][$curr_term])) {
				unset($curr_overrides[$curr_tax][$curr_term]);
			}
			$curr_overrides = array_merge_recursive($curr_overrides, $curr_data);
			update_option('prdctfltr_overrides', $curr_overrides);
			die('1');
			exit;
		}

		die();
		exit;

	}
	add_action( 'wp_ajax_prdctfltr_or_add', 'prdctfltr_or_add' );

	function prdctfltr_or_remove() {
		$curr_tax = $_POST['curr_tax'];
		$curr_term = $_POST['curr_term'];
		$curr_overrides = get_option('prdctfltr_overrides');

		if ( $curr_overrides === false ) {
			$curr_overrides = array();
		}
		if ( isset($curr_overrides) && is_array($curr_overrides) ) {
			if ( isset($curr_overrides[$curr_tax]) && isset($curr_overrides[$curr_tax][$curr_term])) {
				unset($curr_overrides[$curr_tax][$curr_term]);
				update_option('prdctfltr_overrides', $curr_overrides);
				die('1');
				exit;
			}
		}

		die();
		exit;

	}
	add_action( 'wp_ajax_prdctfltr_or_remove', 'prdctfltr_or_remove' );

	function prdctfltr_c_fields() {
		$taxonomies = get_object_taxonomies( 'product', 'object' );
		$pf_id = ( isset( $_POST['pf_id'] ) ? $_POST['pf_id'] : 0 );

		$html = '';

		$html .= sprintf( '<label><span>%2$s</span> <input type="text" name="pfa_title[%3$s]" value="%1$s" /></label>', ( isset($_POST['pfa_title']) ? $_POST['pfa_title'] : '' ), __( 'Override title', 'prdctfltr' ), $pf_id );

		$html .= '<label><span>' . __( 'Select taxonomy','prdctfltr' ) . '</span> <select class="prdctfltr_adv_select" name="pfa_taxonomy[' . $pf_id . ']">';

		$i=0;

		foreach ( $taxonomies as $k => $v ) {
			if ( $k == 'product_type' ) {
				continue;
			}
			$selected = ( isset($_POST['pfa_taxonomy']) && $_POST['pfa_taxonomy'] == $k ? ' selected="selected"' : '' ) ;
			$html .= '<option value="' . $k . '"' . $selected . '>' . $v->label . '</option>';
			if ( !isset($_POST['pfa_taxonomy']) && $i==0 ) {
				$curr_fix = $k;
			}
			$i++;
		}
		if ( isset($_POST['pfa_taxonomy']) ) {
			$curr_fix = $_POST['pfa_taxonomy'];
		}

		$html .= '</select></label>';

		$catalog_attrs = get_terms( $curr_fix );
		$curr_options = '';
		if ( !empty( $catalog_attrs ) && !is_wp_error( $catalog_attrs ) ){
			foreach ( $catalog_attrs as $term ) {
				$selected = ( isset($_POST['pfa_include']) && is_array($_POST['pfa_include']) && in_array($term->slug, $_POST['pfa_include']) ? ' selected="selected"' : '' ) ;
				$curr_options .= sprintf( '<option value="%1$s"%3$s>%2$s</option>', $term->slug, $term->name, $selected );
			}
		}

		$html .= sprintf( '<label><span>%2$s</span> <select name="pfa_include[%3$s][]" multiple="multiple">%1$s</select></label>', $curr_options, __( 'Include terms', 'prdctfltr' ), $pf_id );

		$selected = ( isset($_POST['pfa_multiselect']) && $_POST['pfa_multiselect'] == 'yes' ? ' checked="checked"' : '' ) ;
		$html .= sprintf( '<label><input type="checkbox" name="pfa_multiselect[%3$s]" value="yes"%1$s /> %2$s</label>', $selected, __( 'Use multi select', 'prdctfltr' ), $pf_id );

		$selected = ( isset($_POST['pfa_adoptive']) && $_POST['pfa_adoptive'] == 'yes' ? ' checked="checked"' : '' ) ;
		$html .= sprintf( '<label><input type="checkbox" name="pfa_adoptive[%3$s]" value="yes"%1$s /> %2$s</label>', $selected, __( 'Use adoptive filtering', 'prdctfltr' ), $pf_id );

		die($pf_id . '%SPLIT%' . $html);
		exit;

	}
	add_action( 'wp_ajax_prdctfltr_c_fields', 'prdctfltr_c_fields' );


	function prdctfltr_c_terms() {

		$curr_tax = ( isset($_POST['taxonomy']) ? $_POST['taxonomy'] : '' );

		if ( $curr_tax == '' ) {
			die();
			exit;
		}

		$html = '';

		$catalog_attrs = get_terms( $curr_tax );
		$curr_options = '';
		if ( !empty( $catalog_attrs ) && !is_wp_error( $catalog_attrs ) ){
			foreach ( $catalog_attrs as $term ) {
				$curr_options .= sprintf( '<option value="%1$s">%2$s</option>', $term->slug, $term->name );
			}
		}

		$html .= sprintf( '<label><span>%2$s</span> <select name="pfa_include[%%%%][]" multiple="multiple">%1$s</select></label>', $curr_options, __( 'Include terms', 'prdctfltr' ) );

		die($html);
		exit;

	}
	add_action( 'wp_ajax_prdctfltr_c_terms', 'prdctfltr_c_terms' );



}
	function prdctfltr_respond() {

		global $prdctfltr_global;

		$shortcode_params = explode('|', $_POST['pf_shortcode']);

		$preset = ( $shortcode_params[0] !== 'false' ? $shortcode_params[0] : '' );
		$columns = ( $shortcode_params[1] !== 'false' ? $shortcode_params[1] : 4 );
		$rows = ( $shortcode_params[2] !== 'false' ? $shortcode_params[2] : 4 );
		$pagination = ( $shortcode_params[3] !== 'false' ? $shortcode_params[3] : '' );
		$no_products = ( $shortcode_params[4] !== 'false' ? $shortcode_params[4] : '' );
		$show_products = ( $shortcode_params[5] !== 'false' ? $shortcode_params[5] : '' );
		$use_filter = ( $shortcode_params[6] !== 'false' ? $shortcode_params[6] : '' );
		$action = ( $shortcode_params[7] !== 'false' ? $shortcode_params[7] : '' );
		$bot_margin = ( $shortcode_params[8] !== 'false' ? $shortcode_params[8] : '' );
		$class = ( $shortcode_params[9] !== 'false' ? $shortcode_params[9] : '' );
		$shortcode_id = ( $shortcode_params[10] !== 'false' ? $shortcode_params[10] : '' );

		$res_paged = ( isset( $_POST['pf_paged'] ) ? $_POST['pf_paged'] : $_POST['pf_page'] );

		$ajax_query = $_POST['pf_query'];


		$current_page = prdctfltr_get_between( $ajax_query, 'paged=', '&' );
		$page = $res_paged;

		$args = str_replace( 'paged=' . $current_page . '&', 'paged=' . $page . '&', $ajax_query );

		if ( $no_products == 'yes' ) {
			$use_filter = 'no';
			$pagination = 'no';
			$orderby = 'rand';
		}

		$add_ajax = ' data-query="' . $args . '" data-page="' . $res_paged . '" data-shortcode="' . $_POST['pf_shortcode'] . '"';

		$bot_margin = (int)$bot_margin;
		$margin = " style='margin-bottom:" . $bot_margin . "px'";

		if ( isset($_POST['pf_filters']) ) {
			$curr_filters = $_POST['pf_filters'];
		}
		else {
			$curr_filters = array();
		}

		$filter_args = '';
		foreach ( $curr_filters as $k => $v ) {
			$filter_args .= '&' . $k . '=' . $v;
		}

		$args = $args . $filter_args;

		global $prdctfltr_global;

		$prdctfltr_global['ajax_paged'] = $res_paged;
		$prdctfltr_global['posts_per_page'] = $columns*$rows;
		$prdctfltr_global['active_filters'] = $curr_filters;

		if ( $action !== '' ) {
			$prdctfltr_global['action'] = $action;
		}
		if ( $preset !== '' ) {
			$prdctfltr_global['preset'] = $preset;
		}

		$out = '';

		global $woocommerce, $woocommerce_loop;

		$woocommerce_loop['columns'] = $columns;
		
		$products = new WP_Query( $args );

		ob_start();

		if ( $use_filter == 'yes' ) {
			include_once( plugin_dir_path( __FILE__ ) . 'woocommerce/loop/orderby.php' );
		}

		if ( $products->have_posts() ) { ?>
			<?php if ( $show_products == 'yes' ) { ?>

			<?php woocommerce_product_loop_start(); ?>

				<?php while ( $products->have_posts() ) : $products->the_post(); ?>

					<?php wc_get_template_part( 'content', 'product' ); ?>

				<?php endwhile; ?>

			<?php woocommerce_product_loop_end(); ?>

			<?php
				}
				else {
					$pagination = 'no';
				}
			?>

		<?php
		}
		else if ( $_POST['pf_widget'] == 'yes' ) {
			$prdctfltr_global['widget_search'] = $_POST['pf_widget'];
			wc_get_template( 'loop/no-products-found.php' );
		}

		$shortcode = str_replace( ' type-product', ' product type-product', ob_get_clean() );

		$out .= '<div' . ( $shortcode_id != '' ? ' id="'.$shortcode_id.'"' : '' ) . ' class="prdctfltr_sc_products woocommerce prdctfltr_ajax' . ( $class != '' ? ' '.$class.'' : '' ) . '"'.$margin.$add_ajax.'>';
		$out .= do_shortcode($shortcode);

		if ( $pagination == 'yes' ) {

			ob_start();
			?>
			<nav class="woocommerce-pagination">
				<?php
					echo paginate_links( apply_filters( 'woocommerce_pagination_args', array(
						'base'         => @add_query_arg('paged','%#%'),
						'format'       => '?page=%#%',
						'current'      => $res_paged,
						'total'        => $products->max_num_pages,
						'prev_text'    => '&larr;',
						'next_text'    => '&rarr;',
						'type'         => 'list',
						'end_size'     => 3,
						'mid_size'     => 3
					) ) );
				?>
			</nav>
			<?php
			$pagination = ob_get_clean();

			$out .= $pagination;

		}

		$out .= '</div>';

		die($out);
		exit;
	}
	add_action('wp_ajax_nopriv_prdctfltr_respond', 'prdctfltr_respond' );
	add_action('wp_ajax_prdctfltr_respond', 'prdctfltr_respond' );

	function prdctfltr_widget_respond() {

		if ( isset($_POST['pf_filters']) ) {
			foreach( $_POST['pf_filters'] as $k => $v ) {
				$_GET[$k] = $v;
			}
		}

		global $prdctfltr_global;

		$shortcode_params = explode('|', $_POST['pf_shortcode']);

		$columns = ( $shortcode_params[1] !== 'false' ? $shortcode_params[1] : 4 );
		$rows = ( $shortcode_params[2] !== 'false' ? $shortcode_params[2] : 4 );

		$prdctfltr_global['posts_per_page'] = $columns*$rows;

		if ( isset($_POST['pf_widget_title']) ) {
			$curr_title = explode('%%%', $_POST['pf_widget_title']);
		}

		if ( isset($_POST['pf_query']) ) {
			parse_str(html_entity_decode($_POST['pf_query']), $pf_args);
			$prdctfltr_global['sc_query'] = $pf_args;
		}

		ob_start();

		the_widget('prdctfltr', 'preset=' . $_POST['pf_preset'] . '&template=' . $_POST['pf_template'], array('before_title'=>stripslashes($curr_title[0]),'after_title'=>stripslashes($curr_title[1])) );

		$out = ob_get_clean();

		die($out);
		exit;
	}
	add_action('wp_ajax_nopriv_prdctfltr_widget_respond', 'prdctfltr_widget_respond' );
	add_action('wp_ajax_prdctfltr_widget_respond', 'prdctfltr_widget_respond' );

	function prdctfltr_r_fields() {
		$taxonomies = wc_get_attribute_taxonomies();
		$pf_id = ( isset( $_POST['pf_id'] ) ? $_POST['pf_id'] : 0 );

		$html = '';

		$html .= sprintf( '<label><span>%2$s</span> <input type="text" name="pfr_title[%3$s]" value="%1$s" /></label>', ( isset($_POST['pfr_title']) ? $_POST['pfr_title'] : '' ), __( 'Override title', 'prdctfltr' ), $pf_id );

		$html .= '<label><span>' . __( 'Select attribute','prdctfltr' ) . '</span> <select class="prdctfltr_rng_select" name="pfr_taxonomy[' . $pf_id . ']">';

		$html .= '<option value="price"' . ( isset($_POST['pfr_taxonomy']) && $_POST['pfr_taxonomy'] == 'price' ? ' selected="selected"' : '' ) . '>' . __( 'Price range', 'prdctfltr' ) . '</option>';

		foreach ( $taxonomies as $k => $v ) {
			$selected = ( isset($_POST['pfr_taxonomy']) && $_POST['pfr_taxonomy'] == 'pa_' . $v->attribute_name ? ' selected="selected"' : '' ) ;
			$curr_label = ! empty( $v->attribute_label ) ? $v->attribute_label : $v->attribute_name;
			$html .= '<option value="pa_' . $v->attribute_name . '"' . $selected . '>' . $curr_label . '</option>';
		}
		if ( isset($_POST['pfr_taxonomy']) ) {
			$curr_fix = $_POST['pfr_taxonomy'];
		}
		else {
			$curr_fix = 'price';
		}

		$html .= '</select></label>';

		if ( $curr_fix == 'price' ) {
			$html .= sprintf( '<label><span>%2$s</span> <select name="pfr_include[%3$s][]" multiple="multiple" disabled>%1$s</select></label>', array(), __( 'Include terms', 'prdctfltr' ), $pf_id );
		}
		else {
			$catalog_attrs = get_terms( $curr_fix );
			$curr_options = '';

			if ( !empty( $catalog_attrs ) && !is_wp_error( $catalog_attrs ) ){
				foreach ( $catalog_attrs as $term ) {
					$selected = ( isset($_POST['pfr_include']) && is_array($_POST['pfr_include']) && in_array($term->slug, $_POST['pfr_include']) ? ' selected="selected"' : '' ) ;
					$curr_options .= sprintf( '<option value="%1$s"%3$s>%2$s</option>', $term->slug, $term->name, $selected );
				}
			}

			$html .= sprintf( '<label><span>%2$s</span> <select name="pfr_include[%3$s][]" multiple="multiple">%1$s</select></label>', $curr_options, __( 'Include terms', 'prdctfltr' ), $pf_id );
		}

		$catalog_style = array( 'flat' => __( 'Flat', 'prdctfltr' ), 'modern' => __( 'Modern', 'prdctfltr' ), 'html5' => __( 'HTML5', 'prdctfltr' ), 'white' => __( 'White', 'prdctfltr' ) );
		$curr_options = '';
		foreach ( $catalog_style as $k => $v ) {
			$selected = ( isset($_POST['pfr_style']) && $_POST['pfr_style'] == $k ? ' selected="selected"' : '' ) ;
			$curr_options .= sprintf( '<option value="%1$s"%3$s>%2$s</option>', $k, $v, $selected );
		}

		$html .= sprintf( '<label><span>%2$s</span> <select name="pfr_style[%3$s]">%1$s</select></label>', $curr_options, __( 'Select style', 'prdctfltr' ), $pf_id );

		$selected = ( isset($_POST['pfr_grid']) && $_POST['pfr_grid'] == 'yes' ? ' checked="checked"' : '' ) ;
		$html .= sprintf( '<label><input type="checkbox" name="pfr_grid[%3$s]" value="yes"%1$s /> %2$s</label>', $selected, __( 'Use grid', 'prdctfltr' ), $pf_id );

		die($pf_id . '%SPLIT%' . $html);
		exit;

	}
	add_action( 'wp_ajax_prdctfltr_r_fields', 'prdctfltr_r_fields' );

	function prdctfltr_r_terms() {

		$curr_tax = ( isset($_POST['taxonomy']) ? $_POST['taxonomy'] : '' );

		if ( $curr_tax == '' ) {
			die();
			exit;
		}

		$html = '';

		if ( !in_array( $curr_tax, array( 'price' ) ) ) {

			$catalog_attrs = get_terms( $curr_tax );
			$curr_options = '';
			if ( !empty( $catalog_attrs ) && !is_wp_error( $catalog_attrs ) ){
				foreach ( $catalog_attrs as $term ) {
					$curr_options .= sprintf( '<option value="%1$s">%2$s</option>', $term->slug, $term->name );
				}
			}

			$html .= sprintf( '<label><span>%2$s</span> <select name="pfr_include[%%%%][]" multiple="multiple">%1$s</select></label>', $curr_options, __( 'Include terms', 'prdctfltr' ) );

		}
		else {
			$html .= sprintf( '<label><span>%1$s</span> <select name="pfr_include[%%%%][]" multiple="multiple" disabled></select></label>', __( 'Include terms', 'prdctfltr' ) );
		}

		die($html);
		exit;

	}
	add_action( 'wp_ajax_prdctfltr_r_terms', 'prdctfltr_r_terms' );

	function prdctfltr_get_between($content,$start,$end){
		$r = explode($start, $content);
		if (isset($r[1])){
			$r = explode($end, $r[1]);
			return $r[0];
		}
		return '';
	}

?>