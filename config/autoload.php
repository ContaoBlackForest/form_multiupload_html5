<?php

/**
 * Register the namespaces
 */
ClassLoader::addNamespaces(
	array
	(
		'tomasiMedia',
	)
);


/**
 * Register the classes
 */
ClassLoader::addClasses(
	array
	(
		'tomasiMedia\FormFileUploadMultiple' => 'system/modules/multiupload_html5/classes/FormFileUploadMultiple.php',
	)
);

