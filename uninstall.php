<?php

	if ( current_user_can( 'activate_plugins' ) && defined( 'WP_UNINSTALL_PLUGIN' ) ) {
		global $wpdb;
		$table = $wpdb->prefix . 'store_lat_lng';
		$wpdb->query( "DELETE FROM $wpdb->posts WHERE ID IN (SELECT post_id FROM $table)" );
		$wpdb->query( "DELETE FROM $wpdb->postmeta WHERE post_id IN (SELECT post_id FROM $table)" );
		$wpdb->query( "DROP TABLE IF EXISTS $table" );
		delete_option( 'sm-options' );
		delete_option( 'sm-openstreetmap-options' );
		
	}
		
?>