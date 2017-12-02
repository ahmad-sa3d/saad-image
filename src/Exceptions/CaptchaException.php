<?php 

namespace Saad\Image\Exceptions;

use \Exception;

class CaptchaException extends Exception{

	public function __construct( $message = null, $code = null, Exception $previous = null )
	{
		$message = !$message ? : Captcha::class . '::' . $message;

		parent::__construct( $message, $code, $previous );
	}

	/**
	 * Get Exception Message when using exception as value
	 * @return string Exception Message
	 */
	public function __toString()
	{
		return $this->getMessage();
	}

}