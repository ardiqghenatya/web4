<?php
/**
 * Wishlist page template
 *
 * @author Your Inspiration Themes
 * @package YITH WooCommerce Wishlist
 * @version 2.0.0
 */
 
global $wpdb;

if( isset( $_GET['user_id'] ) && !empty( $_GET['user_id'] ) ) {
    $user_id = $_GET['user_id'];
} elseif( is_user_logged_in() ) {
    $user_id = get_current_user_id();
}
$current_page = 1;
$limit_sql = '';

$myaccount_page_id = get_option( 'woocommerce_myaccount_page_id' );
$myaccount_page_url = "";
if ( $myaccount_page_id ) {
  $myaccount_page_url = get_permalink( $myaccount_page_id );
}

if( is_user_logged_in() )
    { $wishlist = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `" . YITH_WCWL_TABLE . "` WHERE `user_id` = %s" . $limit_sql, $user_id ), ARRAY_A ); }
elseif( yith_usecookies() )
    { $wishlist = yith_getcookie( 'yith_wcwl_products' ); }
else
    { $wishlist = isset( $_SESSION['yith_wcwl_products'] ) ? $_SESSION['yith_wcwl_products'] : array(); }
?>
<?php if (!is_account_page()) { ?>
<section class="page-padding">
<?php } ?>
<div class="small-12 small-centered medium-10 columns">
	<?php wc_print_notices() ?>
	<div class="text-center"><a href="<?php echo $myaccount_page_url; ?>" class="back_to_account"><?php _e("<small>Back to</small> My Account", THB_THEME_NAME); ?></a></div>
	<div class="smalltitle text-center"><?php echo apply_filters( 'woocommerce_my_account_my_orders_title', __( 'My Wishlist',THB_THEME_NAME ) ); ?></div>
	<form id="yith-wcwl-form" action="<?php echo esc_url( YITH_WCWL()->get_wishlist_url( 'view' . ( $wishlist_meta['is_default'] != 1 ? '/' . $wishlist_meta['wishlist_token'] : '' ) ) ) ?>" method="post">
	    <table class="shopping_bag wishlist">
	    	<thead>
	    		<tr>
					<th class="product-name" colspan="2"><?php _e( 'Product', THB_THEME_NAME) ?></th>
					<th class="product-price"><?php _e( 'Price', THB_THEME_NAME) ?></th>
					<th class="product-quantity"><?php _e( 'Stock Status', THB_THEME_NAME ) ?></th>
					<th class="product-add-to-bag"></th>
					<th class="product-remove"></th>
	    		</tr>
	    	</thead>
	    	<tbody>
    	        <?php
    	        if( count( $wishlist_items ) > 0 ) :
    	            foreach( $wishlist_items as $item ) :
    	                global $product;
    	                $product = get_product( $item['prod_id'] );
    	
    	                if( $product !== false && $product->exists() ) : ?>
    	                    <tr id="yith-wcwl-row-<?php echo $item['prod_id'] ?>" data-row-id="<?php echo $item['prod_id'] ?>">
    	
    	                        <td class="product-thumbnail">
    	                            <a href="<?php echo esc_url( get_permalink( apply_filters( 'woocommerce_in_cart_product', $item['prod_id'] ) ) ) ?>">
    	                                <?php echo $product->get_image() ?>
    	                            </a>
    	                        </td>
    	
    	                        <td class="product-name">
    	                            <h6><a href="<?php echo esc_url( get_permalink( apply_filters( 'woocommerce_in_cart_product', $item['prod_id'] ) ) ) ?>"><?php echo apply_filters( 'woocommerce_in_cartproduct_obj_title', $product->get_title(), $product ) ?></a></h6>
    	                        </td>
    	
    	                        <?php if( $show_price ) : ?>
    	                            <td class="product-price">
    	                                <?php
    	                                if( $product->price != '0' ) {
    	                                    $wc_price = function_exists('wc_price') ? 'wc_price' : 'woocommerce_price';
    	
    	                                    if( $price_excl_tax ) {
    	                                        echo apply_filters( 'woocommerce_cart_item_price_html', $wc_price( $product->get_price_excluding_tax() ), $item, '' );
    	                                    }
    	                                    else {
    	                                        echo apply_filters( 'woocommerce_cart_item_price_html', $wc_price( $product->get_price() ), $item, '' );
    	                                    }
    	                                }
    	                                else {
    	                                    echo apply_filters( 'yith_free_text', __( 'Free!', 'yit' ) );
    	                                }
    	                                ?>
    	                            </td>
    	                        <?php endif ?>
    	
    	                        <?php if( $show_stock_status ) : ?>
    	                            <td class="product-quantity">
    	                                <?php
    	                                $availability = $product->get_availability();
    	                                $stock_status = $availability['class'];
    	
    	                                if( $stock_status == 'out-of-stock' ) {
    	                                    $stock_status = "Out";
    	                                    echo '<span class="wishlist-out-of-stock">' . __( 'Out of Stock', 'yit' ) . '</span>';
    	                                } else {
    	                                    $stock_status = "In";
    	                                    echo '<span class="wishlist-in-stock">' . __( 'In Stock', 'yit' ) . '</span>';
    	                                }
    	                                ?>
    	                            </td>
    	                        <?php endif ?>
    	
    	                        <?php if( $show_add_to_cart ) : ?>
    	                            <td class="product-add-to-cart">
    	                                <?php if( isset( $stock_status ) && $stock_status != 'Out' ): ?>
    	                                    <?php
    	                                    if( function_exists( 'wc_get_template' ) ) {
    	                                        wc_get_template( 'loop/add-to-cart.php' );
    	                                    }
    	                                    else{
    	                                        woocommerce_get_template( 'loop/add-to-cart.php' );
    	                                    }
    	                                    ?>
    	                                <?php endif ?>
    	                            </td>
    	                        <?php endif ?>
    	                        <?php if( $is_user_owner ): ?>
    	                        <td class="product-remove">
    	                            <div>
    	                                <a href="<?php echo esc_url( add_query_arg( 'remove_from_wishlist', $item['prod_id'] ) ) ?>" class="remove remove_from_wishlist" title="<?php _e( 'Remove this product', 'yit' ) ?>">&times;</a>
    	                            </div>
    	                        </td>
    	                        <?php endif; ?>
    	                    </tr>
    	                <?php
    	                endif;
    	            endforeach;
    	        else: ?>
    	            <tr class="pagination-row">
    	                <td colspan="6" class="wishlist-empty"><?php _e( 'No products were added to the wishlist', 'yit' ) ?></td>
    	            </tr>
    	        <?php
    	        endif;
    			?>
    	    </tbody>
	     </table>
	     
	      <?php wp_nonce_field( 'yith_wcwl_edit_wishlist_action', 'yith_wcwl_edit_wishlist' ); ?>
	     
         <?php if( $wishlist_meta['is_default'] != 1 ): ?>
             <input type="hidden" value="<?php echo $wishlist_meta['wishlist_token'] ?>" name="wishlist_id" id="wishlist_id">
         <?php endif; ?>
     
         <?php do_action( 'yith_wcwl_after_wishlist' ); ?>
	</form>
</div>
<?php if (!is_account_page()) { ?>
</section>
<?php } ?>