<?php
/*
Template Name: LookBook
*/
?>
<?php get_header(); ?>
<?php $id = $wp_query->get_queried_object_id();
	  $look_book = get_post_meta($id, 'look_book', true);
	  $look_book_bg = get_post_meta($id, 'look_book_bg', true);?>
<div class="one_section left" style="<?php thb_bgecho($look_book_bg); ?>">
	<?php if (have_posts()) :  while (have_posts()) : the_post(); ?>
		  <article <?php post_class('post'); ?> id="post-<?php the_ID(); ?>">
			<div class="post-content">
				<?php the_content(); ?>
			</div>
		  </article>
	  <?php endwhile; else : endif; ?>
</div>
<div class="two_section right">
	<div class="carousel owl lookbook-container" data-navigation="true" data-autoplay="false" data-autowidth="true" data-columns="3" data-loop="true">
		<?php foreach ($look_book as $look) { ?>
			<div class="look">
				<img src="<?php echo $look['look_image']; ?>" alt="<?php echo $look['title']; ?>" />
				
				<?php if ($look['look_product_ids']) {
					$product_id_array = explode(',', $look['look_product_ids']);
					$args = array(
						'post_type' => 'product',
						'post_status' => 'publish',
						'ignore_sticky_posts'   => 1,
						'post__in'		=> $product_id_array
					);	
					$products = new WP_Query( $args );

					if ( $products->have_posts() && $look['look_product_ids']) {
						echo '<div class="info">';
						while ( $products->have_posts() ) : $products->the_post();
							$product = get_product( $products->post->ID );

							echo '<a href="'.get_permalink().'"><span class="title">'.get_the_title().'</span> '.$product->get_price_html().'</a>';
						endwhile;
						echo '</div>';
					}
				
				 } ?>
			</div>
		<?php } ?>
	</div>
</div>
<?php get_footer(); ?>