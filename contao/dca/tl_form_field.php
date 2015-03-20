<?php
/**
 * Contao Form multi upload
 * Copyright (C) 2015 Sven Baumann
 *
 * PHP version 5
 *
 * @package   contaoblackforest/form_multiupload_html5
 * @file      tl_form_field.php
 * @author    Sven Baumann <baumann.sv@gmail.com>
 * @author    Dominik Tomasi <dominik.tomasi@gmail.com>
 * @license   LGPL-3.0+
 * @copyright ContaoBlackforest 2015
 */


/**
 * Insert Config for Multiple
 */
$GLOBALS['TL_DCA']['tl_form_field']['palettes']['upload'] = str_replace('mandatory', 'mandatory,multipleUpload,maxFileCount', $GLOBALS['TL_DCA']['tl_form_field']['palettes']['upload']);

$GLOBALS['TL_DCA']['tl_form_field']['fields']['multipleUpload'] = array(
	'label'     => &$GLOBALS['TL_LANG']['tl_form_field']['multipleUpload'],
	'exclude'   => true,
	'inputType' => 'checkbox',
	'sql'       => "char(1) NOT NULL default ''",
	'eval'      => array('tl_class' => 'w50')
);

$GLOBALS['TL_DCA']['tl_form_field']['fields']['maxFileCount'] = array(
	'label'     => &$GLOBALS['TL_LANG']['tl_form_field']['maxFileCount'],
	'exclude'   => true,
	'inputType' => 'text',
	'eval'      => array('rgxp' => 'digit', 'tl_class' => 'w50'),
	'sql'       => "smallint(5) unsigned NOT NULL default '0'"
);

