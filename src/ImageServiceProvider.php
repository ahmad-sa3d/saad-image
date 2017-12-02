<?php
namespace Saad\Image;

use Illuminate\Support\ServiceProvider;

class ImageServiceProvider extends ServiceProvider
{
	/**
	 * Package name
	 * @var string
	 */
	protected $package_name = 'saad-image';

	/**
	 * Boot Package
	 * @return Void
	 */
	public function boot(){

		// Publish Config File
		$this->publishes([
			__DIR__ .DS. '..' .DS. 'config' .DS. $this->package_name . '.php' => config_path($this->package_name . '.php'),
		], 'config');

		// Publish fonts
		$this->publishes([
			__DIR__ .DS. '..' .DS. 'resources' .DS. 'fonts' => resource_path( 'assets' .DS. 'vendor' .DS. $this->package_name .DS. 'fonts'),
		], 'fonts');
	}

	/**
	 * Register Package Setup
	 * @return Void
	 */
	public function register(){

	}
}