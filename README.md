# Image Library, used to manipulate images like crop, resize and writing texts on images

## Install

You can pull in the package via composer:

```bash
$ composer require saad/image
```

## Register Package Service Provider for Laravel

> `Laravel 5.5` The package will automatically register itself.

#

> `Laravel < 5.5`

> add the following to your providers in config\app.php 
> 
> ```php
> 'providers' => [
> 	.....
> 	Saad\Image\ImageServiceProvider::class,
> ]
> ```

## Publish package aconfiguration and assets
```bash
	php artisan vendor:publish --provider="Saad\Image\ImageServiceProvider"
```


## use

to use Core Image Library

```php
	<?php
	
	use Saad\Image\Image;
	
	$image = new Image( $image_src );
	
	// you can then manipulate image
	
	// Set Save Output Format
	$image->setOutputFormat('png');
	
	// Set Save Options
	$image->setSaveOptions('image_name', 'Save_Path');
	
	// Create Thumbnail
	$image->createThumbnail(100, 100);
	
	
	// Save and destroy resource from memory
	$image->export();
	
	// Save and keep resource to continue manipulating same resource
	$image->export(true);
	
	// Get Image as data-url string
	$image->embed();

```


## Laravel Eloquent Traits
this package ships with two traits to make it easy to save images on eloquent models


`EloquentImageSaverTrait` used to dynamically save uploaded images and creating thumbnails

`EloquentPublicImageTrait` used to get public url for saved images and it's created thumbnails

__Full Example__

assume we have a user Model which has image column to store user profile image

```php
	<?php
	
	....
	use Saad\Image\Traits\EloquentImageSaverTrait;
	use Saad\Image\Traits\EloquentPublicImageTrait;
	
	class User extends Model
	{
		use EloquentImageSaverTrait, EloquentPublicImageTrait;
		
		protected static $profile_image_sizes = [
        	[ 256, 256 ],  	// Default image size
        	[ 100, 100 ], 	// Thumbnail
        	[ 46, 46 ],		// Thumbnail
        	[ 26, 26 ],		// Thumbnail
        ];
	}
	
	/**
     * Get Image
     * @return String       Image Url
     */
    public function getImage()
    {
        return $this->getImagePublicLink( 'image', 'images/profiles/' );
    }

    /**
     * Get Image Thumbnail
     * @param  String $size Thumbnail size in format '46x46'
     * @return String       Image Thumbnail Url
     */
    public function getImageThumb( $size )
    {
        return $this->getImagePublicLink( 'image', 'images/profiles/thumb/', $size );
    }
    
    
	
	/**
     * Mutator To Save and Set Image
     *
     * Save Image and create thumbnails, and set image name attribute to model
     */
    public function setImageAttribute( $file )
    {
        $path = public_path( 'images/profiles/' );        

        if($file instanceof \Illuminate\Http\UploadedFile) {

            $this->attributes['image'] = $this->saveImage( $file, $path, null, static::$profile_image_sizes, function( $object, $save_name ) use($path){
                /**
                 * Delete Old Images
                 */
                $this->deleteOldFor( $object->image, $path );
                
            } );

        } else {
            $file = realpath(public_path('/images/temp/'.$file));
            $this->attributes['image'] = $this->saveLocalImage( $file, $path, null, static::$profile_image_sizes, function( $object, $save_name ) use($path){
                /**
                 * Delete Old Images
                 */
                $this->deleteOldFor( $object->image, $path );
            });
        }
    }
```
