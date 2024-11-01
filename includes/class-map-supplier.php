<?php

abstract class Map_Supplier {

	protected static $options = array(); // 'key' => array('label' => 'Label', 'value' => 'Value')

	public static $suppliers = array(
		'google',
		'openstreetmap',
//		'mapquest',
	);

	public static function get_options( $supplier = '' ) {
		if( ! empty( $supplier ) && in_array( $supplier, static::$suppliers ) ) {

			$path = dirname(__FILE__) . '/class-supplier-' . $supplier . '.php';
			if( ! class_exists( $supplier ) && file_exists( $path ) ) {
				require_once $path;
				$obj = new $supplier;
				$obj->hooks();
			}

			$options = $supplier::$options;
			$db_options =  json_decode( get_option( "sm-$supplier-options", '[]' ) );
			
			foreach ($db_options as $key => $value) {
				if( isset( $options[ $key ] ) ) {
					$options[ $key ]['value'] = $value;
				}
			}

			return $options;
		}

		return static::$options;
	}
		
	public static function get( $supplier = null ) {
		
		if( ! is_string( $supplier ) ) {
			$options = json_decode( get_option( 'sm-options', '{"supplier":"openstreetmap"}' ) );
			$supplier = $options->supplier;
		}
		
		if( ! in_array( $supplier, self::$suppliers ) ) {
			$supplier = 'openstreetmap';
		}
		
		$path = dirname(__FILE__) . '/class-supplier-' . $supplier . '.php';
		if( file_exists( $path ) ) {
			require_once $path;
			$obj = new $supplier;
			$obj->hooks();
			return $obj;
		}
		return false;
	}
	
	// http://stackoverflow.com/a/3535850
	protected function file_get_contents_curl( $url ) {
		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		$data = curl_exec( $ch );
		curl_close( $ch );

		return $data;
	}
	
	abstract public function map( $lat, $lng, $attr = array() );
	abstract public function get_lat_lng_by_q( $q );
	abstract public function get_lat_lng_by_zipcode( $zipcode );
	abstract public function get_url( $ll, $zoom );
	abstract public function hooks();
	
}
