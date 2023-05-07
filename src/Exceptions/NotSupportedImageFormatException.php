<?php
namespace Saad\Image\Exceptions;

class NotSupportedImageFormatException extends \Exception
{
    public function __construct( $message = null )
    {
        parent::__construct( $message );
    }
}
