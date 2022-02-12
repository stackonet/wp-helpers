<?php

use Stackonet\WP\Examples\Faqs\FaqManager;

// If this file is called directly, abort.
defined( 'ABSPATH' ) || exit;

spl_autoload_register( function ( $class ) {
	// project-specific namespace prefix
	$prefix = 'Stackonet\\WP\\Examples\\';

	// does the class use the namespace prefix?
	$len = strlen( $prefix );
	if ( strncmp( $prefix, $class, $len ) !== 0 ) {
		// no, move to the next registered autoloader
		return;
	}

	// get the relative class name
	$relative_class = substr( $class, $len );

	// replace the namespace prefix with the base directory, replace namespace
	// separators with directory separators in the relative class name, append
	// with .php
	$file = __DIR__ . '/' . str_replace( '\\', '/', $relative_class ) . '.php';

	// if the file exists, require it
	if ( file_exists( $file ) ) {
		require $file;
	}
} );

add_action(
	'plugins_loaded',
	function () {
		FaqManager::init();
	}
);
