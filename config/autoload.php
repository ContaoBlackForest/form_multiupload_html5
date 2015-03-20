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
		'tomasiMedia\FormFileUploadMultiple' => 'system/modules/form_multiupload_html5/classes/FormFileUploadMultiple.php',
	)
);

