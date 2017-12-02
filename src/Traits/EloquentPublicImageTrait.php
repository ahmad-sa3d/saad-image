<?php
namespace Saad\Image\Traits;

use Illuminate\Support\Facades\Request;

trait EloquentPublicImageTrait
{
    protected $cached_links = [];

    
    public function getImagePublicLink( $key, $path, $prefix = null, $dynamic_url_size = false )
    {
        if( $dynamic_url_size && Request::has( 'image_size_prefix' ) )
        {
            $prefix =  Request::get( 'image_size_prefix' );

            if( $prefix == 'full' )
            {
                $path = rtrim( rtrim( $path, '/thumb/' ), '/' ) . '/';
                $prefix = null;
            }
        }
        else
            $prefix = $prefix ? $prefix . '_' : null;

        $ckey = $prefix . $key;

        if( isset( $this->cached_links[ $ckey ] ) )
            return $this->cached_links[ $ckey ];

        $link = $path . $prefix . $this->$key;

        if( !is_file( public_path( $link ) ) )
            $link = $path . $prefix . 'default.png';

        return $this->cached_links[ $ckey ] = $link;
    }

    /**
     * Delete Files ( Image and its thumbnails )
     * @param  [type] $old  [description]
     * @param  [type] $path [description]
     * @return [type]       [description]
     */
    public function deleteOldFor( $old, $path )
    {
        if( $old )
        {
            if( file_exists( $path . $old ) )
                unlink( $path . $old );

            $thumb_dir = $path . 'thumb' . DIRECTORY_SEPARATOR;
            
            if( is_dir( $thumb_dir ) )
            {
                // get cwd first, then change cwd
                $cwd = getcwd();
                chdir( $thumb_dir );
                foreach( glob( '*' . pathinfo( $old )[ 'filename' ] . '*' ) as $file )
                {
                    unlink( $thumb_dir . $file );
                }
                // go back to original cwd
                chdir( $cwd );
            }
            
        }
    }

}