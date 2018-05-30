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
			__DIR__ .DIRECTORY_SEPARATOR. '..' .DIRECTORY_SEPARATOR. 'config' .DIRECTORY_SEPARATOR. $this->package_name . '.php' => config_path($this->package_name . '.php'),
		], 'config');

		// Publish fonts
		$this->publishes([
			__DIR__ .DIRECTORY_SEPARATOR. '..' .DIRECTORY_SEPARATOR. 'resources' .DIRECTORY_SEPARATOR. 'fonts' => resource_path('assets' .DIRECTORY_SEPARATOR. 'vendor' .DIRECTORY_SEPARATOR. $this->package_name .DIRECTORY_SEPARATOR. 'fonts'),
		], 'fonts');
	}

	/**
	 * Register Package Setup
	 * @return Void
	 */
	public function register(){

	}
}