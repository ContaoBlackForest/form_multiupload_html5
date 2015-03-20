<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @package Form_multiupload_html5
 * @link    https://contao.org
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL
 */


/**
 * Register the namespaces
 */
ClassLoader::addNamespaces(
	array
	(
		'ContaoBlackforest',
	)
);


/**
 * Register the classes
 */
ClassLoader::addClasses(
	array
	(
		// Classes
		'ContaoBlackforest\Frontend\FormFields\FileUploadMultiple' => 'system/modules/form_multiupload_html5/classes/FileUploadMultiple.php',
	)
);
