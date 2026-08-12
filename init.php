<?php
// MODPATH/about/init.php
defined('BASIS_VERSION') OR define('BASIS_VERSION', '4.0.1');

Kohana::$config->load('menu')
    ->set('basis', array(
        'title' => 'basis',
        'url' => '/basis',
        'icon' => 'fa-cog',
        'order' => 1,
		'disabled' => true, 
         )
    );

	
Route::set('module_default', '(<controller>(/<action>(/<id>)))')
	->defaults(array(
		'controller' => 'basis',
		'action'     => 'index',
	));