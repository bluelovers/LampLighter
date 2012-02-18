<?php

class InvalidImageException extends Exception {
	
	public function __construct( $message ) {
		
		parent::__construct( 'Invalid Image: ' . $message );
		
	}
}

class ImageManip {

	const WATERMARK_POS_UPPER_LEFT = 1;
	const WATERMARK_POS_UPPER_RIGHT = 2;
	const WATERMARK_POS_LOWER_LEFT = 3;
	const WATERMARK_POS_LOWER_RIGHT = 4;
	const WATERMARK_POST_CENTER = 5;

	public $jpeg_extension_to_jpg = true;

	public function is_valid_image( $img_path ) {

		try { 
			if ( @getimagesize($img_path) ) {
				return true;
			}
	
			return false;
		}
		catch( Exception $e ) {
			throw $e;
		}
	}


	public function get_image_info( $image_file ) {

		try { 
			
			if ( false === ($gis_array = getimagesize($image_file)) ) {
				throw new InvalidImageException($image_file);
			}
			else {
				$image_info['width']  = $gis_array[0];
				$image_info['height'] = $gis_array[1];
				$image_info['type']   = $gis_array[2];
	
				return $image_info;
			}
			
		}
		catch( Exception $e ) {
			throw $e;
		}
	}

	public function resize_image( $src_path, $dst_path, $new_width, $new_height, $quality = 100, $options = array() ) {
		try { 
			
			$zoom_img = null;
			
			if ( !$src_path ) {
				throw new MissingParameterException('src_path');
			}
	
			if ( !$dst_path ) {
				throw new MissingParameterException('dst_path');
			}
	
			if ( !$new_width ) {
				throw new MissingParameterException('new_width');
			}
	
			if ( !$new_height ) {
				throw new MissingParameterException('new_height');
				return false;
			}
	
			if ( !($image_info = @getimagesize($src_path)) ) {
				throw new InvalidImageException($src_path);
			}
			
			$img_type = $image_info[2];
	
			if ( !($func_key = $this->image_function_key_by_type($img_type)) ) {
				throw new InvalidImageException("unknown_type %{$src_path}");
			}
		
			$imagecreate_func = "imagecreatefrom{$func_key}";
			$image_func       = "image{$func_key}";
	
			if ( !function_exists($imagecreate_func) ) {
				throw new InvalidImageException("no_create_function %{$imagecreate_func}");
			}
	
			if ( !function_exists($image_func) ) {
				throw new InvalidImageException("no_image_function %{$image_func}");
			}
	
	        if ( !($src_img = $imagecreate_func($src_path)) ) {
				throw new Exception("bad_imagecreate %{$src_path}%" );
			}
			
			if ( !($dst_img = imagecreatetruecolor($new_width, $new_height)) ) {
				@imagedestroy($src_img);
				throw new Exception("bad_imagecreatetruecolor %{$src_path}%" );
			}
			
			$dst_x = isset($options['dst_x']) ? $options['dst_x'] : 0;
			$dst_y = isset($options['dst_y']) ? $options['dst_y'] : 0;
			$src_x = isset($options['src_x']) ? $options['src_x'] : 0;
			$src_y = isset($options['src_y']) ? $options['src_y'] : 0;
	        $src_w = isset($options['src_w']) ? $options['src_w'] : ImageSX($src_img);
	        $src_h = isset($options['src_h']) ? $options['src_h'] : ImageSY($src_img);
	
			if ( (isset($options['presize_width']) && $options['presize_width']) || (isset($options['presize_height']) && $options['presize_height']) ) {
				$presize_width = ( isset($options['presize_width']) && $options['presize_width'] ) ? $options['presize_width'] : $src_w;
				$presize_height = ( isset($options['presize_height']) && $options['presize_height'] ) ? $options['presize_height'] : $src_h;

				if ( !($zoom_img = imagecreatetruecolor($presize_width, $presize_height)) ) {
					@imagedestroy($src_img);
					throw new Exception("bad_imagecreatetruecolor %{$src_path}%" );
				}
				
				if ( !imagecopyresampled( $zoom_img, $src_img, $dst_x,$dst_y, 0, 0, $presize_width,$presize_height,ImageSX($src_img),ImageSY($src_img)) ) {
					@imagedestroy($src_img);
					@imagedestroy($dst_img);
					throw new Exception("bad_imagecopyresampled %{$src_path}% %{$dst_path}%" );
				}
				
				$src_img = $zoom_img;
				
			}
	
	
			if ( isset($options['crop']) && $options['crop'] ) {
				
				//
				// first, let's resample the image as close as possible
				// to its final size, that way our croppable area 
				// contains as much of the image as possible
				//
				
				//
				// The scale is the amount we have to multiply the smaller
				// side by so that the image reaches as close to $new_height 
				// or $new_width as possible
				//
				// We take the max here because we want to account for
				// the side that requires the most significant change.
				// We could be zooming in (if one side of the image
				// isn't as big as our desired width/height) or out
				// (if one side is too big and we'd be zooming in more than
				// we have to)
				$scale = max(($new_width/$src_w),( $new_height / $src_h )); 	
				
				$zoom_to_w = (int)$src_w * $scale;
				$zoom_to_h = (int)$src_h * $scale;
					
				if ( !($zoom_img = imagecreatetruecolor($zoom_to_w, $zoom_to_h)) ) {
					@imagedestroy($zoom_img);
					throw new Exception("bad_imagecreatetruecolor %{$src_path}%" );
				}
				
				if ( !imagecopyresampled( $zoom_img, $src_img, $dst_x,$dst_y, 0, 0, $zoom_to_w, $zoom_to_h, ImageSX($src_img),ImageSY($src_img)) ) {
					@imagedestroy($src_img);
					@imagedestroy($dst_img);
					throw new Exception("bad_imagecopyresampled %{$src_path}% %{$dst_path}%" );
				}
				
				$img_to_crop = $zoom_img;

					
				//}
				//else {
				//	$img_to_crop = $src_img;
				//}
				
				//$src_x = 0;
				//$src_y = 0;
			
				switch( $options['crop']['type'] ) {
					case 'cut_bottom':
						$crop_src_x = (ImageSX($img_to_crop) - $new_width) / 2;
						$crop_src_y = 0;
						break;
					case 'cut_top':
						$crop_src_x = 0;
						$crop_src_y = (ImageSY($img_to_crop) - $new_height) / 2;
						break;
					case 'center_square':
					default:
						$crop_src_x = (ImageSX($img_to_crop) - $new_width) / 2;
						$crop_src_y = (ImageSY($img_to_crop) - $new_height) / 2;
						break;
						
				}			

				/*
				if ( !($croppable_img = imagecreatetruecolor($new_width, $new_height)) ) {
					@imagedestroy($croppable_img);
					throw new Exception("bad_imagecreatetruecolor %{$src_path}%" );
				}

				if ( !imagecopyresampled( $croppable_img, $img_to_crop, 0, 0, $crop_src_x, $crop_src_y,$new_width, $new_height, $new_width, $new_height) ) {
					@imagedestroy($src_img);
					throw new Exception("bad_imagecopyresampled %{$src_path}% %{$dst_path}%" );
					return false;	
				}
				
				$src_img = $croppable_img;
				*/
				
				
				$src_x = $crop_src_x;
				$src_y = $crop_src_y;
				$src_w = $new_width;
				$src_h = $new_height;
				$src_img = $img_to_crop;
	 			
			}
			
			if ( !imagecopyresampled( $dst_img, $src_img, $dst_x,$dst_y, $src_x, $src_y,$new_width,$new_height,$src_w,$src_h) ) {
				@imagedestroy($src_img);
				@imagedestroy($dst_img);
				throw new Exception("bad_imagecopyresampled %{$src_path}% %{$dst_path}%" );
				return false;	
			}
			
			if ( $this->image_type_supports_quality($img_type) ) {
				if ( !$image_func($dst_img, $dst_path, $quality) ) {
					@imagedestroy($src_img);
					@imagedestroy($dst_img);
					throw new Exception("bad_imagefunc %{$dst_path}%" );			
				}
					
			}
			else {
				if ( !$image_func($dst_img, $dst_path) ) {
					@imagedestroy($src_img);
					@imagedestroy($dst_img);
					throw new Exception("bad_imagefunc %{$dst_path}%" );			
				}
			}
	                            
			imagedestroy($dst_img);
			imagedestroy($src_img);
	
			return true;
		}
		catch( Exception $e ) {
			throw $e;
		}          

	}

	public function image_type_supports_quality( $img_type ) {

		if ( $img_type == IMAGETYPE_JPEG ) {
			return true;
		}

		return false;

	}

	public function image_function_key_by_type( $img_type ) {

		try { 

			if ( $img_type == IMAGETYPE_JPEG ) {
				return 'jpeg';
			}
			else {
				return $this->image_extension_by_type($img_type, false);
			}

		}
		catch( Exception $e ) {
			throw $e;
		}
	}

	public function image_extension_by_type( $img_type, $include_dot = true ) {

		try { 
			
			$dot = $include_dot ? '.' : '';
	
			if ( !$img_type ) {
				throw new MissingParameterException('img_type');
			}
	
			if ( function_exists('image_type_to_extension') ) {
				$ext = image_type_to_extension($img_type, $include_dot);
				
				if ( $this->jpeg_extension_to_jpg && ($ext == $dot . 'jpeg') ) {
					$ext = $dot . 'jpg';
				}
				
				return $ext;
			}
			else {
				
	       
				switch($img_type) {
	 
					case IMAGETYPE_GIF    : return $dot . 'gif';
	           			case IMAGETYPE_JPEG   : return $dot . 'jpg';
	          			case IMAGETYPE_PNG    : return $dot . 'png';
	           			case IMAGETYPE_SWF    : return $dot . 'swf';
					case IMAGETYPE_PSD    : return $dot . 'psd';
					case IMAGETYPE_WBMP   : return $dot . 'wbmp';
	 				case IMAGETYPE_XBM    : return $dot . 'xbm';
					case IMAGETYPE_TIFF_II : return $dot .'tiff';
	           			case IMAGETYPE_TIFF_MM : return $dot .'tiff';
	           			case IMAGETYPE_IFF    : return $dot . 'aiff';
	           			case IMAGETYPE_JB2    : return $dot . 'jb2';
	           			case IMAGETYPE_JPC    : return $dot . 'jpc';
	           			case IMAGETYPE_JP2    : return $dot . 'jp2';
	           			case IMAGETYPE_JPX    : return $dot . 'jpf';
					case IMAGETYPE_SWC    : return $dot . 'swc';
				}
			}
			
			return false;
		}
		catch( Exception $e ) {
			throw $e;
		}
	}


	public function mime_type_to_image_type( $mime_type ) {

		try { 
			if ( !$mime_type ) {
				throw new MissingParameterException( 'mime_type' );
			}
	
			
			switch( $mime_type ) {
	 
				case 'image/gif' : return IMAGETYPE_GIF;
				case 'image/jpeg': return IMAGETYPE_JPEG;
				case 'image/pjpeg': return IMAGETYPE_JPEG;
				case 'image/jpg' : return IMAGETYPE_JPEG;
				case 'image/png' : return IMAGETYPE_PNG;
				case 'image/psd' : return IMAGETYPE_PSD;
				case 'image/bmp' : return IMAGETYPE_BMP;
				case 'image/tiff' : return IMAGETYPE_TIFF_II;
				case 'image/jp2' : return IMAGETYPE_JP2;
				case 'image/iff' : return IMAGETYPE_IFF;
				case 'image/vnd.wap.wbmp' : return IMAGETYPE_WBMP;
				case 'image/xbm' : return IMAGETYPE_XBM;
				
			}
			
	
			return null;
		}
		catch( Exception $e ) {
			throw $e;
		}
	}
	

	public function reproportion_image( $src_path, $dst_path, $new_width, $new_height, $shrink_only = false, $quality = 100 ) {

		try { 
			if ( $new_width && !$new_height ) {
				return $this->reproportion_by_width( $src_path, $dst_path, $new_width, $shrink_only, $quality );
			}
			else if ( $new_height && !$new_width ) {
				return $this->reproportion_by_height( $src_path, $dst_path, $new_height, $shrink_only, $quality );	
			}
			else if ( $new_height && $new_width ) {
	
				if ( $shrink_only ) {
	
					if ( !$image_info = $this->get_image_info($src_path) ) {
						throw new InvalidImageException($src_path);
					}
	
					if ( $image_info['width'] > $new_width ) {
						return $this->reproportion_by_width( $src_path, $dst_path, $new_width, $shrink_only, $quality );
					}
					else if ( $image_info['height'] > $new_height ) {
						return $this->reproportion_by_width( $src_path, $dst_path, $new_width, $shrink_only, $quality );		
					}
				}
				else {
	
					return $this->resize_image( $src_path, $dst_path, $new_width, $new_height, $quality );
				}
			}
	
			return null;
		}
		catch( Exception $e ) {
			throw $e;
		}

	}


	public function reproportion_by_width( $src_path, $dst_path, $new_width, $shrink_only = false, $quality = 100, $options = array() ) {
		
		try {
			if ( !$new_width ) {
				throw new MissingParameterException('new_width');
			}
	
			$dimensions = $this->get_reproportioned_dimensions($src_path, $new_width, null, $shrink_only);
	
			return $this->resize_image( $src_path, $dst_path, $dimensions['width'], $dimensions['height'], $quality, $options );
		}
		catch( Exception $e ) {
			throw $e;
		}
	}

	public function reproportion_by_height( $src_path, $dst_path, $new_height, $shrink_only = false, $quality = 100, $options = array() ) {

		try { 
			if ( !$new_height ) {
				throw new MissingParameterException('new_height');
			}
	
			$dimensions = $this->get_reproportioned_dimensions($src_path, null, $new_height, $shrink_only);
	
			return $this->resize_image( $src_path, $dst_path, $dimensions['width'], $dimensions['height'], $quality, $options );
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}

	public function get_reproportioned_dimensions( $img_path, $fixed_width = null, $fixed_height = null, $shrink_only = false ) {
		
		try { 

			if ( !$fixed_width AND !$fixed_height ) {
				throw new MissingParameterException('size_constraints');
			}
			else {
	
				$new_dimensions = array();
	
				$image_info = $this->get_image_info($img_path);

				$image_width  = $image_info['width'];
				$image_height = $image_info['height'];
		
				if ( $fixed_width ) { 
					if ( ($image_width > $fixed_width) OR !$shrink_only ) {			
        		        	$image_height = floor( ($fixed_width * $image_height) / $image_width ) ;
      	        		        $image_width = $fixed_width;
					}
				}
				else if ( $fixed_height ) {
					if ( ($image_height > $fixed_height) OR !$shrink_only ) {
       		        	$image_width = floor( ($fixed_height * $image_width) / $image_height ) ;
	       		        $image_height = $fixed_height;
					}
				}
	
				$new_dimensions['width'] = $image_width;
				$new_dimensions['height'] = $image_height;
			
				return $new_dimensions;
			}
		}
		catch( Exception $e ) {
			throw $e;
		}		
	}

	public function apply_watermark_to_image( $src_path, $dst_path, $watermark_path, $watermark_pos = null, $wm_transparency = 50 ) {

		try { 
			
			if ( !$src_path ) {
				throw new MissingParameterException('src_path');
			}
	
			if ( !$dst_path ) {
				throw new MissingParameterException('dst_path');
			}
	
			if ( !is_readable($src_path) ) {
				throw new ReadException($src_path);
			}
	
			if ( !is_readable($watermark_path) ) {
				throw new ReadException($watermark_path);
			}
	
	
			if ( !$watermark_pos ) {
				$watermark_pos = self::WATERMARK_POS_LOWER_RIGHT;
			}
			
			$image_info = $this->get_image_info($src_path);
			$wm_info = $this->get_image_info($watermark_path);
	
			if ( !($image_func_key = $this->image_function_key_by_type($image_info['type'])) ) {
				throw new InvalidImageException("unknown_type %{$src_path}");
			}
	
			if ( !($wm_func_key = $this->image_function_key_by_type($wm_info['type'])) ) {
				throw new InvalidImageException("unknown_type %{$wm_path}");
			}
		
			$imagecreate_func = "imagecreatefrom{$image_func_key}";
			$wm_create_func   = "imagecreatefrom{$wm_func_key}";
	
			$image_func       = "image{$image_func_key}";
	
			if ( !function_exists($image_func) ) {
				throw new InvalidImageException("no_image_function %{$image_func}");
			}
	
			if ( !function_exists($imagecreate_func) ) {
				throw new InvalidImageException("no_imagecreate_function %{$image_func}");
			}
	
			if ( !function_exists($wm_create_func) ) {
				throw new InvalidImageException("no_imagecreate_function %{$wm_create_func}");
			}
	
	
	       	if ( !($wm_resource = @$wm_create_func($watermark_path)) ) {
				throw new Exception( 'bad_imagecreate %$watermark_path%' );
			}
	
        	if ( !($dst_resource = @$imagecreate_func($src_path)) ) {
				@imagedestroy($wm_resource);
				throw new Exception( 'bad_imagecreate %$src_path%' );
			}
	
	
			if ( false === (list($wm_x, $wm_y) = $this->watermark_xy_by_img_resource($dst_resource, $wm_resource, $watermark_pos)) ) {
				@imagedestroy($wm_resource);
				@imagedestroy($dst_resource);
	
				throw new Exception( 'invalid_xy_coords' );
			}
	
			if ( !imageCopyMerge($dst_resource, $wm_resource, $wm_x, $wm_y, 0, 0, $wm_info['width'], $wm_info['height'], $wm_transparency) ) {
				@imagedestroy($wm_resource);
				@imagedestroy($dst_resource);
				throw new Exception("bad_imagecopymerge {$src_path}" );
			}
	
			if ( $this->image_type_supports_quality($image_info['type']) ) {
				if ( !$image_func($dst_resource, $dst_path, 100) ) {
					@imagedestroy($wm_resource);
					@imagedestroy($dst_resource);
					throw new Exception("bad_imagefunc {$dst_path}" );
				}
					
			}
			else {
				if ( !$image_func($dst_resource, $dst_path) ) {
					@imagedestroy($wm_resource);
					@imagedestroy($dst_resource);
					throw new Exception("bad_imagefunc {$dst_path}" );
				}
			}
	
			imagedestroy($wm_resource);
			imagedestroy($dst_resource);
	
			return true;
		}
		catch( Exception $e ) {
			throw $e;
		}
	}

	public function watermark_xy_by_img_resource( $img_resource, $wm_resource, $watermark_pos ) {

		try { 
		
			if ( !$watermark_pos ) {
				throw new MissingParameterException('watermark_pos');
			}
	
			if ( !($image_width = @imageSX($img_resource)) ) {
				throw new Exception( 'bad_imageSX %img_resource%'  );
			}
	
			if ( !($image_height = @imageSY($img_resource)) ) {
				throw new Exception( 'bad_imageSY %img_resource%'  );
			}
	
			if ( !($wm_width = @imageSX($wm_resource)) ) {
				throw new Exception( 'bad_imageSX %wm_resource%'  );
			}
	
			if ( !($wm_height = @imageSY($wm_resource)) ) {
				throw new Exception( 'bad_imageSY %wm_resource%'  );
			}
	
			switch( $watermark_pos ) {
	
				case self::WATERMARK_POS_UPPER_LEFT: 
					return array( 0,0 );
					break;
				case self::WATERMARK_POS_UPPER_RIGHT:
					$wm_x = $image_width - $wm_width;
					$wm_x = ( $wm_x > 0 ) ? $wm_x : 0;
					$wm_y = 0;
	
					return array( $wm_x, $wm_y );
					break;
				case self::WATERMARK_POS_LOWER_LEFT:
	
					$wm_x = 0;
					$wm_y = $image_height - $wm_height;
					$wm_y = ( $wm_y > 0 ) ? $wm_y : 0;
	
					return array( $wm_x, $wm_y );
					break;
				case self::WATERMARK_POS_LOWER_RIGHT:
	
					$wm_x = $image_width - $wm_width;
					$wm_x = ( $wm_x > 0 ) ? $wm_x : 0;
	
					$wm_y = $image_height - $wm_height;
					$wm_y = ( $wm_y > 0 ) ? $wm_y : 0;
	
					return array( $wm_x, $wm_y );
					break;
				case self::WATERMARK_POS_CENTER:
					
					$wm_x = ( $image_width / 2 ) - ( $wm_width / 2 );
					$wm_x = ( $wm_x > 0 ) ? $wm_x : 0;
	
					$wm_y = ( $image_height / 2 ) - ( $wm_height / 2 );
					$wm_y = ( $wm_y > 0 ) ? $wm_y : 0;
	
					return array( $wm_x, $wm_y );
					break;
				
			}
	
	
		 	throw new InvalidParameterException ('watermark_pos' );
		}
		catch( Exception $e ) {
			throw $e;
		}
	}

} //end class

/*
 *  Fuse Compatibility
 */
class FusePhoto extends ImageManip {
	
}

