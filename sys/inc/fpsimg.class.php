<?php
/*-----------------------------------------------\
| 												 |
| @Author:       Andrey Brykin (Drunya)          |
| @Email:        drunyacoder@gmail.com           |
| @Site:         http://fapos.net                |
| @Version:      1.1                             |
| @Project:      CMS                             |
| @package       CMS Fapos                       |
| @subpackege    Watermark class  			     |
| @copyright     ©Andrey Brykin 2010-2013        |
| @last  mod     2013/03/31                      |
\-----------------------------------------------*/

/*-----------------------------------------------\
| 												 |
|  any partial or not partial extension          |
|  CMS Fapos,without the consent of the          |
|  author, is illegal                            |
|------------------------------------------------|
|  Любое распространение                         |
|  CMS Fapos или ее частей,                      |
|  без согласия автора, является не законным     |
\-----------------------------------------------*/




class FpsImg {

	private $imgTypes = array(
		1 => 'GIF',
		2 => 'JPEG',
		3 => 'PNG',
	);

	public function createWaterMark($orig, $watermark) {
		if (!file_exists($orig) || !file_exists($watermark)) return false;
		
		$hpos = Config::read('watermark_hpos');
		$hpos = ($hpos > 0 && $hpos < 4) ? $hpos : 3;
		$vpos = Config::read('watermark_vpos');
		$vpos = ($vpos > 0 && $vpos < 4) ? $vpos : 3;
		$alpha_level = Config::read('watermark_alpha');
		$alpha_level = ($alpha_level >= 0 && $alpha_level <= 100) ? $alpha_level : 50;
		
		if (function_exists('exif_imagetype')) {
			$orig_type = exif_imagetype($orig);
			$water_type = exif_imagetype($watermark);
		} else if (function_exists('getimagesize')) {
			$orig_type = getimagesize($orig);
			$water_type = getimagesize($watermark);
			$orig_type = $orig_type['mime'];
			$water_type = $water_type['mime'];
		} else {
			return false;
		}
		
		if (empty($orig_type) || empty($water_type)) return false;
		if ($orig_type === 1 || $orig_type === 'image/gif') $main_img = imagecreatefromgif($orig);
		else if ($orig_type === 2 || $orig_type === 'image/jpeg') $main_img = imagecreatefromjpeg($orig);
		else if ($orig_type === 3 || $orig_type === 'image/png') $main_img = imagecreatefrompng($orig);
		
		if ($water_type === 1 || $water_type === 'image/gif') $watermark_img = imagecreatefromgif($watermark);
		else if ($water_type === 2 || $water_type === 'image/jpeg') $watermark_img = imagecreatefromjpeg($watermark);
		else if ($water_type === 3 || $water_type === 'image/png') $watermark_img = imagecreatefrompng($watermark);
		if (empty($main_img) || empty($watermark_img)) return false;

		$alpha_level /= 100; 

		// get sizes
		$main_w = imagesx($main_img);
		$main_h = imagesy($main_img);
		$watermark_w = imagesx($watermark_img);
		$watermark_h = imagesy($watermark_img);

		switch ($hpos) {
			case 1:
				$min_x = 10;
				$max_x = $min_x + $watermark_w;
				break;
			case 2:
				$min_x = ceil(($main_w - $watermark_w) / 2);
				$max_x = $min_x + $watermark_w;
				break;
			default:
				$max_x = $main_w - 10;
				$min_x = $max_x - $watermark_w;
				break;
		}
		switch ($vpos) {
			case 1:
				$min_y = 10;
				$max_y = $min_y + $watermark_h;
				break;
			case 2:
				$min_y = ceil(($main_h - $watermark_h) / 2);
				$max_y = $min_y + $watermark_h;
				break;
			default:
				$max_y = $main_h - 10;
				$min_y = $max_y - $watermark_h;
				break;
		}

		// create image
		$return_img = imagecreatetruecolor($main_w, $main_h);
		switch ($orig_type) {
                        case 1:
			case 'image/gif':
			case 3:
			case 'image/png':
				imagecolortransparent($return_img, imagecolortransparent($main_img));
				imagealphablending($return_img, false);
				imagesavealpha($return_img, true);
				break;
			default: break;
		}
		imagecopy($return_img, $main_img, 0, 0, 0, 0, $main_w, $main_h);

		$start_x = $min_x < 0 ? 0 : $min_x;
		$start_y = $min_y < 0 ? 0 : $min_y;
		$end_x = $max_x >= $main_w ? $main_w - 1 : $max_x;
		$end_y = $max_y >= $main_h ? $main_h - 1 : $max_y;
		
		$watermark_tr = imagecolortransparent($watermark_img);
		
		// Each pixel watermarks image
		for($y = $start_y; $y < $end_y; $y++) { 
			for ($x = $start_x; $x < $end_x; $x++) { 

				// pixel position
				$watermark_x = $x - $min_x;
				$watermark_y = $y - $min_y;

				// Get color info
				$main_rgb = imagecolorsforindex($main_img, imagecolorat($main_img, $x, $y));
			
				$watermark_px = imagecolorat($watermark_img, $watermark_x, $watermark_y);
				if ($watermark_px == $watermark_tr) continue;
				
				$watermark_rbg = imagecolorsforindex($watermark_img, $watermark_px);

				// Alpha chanel
				$watermark_alpha = round(((127-$watermark_rbg['alpha'])/127),2);
				$watermark_alpha = $watermark_alpha * $alpha_level;

				
				// Get color in overlay place
				$avg_red = $this->__get_ave_color( $main_rgb['red'],
						   $watermark_rbg['red'], $watermark_alpha );
				$avg_green = $this->__get_ave_color( $main_rgb['green'],
							 $watermark_rbg['green'], $watermark_alpha );
				$avg_blue = $this->__get_ave_color( $main_rgb['blue'],
							$watermark_rbg['blue'], $watermark_alpha );
			
			
				// Index of color
				$return_color = $this->__get_image_color($return_img, $avg_red, $avg_green, $avg_blue);
	
				// Create new image with new pixels
				imagesetpixel($return_img, $x, $y, $return_color );
			}
		} 
		
		// View image
		$quality_jpeg = Config::read('quality_jpeg');
		if (isset($quality_jpeg)) $quality_jpeg = (intval($quality_jpeg) < 0 || intval($quality_jpeg) > 100) ? 75 : intval($quality_jpeg);
		$quality_png = Config::read('quality_png');
		if (isset($quality_png)) $quality_png = (intval($quality_png) < 0 || intval($quality_png) > 9) ? 9 : intval($quality_png);
		
		switch ($orig_type) {
			case 1:
			case 'image/gif':
				imagegif($return_img, $orig);
				break;
			case 2:
			case 'image/jpeg':
				imagejpeg($return_img, $orig, $quality_jpeg);
				break;
			case 3:
			case 'image/png':
				imagepng($return_img, $orig, $quality_png);
				break;
			default:
				imagejpeg($return_img, $orig, $quality_jpeg);
				break;
		}
		
		
		imagedestroy($return_img);
		imagedestroy($main_img);
		imagedestroy($watermark_img);
	}
	
	
	/**
	 * merge 2 colors with alpha chanel
	 */
	private function __get_ave_color($color_a, $color_b, $alpha_level) { 
		return round((($color_a*(1-$alpha_level))+($color_b*$alpha_level))); 
	} 
	
	
	/**
	 * return RGB color
	 */
	private function __get_image_color($im, $r, $g, $b) { 
		$c = imagecolorexact($im, $r, $g, $b);
		if ($c != -1) return $c;
		$c = imagecolorallocate($im, $r, $g, $b);
		if ($c != -1) return $c;
		return imagecolorclosest($im, $r, $g, $b);
	} 
}


?>