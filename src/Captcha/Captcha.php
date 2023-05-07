<?php

namespace Saad\Image\Captcha;

use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Config;
use Saad\Image\Exceptions\CaptchaException;
use Saad\Image\Image;

class Captcha extends Image
{

	# Properties
	protected $_code;

	# backgrount text
	protected $_background_text;		# array

	# front text
	protected $_front_text;				# array

	protected $_blocks_seperation;
	protected $_rotation_angle;

	protected $_calculated_text_box;	# array


    /**
     * @throws CaptchaException
     */
	public function __construct()
	{

		// Check Generated Code
		if( !self::hasCode() )
			// Not Set, Generate One
			self::init();

		// Get Code
		$this->_code = Session::get( 'captcha' );

		$this->_code_length = strlen( $this->_code );

		// Method Inhereted From Image Class
		$font = $this->getFontFilePath();

		if( file_exists( $font ) )
			$this->_background_text['font'] = $this->_front_text['font'] = $font;
		else
			throw new CaptchaException( "font file '$font' doesn't exists." );

		# Text Size
		$this->_front_text['size'] = Config::get( 'images.captcha.font_size' );

		$this->_background_text['size'] = $this->_front_text['size'] + 4;

		# Text Color
		$this->_front_text['color'] = Config::get( 'images.captcha.front_text_color' );

		# Background Text Alpha
		$this->_background_text['color'] = Config::get( 'images.captcha.background_text_color' );

		# Image Color in rgba
		$this->_image_info['color'] = Config::get( 'images.captcha.background' );	# White

		$this->_blocks_seperation = Config::get( 'images.captcha.blocks_seperation' );

		$this->_rotation_angle = Config::get( 'images.captcha.rotation_angle' );

	}


	/**
	 * send image to browser
	 */
	public function exportImage( $keep_resorce = false )
	{
		// prepare Capatcha
		$this->getCaptchaImage();

		# Sending Header Info
		header( 'content-type: image/png' );

		# Exporting the final image
		imagepng( $this->_final_image );

		# Clearing final image from the memory
		imagedestroy( $this->_final_image );

		# Method End
	}


	/**
	 * get capatcha image to browser directly on using instance, on return, echo
	 * @return string [description]
	 */
	public function __toString()
	{
		return $this->exportImage();
	}


	/**
	 *  This Method Will Generate a Capatcha Code and Store it in Session
	 */
	public static function init( $blocks = 2, $block_min_length = 4, $block_max_length = 7 )
	{
		if( !is_numeric( $blocks ) || !is_numeric( $block_min_length ) || !is_numeric( $block_max_length ) )
			throw new CaptchaException( __METHOD__ . ' arguments must be numeric values' );

		if( $blocks < 1 || $blocks > 5 )
			throw new CaptchaException( '__construct($blocks, , ) $blocks must be +ve integer and less than or equal 5.' );

		if( $block_min_length < 1 || $block_min_length > 20 )
			throw new CaptchaException( '__construct( , $block_min_length, ) $block_min_length must be +ve integer' );

		if( $block_max_length < $block_min_length || $block_max_length > 100 )
			throw new CaptchaException( '__construct( , , $block_max_length ) $block_max_length must be +ve integer and greater than or equal to min_length and less than 100' );

		#	Generate Captcha Code


		$code = '';

		$pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

		for( $i = 1; $i <= $blocks ; $i++)
		{
			# generate block #i

			$block_length = rand( $block_min_length, $block_max_length );

			# get block, from 0 to length-1 to get exact block_length
			$code .= substr(str_shuffle(str_repeat($pool, 5)), 0, $block_length-1 );

			# Add Space if it wasn't the last block
			$code .= ( $i >= $blocks ) ?: ' ';

		}

		# Save Captcha Code To Session
		Session::put( 'captcha', $code );
	}

	public static function hasCode()
	{
		return Session::has( 'captcha' );
	}

	public static function getToValidate()
	{
		return self::hasCode() ? Session::pull( 'captcha' ) : null;
	}



	/********************************************************************/
	/* 							Processes Methods						*/
	/********************************************************************/


	/**
	 * get capatcha image , this is the method to get captcha image
	 * @return imagepng set headers content type to image/png and send the image data to the browser
	 */
	private function getCaptchaImage(){

		// This Is an Organizer Methods that uses all other methods to get the final image

		// 1- Calculating && Setting required Image Dimensions ==> min_width, min_height, width, height
		$this->calculateDimensions();

		// 2- Creating image itself
		$this->createCaptchaImage();

		// 3- Adding the Texts to the image
		$this->drawCapatchaImageText();

		// 4- Doing Some fixs to the final image
		$this->fixCaptchaImage();

	}


	/**
	 * calculate required size of capatcha image
	 */
	private function calculateDimensions()
	{
		# calculate capatcha text box dimensions
		# which are the minimum image resource dimensions

		$this->_calculated_text_box['dimensions'] = $this->getTextBoxDimensions(
														$this->_background_text['size'],
														0,
														$this->_background_text['font'],
														$this->_code
														);

		# calculate how much each character takes from width
		$this->_calculated_text_box['char_width'] = $this->_calculated_text_box['dimensions']['width'] / $this->_code_length;

		# calculate additional width to add to the minimum text box width later
		$this->_calculated_text_box['additional_width'] = $this->_calculated_text_box['char_width'] * $this->_code_length + $this->_blocks_seperation;

		$this->_calculated_text_box['additional_height'] = rand( 5, 15 );

		# set Initial Image Information width, height

		$this->_image_info['width']  = $this->_calculated_text_box['dimensions']['width']  + $this->_calculated_text_box['additional_width'];

		$this->_image_info['height'] = $this->_calculated_text_box['dimensions']['height'] + $this->_calculated_text_box['additional_height'];


		# Method End
	}


	/**
	 * create capatcha image resource
	 */
	private function createCaptchaImage()
	{

		$this->_image_resource = imagecreatetruecolor( $this->_image_info['width'], $this->_image_info['height'] );

		if( function_exists( 'imageantialias' ) )
			imageantialias( $this->_image_resource, true );

		# Get Colors Values
		$rgb = $this->getRGBFromRange(
			$this->_image_info['color'][0],
			$this->_image_info['color'][1],
			$this->_image_info['color'][2],
			$this->_image_info['color']['range']
			);

		# Get Color;
		$background_color = imagecolorallocate(
			$this->_image_resource,					# Image Resource
			$rgb[0],								# R
			$rgb[1],								# G
			$rgb[2]									# B
			);

		# Apply Color
		imagefill(
			$this->_image_resource,					# Image Resource
			0,										# Positon X
			0,										# Positon Y
			$background_color						# Color
			);


		# Method End
	}


	/**
	 * Draw Capatcha Code to Image
	 */
	private function drawCapatchaImageText()
	{

		#	Create Image Text

		# here we will first create background text, then front text
		# we will set x- position
		# initial front text position 'initial_x'
		# current position 'current_x'

		$this->_front_text['initial_x'] = $this->_front_text['x'] = rand( 10, 25 );

		# Background Text
		# X, Y positions

		$this->_background_text['x'] = $this->_front_text['x'] / 2;
		$this->_background_text['y'] = ( $this->_calculated_text_box['dimensions']['height'] ) + ( $this->_calculated_text_box['additional_height'] / 2 );

		// Developing::preview( $this->_calculated_text_box['additional_height'], $this->_image_info['height'] - $this->_calculated_text_box['dimensions']['height'] );

		$characters = str_split( $this->_code, 1 );

		# Generating background text By Drawing letter by letter
		foreach( $characters as $char )
		{
			# character rotation angle
			$rotation_angle = rand( 0 - $this->_rotation_angle, $this->_rotation_angle );

			# get color rgb values
			$rgb = $this->getRGBFromRange(
				$this->_background_text['color'][0],
				$this->_background_text['color'][1],
				$this->_background_text['color'][2],
				$this->_background_text['color']['range']
				);

			$character_color = imagecolorallocatealpha(
				$this->_image_resource,
				$rgb[0],
				$rgb[1],
				$rgb[2],
				$this->_background_text['color'][3]
				);

			# check to add the blocks seperation distance
			if( $char === ' ' )
				$this->_background_text['x'] += $this->_blocks_seperation;

			# Draw Character to the image
			imagettftext(
				$this->_image_resource,					# Image resource
				$this->_background_text['size'],		# Font Size
				$rotation_angle,						# Text Angle
				$this->_background_text['x'],			# X Position
				$this->_background_text['y'],			# Y Position
				$character_color,						# Color
				$this->_background_text['font'],		# Font
				$char 									# Text
				);

			# move x to the next position
			// $this->_background_text['x'] += $this->getTextBoxDimensions(
			// 	$this->_background_text['size'],
			// 	$rotation_angle,
			// 	$this->_background_text['font'],
			// 	$char
			// 	)['width'];

			$box_dimension = $this->getTextBoxDimensions(
				$this->_background_text['size'],
				$rotation_angle,
				$this->_background_text['font'],
				$char
				);

			$this->_background_text['x'] += $box_dimension['width'];


			# Method End
		}


		# space between characters equal 1/4  average character width
		$space_between_chars = $this->_calculated_text_box['char_width'] / 4;

		# final character index
		$final_character_index = $this->_code_length - 1;


		# Front Text
		foreach( $characters as $index => $char )
		{

			# the same as background text

			$rotation_angle = rand( 0 - $this->_rotation_angle, $this->_rotation_angle );

			# get color rgb values
			$rgb = $this->getRGBFromRange(
				$this->_front_text['color'][0],
				$this->_front_text['color'][1],
				$this->_front_text['color'][2],
				$this->_front_text['color']['range']
				);

			$character_color = imagecolorallocatealpha(
				$this->_image_resource,
				$rgb[0],
				$rgb[1],
				$rgb[2],
				$this->_front_text['color'][3]
				);

			# Calculate Character Dimensions
			$character_box = $this->getTextBoxDimensions(
				$this->_front_text['size'],
				$rotation_angle,
				$this->_front_text['font'],
				$char
				);

			# # calculate y position
			// $this->_front_text['y'] = ( $character_box['height'] - abs( $character_box['points'][1] ) ) + ( ( $this->_image_info['height'] - $character_box['height'] ) / 2 );
			// Developing::preview( 'index' . $index . ': ' . ( $character_box['height'] - abs( $character_box['points'][1] ) ), 'index' . $index . ': ' . $character_box['points'][5] );
			// exit;

			$this->_front_text['y'] = abs( $character_box['height'] ) + ( ( $this->_image_info['height'] - $character_box['height'] ) / 2 ) ;

			# check to add the blocks seperation distance
			if( $char === ' ' )
				$this->_front_text['x'] += $this->_blocks_seperation;


			# Draw Character To Image
			imagettftext(
						 $this->_image_resource,
						 $this->_front_text['size'],
						 $rotation_angle,
						 $this->_front_text['x'],
						 $this->_front_text['y'],
						 $character_color,
						 $this->_front_text['font'],
						 $char
						);

			$this->_front_text['x'] += $character_box['width'];

			$this->_front_text['x'] += ( $index !== $final_character_index ) ? $space_between_chars : null;

		}

		# Method End
	}


	/**
	 * Fix Final Capatcha image Size if needed
	 */
	private function fixCaptchaImage(){

		# Final equired image width must be
		# Last _front_text x position + initial_x

		$final_width = $this->_front_text['x'] + $this->_front_text['initial_x'];

		# check if to set final width
		if( $this->_image_info['width'] > $final_width )
			$this->_image_info['width'] = $final_width;


		$this->_final_image = imagecreatetruecolor( $this->_image_info['width'], $this->_image_info['height'] );

		if( function_exists( 'imageantialias' ) )
			imageantialias( $this->_final_image, true );


		imagecopy(
					$this->_final_image,				# destination image resource to copy into
					$this->_image_resource,				# source image resource to copy from
					0,									# start x position to copy into in destination image
					0,									# start y position to copy into in destination image
					0,									# start x position to copy from in source image
					0,									# start y position to copy from in source image
					$this->_image_info['width'],		# width to copy from source image
					$this->_image_info['height'] 		# height to copy from source image
				 );

		# Removing the first image from memory
		imagedestroy( $this->_image_resource );

		# Method End
	}



	/********************************************************************/
	/* 							Setters Methods							*/
	/********************************************************************/


	/**
	 * Set Text Size
	 * @param integer $front front text size
	 * @param integer $background text size
	 */
	public function setTextSize( $front, $background = null )
	{
		#	comment

		if( !is_numeric( $front ) )
			throw new CaptchaException( __METHOD__ . '($arg) must be numeric' );

		if( !$background )
		{

			$this->_front_text['size'] = $front;
			$this->_background_text['size'] = $front + 10;

		}
		else
		{
			if( !is_numeric( $background ) )
				throw new CaptchaException( __METHOD__ . '($arg) must be numeric' );

			if( $front > $background )
				throw new CaptchaException( __METHOD__ . '($front, $background) $background must be greater than or equal front' );

			$this->_front_text['size'] = $front;
			$this->_background_text['size'] = $background;
		}

		return $this;
		# Method End
	}


	/**
	 * Set Text Color
	 * @param string  $key   define which property to set it's color
	 * @param integer  $r    red value
	 * @param integer  $g    green value
	 * @param integer  $b    blue value
	 * @param integer $alpha alpha value
	 * @param integer $range range
	 */
	public function setColor( $key, $r, $g, $b, $alpha = 0, $range = 0 )
	{
		#	Set color

		if( !in_array( $key, array( 'background', 'background_text', 'front_text' ) ) )
			throw new CaptchaException( __METHOD__ . '($key, ) must be one of( background, background_text, front_text ).' );

		if( !is_numeric( $r ) || !is_numeric( $g ) || !is_numeric( $b ) || !is_numeric( $alpha ) || !is_numeric( $range ) )
			throw new CaptchaException( __METHOD__ . '( , $args ) second argument and the followers must be numeric values.' );

		# set values
		$property = ( $key == 'background' ) ? '_image_info' : '_' . $key;


		$this->{$property}['color']				= array( $r, $g, $b, $alpha );
		$this->{$property}['color']['range'] 	= $range;

		return $this;
		# Method End
	}


	/**
	 * Set Captcha Font
	 * @param string $key  define which text we want to set it's font
	 * @param string $font font file name
	 */
	public function setFont( $key, $font )
	{
		#	Set Fonts

		if( $key != 'background_text' && $key != 'front_text' )
			throw new CaptchaException( __METHOD__ . '($key, ) $key must be background_text or front_text.' );

		# check font file existence
		$font_file = Config::get( 'images.captcha.font_dir' ) . DIRECTORY_SEPARATOR . $font;

		if( !file_exists( $font_file ) )
			throw new CaptchaException( __METHOD__ . "( , $font) provided font file '$font_file' doesn't exists." );

		$property = '_' . $key;

		$this->{$property}['font'] = $font_file;

		return $this;
		# Method End
	}

}

?>
