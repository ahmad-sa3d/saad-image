<?php 

namespace Saad\Image\Exceptions;

use \Exception;

class ImageException extends Exception{

	
	public function __construct( $message = null, $code = null, Exception $previous = null )
	{
		$message = !$message ? : Image::class . '::' . $message;

		parent::__construct( $message, $code, $previous );
	}

	
	public function __toString()
	{
		return $this->getMessage();
	}

}