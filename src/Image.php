<?php

namespace Saad\Image;

use Exceptions\ImageException;
use Illuminate\Support\Facades\Config;
use Saad\Image\Traits\CopyrightTrait;

/**
* Deal With Images
*/
class Image
{

	// Use Copyright Methods
	use CopyrightTrait;

	protected $_image_src;
	protected $_image_info;
	protected $_image_resource;			# binary

	protected $_preserve_transparency = false;

	protected $_save_name;
	protected $_save_dir;
	protected $_save_path;
	protected $_output_format = 'png';
	protected $_output_options = [];

	protected static $_allowed_formates = ['png', 'jpeg', 'jpg', 'gif'];

	public $error;

	/**
	 * Package Name
	 * @var string
	 */
	CONST PACKAGE_NAME = 'saad-image';

	// There Will be auto generated Properties According to Each Method needs

	/**
	 * Create Binary 'Resource' image from image file path
	 * @param string $image_src source 'image file path'
	 */
	public function __construct($image_src = null, $fallback_extension = null)
	{
		# code...
		if (!file_exists($image_src) || !($this->_image_info = getimagesize($image_src))) {
			throw new ImageException('__Construct() requires one argument to be an image file path.');
		}

		// Set File Fortmates
		if (! static::$_allowed_formates) {
			static::$_allowed_formates = Config::get(self::PACKAGE_NAME . '.image.formates');
		}

		# here we have an image src and we have it's info stored in image_info array
		$this->_image_src = $image_src;

		$this->_image_info = array_merge($this->_image_info, pathinfo($this->_image_src));

		# get orginal extension from mime
		$this->_image_info['mime_extension'] = str_replace('image/', '', $this->_image_info['mime']);

		# And Set default output format
		// $this->_output_format = $this->_image_info['extension'];
		if (isset($this->_image_info['extension'])) {
			$this->_output_format = $this->_image_info['extension'];
		} else {
			if ($fallback_extension) {
				$this->_output_format = $fallback_extension;
			} else {
				throw new ImageException(__METHOD__ . ' Couldnot Get Image Extension, Please Provide Extension as the second argument.');
			}
		}

		// Set Preserve Transparency For PNG
		// if($this->_image_info['mime_extension'] == 'png' || $this->_image_info['extension'] == 'png')
		if ($this->_output_format == 'png') {
			$this->_preserve_transparency = true;
		}

		# Create Image Binary File From Source File According To Image Type
		switch($this->_image_info['mime_extension']) {
			case 'jpeg' :
				$this->_image_resource = imagecreatefromjpeg($this->_image_src);
				break;

			case 'png' :
				$this->_image_resource = imagecreatefrompng($this->_image_src);
				break;

			case 'gif' :
				$this->_image_resource = imagecreatefromgif($this->_image_src);
				break;

			default:
				// throw new ImageException("Couldn't identify image mime-type OR image mime-type not supported" );
				# Set Error Beeter than Imageexception
				$this->error[] = "Couldn't identify image mime-type '{$this->_image_info['mime']}' OR image mime-type not supported.";
				return false;
		}

		// Respect Transparency
		imagealphablending($this->_image_resource, false);
		imagesavealpha($this->_image_resource, true);

		# Set Error in case of failing to create a resource'Binary' from sorce image
		if (!$this->_image_resource) {
			$this->error[] = "Couldn't create image resource.";
		}

	}

	/**
	 * Create Thumbnail Or Resize Image
	 * @param  number  $thumb_width     new width
	 * @param  number  $thumb_height    new height
	 * @param  boolean $preserve_aspect get neglected dimension according to originel aspect or set it as the other given dimension
	 * @return object                   current instance to allow chaining
	 */
	public function createThumbnail($thumb_width = null, $thumb_height = null, $preserve_aspect = false) {
		if ((is_numeric($thumb_width) && $thumb_width <= 0) || (is_numeric($thumb_height) && $thumb_height <= 0)) {
			throw new ImageException(__METHOD__ . ' require width or height to be non negative numbers or zero.');
		}

		if (!$thumb_width && !$thumb_height) {
			throw new ImageException(__METHOD__ . ' require at least width or height.');
		}


		# -1
		# Get aspect ratio for both Orginal Source and Thumb
		# to determine if there will be crop and if, how it will be
		# Aspect = Width / Height
		# So We Deduces:
		# Aspect > 1	=> Image is a Landscap Mode
		# 		 < 1	=> 			  Portrait Mode
		# 		 = 1	=> 			  Square Mode

		$this->_image_info['aspect'] = $this->_image_info[0] / $this->_image_info[1];


		// Verify Given dimensions
		if ($thumb_width && !$thumb_height) {
			// No Height
			$thumb_height = ($preserve_aspect) ? $thumb_width / $this->_image_info['aspect'] : $thumb_width;
		} else if (!$thumb_width && $thumb_height) {
			// no width given
			$thumb_width = ($preserve_aspect) ? $thumb_height * $this->_image_info['aspect'] : $thumb_height;
		}

		// Then Get Thumbnail Aspect
		$thumb_aspect = $thumb_width / $thumb_height;

		# -2
		# Create Thumbnail empty resource
		$thumb = imagecreatetruecolor($thumb_width, $thumb_height);

		// Fill Default White Color
		$bg = imagecolorallocate($thumb, 255, 255, 255);
		// imagefill($thumb, 0, 0, $bg);
		imagefilledrectangle($thumb, 0, 0, $thumb_width, $thumb_height, $bg);

		// Respect Transparency
		if ($this->_preserve_transparency && $this->_output_format != 'jpg') {
			// Ignore Filled Color, Make it transparent
			imagealphablending($thumb, false);
			imagesavealpha($thumb, true);
		}


		# -3
		# Compare Source Image aspect with Thumb aspect
		# to see how the crop will be if it exists

		if ($this->_image_info['aspect'] > $thumb_aspect) {
			# That's Means:
			# Source image Width will be greater than thumb width
			# the crop will happen on source image width

			#	- Get The Nearest Size of the source image that can contains the thumb
			#	  So, new source height will be thumb_height

			$source_imaginary_new_size['height'] = $thumb_height;

			# and we will calculate how much source image scaled
			# this equals the ratio between new height to original height, since heights of new and original preserve proportional relation

			$scale = $source_imaginary_new_size['height'] / $this->_image_info[1];

			#	since source_aspect_ratio = width / height,
			#	then width = source_aspect_ratio * Height,
			#	then if height changes, width will be changes and will be calculated from last equation as the ration is constant

			$source_imaginary_new_size['width'] = $this->_image_info['aspect'] * $source_imaginary_new_size['height'];

			# -- After we get the nearest imaginary source image dimensions to the thumb
			# 	 then we can get how much we will crop from the source width

			# 	 the cropped value will be the difference between the new source imaginary width and thumb width devided by the scale value
			# 	 to get the correct value as source image pixels are scaled

			# 	$croped_value = ($source_imaginary_new_size['width'] - $thumb_width) / $scale;

			# 	so we can get the crop position, it will start from the half value of the $crop_value, to center thumb scene
			# 	that's mean that thumb will trim the value of  ' $croped_value / 2 ' from both sides

			# 	since height will not changes so the start position will be 0
			# 	and we can write the copying position

			$copying_position = array(($source_imaginary_new_size['width'] - $thumb_width) / $scale / 2 , 0);

		} else if ($this->_image_info['aspect'] < $thumb_aspect) {
			# That's Means:
			# Source image Height will be greater than thumb height
			# the crop will happen on source image height
			#
			# -- It will be as descriped previously but we will reverse between width and height

			$source_imaginary_new_size['width'] = $thumb_width;

			# -- SCALE, here represents ratio between widths

			$scale = $source_imaginary_new_size['width'] / $this->_image_info[0];

			# -- calculate height
			# 	aspect = width / height
			# 	height = width / aspect
			$source_imaginary_new_size['height'] = $source_imaginary_new_size['width'] / $this->_image_info['aspect'];

			# -- Calculate copying Positions
			# 	width_start_position  = 0, as no crop will happens on width
			# 	height_start_position = ($source_imaginary_new_size['height'] - $thumb_height) / $scale / 2;
			$copying_position = array(0, ($source_imaginary_new_size['height'] - $thumb_height) / $scale / 2);

		} else {
			# here there will be no crops as thumb is resized version of source image
			# so,
			# 	source_imaginary_new_size will be the same thumb dimensions
			$source_imaginary_new_size = array(
				'width' => $thumb_width,
				'height' => $thumb_height
				);

			# Copying position will be 0, 0 as no crops happens
			$copying_position = array(0, 0);
		}

		# --4
		# 	Copy to thumb resource ($thumb) from source resource ($this->_image_resource) , with defining copying parameters
		#
		imagecopyresampled(
				$thumb,										#	copy into
				$this->_image_resource,						#	from
				0,											#	copy into at x
				0,											#	copy into at y
				$copying_position[0],						#	from at x
				$copying_position[1],						# 	from at y
				$source_imaginary_new_size['width'],		#	from resampled width
				$source_imaginary_new_size['height'],		#	from resampled height
				$this->_image_info[0],						#	from original width
				$this->_image_info[1]						#	from original height
			);


		# --5 Set new thumb as _image_resource for saving or exporting
		$this->_image_resource = $thumb;

		# Update _image_info_basic and important data that might be used in later calculations

		$this->_image_info[0] = $thumb_width;
		$this->_image_info[1] = $thumb_height;
		$this->_image_info[3] = 'width="' . $thumb_width . '" height="' . $thumb_height . '"';
		$this->_image_info['aspect'] = $thumb_aspect;

		unset($thumb);

		// allow Chaining
		return $this;

		# Method End
	}



	/**
	 * Set Saving Options
	 * @param String  $file_name saving file name
	 * @param String  $directory directory to save into
	 */
	public function setSaveOptions($file_name = null, $directory = null, $suffix = null) {
		# Set Directory, or use Old if setted before from instance, or use img src directory
		if ($directory) {
			$this->_save_dir = rtrim($directory, DIRECTORY_SEPARATOR);
		} else if (!$this->_save_dir) {
			$this->_save_dir = $this->_image_info['dirname'];
		}

		// Check Directory
		if (!is_dir($directory)) {
			mkdir($directory, 0755, true);
		} else if (!is_writable($directory)) {
			chmod($directory, 0755);
		}

		# Set Filename
		if ($file_name) {
			$this->_save_name = basename($file_name, '.' . pathinfo($file_name, PATHINFO_EXTENSION)) . $suffix . '.' . $this->_output_format;
		} else if (!$this->_save_name) {
			$this->_save_name = $this->_image_info['filename'] . $suffix . '.' . $this->_output_format;
		}


		$this->_save_path = $this->_save_dir . DIRECTORY_SEPARATOR . $this->_save_name;

		return $this;
		# Method End
	}


	/**
	 * Setting Output Format
	 * @param String $format png, jpeg, jpg OR gif
	 */
	public function setOutputFormat($format = null, $quality = null, $filters = null) {

		if ($format) {
			$format = strtolower($format);

			if (in_array($format, static::$_allowed_formates)) {
				$this->_output_format = $format;

				# fix file extension if this method was called after calling setSaveOptions Method
				if ($this->_save_path) {
					$this->_save_name = preg_replace(
						'/(?<=\.)(?:png|jpeg|jpg|gif)$/i',
						$this->_output_format,
						$this->_save_name
					);

					$this->_save_path = $this->_save_dir . DIRECTORY_SEPARATOR . $this->_save_name;
				}
			}
		}

		// Quality
		$this->_output_options = [];
		if (is_numeric($quality)) {
			// if($format == 'jpg' || $format == 'jpeg')
			// {
			// 	if($quality < 0 || $quality > 100)
			// 		throw new ImageException(__METHOD__ . ' ' . $format . ' quality must be between 0-100');
			// }
			// else if($format == 'png')
			// {
			// 	if($quality < 0 || $quality > 9)
			// 		throw new ImageException(__METHOD__ . ' ' . $format . ' quality must be between 0-9');
			// }
			
			if ($this->_output_format == 'jpg' || $this->_output_format == 'jpeg') {
				$quality = round($quality * 100 / 100);
			} else if ($this->_output_format == 'png') {
				$quality = round($quality * 9 / 100);
			}

			$this->_output_options[] = $quality;
		}

		// Filters
		if ($filters !== null) {
			// Filters must come after quality
			if (empty($this->_output_options)) {
				$this->_output_options[] = null;
			}

			$this->_output_options[] = $filters;
		}

		return $this;
	}

	/**
	 * Aliase For exportImage() Method
	 * @return Boolean indicates to success or fail
	 */
	public function export($keep_resource = false) {
		return $this->exportImage($keep_resource);
	}

	/**
	 * Save Or Output Current Image Resource
	 * @return Boolean indicates to success or fail
	 * @param  $keep_resource  Boolean  If To Not Destroy Resource After Exporting, Useful If Weneed to continue on image resource
	 */
	public function exportImage($keep_resource = false) {

		# export current resource image
		array_unshift($this->_output_options, $this->_image_resource, $this->_save_path);

		switch($this->_output_format) {
			case 'jpeg' :
			case 'jpg'  :
				if (!$this->_save_path) {
					header('content-type: image/jpeg');
				}

				$result = call_user_func_array('imagejpeg',  $this->_output_options);
				break;

			case 'png' :
				if (!$this->_save_path) {
					header('content-type: image/png');
				}

				$result = call_user_func_array('imagepng',  $this->_output_options);
				break;

			case 'gif' :
				if (!$this->_save_path) {
					header('content-type: image/gif');
				}

				$result = call_user_func_array('imagegif',  $this->_output_options);
				break;
		}

		// Reset output_options
		unset($this->_output_options[0], $this->_output_options[1]);
		$this->_output_options = array_values($this->_output_options);

		// Destroy Image
		if ($keep_resource === false) {
			$this->destroy();
		}

		// Exit incase of exporting to browser
		if (!$this->_save_path) {
			exit;
		}

		return ($result) ? ($this->_save_name  ? $this->_save_name : true) : false;
		# Method End
	}

	public function embed($keep_resource = false) {
		ob_start();

			switch ($this->_output_format) {
				case 'jpeg' :
				case 'jpg'  :
					imagejpeg($this->_image_resource);
					break;
				case 'png':
					imagepng($this->_image_resource);
					break;
				case 'gif':
					imagegif($this->_image_resource);
					break;
			}

			$str = ob_get_contents();

		ob_end_clean();

		if (!$keep_resource) {
			imagedestroy($this->_image_resource);
		}

		return 'data:image/' . $this->_output_format . ';base64,' . base64_encode($str);
	}

	/**
	 * Destroy Image Resource
	 */
	public function destroy() {
		imagedestroy($this->_image_resource);
	}


	/********************************************************************/
	/*							Magic Method							*/
	/********************************************************************/


	public function __toString() {
		// While Using Object as string Like: echoing, returning assigning... we export it
		return $this->exportImage();
	}


	/********************************************************************/
	/*							Helper Method							*/
	/********************************************************************/


	/**
	 * Get Random RGB Values on a specific range
	 * @param  integer $r     Red
	 * @param  integer $g     Green
	 * @param  integer $b     Blue
	 * @param  integer $range Range to randomize given values
	 * @return array        array of randomized rgb
	 */
	protected function getRGBFromRange($r, $g, $b, $range) {
		#	get randomized rgb values on a specific range from a agiven value

		if (!$range) {
			return array($r, $g, $b);
		}

		$range = round($range / 2);
		$color = array();

		# red
		$min = $r - $range;
		$max = $r + $range;

		if ($min < 0) {
			$min = 0;
		}

		if ($max > 255) {
			$max = 255;
		}

		$color['r'] = $color[0] = rand($min, $max);

		# Green
		$min = $g - $range;
		$max = $g + $range;

		if ($min < 0) {
			$min = 0;
		}

		if ($max > 255) {
			$max = 255;
		}

		$color['g'] = $color[1] = rand($min, $max);

		# blue
		$min = $b - $range;
		$max = $b + $range;

		if ($min < 0) {
			$min = 0;
		}

		if ($max > 255) {
			$max = 255;
		}

		$color['b'] = $color[2] = rand($min, $max);

		return $color;
	}


	/**
	 *-----------------------------------------
	 * 			getFontFilePath
	 *-----------------------------------------
	 *
	 * Get Font File Full Path
	 *
	 * @param  string $font         font file
	 * @return String               Font Full Path
	 */
	protected function getFontFilePath($font = null, $custom_key = null) {

		// Get Calling Class Name
		$cls = $custom_key ? $custom_key : strtolower(str_replace(__NAMESPACE__.'\\', '', get_called_class()));

		$font_path = Config::get(self::PACKAGE_NAME . '.font_dir') . DIRECTORY_SEPARATOR;

		// Check if has Sub Directory
		$font_path .= Config::get(self::PACKAGE_NAME . '.'.$cls.'.font_dir') ?  Config::get(self::PACKAGE_NAME . '.'.$cls.'.font_dir') . DIRECTORY_SEPARATOR : '';

		// $font OR Default
		$font_path .= ($font) ? $font : Config::get(self::PACKAGE_NAME . '.'.$cls.'.font');

		return $font_path;
	}


	/**
	 *-----------------------------------------
	 * 			getTextBoxDimensions
	 *-----------------------------------------
	 *
	 * Get Box Informaton that created by drawing a ttf text with the specified arguments
	 *
	 * @param  integer $font_size   text font size
	 * @param  integer $text_angle  drawing angle
	 * @param  string $font         font file
	 * @param  string $text         text string
	 * @return array                array of box information like width, height and corner points array
	 */
	protected function getTextBoxDimensions($font_size, $text_angle, $font, $text) {
		#	comment

		$dimensions = imagettfbbox(
			$font_size,
			$text_angle,
			$font,
			$text
		);

		$arr = array(
			'width' =>  max(abs($dimensions[4] - $dimensions[0]), abs($dimensions[6] - $dimensions[2])),
			'height'=>  max(abs($dimensions[5] - $dimensions[1]), abs($dimensions[7] - $dimensions[3])),
			'points'=>  $dimensions,
		);


		$arr['draw_y_center'] = $arr['height'] / 2;

		if ($text_angle % 180 == 0) {
			// Horizontal Typed, angle = 0, 180
			$arr['real_width'] = (abs($dimensions[2] - $dimensions[0]) + abs($dimensions[4] - $dimensions[6])) / 2;
			$arr['real_height'] = (abs($dimensions[3] - $dimensions[5]) + abs($dimensions[1] - $dimensions[7])) / 2;
		} else if ($text_angle % 90 == 0) {
			// Vertically
			$arr['real_width'] = (abs($dimensions[0] - $dimensions[6]) + abs($dimensions[2] - $dimensions[4])) / 2;
			$arr['real_height'] = (abs($dimensions[1] - $dimensions[3]) + abs($dimensions[7] - $dimensions[5])) / 2;
		} else {
			// Rotated by angle

			// Point Related Calculations, From Triangle Fethaghorth

			// Height dx and dy
			$Hdx = abs($dimensions[6] - $dimensions[0]);
			$Hdy = abs($dimensions[7] - $dimensions[1]);
			$H = round(sqrt($Hdx ** 2  +  $Hdy ** 2));

			// Width dx and dy
			$Wdx = abs($dimensions[6] - $dimensions[4]);
			$Wdy = abs($dimensions[7] - $dimensions[5]);
			$W = round(sqrt($Wdx ** 2  +  $Wdy ** 2));

			$arr['real_width'] = $W;
			$arr['real_height'] = $H;
			$arr['Hdx'] = $Hdx;
			$arr['Hdy'] = $Hdy;
			$arr['Wdx'] = $Wdx;
			$arr['Wdy'] = $Wdy;

		}

		// Calculate Offsets according to rotation angle
		switch(true) {
			case ($text_angle == 0):
				// 0
				$arr['offset_x'] = 0;
				$arr['offset_y'] = 0;
				break;

			case ($text_angle < 90):
				// 1 - 89
				$arr['offset_x'] = $Hdx;
				$arr['offset_y'] = 0;
				break;

			case ($text_angle == 90):
				// 90
				$arr['offset_x'] = $arr['width'];
				$arr['offset_y'] = 0;
				break;

			case ($text_angle < 180):
				// 91 - 179
				$arr['offset_x'] = $arr['width'];
				$arr['offset_y'] = $Hdy;
				break;

			case ($text_angle == 180):
				// 180
				$arr['offset_x'] = $arr['width'];
				$arr['offset_y'] = $arr['height'];
				break;

			case ($text_angle < 270):
				// 181 - 269
				$arr['offset_x'] = $arr['width'] - $Hdx;
				$arr['offset_y'] = $arr['height'];
				break;

			case ($text_angle == 270):
				// 270
				$arr['offset_x'] = 0;
				$arr['offset_y'] = $arr['height'];
				break;

			case ($text_angle < 360):
				// 271 - 359
				$arr['offset_x'] = 0;
				$arr['offset_y'] = $arr['height'] - $Hdy;
				break;
		}

		return $arr;
	}

	/**
	 * Disable Transparency
	 * @return App\Services\Image instance
	 */
	public function noTransparency() {
		$this->_preserve_transparency = false;
		return $this;
	}

	/**
	 * Enable Transparency
	 * @return App\Services\Image instance
	 */
	public function forceTransparency() {
		$this->_preserve_transparency = true;
		return $this;
	}

	/**
	 * Set Status Code Header
	 *
	 * @return App\Services\Image instance
	 */
	public function setStatusCode($code = 200, $message = 'OK') {
		// Set Header
		header($_SERVER['SERVER_PROTOCOL'] . ' ' . $code . ' ' . $message);

		// Allow Method Chaining
		return $this;
	}
}