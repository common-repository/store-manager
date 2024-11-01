<?php

class openstreetmap extends Map_Supplier {

	private $local_options; // get_options doet altijd request naar DB, dus voor het begin even opslaan

	public static function get_options( $supplier = '' ) {
		return parent::get_options('openstreetmap');
	}

	private function parse_server_result_string( $result_string ) {
		
		$result_array = json_decode( $result_string, true );
		if( empty( $result_array ) ) {
			return false;
		} else {
			return array( $result_array[0]['lat'], $result_array[0]['lon'] );
		}
		
	}
	
	public function map( $lat, $lng, $attr = array() ) {
		wp_register_script( 'leafletjs', plugins_url( 'leaflet/leaflet.js', dirname(__FILE__) ) );
		wp_enqueue_script( 'leafletjs' );
		
		wp_register_style( 'leafletcss', plugins_url( 'leaflet/leaflet.css', dirname(__FILE__) ) );
		wp_enqueue_style( 'leafletcss' );
		
		$zoom = isset( $attr['zoom'] ) ? $attr['zoom'] : 13;
		$markers = isset( $attr['markers'] ) ? $attr['markers'] : array();
		$width = isset( $attr['width'] ) ? $attr['width'] : '100%';
		$height = isset( $attr['height'] ) ? $attr['height'] : '200px';
		
		$center = '';
		if( isset( $attr['center'] ) ) {
			if( $attr['center'] == 'marker' || $attr['center'] == 'both' ) {
				$center .= "L.marker([$lat,$lng]).addTo(map);";
			}
			if( ( $attr['center'] == 'circle' || $attr['center'] == 'both' ) && isset( $attr['radius'] ) ) {
				$center .= "var cr = L.circle( [ $lat, $lng ], " . ( (int)$attr['radius'] * 1000 ) . ", { 'stroke': false, 'fillOpacity': 0.1 } ).addTo( map ); map.fitBounds( cr.getBounds() );";
			}
		}
		ob_start();
?>
<div id="map"></div>
<script type="text/javascript">
	var _old_wo = window.onload;
	window.onload = function() {
		var map = L.map('map').setView([<?php echo "$lat,$lng"; ?>], <?php echo $zoom; ?>);

		L.tileLayer('<?php echo $this->local_options['source']['value']; ?>', {
			attribution: '&copy; <a href="http://osm.org/copyright">OpenStreetMap</a> contributors'
		}).addTo(map);
<?php if( ! empty( $markers ) ): ?>
		var m, i, k, e,
			markers = <?php echo json_encode( $markers ); ?>;
		for( i in markers ) {
			m = L.marker( markers[ i ].ll );
			if( markers[ i ].name ) {
				m.options.name = markers[ i ].name;
			}

			if( markers[ i ].title ) {
				m.options.title = markers[ i ].title;
			}
			
			m.bindPopup( m.options.title );

			es = markers[ i ].events;
			m.options.customEvents = {};

			if( es && typeof es == 'object' ) {
				for ( k in es ) {
					m.options.customEvents[ k ] = new Function( es[ k ] );
					m.on( k, function( e ) {
						this.options.customEvents[ e.type ]();
					});
				}
			}

			m.addTo( map );
		}
<?php
	endif;
	echo $center;
?>
		
		if( typeof _old_wo == 'function' ) {
			_old_wo();
		}
	}
</script>
<style>#map{width: <?php echo $width; ?>;height: <?php echo $height; ?>;}.hover{background-color: #eee;}</style>
<?php
		$out = ob_get_clean();
		return $out;
	}

	
	public function get_url( $ll, $zoom = 12 ) {
		return $this->local_options['external_url']['value'] . "?mlat=$ll[0]&mlon=$ll[1]#map=$zoom/$ll[0]/$ll[1]";
	}
	
	public function get_lat_lng_by_q( $q ) {
		$result_string = $this->file_get_contents_curl( $this->local_options['nominatim_base']['value'] . 'format=json&q=' . urlencode( $q ) );
		return $this->parse_server_result_string( $result_string );
	}
	
	public function get_lat_lng_by_zipcode( $zipcode ) {
		$options = json_decode( get_option( 'sm-options' ), true );
		$country_str = isset( $options['country'] ) && ! empty( $options['country'] ) ? '&country=' . $options['country'] : '' ;
		$result_string = $this->file_get_contents_curl( $this->local_options['nominatim_base']['value'] . 'format=json&postalcode=' . urlencode( $zipcode ) . $country_str );
		return $this->parse_server_result_string( $result_string );
	}
	
	public function hooks() {
		static::$options = array(
			'source' => array( 'label' => __( 'Source string', 'store-manager' ), 'value' => 'http://{s}.tile.osm.org/{z}/{x}/{y}.png' ),
			'external_url' => array( 'label' => __( 'Base URL to external map', 'store-manager' ), 'value' => 'http://www.openstreetmap.org/' ),
			'nominatim_base' => array( 'label' => __( 'Nominatim base URL', 'store-manager' ), 'value' => 'http://nominatim.openstreetmap.org/search?' ),
		);

		$this->local_options = self::get_options();
	}
	
}
