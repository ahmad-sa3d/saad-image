<?php
namespace Saad\Image\Exceptions;

class NotSupportedImageFormatException
{
    public function __construct( $message = null )
    {
        parent::__construct( $message );
    }
}
