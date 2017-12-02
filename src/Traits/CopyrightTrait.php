<?php

namespace Saad\Image\Traits;

use Illuminate\Support\Facades\Config;

trait CopyrightTrait
{
	
	/**
	 *-----------------------------------------
	 * 			Write Text To Image
	 *-----------------------------------------
	 *
	 * @param array $info_array Method Information array like text to write and who to write
	 *
	 * @return Image $this Current Image instance
	 */
	public function addCopyright( Array $info_array = array() )
	{
		
		// Overwrite Default Settings
		$this->_copyright = array_merge( Config::get( static::PACKAGE_NAME . 'image.copyright' ), $info_array );

		
		// Set Font File
		$this->_copyright[ 'font' ] = !empty( $this->_copyright[ 'font' ] ) ?
					$this->getFontFilePath( $this->_copyright[ 'font' ] ) :
					$this->getFontFilePath();


		// Check Font File
		if( !file_exists( $this->_copyright['font'] ) )
			
			throw new ImageException( __METHOD__ . "() '$this->_copyright[ 'font' ]' doesn't exists" );

		
		// Check Numeric Values
		if( !is_numeric( $this->_copyright['font_size'] ) || !is_numeric( $this->_copyright['text_angle'] ) || !is_numeric( $this->_copyright['block_separation'] ) )
		
			throw new ImageException( __METHOD__ . '() font_size, text_angle, block_separation.' );
		
		
		
		// Check text before perform any other checks
		$this->_copyright['text'] = trim( $this->_copyright['text'] );

		
		// Exit If No Text To be Written
		if( !$this->_copyright['text'] )
			return true;

		
		// Get Alwayes angle in range of 360
		$this->_copyright[ 'text_angle' ] = $this->_copyright[ 'text_angle' ] % 360;

		// Fix Negative Angle Values
		if( $this->_copyright[ 'text_angle' ] < 0 )
			$this->_copyright[ 'text_angle' ] = 360 - abs( $this->_copyright[ 'text_angle' ] );

		// ----------------------------------- TEST ------------------------------------
			
			// # Get Cross Line angle
			// # tan ø = y / x 		==> 	ø = rad2deg( atan( y / x ) )
			
			// $angle = $this->_image_info[ 1 ] / $this->_image_info[ 0 ];

			// $angle = rad2deg( atan( $angle ) );

			// $this->_copyright[ 'text_angle' ] = $angle;

		// --------------------------------- END TEST ------------------------------------

		// Detecting required text vertical location
		if( strpos( $this->_copyright['vertical_location'], '%' ) === false )
		
			// in pixels
			$this->_copyright['vertical_location'] = intval( $this->_copyright['vertical_location'] );
		
		else
			// in percentage
			$this->_copyright['vertical_location'] = $this->_image_info[1] * intval( $this->_copyright['vertical_location'] ) / 100;
		

	
		
		// 	Text Color
		$text_color = imagecolorallocatealpha(
			$this->_image_resource,
			$this->_copyright['text_color']['r'],
			$this->_copyright['text_color']['g'],
			$this->_copyright['text_color']['b'],
			$this->_copyright['text_color']['a']
			);

		
		// Calculate one block text box dimensions
		$this->_copyright['text_box_dimensions'] = $this->getTextBoxDimensions( 
			$this->_copyright['font_size'],
			$this->_copyright['text_angle'],
			$this->_copyright['font'],
			$this->_copyright['text']
			);
		
		
		// Check Textbox Size
		$this->CheckTextSize();
				
		// Calculate Y Position
		$this->setYPosition();

		

		/*-----------------------------| Writing Process |-----------------------------*/


		if( !$this->_copyright[ 'text_repeat' ] )
		{
			// A- No Repeat, Manual Positioning
			
			// Get Max X Offset
			$max_x = $this->_image_info[0] - $this->_copyright['text_box_dimensions']['width'];
			
			// Get X Location
			$this->_copyright['x_position'] = intval( $this->_copyright['horizontal_location'] );


			// If X Relative, Get Relative Position in Pixels
			if( strpos( $this->_copyright['horizontal_location'], '%' ) !== false && $this->_copyright['x_position'] > 0 )

				$this->_copyright['x_position'] = $this->_copyright['x_position'] * $this->_image_info[0] / 100 - $this->_copyright['text_box_dimensions']['width'] / 2;
			

			// Check For Minimum position, 0
			if( $this->_copyright['x_position'] < 0 )
				$this->_copyright['x_position'] = 0;


			 // check max_x position
			if( $this->_copyright['x_position'] > $max_x )
				$this->_copyright['x_position'] = $max_x;

			// Set X Position
			$this->setXPosition();

			// Write Text
			imagettftext(
					$this->_image_resource,
					$this->_copyright['font_size'],
					$this->_copyright['text_angle'],
					$this->_copyright['x_position'],
					$this->_copyright['y_position'],
					$text_color,
					$this->_copyright['font'],
					$this->_copyright['text']
					);
			

			return true;

		}
		else
		{
		
			// B- REPATE Enabled

			# -so Check for repeating and centering
			# -will repeated if image width greater or equal min_repeat_width
			
			$min_repeat_width = $this->_copyright['block_separation'] + ( 2 * $this->_copyright['text_box_dimensions']['width'] );

			if( $this->_image_info[0] >= $min_repeat_width )
			{
				// will be repeated
				
				# See how many repeat times can happens
				$repeat_times = floor( $this->_image_info[0] / ( $this->_copyright['block_separation'] + $this->_copyright['text_box_dimensions']['width'] ) );

				
				// calculate required width for all repeated times
				$required_width = ( $repeat_times * $this->_copyright['text_box_dimensions']['width'] ) + ( ( $repeat_times - 1 ) * $this->_copyright['block_separation'] );

				// Calculate Margins
				$margins = $this->_image_info[0] - $required_width;


				// Set X Position
				$this->setXPosition( $margins );

				
				for( $i = 1; $i <= $repeat_times; $i++ )
				{
					# time #i
					
					// Write Text
					imagettftext(
						$this->_image_resource,
						$this->_copyright['font_size'],
						$this->_copyright['text_angle'],
						$this->_copyright['x_position'],
						$this->_copyright['y_position'],
						$text_color,
						$this->_copyright['font'],
						$this->_copyright['text']
						);

					# Move to next position
					$this->_copyright['x_position'] += ( $i == $repeat_times ) ?
						$this->_copyright['text_box_dimensions']['width'] :
						$this->_copyright['text_box_dimensions']['width'] + $this->_copyright['block_separation'];

				}

			}
			else
			{
				# will not be repeated and centered horizontally
				
				$margins = floor( $this->_image_info[0] - $this->_copyright['text_box_dimensions']['width'] );


				// Set X Position Offset
				$this->setXPosition( $margins );

				// Write Text
				imagettftext(
					$this->_image_resource,
					$this->_copyright['font_size'],
					$this->_copyright['text_angle'],
					$this->_copyright['x_position'],
					$this->_copyright['y_position'],
					$text_color,
					$this->_copyright['font'],
					$this->_copyright['text']
					);

			}

		} # End string repeat Condition
		

		return $this;

		# Method End
	}


	/**
	 *-----------------------------------------
	 * 				setYPosition
	 *-----------------------------------------
	 * 
	 * Set Y Position Where Text Will be Written
	 *
	 */
	private function setYPosition()
	{
		#	Calculate Y position on Image
	
		# Height Difference Between Image and CopyRight text box
		$heights_difference = $this->_image_info[1] - $this->_copyright['text_box_dimensions']['height'];
		
		if( $heights_difference > 0 )
		{
			if( $this->_copyright['vertical_location'] < $this->_copyright['text_box_dimensions']['draw_y_center'] ) 				
				
				# so un complete text (Cropped ) from top ===> align text to top
				$this->_copyright['y_position'] = $this->_copyright['text_box_dimensions']['height'];
			
			else if( ( $this->_copyright['vertical_location'] + $this->_copyright['text_box_dimensions']['draw_y_center'] ) > $this->_image_info[1] )
				
				# align text to bottom
				$this->_copyright['y_position'] = $this->_image_info[1];
			
			else
				
				# align text to the specified percentage location
				$this->_copyright['y_position'] =  $this->_copyright['vertical_location'] + ( $this->_copyright['text_box_dimensions']['draw_y_center'] );
		}
		else
		
			# align text to bottom
			$this->_copyright['y_position'] = $this->_image_info[1];



		// Check to Subtract or add Value, of drawing offset Mechanism, ( itis y cordinate of first point )
		// -1, 15, -18
		if( $this->_copyright['text_box_dimensions']['points'][1] >= -1 )
		{
			if( $this->_copyright['text_box_dimensions']['points'][1] > 0 || $this->_copyright['text_angle'] > 180 )
				// +ve values and angles more than 180
				$this->_copyright['y_position'] += $this->_copyright['text_box_dimensions']['points'][1] * -1;
			else
				$this->_copyright['y_position'] += $this->_copyright['text_box_dimensions']['points'][1];

		}
		
		else
			// < -1 
			$this->_copyright['y_position'] -= $this->_copyright['text_box_dimensions']['points'][1];

		
		// Alwayes Minus Offset Y that will have value because Rotation
		$this->_copyright['y_position'] -= $this->_copyright['text_box_dimensions']['offset_y'];


		
		# Method End
	}

	
	/**
	 *-----------------------------------------
	 * 				setXPosition
	 *-----------------------------------------
	 * 
	 * Set X Position Where Text Will be Written
	 *
	 */
	private function setXPosition( $margins = 0 )
	{
		
		// Set to the first start if it hasnot been set
		if( !isset( $this->_copyright['x_position'] ) )
			$this->_copyright['x_position'] = 0;


		// Fix Offset Values
		$this->_copyright['x_position'] += $margins / 2 +
				$this->_copyright['text_box_dimensions']['offset_x'] -
				$this->_copyright['text_box_dimensions']['points'][1] / 2 -
				$this->_copyright['text_box_dimensions']['points'][0] ;
	}

	
	/**
	 *-----------------------------------------
	 * 				CheckTextSize
	 *-----------------------------------------
	 * 
	 * Fix text overflow if text box size is greater than image
	 *
	 * @param String $for text represents check for 'width' Or 'height' Or 'both'
	 *
	 */
	private function CheckTextSize( $for = 'both' )
	{
		
		// Check For What ? 
		if( $for == 'width' || $for == 'both' )
		{
			// By Default Start With Width
			$key = 'width';
			$index = 0;
		}
		else if( $for == 'height' )
		{
			// Check For Height
			$key = 'height';
			$index = 1;
		}
		else
			// Not Valid Check Key, Do Nothing
			return;

		

		// Perform Check Process
		if( $this->_copyright['text_box_dimensions'][ $key ] > $this->_image_info[ $index ] )
		{
			
			// Get Difference Ratio
			$difference_ratio = $this->_image_info[ $index ] / $this->_copyright['text_box_dimensions'][ $key ];

			// Reduce Font Size By Difference Ration
			$this->_copyright['font_size'] = $this->_copyright['font_size'] * $difference_ratio;
		

			# Recalculate Text Box Dimensions For New Font Size
			$this->_copyright['text_box_dimensions'] = $this->getTextBoxDimensions(
				$this->_copyright['font_size'],
				$this->_copyright['text_angle'],
				$this->_copyright['font'],
				$this->_copyright['text']
				);
		}


		// Check if to check Height
		if( $for == 'both' )
			$this->CheckTextSize( 'height' );

	}

}