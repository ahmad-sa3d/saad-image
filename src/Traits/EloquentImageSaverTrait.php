<?php

/**
 * Eloquent Image Saver
 *
 * @author Ahmed Saad <a7mad.sa3d.2014@gmail.com
 * @package Saad\Image
 * @license MIT
 */

namespace Saad\Image\Traits;
use Saad\Image\Exceptions\NotSupportedImageFormatException;
use Saad\Image\Image;

trait EloquentImageSaverTrait
{
    /**
     * Save Uploaded File Image
     * @param  \Illuminate\Http\UploadedFile $imageFile [description]
     * @param  String                        $path      Path where to save Image
     * @param  String                        $name      Image Save Name
     * @param  Array|array                   $sizes     Array of image sizes
     * @param  Function                      $callback  Callback to run after saving image
     * @return String                                   Image saved name
     */
    public function saveImage( \Illuminate\Http\UploadedFile $imageFile, $path, $name = null, Array $sizes = [], $callback = null )
    {
        $extension = $imageFile->extension();

        if( !in_array( $extension, [ 'png', 'jpeg', 'jpg', 'gif' ] )  )
            throw new NotSupportedImageFormatException( $extension . ' extension isnot supported only supported formates are PNG, JPG, JPEG and GIF' );

        // Ok
        $image = ( new Image( $imageFile->getPathname(), $extension ) )->setOutputFormat( $extension, 100, PNG_NO_FILTER );
        // $image = ( new Image( $imageFile->getPathname(), $extension ) )->setOutputFormat( 'png', 100, PNG_NO_FILTER );

        $name = $name ?: str_random( 10 ) . time();

        if( empty( $sizes ) )
        {
            $save_name = $image->setSaveOptions( $name, $path )->export();
        }
        else
        {
            $main_size = array_shift( $sizes );

            $main = self::getSize( $main_size );

            $save_name = $image->createThumbnail( $main[ 'w' ], $main[ 'h' ], true )
                        ->setSaveOptions( $name, $path )->export( true );

            foreach( $sizes as $size )
            {
                $size = self::getSize( $size );
                $image->createThumbnail( $size[ 'w' ], $size[ 'h' ], true )->setSaveOptions( join( 'x', $size ) .  '_' . $save_name, $path . 'thumb'  )->export( true );
            }

            // Destroy Resource
            $image->destroy();
            
        }

        // CallBack
        if( is_callable( $callback ) )
            call_user_func( $callback, $this, $save_name, $extension );

        return $save_name;

    }


    /**
     * Save Array Of Uploaded Files Image
     * @param  Array                         $images    Array of  \Illuminate\Http\UploadedFile
     * @param  String                        $path      Path where to save Image
     * @param  String                        $name      Image Save Name
     * @param  Array|array                   $sizes     Array of image sizes
     * @param  Function                      $callback  Callback to run after saving image
     * @return Array                                    Array of Images saved names
     */
    public function saveImages( Array $images, $path, $name = null, Array $sizes = [], $callback = null )
    {
        $array = [];

        foreach( $images as $file )
        {
            $array[] = $this->saveImage( $file, $path );
        }

        // CallBack
        if( is_callable( $callback ) )
            call_user_func( $callback, $this, $array );

        return $array;

    }

    /**
     * Get Width, Height values
     */
    public static function getSize( $size )
    {
        $dim = [];
        if( is_array( $size ) )
        {
            $dim[ 'w' ] = $size[0];
            $dim[ 'h' ] = isset( $size[1] ) ? $size[1] : $size[0];
        }
        else
        {
            $dim[ 'w' ] = $size;
            $dim[ 'h' ] = null;
        }

        return $dim;
    }

    /**
     * Save Locale Images
     * 
     * @param  String                        $imageFile Image Path
     * @param  String                        $path      Path where to save Image
     * @param  String                        $name      Image Save Name
     * @param  Array|array                   $sizes     Array of image sizes
     * @param  Function                      $callback  Callback to run after saving image
     * @return String                                   Image saved name
     */
    public function saveLocalImage( $imageFile, $path, $name = null, Array $sizes = [], $callback = null )
    {
        $arr = explode('.',$imageFile);
        $extension = end($arr);

        if( !in_array( $extension, [ 'png', 'jpeg', 'jpg', 'gif' ] )  )
            throw new NotSupportedImageFormatException( $extension . ' extension isnot supported only supported formates are PNG, JPG, JPEG and GIF' );

        // Ok
        $image = ( new Image( $imageFile, $extension ) )->setOutputFormat( $extension, 100, PNG_NO_FILTER );
        // $image = ( new Image( $imageFile->getPathname(), $extension ) )->setOutputFormat( 'png', 100, PNG_NO_FILTER );

        $name = $name ?: str_random( 10 ) . time();

        if( empty( $sizes ) )
        {
            $save_name = $image->setSaveOptions( $name, $path )->export();
        }
        else
        {
            $main_size = array_shift( $sizes );

            $main = self::getSize( $main_size );

            $save_name = $image->createThumbnail( $main[ 'w' ], $main[ 'h' ], true )
                        ->setSaveOptions( $name, $path )->export( true );

            foreach( $sizes as $size )
            {
                $size = self::getSize( $size );
                $image->createThumbnail( $size[ 'w' ], $size[ 'h' ], true )->setSaveOptions( join( 'x', $size ) .  '_' . $save_name, $path . 'thumb'  )->export( true );
            }

            // Destroy Resource
            $image->destroy();
            
        }

        // CallBack
        if( is_callable( $callback ) )
            call_user_func( $callback, $this, $save_name, $extension );

        return $save_name;

    }

}
