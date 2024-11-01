<?php

class google extends Map_Supplier {

	private $local_options; // get_options doet altijd request naar DB, dus voor het begin even opslaan

	public static function get_options( $supplier = '' ) {
		return parent::get_options('google');
	}

	private function parse_server_result_string( $result_string ) {
		$result_array = json_decode( $result_string, true );
		if( empty( $result_array ) || $result_array['status'] != 'OK' ) {
			return false;
		} else {
			return array_values( $result_array['results'][0]['geometry']['location'] );
		}
		
	}

	public function map( $lat, $lng, $attr = array() ) {
		
		$zoom = isset( $attr['zoom'] ) ? $attr['zoom'] : 13;
		$markers = isset( $attr['markers'] ) ? $attr['markers'] : array();
		$width = isset( $attr['width'] ) ? $attr['width'] : '100%';
		$height = isset( $attr['height'] ) ? $attr['height'] : '200px';
		
		$center = '';
		if( isset( $attr['center'] ) ) {
			if( $attr['center'] == 'marker' || $attr['center'] == 'both' ) {
				$center .= "new google.maps.Marker( { position: { lat: $lat, lng: $lng }, map: map } );";
			}
			if( ( $attr['center'] == 'circle' || $attr['center'] == 'both' ) && isset( $attr['radius'] ) ) {
				$center .= "var cr = new google.maps.Circle( { strokeWeight:0, fillColor: '#0000FF', fillOpacity: 0.1, map: map, center: { lat: $lat, lng: $lng }, radius: " . ( (int)$attr['radius'] * 1000 ) . " } ); map.fitBounds( cr.getBounds() );";
			}
		}
		ob_start();
?>
<div id="map"></div>
<script type="text/javascript">

	var _old_wo = window.onload;
	window.onload = function() {

		var map = new google.maps.Map( document.getElementById( 'map' ), { zoom: <?php echo $zoom; ?>, center: new google.maps.LatLng(<?php echo "$lat,$lng"; ?>) } );

<?php if( ! empty( $markers ) ): ?>
		var es, m, i, k, markers = <?php echo json_encode( $markers ); ?>;
		for( i in markers ) {
			m = new google.maps.Marker( { position: { lat: parseFloat( markers[ i ].ll[0] ), lng: parseFloat( markers[ i ].ll[1] ) }, map: map } );

			if( markers[ i ].title ) {
				m.setTitle( markers[ i ].title );
				m.addListener('click', function() {
					new google.maps.InfoWindow( { content: this.title } ).open( this.map, this );
				});
			}
			

			es = markers[ i ].events;

			if( es && typeof es == 'object' ) {
				for ( k in es ) {
					m.addListener( k, new Function( es[ k ] ) );
				}
			}
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
<script async defer src="https://maps.googleapis.com/maps/api/js?key=<?php echo $this->local_options['browser_key']['value']; ?>"></script>
<style>#map{width: <?php echo $width; ?>;height: <?php echo $height; ?>;}.hover{background-color: #eee;}</style>
<?php
		$out = ob_get_clean();
		return $out;
	}

	
	public function get_url( $ll, $zoom = 12 ) {
		return 'https://maps.google.com';
	}
	
	public function get_lat_lng_by_q( $q ) {
		$g_options = json_decode( get_option( 'sm-google-options' ), true );
		$api_str = isset( $g_options['server_key'] ) && ! empty( $g_options['server_key'] ) ? '&key=' . $g_options['server_key'] : '' ;

		$result_string = $this->file_get_contents_curl( 'https://maps.googleapis.com/maps/api/geocode/json?address=' . urlencode( $q ) . $api_str );

		return $this->parse_server_result_string( $result_string );
	}
	
	public function get_lat_lng_by_zipcode( $zipcode ) {
		$options = json_decode( get_option( 'sm-options' ), true );
		$country_str = isset( $options['country'] ) && ! empty( $options['country'] ) ? '|country:' . $options['country'] : '' ;

		$g_options = json_decode( get_option( 'sm-google-options' ), true );
		$api_str = isset( $g_options['server_key'] ) && ! empty( $g_options['server_key'] ) ? '&key=' . $g_options['server_key'] : '' ;
//echo 'https://maps.googleapis.com/maps/api/geocode/json?components=postal_code:' . urlencode( $zipcode ) . $country_str . $api_str;
		$result_string = $this->file_get_contents_curl( 'https://maps.googleapis.com/maps/api/geocode/json?components=postal_code:' . urlencode( $zipcode ) . $country_str . $api_str );

		return $this->parse_server_result_string( $result_string );
	}
	
	public function hooks() {
		static::$options = array(
			'browser_key' => array( 'label' => __( 'API browser Key' ), 'value' => '' ),
			'server_key' => array( 'label' => __( 'API server Key' ), 'value' => '' ),
		);

		$this->local_options = self::get_options();
	}
	
}
