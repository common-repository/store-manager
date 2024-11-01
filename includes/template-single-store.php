<?php get_header(); ?>

<div id="main-content" class="main-content">
	<div id="primary" class="content-area">
		<div id="content" class="site-content" role="main">

			<?php
				// Start the Loop.
				while ( have_posts() ) : the_post();

					// Include the page content template.
					get_template_part( 'content', 'page' );
					
					// print map
					echo Store_Manager::get_instance()->map( array(
						'zoom' => 12,
						'width' => '100%',
						'height' => '200px',
						'center' => 'marker', // possible values are marker , circle , both *
						'markers' => array( ),
						//'radius' => 18,
					) );
					
					// * marker puts a marker at the center of the map.
					// * circle will draw a circle at the center of the map, but only if a radius is given.
					// * both will do both.


					$meta = get_post_custom();

					if( isset( $meta['sm-contactinfo'] ) ) {
						$contact_info = json_decode( $meta['sm-contactinfo'][0], true );
						echo '<h3>' . __( 'Contact Information', 'store-manager' ) . '</h3>';
						foreach( $contact_info as $key => $value ) {
							echo '<label>' . Store_Manager::get_label( $key ) . '</label> : <span>' . $value . '</span><br />';
						}
					}

					if( isset( $meta['sm-address'] ) ) {
						$address = json_decode( $meta['sm-address'][0], true );
						echo '<h3>' . __( 'Address', 'store-manager' ) . '</h3>';
						foreach( $address as $key => $value ) {
							echo '<label>' . Store_Manager::get_label( $key ) . '</label> : <span>' . $value . '</span><br />';
						}
					}
					
					if( isset( $meta['sm-openinghours'] ) ) {
						
						$opening_hours = json_decode( $meta['sm-openinghours'][0], true );
						echo '<h3>' . __( 'Opening Hours', 'store-manager' ) . '</h3>';
						foreach( $opening_hours as $key => $value ) {
							echo '<label>' . Store_Manager::get_label( $key ) . '</label> : ';
							if( is_array( $value ) ) {
								
								foreach( $value as $period ) {
									echo '<span>' . $period['start'] . '-' . $period['end'] . '</span>';
								}
								
							}
							echo '<br />';
						}
						
					}
					
					if( isset( $meta['sm-gallery-ids'] ) ) {
						echo do_shortcode( '[gallery ids=' . $meta['sm-gallery-ids'][0] . ']' );
					}
					// end meta values

					echo '<a href="mailto:' . get_the_author_meta( 'user_email' ) . '" title="' . get_the_author() . '">' . __( 'contact page manager', 'store-manager' ) . '</a>';

					// If comments are open or we have at least one comment, load up the comment template.
					if ( comments_open() || get_comments_number() ) {
						comments_template();
					}
				endwhile;
			?>

		</div><!-- #content -->
	</div><!-- #primary -->
	<?php get_sidebar( 'content' ); ?>
</div><!-- #main-content -->

<?php
	get_sidebar();
	get_footer();
?>