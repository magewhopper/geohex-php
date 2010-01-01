<?php
/* GeoHex module for php */
/*  DISCLAIMER OF WARRANTY

BECAUSE THIS SOFTWARE IS LICENSED FREE OF CHARGE, THERE IS NO WARRANTY
FOR THE SOFTWARE, TO THE EXTENT PERMITTED BY APPLICABLE LAW. EXCEPT WHEN
OTHERWISE STATED IN WRITING THE COPYRIGHT HOLDERS AND/OR OTHER PARTIES
PROVIDE THE SOFTWARE "AS IS" WITHOUT WARRANTY OF ANY KIND, EITHER
EXPRESSED OR IMPLIED, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE. THE
ENTIRE RISK AS TO THE QUALITY AND PERFORMANCE OF THE SOFTWARE IS WITH
YOU. SHOULD THE SOFTWARE PROVE DEFECTIVE, YOU ASSUME THE COST OF ALL
NECESSARY SERVICING, REPAIR, OR CORRECTION.

IN NO EVENT UNLESS REQUIRED BY APPLICABLE LAW OR AGREED TO IN WRITING
WILL ANY COPYRIGHT HOLDER, OR ANY OTHER PARTY WHO MAY MODIFY AND/OR
REDISTRIBUTE THE SOFTWARE AS PERMITTED BY THE ABOVE LICENCE, BE
LIABLE TO YOU FOR DAMAGES, INCLUDING ANY GENERAL, SPECIAL, INCIDENTAL,
OR CONSEQUENTIAL DAMAGES ARISING OUT OF THE USE OR INABILITY TO USE
THE SOFTWARE (INCLUDING BUT NOT LIMITED TO LOSS OF DATA OR DATA BEING
RENDERED INACCURATE OR LOSSES SUSTAINED BY YOU OR THIRD PARTIES OR A
FAILURE OF THE SOFTWARE TO OPERATE WITH ANY OTHER SOFTWARE), EVEN IF
SUCH HOLDER OR OTHER PARTY HAS BEEN ADVISED OF THE POSSIBILITY OF
SUCH DAMAGES.

GeoHex oliginaly written by OHTSUKA Ko-hei (http://svn.coderepos.org/share/lang/perl/Geo-Hex/trunk/) in perl
Ported by Mage Whopper (http://twitter.com/Mage_Whopper) 2009.12.29
*/

class GeoHex{
	var $hex_key = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWX';
	var $hex_grid = 1000;
	var $hex_size = 0.5;
	var $min_x_lon  = 122930; // 与那国等
	var $min_x_lat   = 24448;
	var $min_y_lon  = 141470; //南硫黄島
	var $min_y_lat   = 24228;
	
	function __constructor(){
	}
	
	function  _geohex2level( $code ){
		$code_length = strlen( $code );
		if( $code_length == 0 ){
			die( 'GeoHex code must be set' );
		}
		$code_array=str_split( $code );
		if( $code_length > 4 ){
			$level = strpos( $this->hex_key, array_shift( $code_array ) );
			if( $level === FALSE ){
				die( 'Code format is something wrong' );
			}
			if( $level == 0 ){
				$level = 60;
			}
		}else{
			$level = 7;
		}
		return array( $level, $code_length, $code_array );
	}
	
	function _geohex2hyhx( $input_code ){
		$h_key = $this->hex_key;
		list( $level, $code_length, $code) = $this->_geohex2level( $input_code );
		$unit = $this->_level2unitsize( $level );
		$h_k = ( round( ( 1.4 / 3 ) * $this->hex_grid ) ) / $this->hex_grid;
		$base_x = floor( ( $this->min_x_lon + $this->min_x_lat / $h_k ) / $unit[0] );
		$base_y = floor( ( $this->min_y_lat - $h_k * $this->min_y_lon ) / $unit[1] );

		if ( $code_length > 5 ) {
 			$h_x = strpos( $h_key, $code[0]) * 3600 + strpos( $h_key,$code[2] ) * 60 + strpos( $h_key, $code[4] );
			$h_y = strpos( $h_key, $code[1]) * 3600 + strpos( $h_key,$code[3] ) * 60 + strpos( $h_key, $code[5] );
		}else{
			 $h_x = strpos( $h_key, $code[0] ) * 60   + strpos( $h_key, $code[2] );
			 $h_y = strpos( $h_key, $code[1] ) * 60   + strpos( $h_key, $code[3] );
		}
		return array( $h_y, $h_x, $level, $unit[0], $unit[1], $h_k, $base_x, $base_y );
	}

	function _hyhx2geohex( $h_y, $h_x, $level ){
		$h_key = $this->hex_key;
		$h_10   = $h_key[floor( ( $h_x % 3600 ) / 60 )] . $h_key[floor( ( $h_y % 3600 ) / 60)] . $h_key[floor( ( $h_x % 3600 ) % 60 )] . $h_key[floor(($h_y % 3600) % 60)];
		$h_100 = $h_key[floor( $h_x / 3600 )] . $h_key[floor( $h_y / 3600 )];
		
		if ( $level < 7 ) {
			$code = $h_key[ $level % 60 ] . $h_100;
		}elseif( $level == 7 ){
			$code = "";
		}else{
			$code = $h_key[ $level % 60 ];
		}
		return ( $code . $h_10 );
	}

	function _level2unitsize( $level ){
		$k = $level * $this->hex_size;
		return array(6.0  * $k, 2.8  * $k);
	}

	function latlng2geohex($lat, $lon, $level){
		if( $level ==0 ){
			$level = 7;
		}else if( $level < 1 || $level > 60){
			die( 'Code format is something wrong' );
		}
		
		$lon_grid = $lon * $this->hex_grid;
		$lat_grid = $lat * $this->hex_grid;
		$unit     = $this->_level2unitsize( $level );
		$h_k      = ( round( (1.4 / 3) * $this->hex_grid) ) / $this->hex_grid;
		$base_x   = floor( ($this->min_x_lon + $this->min_x_lat / $h_k ) / $unit[0]);
		$base_y   = floor( ($this->min_y_lat - $h_k * $this->min_y_lon) / $unit[1]);
		$h_pos_x  = ( $lon_grid + $lat_grid / $h_k ) / $unit[0] - $base_x;
		$h_pos_y  = ( $lat_grid - $h_k * $lon_grid) / $unit[1] - $base_y;
		$h_x_0    = floor($h_pos_x);
		$h_y_0    = floor($h_pos_y);
		$h_x_q    = floor(($h_pos_x - $h_x_0) * 100) / 100;
		$h_y_q    = floor(($h_pos_y - $h_y_0) * 100) / 100;
		$h_x      = round($h_pos_x);
		$h_y      = round($h_pos_y);
		if ( $h_y_q > -$h_x_q + 1 ) {
			if( ($h_y_q < 2 * $h_x_q ) && ( $h_y_q > 0.5 * $h_x_q ) ){
				$h_x = $h_x_0 + 1;
				$h_y = $h_y_0 + 1;
			}
		} elseif ( $h_y_q < -$h_x_q + 1 ) {
			if( ($h_y_q > (2 * $h_x_q ) - 1 ) && ( $h_y_q < ( 0.5 * $h_x_q ) + 0.5 ) ) {
				$h_x = $h_x_0;
				$h_y = $h_y_0;
			}
		}
		 return $this->_hyhx2geohex( $h_y, $h_x, $level );
	}

	function geohex2latlng( $code ){
		list ( $h_y, $h_x, $level, $unit_x, $unit_y, $h_k, $base_x, $base_y ) =  $this->_geohex2hyhx( $code );
		if( is_array( $code )){
			var_dump( $code );
			die( 'invalid input $code' );
		}

		$h_lat = ( $h_k   * ( $h_x + $base_x ) * $unit_x + ( $h_y + $base_y ) * $unit_y ) / 2;
		$h_lon = ( $h_lat - ( $h_y + $base_y ) * $unit_y ) / $h_k;
		$lat   = $h_lat / $this->hex_grid;
		$lon  = $h_lon / $this->hex_grid;
		return array( $lat, $lon, $level );
	}

	function geohex2polygon( $code ){
		list( $lat, $lon, $level ) = $this->geohex2latlng( $code );
		$d = $level * $this->hex_size / $this->hex_grid;
		$dk = 1.4 * $d;
		$dk2 = 1.0 * $d;
		return array(
			array($lat, $lon - 2 * $dk2 ),
			array($lat + $dk, $lon - $dk2 ),
			array($lat + $dk, $lon + $dk2 ),
			array($lat, $lon + 2 * $dk2 ),
			array($lat - $dk, $lon + $dk2 ),
			array($lat - $dk, $lon - $dk2 ),
			array($lat, $lon - 2 * $dk2 )
		);
	}

	function geohex2distance( $code1, $code2 ){
		list( $h_y1, $h_x1, $level1 ) = $this->_geohex2hyhx( $code1 );
		list( $h_y2, $h_x2, $level2 ) = $this->_geohex2hyhx( $code2 );
		if( $level1 != $level2 ){
			die( 'Level of codes are must same value');
		}		
		$dh_y = $h_y1 - $h_y2;
		$dh_x = $h_x1 - $h_x2;
		$ah_y = abs( $dh_y );
		$ah_x = abs( $dh_x );
		if ( $dh_y * $dh_x > 0 ){
			return $ah_x > $ah_y ? $ah_x : $ah_y;
		}else{
			return $ah_x + $ah_y;
		}
	}

	function  distance2geohexes( $code, $dist ){
		list( $h_y, $h_x, $level ) = $this->_geohex2hyhx( $code );
		$results = Array();
		//croak $@ if ( $@ );
		$mdist = -1 * $dist;
		for ( $i = $mdist; $i <= $dist; $i++ ){
			$dmn_x = $mdist + ( $i > 0 ?  $i : 0 );
			$dmx_x = $dist + ( $i < 0 ? $i : 0 );
			for( $j = $dmn_x; $j<=$dmx_x; $j++ ) {
				if( ( $i == 0 ) && ( $j == 0 ) ){
					next;
				}
				array_push( $results, $this->_hyhx2geohex( $h_y + $i, $h_x + $j, $level ) );
			}
		}
		return $results;
	}
	
} // class GeoHex end
