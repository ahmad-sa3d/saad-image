<?php

return [
	
	'font_dir' => resource_path( 'assets/vendor/saad-image' ),

	// Image Configuration
	'image' => [

		// Allowable Image Formates
		'formates' => [ 'jpeg', 'jpg', 'png', 'gif' ],

		// Font Sub Folder
		'font_dir' => 'fonts',

		// Copyrights Default Font Filename
		'font' => 'Aller_Rg.ttf',

		'copyright' => [
			
			'text_color' => [ 'r' => 100, 'g' => 100, 'b' => 100, 'a' => 50 ],
			
			'text' 					=> 'Copyright',
			
			'font_size' 			=> 30,
			
			'text_angle'			=> 0,
			
			'block_separation'		=> 20,
			
			'vertical_location'		=> '50%',
			
			'horizontal_location'	=> '50%',
			
			'text_repeat'			=> true,

		],
		

	],
	
];