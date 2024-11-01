<?php

class Store_Manager {
	
	protected static $instance;
	protected static $supplier;
	
	protected static $post_type = 'store';
	
	protected static $labels;
	
	/* paths */
	protected $base;
	protected $inc;
	
	protected $table;
	
	public function __construct($base_path) {
		global $wpdb;
		
		$this->base = $base_path;
		$this->inc = $base_path  . 'includes/';
		load_plugin_textdomain( 'store-manager', false, basename( $this->base ) . '/lang/' );
		
		$this->table = $wpdb->prefix . 'store_lat_lng';
		
		static::$instance = $this; // op deze manier heeft static::$instance altijd de laatst geinstantieerde object.
		
		require_once dirname(__FILE__) . '/class-map-supplier.php';
		static::$supplier = Map_Supplier::get();
		static::$labels = array(
			'address-r1'	=> __( 'Address', 'store-manager' ),
			'address-r2'	=> __( 'Address (extra line)', 'store-manager' ),
			'zipcode'		=> __( 'Zipcode', 'store-manager' ),
			'place'			=> __( 'Place', 'store-manager' ),
			'country'		=> __( 'Country', 'store-manager' ),
			'radius'		=> __( 'Radius', 'store-manager' ),
			'submit'		=> __( 'Submit', 'store-manager' ),

			'sunday'		=> __( 'Sunday', 'store-manager' ),
			'monday'		=> __( 'Monday', 'store-manager' ),
			'tuesday'		=> __( 'Tuesday', 'store-manager' ),
			'wednesday'		=> __( 'Wednesday', 'store-manager' ),
			'thursday'		=> __( 'Thursday', 'store-manager' ),
			'friday'		=> __( 'Friday', 'store-manager' ),
			'saturday'		=> __( 'Saturday', 'store-manager' ),

			'name'			=> __( 'Contact person', 'store-manager' ),
			'email'			=> __( 'Email address', 'store-manager' ),
			'phone'			=> __( 'Phone number', 'store-manager' ),
			'website'		=> __( 'Website', 'store-manager' ),

		);
	}
	
	public static function get_instance() {
		return static::$instance;
	}
	public static function get_supplier() {
		return static::$supplier;
	}
	public static function get_label( $key ) {
		return isset( static::$labels[ $key ] ) ? static::$labels[ $key ] : $key ;
	}
	
	/* hooks */
	
	public static function activate() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
		
		global $wpdb;
		$table = $wpdb->prefix . 'store_lat_lng';
		
		$charset_collate = '';
		if ( ! empty( $wpdb->charset ) ) {
		  $charset_collate = "DEFAULT CHARACTER SET {$wpdb->charset}";
		}
		if ( ! empty( $wpdb->collate ) ) {
		  $charset_collate .= " COLLATE {$wpdb->collate}";
		}

		$create_sql = "CREATE TABLE $table (
			post_id int(11) NOT NULL,
			lat float NOT NULL,
			lng float NOT NULL,
			UNIQUE KEY post_id (post_id)
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $create_sql );
		
		static::custom_post_type_and_role_registration();

		flush_rewrite_rules();

	}
/*
	// ook als je besluit toch de hook te gebruiken, niet deze code gebruiken.
	// pak die van uninstall.php, is beter
	public static function uninstall() {
		if ( ! current_user_can( 'activate_plugins' ) || __FILE__ != WP_UNINSTALL_PLUGIN ) {
			return;
		}
		
		global $wpdb;
		$wpdb->query( 'DROP TABLE IF EXISTS' . $wpdb->prefix . 'store_lat_lng' );
		
	}
*/
	protected static function custom_post_type_and_role_registration() {

		if ( ! post_type_exists( static::$post_type ) ) {
			
			$labels = array(
				'name'               => _x( 'Stores', 'Store type general name', 'store-manager' ),
				'singular_name'      => _x( 'Store', 'Store type singular name', 'store-manager' ),
				'menu_name'          => __( 'Stores', 'store-manager' ),
				'name_admin_bar'     => __( 'Store', 'store-manager' ),
				'add_new'            => __( 'Add New', 'store-manager' ),
				'add_new_item'       => __( 'Add New Store', 'store-manager' ),
				'new_item'           => __( 'New Store', 'store-manager' ),
				'edit_item'          => __( 'Edit Store', 'store-manager' ),
				'view_item'          => __( 'View Store', 'store-manager' ),
				'all_items'          => __( 'All Stores', 'store-manager' ),
				'search_items'       => __( 'Search Stores', 'store-manager' ),
				'parent_item_colon'  => __( 'Parent Stores:', 'store-manager' ),
				'not_found'          => __( 'No stores found.', 'store-manager' ),
				'not_found_in_trash' => __( 'No stores found in Trash.', 'store-manager' ),
			);

			$caps = array(
				'edit_post' => 'edit_store',
				'read_post' => 'read_store',
				'delete_post' => 'delete_store',
				'edit_posts' => 'edit_stores',
				'edit_others_posts' => 'edit_others_stores',
				'publish_posts' => 'publish_stores',
				'read_private_posts' => 'read_private_stores',
				'read' => 'read',
				'delete_posts' => 'delete_stores',
				'delete_private_posts' => 'delete_private_stores',
				'delete_published_posts' => 'delete_published_stores',
				'delete_others_posts' => 'delete_others_stores',
				'edit_private_posts' => 'edit_private_stores',
				'edit_published_posts' => 'edit_published_stores',
				'create_posts' => 'create_stores',
			);

			register_post_type( static::$post_type, array(
				'labels' => $labels,
				'public' => true,
				'publicly_queryable' => true,
				'show_ui' => true,
				'supports' => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments'/*, 'revisions'*/, 'page-attributes' ),
				'register_meta_box_cb' => array( 'Store_Manager', 'meta_boxes' ),
				'hierarchical' => true,
				'capability_type' => static::$post_type,
				'capabilities' => $caps,
				'map_meta_cap' => true,
				'rewrite' => array( 'slug' => 'store' ),
			) );
		}

		$sm_role = get_role( 'store-manager' );
		if( is_null( $sm_role ) ) {

			$admin_role = get_role( 'administrator' );

			foreach( $caps as $cap ) {
				$admin_role->add_cap( $cap );
			}

			$sm_caps = array(
				'edit_store' => true,
				'read_store' => true,
				'delete_store' => false,
				'edit_stores' => true,
				'edit_others_stores' => false,
				'publish_stores' => false,
				'read_private_stores' => true,
				'read' => true,
				'delete_stores' => false,
				'delete_private_stores' => false,
				'delete_published_stores' => false,
				'delete_others_stores' => false,
				'edit_private_stores' => true,
				'edit_published_stores' => true,
				'create_stores' => true,
			);

			add_role( 'store-manager', __( 'Store Manager', 'store-manager'), $sm_caps );
		}
	}
	
	public function init() {
		global $post;
			
		static::custom_post_type_and_role_registration();

		//add_filter( 'post_row_actions', array( $this, 'row_actions' ), 10, 2 );
		add_action( 'admin_footer', array( $this, 'footer_javascript' ) );

	}
	
	public function menu() {
		add_submenu_page('edit.php?post_type=' . static::$post_type, 'Options', __( 'Options', 'store-manager' ), 'manage_options', 'sm-options', array($this, 'options') );
	}

	public function save( $post_id ) {
		global $post;
		
		if( isset( $_POST['address-r1'], $_POST['address-r2'], $_POST['zipcode'], $_POST['place'], $_POST['country'] ) ) {
			$address = array(
				'address-r1' => $_POST['address-r1'],
				'address-r2' => $_POST['address-r2'],
				'zipcode' => $_POST['zipcode'],
				'place' => $_POST['place'],
				'country' => $_POST['country'],
			);
			update_post_meta( $post_id, 'sm-address', json_encode( $address, JSON_HEX_QUOT ) );
			
			if( $post && $post->post_type == static::$post_type ) {
				
				$q = $_POST['address-r1'] . ' ' . $_POST['zipcode'] . ' ' . $_POST['place'] . ' ' . $_POST['country'];
				$ll = static::$supplier->get_lat_lng_by_q( $q );
				if( $ll ) {
					global $wpdb;
					$wpdb->replace( $this->table, array( 'post_id' => $post_id, 'lat' => $ll[0], 'lng' => $ll[1] ), array( '%d', '%f', '%f' ) );
				}
				
			}
		}
		
		if( isset( $_POST['sunday'], $_POST['monday'], $_POST['tuesday'], $_POST['wednesday'], $_POST['thursday'], $_POST['friday'], $_POST['saturday'] ) ) {
			$openinghours = array(
				'sunday' => $_POST['sunday'],
				'monday' => $_POST['monday'],
				'tuesday' => $_POST['tuesday'],
				'wednesday' => $_POST['wednesday'],
				'thursday' => $_POST['thursday'],
				'friday' => $_POST['friday'],
				'saturday' => $_POST['saturday'],
			);
			update_post_meta( $post_id, 'sm-openinghours', json_encode( $openinghours, JSON_HEX_QUOT ) );
		}
		
		if( isset( $_POST['name'], $_POST['email'], $_POST['phone'], $_POST['website'] ) ) {
			$contactinfo = array(
				'name' => $_POST['name'],
				'email' => $_POST['email'],
				'phone' => $_POST['phone'],
				'website' => $_POST['website'],
			);
			update_post_meta( $post_id, 'sm-contactinfo', json_encode( $contactinfo, JSON_HEX_QUOT ) );
		}

		if( isset( $_POST['gallery-ids'] ) ) {
			update_post_meta( $post_id, 'sm-gallery-ids', $_POST['gallery-ids'] );
		}
		
	}

	public function styles() {
		global $post;
		if( ! $post || $post->post_type != static::$post_type ) {
			return;
		}
		wp_register_style( 'sm_css', plugins_url( 'style.css', $this->base . 'style.css' ) );
		wp_enqueue_style( 'sm_css' );
	}

	public function scripts() {
		global $post;
		if( ! $post || $post->post_type != static::$post_type ) {
			return;
		}
		
		wp_enqueue_script( 'jquery' );
	}
	
	public static function meta_boxes() {
//		$this->get_meta();

		if( current_user_can( 'administrator' ) ) {

			remove_meta_box( 'authordiv', static::$post_type, 'normal' );
			add_meta_box( 'authordiv', 'Author', function() {
				global $post;

				$user = wp_get_current_user();
				$users = get_users( array( 'role' => 'store-manager' ) );
?>
				<select class="" id="post_author_override" name="post_author_override">
					<option value="<?php echo $user->ID; ?>"><?php echo $user->user_nicename; ?></option>
<?php
			foreach ( $users as $u ) {
				echo '<option value="' . $u->ID . '"' . ( $u->ID == $post->post_author ? ' selected="selected"' : '' ) . '>' . $u->user_nicename . '</option>';
			}
?>
				</select>
<?php
			});
		}

		add_meta_box( 'gallery', __( 'Photo gallery', 'store-manager' ), array( 'Store_Manager', 'gallery' ) );
		add_meta_box( 'contactinfo', __( 'Contact Information', 'store-manager' ), array( 'Store_Manager', 'contact_info' ) );
		add_meta_box( 'address', __( 'Address', 'store-manager' ), array( 'Store_Manager', 'address' ) );
		add_meta_box( 'openinghours', __( 'Opening Hours', 'store-manager' ), array( 'Store_Manager', 'opening_hours' ) );
	}
	
	
	/* end hooks */
	
	
	/* Page views */
	
	public function map( $attr = array() ) {
		global $post, $wpdb;
		$r = $wpdb->get_row( "SELECT lat,lng FROM $this->table WHERE post_id = $post->ID" );
		if ( $r ) {
			return static::$supplier->map( $r->lat, $r->lng, $attr );
		} else {
			return false;
		}
	}
	public function single_template( $single_template ) {
		global $post;
		if ( $post->post_type == static::$post_type ) {
			$single = 'single-' . static::$post_type . '.php';
			
			if( ! file_exists( get_stylesheet_directory() . '/' . $single ) ) {
				$single_template = $this->inc . "/template-$single";
			}
			
		}
		return $single_template;
	}
	
	public static function gallery() {
		global $post;
		$ids = get_post_meta( $post->ID, 'sm-gallery-ids', true );
		if( empty( $ids ) ) {
			$ids = '';
			$ids_array = array();
		} else {
			$ids_array = explode( ',', $ids );
		}
		$thumb_string = '';
		foreach ($ids_array as $id) {
			$thumb_string .= wp_get_attachment_image( $id, 'thumbnail' );
		}
?>
		<input type="hidden" id="sm-gallery-ids" name="gallery-ids" value="<?php echo $ids; ?>" />
		<div id="sm-gallery">
			<?php echo $thumb_string; ?>
		</div>
		<button class="button sm-gallery-button" type="button"><?php _e( 'Edit photo gallery', 'store-manager' ); ?></button>
		<script type="text/javascript">
			(function( $ ) {
				$( '.sm-gallery-button' ).on( 'click', function( e ) {
					var frame, image;

					frame = wp.media({
						title: 'Upload Image',
						multiple: true
					});

					frame.on( 'open', function( e ) {
						var ids = $( '#sm-gallery-ids' ).val().split( ',' ),
							selection = frame.state().get('selection'), attachment;
							$( ids ).each( function( k, v ) {
								if ( v.trim() == '' ) {
									return;
								}
								attachment = wp.media.attachment( v );
								attachment.fetch();
								selection.add( attachment ? [ attachment ] : [] );
							});
					});
					
					image = frame.open().on( 'select', function( e ) {
						var sImages = image.state().get('selection').toArray(), html = '', ids = '', i;
						for ( i = 0; i < sImages.length; i++ ) {
							html += '<img alt="' + sImages[ i ].attributes.title + '" src="' + sImages[ i ].attributes.sizes.thumbnail.url + '" />';
							ids += ',' + sImages[ i ].attributes.id;
						}
						$( '#sm-gallery' ).html( html );
						$( '#sm-gallery-ids' ).val( ids.substr(1) );
					});
				});

			})( jQuery );
		</script>
<?php
	}
	public static function address() {
		global $post;
		$meta = get_post_meta( $post->ID, 'sm-address', true );
		if( $meta ) {
			$meta = json_decode( $meta, true );
		} else {
			$meta = array();
		}
		
		$address_r1 = isset( $meta['address-r1'] ) ? $meta['address-r1'] : '' ;
		$address_r2 = isset( $meta['address-r2'] ) ? $meta['address-r2'] : '' ;
		$zipcode = isset( $meta['zipcode'] ) ? $meta['zipcode'] : '' ;
		$place = isset( $meta['place'] ) ? $meta['place'] : '' ;
		$country = isset( $meta['country'] ) ? $meta['country'] : '' ;
		
?>
		
		<div class="field-title"><label for="address-r1"><?php echo static::get_label( 'address-r1' ) ?></label></div>
		<div class="field-item"><input type="text" value="<?php echo $address_r1; ?>" name="address-r1" id="sm-address-r1" /></div>
		
		<div class="field-title"><label for="address-r2"><?php echo static::get_label( 'address-r2' ); ?></label></div>
		<div class="field-item"><input type="text" value="<?php echo $address_r2; ?>" name="address-r2" id="sm-address-r2" /></div>
		
		<div class="field-title"><label for="zipcode"><?php echo static::get_label( 'zipcode' ) ?></label></div>
		<div class="field-item"><input type="text" value="<?php echo $zipcode; ?>" name="zipcode" id="sm-zipcode" /></div>
		
		<div class="field-title"><label for="place"><?php echo static::get_label( 'place' ) ?></label></div>
		<div class="field-item"><input type="text" value="<?php echo $place; ?>" name="place" id="sm-place" /></div>
		
		<div class="field-title"><label for="country"><?php echo static::get_label( 'country' ) ?></label></div>
		<div class="field-item"><input type="text" value="<?php echo $country; ?>" name="country" id="sm-country" /></div>
		
		<a href="javascript:;" class="sm-get-ll-by-q"><?php _e( 'Check address at your geocoding provider', 'store-manager' ); ?></a>
		<div class="sm-get-ll-by-q-result"></div>
		
<?php
	}
	
	public static function opening_hours() {
		global $post;
		$meta = get_post_meta( $post->ID, 'sm-openinghours', true );
		if( $meta ) {
			$meta = json_decode( $meta, true );
		} else {
			$meta = array();
		}
		
		$sunday = isset( $meta['sunday'] ) && is_array( $meta['sunday'] ) ? $meta['sunday'] : array() ;
		$monday = isset( $meta['monday'] ) && is_array( $meta['monday'] ) ? $meta['monday'] : array() ;
		$tuesday = isset( $meta['tuesday'] ) && is_array( $meta['tuesday'] ) ? $meta['tuesday'] : array() ;
		$wednesday = isset( $meta['wednesday'] ) && is_array( $meta['wednesday'] ) ? $meta['wednesday'] : array() ;
		$thursday = isset( $meta['thursday'] ) && is_array( $meta['thursday'] ) ? $meta['thursday'] : array() ;
		$friday = isset( $meta['friday'] ) && is_array( $meta['friday'] ) ? $meta['friday'] : array() ;
		$saturday = isset( $meta['saturday'] ) && is_array( $meta['saturday'] ) ? $meta['saturday'] : array() ;
		
?>
		
		<div class="field-title"><label for="sunday"><?php echo static::get_label( 'sunday' ); ?></label></div>
		<div class="field-item"><input type="hidden" data-times="<?php echo str_replace('"', '&quot;', json_encode( $sunday ) ); ?>" name="sunday" id="sm-sunday" /><a href="javascript:;" data="0" class="add-time-slot button-secondary"><?php _e( 'Add Time slot', 'store-manager' ); ?></a></div>
		
		<div class="field-title"><label for="monday"><?php echo static::get_label( 'monday' ); ?></label></div>
		<div class="field-item"><input type="hidden" data-times="<?php echo str_replace('"', '&quot;', json_encode( $monday ) ); ?>" name="monday" id="sm-monday" /><a href="javascript:;" data="0" class="add-time-slot button-secondary"><?php _e( 'Add Time slot', 'store-manager' ); ?></a></div>
		
		<div class="field-title"><label for="tuesday"><?php echo static::get_label( 'tuesday' ); ?></label></div>
		<div class="field-item"><input type="hidden" data-times="<?php echo str_replace('"', '&quot;', json_encode( $tuesday ) ); ?>" name="tuesday" id="sm-tuesday" /><a href="javascript:;" data="0" class="add-time-slot button-secondary"><?php _e( 'Add Time slot', 'store-manager' ); ?></a></div>
		
		<div class="field-title"><label for="wednesday"><?php echo static::get_label( 'wednesday' ); ?></label></div>
		<div class="field-item"><input type="hidden" data-times="<?php echo str_replace('"', '&quot;', json_encode( $wednesday ) ); ?>" name="wednesday" id="sm-wednesday" /><a href="javascript:;" data="0" class="add-time-slot button-secondary"><?php _e( 'Add Time slot', 'store-manager' ); ?></a></div>
		
		<div class="field-title"><label for="thursday"><?php echo static::get_label( 'thursday' ); ?></label></div>
		<div class="field-item"><input type="hidden" data-times="<?php echo str_replace('"', '&quot;', json_encode( $thursday ) ); ?>" name="thursday" id="sm-thursday" /><a href="javascript:;" data="0" class="add-time-slot button-secondary"><?php _e( 'Add Time slot', 'store-manager' ); ?></a></div>
		
		<div class="field-title"><label for="friday"><?php echo static::get_label( 'friday' ); ?></label></div>
		<div class="field-item"><input type="hidden" data-times="<?php echo str_replace('"', '&quot;', json_encode( $friday ) ); ?>" name="friday" id="sm-friday" /><a href="javascript:;" data="0" class="add-time-slot button-secondary"><?php _e( 'Add Time slot', 'store-manager' ); ?></a></div>
		
		<div class="field-title"><label for="saturday"><?php echo static::get_label( 'saturday' ); ?></label></div>
		<div class="field-item"><input type="hidden" data-times="<?php echo str_replace('"', '&quot;', json_encode( $saturday ) ); ?>" name="saturday" id="sm-saturday" /><a href="javascript:;" data="0" class="add-time-slot button-secondary"><?php _e( 'Add Time slot', 'store-manager' ); ?></a></div>
		
<?php
	}
	public static function contact_info() {
		global $post;
		$meta = get_post_meta( $post->ID, 'sm-contactinfo', true );
		if( $meta ) {
			$meta = json_decode( $meta, true );
		} else {
			$meta = array();
		}
		
		$name = isset( $meta['name'] ) ? $meta['name'] : '' ;
		$email = isset( $meta['email'] ) ? $meta['email'] : '' ;
		$phone = isset( $meta['phone'] ) ? $meta['phone'] : '' ;
		$website = isset( $meta['website'] ) ? $meta['website'] : '' ;
		
?>
		<div class="field-title"><label for="name"><?php echo static::get_label( 'name' ); ?></label></div>
		<div class="field-item"><input type="text" value="<?php echo $name; ?>" name="name" id="sm-name" /></div>

		<div class="field-title"><label for="email"><?php echo static::get_label( 'email' ); ?></label></div>
		<div class="field-item"><input type="text" value="<?php echo $email; ?>" name="email" id="sm-email" /></div>

		<div class="field-title"><label for="phone"><?php echo static::get_label( 'phone' ); ?></label></div>
		<div class="field-item"><input type="text" value="<?php echo $phone; ?>" name="phone" id="sm-phone" /></div>

		<div class="field-title"><label for="website"><?php echo static::get_label( 'website' ); ?></label></div>
		<div class="field-item"><input type="text" value="<?php echo $website; ?>" name="website" id="sm-website" /></div>
<?php
	}
	
	public function options() {
		
		$options = json_decode( get_option( 'sm-options', '{"supplier":"openstreetmap"}' ), true );
		$update = false;

		if( isset( $_POST['supplier'] ) && in_array($_POST['supplier'], Map_Supplier::$suppliers) ) {
			$options['supplier'] = $_POST['supplier'];
			$update = true;
		}

		if( isset( $_POST['country'] ) && strlen( $_POST['country'] ) <= 2 ) {
			$options['country'] = $_POST['country'];
			$update = true;
		}

		if( isset( $_POST['unit'] ) ) {
			$options['unit'] = ! empty( $_POST['unit'] );
			$update = true;
		}

		if( $update ) {
			update_option( 'sm-options', json_encode( $options ) );
		}


		$supplier_options = Map_Supplier::get_options( $options['supplier'] );
		if( isset( $_POST['options'] ) && is_array( $_POST['options'] ) ) {

			$new_options = array();

			foreach( $supplier_options as $key => $value ) {
				if( isset( $_POST['options'][ $key ] ) && ! empty( $_POST['options'][ $key ] ) ) {
					$new_options[ $key ] = $_POST['options'][ $key ];
				} else {
					$new_options[ $key ] = $value['value'];
				}
			}
			
			update_option( 'sm-' . $options['supplier'] . '-options', json_encode( $new_options ) );
		}


		
?>
		<div class="wrap">
		<h1><?php _e( 'Store Manager Options', 'store-manager' ); ?></h1>
			<form method="POST" action="">
				<table class="form-table">
					<tr>
						<th scope="row"><label for="country"><?php _e( 'Country', 'store-manager' ); ?></label></th>
						<td>
							<input id="sm-country" type="text" class="regular-text" name="country" value="<?php echo isset( $options['country'] ) ? $options['country'] : '' ; ?>" />
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="unit"><?php _e( 'Unit', 'store-manager' ); ?></label></th>
						<td>
							<select id="sm-unit" name="unit">
								<option value="0"><?php _e( 'Kilometres', 'store-manager' ); ?></option>
								<option value="1"<?php if( isset( $options['unit'] ) && $options['unit'] ) { echo 'selected="selected"'; } ?>><?php _e( 'Miles', 'store-manager' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="supplier"><?php _e( 'Geolocation provider', 'store-manager' ); ?></label></th>
						<td>
							<select id="sm-key" name="supplier">
<?php
								foreach( Map_Supplier::$suppliers as $s ) {
									echo '<option value="' . $s . '"' . ( $s == $options['supplier'] ? ' selected="selected"' : '' ) . '>' . $s . '</option>';
								}
?>
							</select>
						</td>
					</tr>
				</table>

				<p class="submit"><input type="submit" value="<?php _e( 'Save Changes', 'store-manager' ); ?>" class="button button-primary" id="submit" name="submit"></p>
			</form>
		</div>
		<script type="text/javascript">
			( function( $ ) {
				$( '#sm-key' ).on( 'change', function( e ) {
					var data = {
						'action': 'sm_get_supplier_options',
						'supplier': this.value,
					};
					$.post( ajaxurl, data, function( response ) {
						
						if( response.error ) {
							alert( response.error );
						} else {

							$('.form-table .dynamic').remove();
							var html = '', k;

							//$( response ).each( function( k, v ) {
							for( k in response ) {
								html += '<tr class="dynamic"><th scope="row"><label for="' + k + '">' + response[ k ]['label'] + '</label></th><td><input type="text" value="' + response[ k ]['value'] + '" id="sm-' + k + '" name="options[' + k + ']" class="regular-text"></td></tr>';
							}
							//});

							$('.form-table tr:last').after( html );
						}
						
					}, 'json' );
				});

				$( document ).ready( function() { $( '#sm-key' ).trigger( 'change' ); } );
			})( jQuery );
		</script>
<?php
	}
	
	
	function footer_javascript() {
		global $post;
		if( ! $post || $post->post_type != static::$post_type ) {
			return;
		}
?>
		<script type="text/javascript">
			( function( $ ) {
				
				$( '.sm-get-ll-by-q' ).on( 'click', function( e ) {
					var data = {
						'action': 'sm_get_ll_by_q',
						'address' : $( '#address input[name="address-r1"]' ).val(),
						'zipcode' : $( '#address input[name="zipcode"]' ).val(),
						'place' : $( '#address input[name="place"]' ).val(),
						'country' : $( '#address input[name="country"]' ).val(),
					};
					$.post( ajaxurl, data, function( response ) {
						
						if( response.error ) {
							alert( response.error );
						} else {
							$( '.sm-get-ll-by-q-result' ).html( '<h3>lat : ' + response[0] + ' - lng : ' + response[1] + '</h3><a target="_blank" href="' + response[2] + '"><?php _e( 'check coordinates on the map', 'store-manager' ); ?></a>' );
						}
						
					}, 'json' );
				});
				
				$( '#openinghours' ).on( 'click', function( e ) {
					if( $( e.target ).is( '.remove-time-slot' ) ) {
						$( e.target ).parent().remove();
					} else if( $( e.target ).is( '.add-time-slot' ) ) {
						var name = e.target.parentNode.firstElementChild.name,
							counter = parseInt( $( e.target ).attr( 'data' ) );
						$( e.target ).before( '<div><input type="text" name="' + name + '[' + counter + '][start]" /><input type="text" name="' + name + '[' + counter + '][end]" /><a href="javascript:;" class="remove-time-slot">x</a></div>' );
						$( e.target ).attr( 'data', counter + 1 )
					}
				});
				
				$( document ).ready( function() {
					$( '#openinghours input[type="hidden"]' ).each( function( k1, v1 ) {
						var ohs = JSON.parse( $( v1 ).attr( 'data-times' ) ),
							ats = $( v1 ).next( '.add-time-slot' )[0],
							counter, nb;
						
						$( v1 ).val('');
						
						$( ohs ).each( function( k2, v2 ) {
							counter = $( ats ).attr( 'data' );
							ats.click();
							nb = $( v1 ).attr( 'name' ) + '[' + counter + ']';
							$( v1 ).parent().find( 'input[name="' + nb + '[start]"]' ).val( v2.start );
							$( v1 ).parent().find( 'input[name="' + nb + '[end]"]' ).val( v2.end );
						});
					});
					
				});
				
			} )( jQuery );
			
		</script>
<?php
	}
	public static function form_results( $stores, $lat = false, $lng = false, $radius = 10 ) {
		if( ! isset( $stores ) || empty( $stores ) ) {
			return __( 'Nothing found', 'store-manager' );
		}
		
		$options = json_decode( get_option( 'sm-options' ), true );

		$out = '';

		if( $lat !== false && $lng !== false ) {
			$out .= static::$supplier->map( $lat, $lng, array( 'center' => 'circle', 'radius' => $radius, 'width' => '100%', 'height' => '300px', 'markers' => array_map( function( $s ) { return array( 'events' => array( 'mouseover' => "document.querySelector('.store-result-item.$s->post_name').className += ' hover'", 'mouseout' => "var d = document.querySelector('.store-result-item.$s->post_name');d.className = d.className.replace(' hover', '');" ), 'll' => array( $s->lat, $s->lng ), 'name' => $s->post_name, 'title' => $s->post_title ); }, $stores ) ) );
		}
		
		$out .= '<div class="sm-form-results">';
		foreach( $stores as $i => $store ) {
			$class = "store-result-item store-result-$i $store->post_name " . ( ( $i % 2 ) == 0 ? 'even' : 'odd' );
			$out .= '<div class="' . $class . '">' .
			'<a href="' . get_the_permalink( $store->ID ) . '">' .
			get_the_post_thumbnail( $store->ID, 'medium', array( 'style' => 'float:right;' ) ) .
			'<span class="post-title">' . $store->post_title . ' (' . ( number_format( $store->distance, 3, ',', '.' ) ) . ( isset( $options['unit'] ) && $options['unit'] ? 'mi' : 'km' ) . ')</span>' .
			'<span class="address">' . Store_Manager::get_the_formatted_address( $store ) . '</span>' .
			'<span class="excerpt">' . $store->post_excerpt . '</span>' .
			'</a></div>';
		}
		$out .= '</div>';
		return $out;
	}
	
	/* End Page views */
	
	
	/* Ajax callbacks */
	
	public function get_supplier_options() {
//		$s = static::$supplier;
		if( isset( $_POST['supplier'] ) && in_array( $_POST['supplier'], Map_Supplier::$suppliers ) ) {
			echo json_encode( Map_Supplier::get_options( $_POST['supplier'] ) );
		} else {
			echo '{"error":"' . __( 'Could not find supplier', 'store-manager' ) . '"}';
		}

		wp_die();
	}

	public function get_ll_by_q() {
		if( isset( $_POST['address'], $_POST['zipcode'], $_POST['place'], $_POST['country'] ) ) {
			
			$q = $_POST['address'] . ' ' . $_POST['zipcode'] . ' ' . $_POST['place'] . ' ' . $_POST['country'];
			$ll = static::$supplier->get_lat_lng_by_q( $q );
			if ( $ll ) {
				array_push( $ll, static::$supplier->get_url( $ll ) );
			} else {
				$ll = array(
					'error' => __( 'Could not find coordinates for this address', 'store-manager' ),
				);
			}
			
		} else {
			$ll = array(
				'error' => __( 'Incorrect input', 'store-manager' ),
			);
		}
		
		echo json_encode( $ll );
		wp_die();
		
	}
	
	
	/* End Ajax callbacks */
	
	
	
	
	/* get stores by ... functions */
	
	public function get_stores_by_zipcode( $zipcode, $radius = 10 ) {
		$ll = static::$supplier->get_lat_lng_by_zipcode( $zipcode );
		return $ll ? array( $ll[0], $ll[1], $this->get_stores( $ll[0], $ll[1], $radius ) ) : false ;
	}
	
	public function get_stores_by_q( $q, $radius = 10 ) {
		$ll = static::$supplier->get_lat_lng_by_q( $q );
		return $ll ? array( $ll[0], $ll[1], $this->get_stores( $ll[0], $ll[1], $radius ) ) : false ;
	}
	
	public function get_stores( $lat, $lng, $radius = 10 ) {
		global $wpdb;
		
		$options = json_decode( get_option( 'sm-options' ), true );

		$rr = pow( $radius, 2 );
		$d = sqrt( $rr + $rr );

		$R = isset( $options['unit'] ) && $options['unit'] ? 3959 : 6371;
		$R2 = 2 * $R;
		$ad = $d / $R;
		$sin_ad = sin( $ad );
		$cos_ad = cos( $ad );
		
		$lat_deg = floatval( $lat );
		$lng_deg = floatval( $lng );
		
		$lat = deg2rad( $lat_deg );
		$lng = deg2rad( $lng_deg );

		$sin_lat = sin( $lat );
		$cos_lat = cos( $lat );
		$sin_lng = sin( $lng );
		$cos_lng = cos( $lng );

		$lba = deg2rad( 315 );
		$roa = deg2rad( 135 );

		$t1 = $sin_lat * $cos_ad;
		$t2 = $cos_lat * $sin_ad;
		$t3 = $sin_ad * $cos_lat;
		$lb_lat_rad = asin( $t1 + $t2 * cos( $lba ) );
		$lb_lat = rad2deg( $lb_lat_rad );
		$lb_lng = rad2deg( $lng + atan2( sin( $lba ) * $t3 , $cos_ad - $sin_lat * sin( $lb_lat_rad ) ) );
		
		$ro_lat_rad = asin( $t1 + $t2 * cos( $roa ) );
		$ro_lat = rad2deg( $ro_lat_rad );
		$ro_lng = rad2deg( $lng + atan2( sin( $roa ) * $t3 , $cos_ad - $sin_lat * sin( $ro_lat_rad ) ) );

		$results = $wpdb->get_results( "SELECT post_id, lat, lng,
										(@sd1:=(SIN(RADIANS(lat-$lat_deg)/2))),
										(@sd2:=(SIN(RADIANS(lng-$lng_deg)/2))),
										(@a:=(@sd1 * @sd1 + COS($lat) * COS(RADIANS(lat)) * @sd2 * @sd2)),
										($R2 * ATAN2(SQRT(@a), SQRT(1-@a))) as d
										FROM $this->table
										WHERE lat<" . number_format( $lb_lat, 6, '.', '' ) .
										" AND lat>" . number_format( $ro_lat, 6, '.', '' ) .
										" AND lng>" . number_format( $lb_lng, 6, '.', '' ) .
										" AND lng<" . number_format( $ro_lng, 6, '.', '' ) .
										" GROUP BY d HAVING d<$radius ORDER BY d ASC", OBJECT_K );
		if( $results ) {
			
			$stores = get_posts( array(
				'post_type' => static::$post_type,
				'include' => array_map( function( $r ) { return $r->post_id; }, $results ),
			) );

			foreach( $stores as $s ) {
				$s->distance = $results[ $s->ID ]->d;
				$s->lat = $results[ $s->ID ]->lat;
				$s->lng = $results[ $s->ID ]->lng;
			}
			return $stores;
			
		}
		return false;
	}
	
	/* End get stores by ... functions */
	
	/* shortcodes */
	
	public static function form_shortcode( $atts ) {
/*
		$options = json_decode( get_option( 'sm-options' ), true );

		if( isset( $options['country'] ) && ! empty( $options['country'] ) ) {
			$country_type = 'hidden';
			$country_value = $options['country'];
		} else {
			$country_type = false;
			$country_value = '';
		}
*/
		$out = '<form method="POST" action=""><input type="hidden" value="search" name="store-manager-action" />';
		
		$atts = shortcode_atts( array(
			'address-r1'				=> false,
			'address-r2'				=> false,
			'zipcode'					=> false,
			'place'						=> false,
			'country'					=> false,
			'radius'					=> false,
			
			'address-r1-value'			=> '',
			'address-r2-value'			=> '',
			'zipcode-value'				=> '',
			'place-value'				=> '',
			'country-value'				=> '',
			'radius-value'				=> '',
			
			'address-r1-placeholder'	=> static::get_label( 'address-r1' ),
			'address-r2-placeholder'	=> static::get_label( 'address-r2' ),
			'zipcode-placeholder'		=> static::get_label( 'zipcode' ),
			'place-placeholder'			=> static::get_label( 'place' ),
			'country-placeholder'		=> static::get_label( 'country' ),
			'radius-placeholder'		=> static::get_label( 'radius' ),
			
			'address-r1-label'			=> static::get_label( 'address-r1' ),
			'address-r2-label'			=> static::get_label( 'address-r2' ),
			'zipcode-label'				=> static::get_label( 'zipcode' ),
			'place-label'				=> static::get_label( 'place' ),
			'country-label'				=> static::get_label( 'country' ),
			'radius-label'				=> static::get_label( 'radius' ),
/*
			'address-r1'				=> ,
			'address-r2'				=> ,
			'zipcode'					=> ,
			'place'						=> ,
			'country'					=> ,
*/
			'submit'					=> __( 'Submit', 'store-manager' ),
			
			
		), $atts );
		
		
		$keys = array(
			'address-r1',
			'address-r2',
			'zipcode',
			'place',
			'country',
			'radius',
		);
		
		$lat = false; $lng = false;
		$stores = false;
		$radius = 10;
		
		if( isset( $_POST['store-manager-action'] ) && $_POST['store-manager-action'] == 'search' ) {
			
			$zipcode = true; // standaard de get_by_zipcode methode gebruiken
			$q = '';
			
			foreach( $keys as $k ) {
				if( isset( $_POST[ $k ] ) && $_POST[ $k ] != '' ) {
					if( $k != 'zipcode' ) {
						$zipcode = false;
					}
					
					if( $k == 'radius' ) {
						$radius = max( 1, intval( $_POST['radius'] ) );
					} else {
						$q .= ' ' . $_POST[ $k ];
					}
					
					$atts[ $k . '-value' ] = $_POST[ $k ];
				}
			}
			
			$q = substr( $q, 1 );

			if( $zipcode ) {
				list($lat, $lng, $stores) = static::$instance->get_stores_by_zipcode( $q, $radius );
			} else {
				list($lat, $lng, $stores) = static::$instance->get_stores_by_q( $q, $radius );
			}
			
		}
		
		$empty = true;
		foreach( $keys as $k ) {
			$empty &= $atts[ $k ] === false;
		}
		
		if( $empty ) {
			$atts['zipcode'] = 'text';
		}
		
		foreach( $keys as $k ) {
			if( $atts[ $k ] ) {

				if( $atts[ $k ] != 'hidden' && $atts[ $k . '-label' ] ) {
					$out .= '<label for="' . $k . '">' . $atts[ $k . '-label' ] . '</label>';
				}
				
				$out .= '<input type="' . $atts[ $k ] . '" value="' . $atts[ $k . '-value' ] . '" placeholder="' . $atts[ $k . '-placeholder' ] . '" name="' . $k . '" id="sm-' . $k . '" />';
				
			}
		}
		
		if( $atts['submit'] ) {
			$out .= '<input type="submit" value="' . $atts['submit'] . '" />';
		}
		
		return $out . '</form>' . static::form_results( $stores, $lat, $lng, $radius );
		
		
		
	}
	
	/* End shortcodes */
	
	
	/* Helper functions */
	
	public static function get_the_formatted_address( $store ) {
		$meta = get_post_meta( $store->ID, 'sm-address', true );
		if( $meta ) {
			$meta = json_decode( $meta, true );
		} else {
			$meta = array();
		}
		if( empty( $meta ) ) {
			return '';
		}
		
		$out = '';
		$out .= isset( $meta['address-r1'] ) && ! empty( $meta['address-r1'] ) ? '<span class="address-r1">' . $meta['address-r1'] . '</span>' : '' ;
		$out .= isset( $meta['address-r2'] ) && ! empty( $meta['address-r2'] ) ? '<span class="address-r2">' . $meta['address-r2'] . '</span>' : '' ;
		$out .= isset( $meta['zipcode'] ) && ! empty( $meta['zipcode'] ) ? '<span class="zipcode">' . $meta['zipcode'] . '</span>' : '' ;
		$out .= isset( $meta['place'] ) && ! empty( $meta['place'] ) ? '<span class="place">' . $meta['place'] . '</span>' : '' ;
		$out .= isset( $meta['country'] ) && ! empty( $meta['country'] ) ? '<span class="country">' . $meta['country'] . '</span>' : '' ;
		
		return $out;
	}
	public static function the_formatted_address( $store ) {
		echo static::get_the_formatted_address( $store );
	}
	
	/* End Helper functions */

}
