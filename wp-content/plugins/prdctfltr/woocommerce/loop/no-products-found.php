<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$curr_shop_disable = get_option( 'wc_settings_prdctfltr_shop_disable', 'no' );

if ( $curr_shop_disable == 'yes' && is_shop() ) {
	return;
}

global $prdctfltr_global;

if ( isset($prdctfltr_global['active']) && $prdctfltr_global['active'] == 'true' ) {
	return;
}

global $wp;

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

if ( isset($prdctfltr_global['preset']) ) {
	$get_options = $prdctfltr_global['preset'];
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
	'wc_settings_prdctfltr_show_counts' => 'no',
	'wc_settings_prdctfltr_disable_showresults' => 'no'
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

if ( isset($_GET['widget_search']) || isset($prdctfltr_global['widget_search'])) {
	$curr_override = $curr_options['wc_settings_prdctfltr_noproducts'];
	if ( $curr_override == '' ) {
		echo '<h1>' . __('No products found') . '</h1>';
		echo '<p>' . __('Please widen your search criteria.') . '</p>';
	}
	else {
		echo $curr_override;
	}
	return;
}

$curr_elements = ( $curr_options['wc_settings_prdctfltr_active_filters'] !== NULL ? $curr_options['wc_settings_prdctfltr_active_filters'] : array() );

if ( isset($curr_options['wc_settings_prdctfltr_style_mode']) ) {
	if ( $curr_options['wc_settings_prdctfltr_style_preset'] !== 'pf_select' ) {
		$curr_mod = $curr_options['wc_settings_prdctfltr_style_mode'];
	}
	else {
		$curr_mod = 'pf_mod_multirow';
	}
}
else {
	$curr_mod = 'pf_mod_multirow';
}

if ( in_array( $curr_options['wc_settings_prdctfltr_style_preset'], array('pf_arrow','pf_arrow_inline') ) !== false ) {
	$curr_options['wc_settings_prdctfltr_always_visible'] = 'no';
	$curr_options['wc_settings_prdctfltr_disable_bar'] = 'no';
}

$curr_styles = array(
	( $curr_options['wc_settings_prdctfltr_style_preset'] !== 'pf_disable' ? ' ' . $curr_options['wc_settings_prdctfltr_style_preset'] : '' ),
	( $curr_options['wc_settings_prdctfltr_always_visible'] == 'no' && $curr_options['wc_settings_prdctfltr_disable_bar'] == 'no' ? 'prdctfltr_slide' : 'prdctfltr_always_visible' ),
	( $curr_options['wc_settings_prdctfltr_click_filter'] == 'no' ? 'prdctfltr_click' : 'prdctfltr_click_filter' ),
	( $curr_options['wc_settings_prdctfltr_limit_max_height'] == 'no' ? 'prdctfltr_rows' : 'prdctfltr_maxheight' ),
	( $curr_options['wc_settings_prdctfltr_custom_scrollbar'] == 'no' ? '' : 'prdctfltr_scroll_active' ),
	( $curr_options['wc_settings_prdctfltr_disable_bar'] == 'no' ? '' : 'prdctfltr_disable_bar' ),
	$curr_mod,
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
<div id="prdctfltr_woocommerce" class="prdctfltr_woocommerce woocommerce<?php echo implode( $curr_styles, ' ' ); ?>">
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

	if ( $curr_options['wc_settings_prdctfltr_disable_bar'] == 'no' ) {
	$prdctfltr_icon = $curr_options['wc_settings_prdctfltr_icon'];
	?>
		<a id="prdctfltr_woocommerce_filter" href="#"><i class="<?php echo ( $prdctfltr_icon == '' ? 'prdctfltr-bars' : $prdctfltr_icon ); ?>"></i></a>
		<span>
	<?php

		if ( $curr_options['wc_settings_prdctfltr_title'] !== '' ) {
			echo $curr_options['wc_settings_prdctfltr_title'];
		}
		else {
			_e('Filter products', 'prdctfltr');
		}

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

		if ( isset($pf_activated) ) {

			foreach( $pf_activated as $k => $v ) {


					switch( $k ) {
						case 'instock_products' :
							if ( isset($_GET['instock_products']) ) {
								echo ' / <span>'.$catalog_instock[$_GET['instock_products']] . '</span> <a href="#" data-key="instock_products"><i class="prdctfltr-delete"></i></a>';
							}
						break;
						case 'orderby' :
							if ( isset($_GET['orderby']) ) {
								echo ' / <span>'.$catalog_orderby[$_GET['orderby']] . '</span> <a href="#" data-key="orderby"><i class="prdctfltr-delete"></i></a>';
							}
						break;
						case 'min_price' :
							if ( isset($_GET['min_price']) && $_GET['min_price'] !== '' ) {

								$min_price = wc_price($_GET['min_price']);

								if ( isset($_GET['max_price']) && $_GET['max_price'] !== '' ) {
									$curr_max_price = $_GET['max_price'];
									$max_price = wc_price($_GET['max_price']);
								}
								else {
									$max_price = '+';
								}

								echo ' / <span>' . $min_price . ' - ' . $max_price . '</span> <a href="#" data-key="byprice"><i class="prdctfltr-delete"></i></a>';
							}
						break;
						case 'max_price' :
						break;
						case 'price' :
							if ( isset($_GET['rng_min_price']) && $_GET['rng_min_price'] !== '' ) {

								$min_price = wc_price($_GET['rng_min_price']);

								if ( isset($_GET['rng_max_price']) && $_GET['rng_max_price'] !== '' ) {
									$curr_max_price = $_GET['rng_max_price'];
									$max_price = wc_price($_GET['rng_max_price']);
								}
								else {
									$max_price = '+';
								}

								echo ' / <span>' . __('Price range', 'prdctfltr') . ' ' . $min_price . ' &rarr; ' . $max_price . '</span> <a href="#" data-key="byprice"><i class="prdctfltr-delete"></i></a>';
							}
						break;
						default :
							if ( $k == 'cat' || $k == 'tag' ) {
								$k = 'product_' . $k;
							}
							$curr_selected = explode(',', $pf_query->query_vars[$k]);
							if ( substr($k, 0, 3) == 'pa_' && $v !== '' ) {
								echo ' / <span>' . wc_attribute_label( $k ) . ' - ';
							}
							else {
								echo ' / <span>';
							}
							$i=0;
							foreach( $curr_selected as $selected ) {
								$curr_term = get_term_by('slug', $selected, $k);
								echo ( $i !== 0 ? ', ' : '' ) . $curr_term->name;
								$i++;
							}
							echo '</span> <a href="#" data-key="' . $k . '"><i class="prdctfltr-delete"></i></a>';
						break;
					}

			}

		}

	}

	if ( $curr_options['wc_settings_prdctfltr_disable_bar'] == 'no' ) {
		if ( $curr_options['wc_settings_prdctfltr_disable_showresults'] == 'no' ) {
			if ( $curr_options['wc_settings_prdctfltr_noproducts'] !=='' && $total == 0 ) {
				
			} elseif ( $total == 0 ) {
				echo ' / ' . __('No products found but you might like these&hellip;', 'prdctfltr');
			} elseif ( $total == 1 ) {
				echo ' / ' . __( 'Showing the single result', 'prdctfltr' );
			} elseif ( $total <= $per_page || -1 == $per_page ) {
				echo ' / ' . __( 'Showing all', 'prdctfltr') . ' ' . $total . ' ' . __( 'results', 'prdctfltr' );
			} else {
				echo ' / ' . __('Showing', 'prdctfltr') . ' ' . $first . ' - ' . $last . ' ' . __('of', 'prdctfltr') . ' ' . $total . ' ' . __('results', 'prdctfltr');
			}
		}
	}

?>
</span>
<?php

	$curr_mix_count = ( count($curr_elements) );
	$curr_columns = ( $curr_mix_count < $curr_options['wc_settings_prdctfltr_max_columns'] ? $curr_mix_count : $curr_options['wc_settings_prdctfltr_max_columns'] );

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
						<span>
							<?php
								if ( $curr_styles[5] == 'prdctfltr_disable_bar' && isset($_GET['instock_products'] ) ) {
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
						<span>
							<?php
								if ( $curr_styles[5] == 'prdctfltr_disable_bar' && isset($_GET['orderby'] ) && isset($catalog_orderby[$_GET['orderby']]) ) {
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
					<span>
						<?php
							if ( $curr_styles[5] == 'prdctfltr_disable_bar' && isset($_GET['min_price']) && $_GET['min_price'] !== '' ) {
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
						<span>
							<?php
								if ( $curr_styles[5] == 'prdctfltr_disable_bar' ) {
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
						<span>
							<?php
								if ( $curr_styles[5] == 'prdctfltr_disable_bar' ) {
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
					?>

				<?php break;

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
						<span>
							<?php
								if ( $curr_styles[5] == 'prdctfltr_disable_bar' ) {
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
						<span>
							<?php
								if ( $curr_styles[5] == 'prdctfltr_disable_bar' ) {
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
						<span>
							<?php
								if ( $curr_styles[5] == 'prdctfltr_disable_bar' ) {
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
					<span>
						<?php
							if ( $curr_styles[5] == 'prdctfltr_disable_bar' ) {
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
	if ( isset($total) && $total == 0 ) {
		$curr_override = $curr_options['wc_settings_prdctfltr_noproducts'];
		if ( $curr_override == '' ) {
			echo do_shortcode('[prdctfltr_sc_products no_products="yes"]');
		}
		else {
			echo do_shortcode($curr_override);
		}
	}
	wp_reset_query();
	wp_reset_postdata();
?>