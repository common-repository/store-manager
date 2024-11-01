<?php
/**
 * Plugin Name: Store Manager
 * Plugin URI: http://designs.dirlik.nl
 * Description: Store manager with control over opening hours, location, images and much more.
 * Version: 1.0.2.2
 * Author: Simon Dirlik
 * Author URI: http://designs.dirlik.nl
 * Text Domain: store-manager
 * Domain Path: /lang
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */
 

	require_once plugin_dir_path( __FILE__ ) . 'includes/class-store-manager.php';
	
	$sm = new Store_Manager( plugin_dir_path( __FILE__ ) );
	
	add_action( 'init', array( $sm, 'init' ) );
	
	register_activation_hook( __FILE__, array( 'Store_Manager', 'activate' ) );
//	register_uninstall_hook( plugin_dir_path( __FILE__ ) . 'includes/class-store-manager.php', array( 'Store_Manager', 'uninstall' ) );

	add_action( 'admin_menu' , array( $sm, 'menu' ) );
	add_action( 'admin_print_scripts', array( $sm, 'scripts' ) );
	add_action( 'admin_print_styles', array( $sm, 'styles' ) );
	
	add_action( 'save_post', array ( $sm, 'save'));

	add_action('wp_ajax_sm_get_ll_by_q', array( $sm, 'get_ll_by_q' ) );
	add_action('wp_ajax_sm_get_supplier_options', array( $sm, 'get_supplier_options' ) );

	add_shortcode( 'store-manager-form', array( 'Store_Manager', 'form_shortcode' ) );

	add_filter( 'single_template', array( $sm, 'single_template' ) );
