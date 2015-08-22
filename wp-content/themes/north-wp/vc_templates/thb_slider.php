<?php function thb_slider( $atts, $content = null ) {
    extract(shortcode_atts(array(
       	'images' => '',
       	'width' => '1170',
       	'height' => '540',
       	'navigation' => ''
    ), $atts));
  $images = explode(',', $images);
 	ob_start();
 	
 	$nav = ($navigation == 'true' ? 'true' : 'false');
 	?>
	<div class="carousel owl" data-columns="1" data-navigation="<?php echo $nav; ?>" data-pagination="false">
		<?php
			foreach ($images as $image) {
				$image_link = wp_get_attachment_image_src($image, 'full');
				$image_src = aq_resize( $image_link[0], $width, $height, true, false, true);
				?>
				<figure>
					<img src="<?php echo $image_link[0]; ?>" alt="<?php echo $image_title; ?>" />
				</figure>
				<?php
			}
		?>
	</div>
	<?php
   $out = ob_get_contents();
   if (ob_get_contents()) ob_end_clean();
  return $out;
}
add_shortcode('thb_slider', 'thb_slider');
