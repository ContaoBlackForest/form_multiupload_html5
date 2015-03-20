<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2013 Leo Feyer
 *
 * @package   MultipleUploadHtml5
 * @author    Dominik Tomasi
 * @license   GNU
 * @copyright tomasiMEDIA 2014
 */

namespace tomasiMedia;

/**
 * Class FormFileUploadMultiple
 *
 * This is a Copy of Class FormFileUpload from Conato-Core with several modifications
 *
 * @package tomasiMedia
 */
class FormFileUploadMultiple extends \Widget implements \uploadable
{

	/**
	 * Template
	 *
	 * @var string
	 */
	protected $strTemplate = 'form_widget';

	/**
	 * Files
	 *
	 * @var array
	 */
	protected $arrFiles = array();

	/**
	 * Add specific attributes
	 *
	 * @param string
	 * @param mixed
	 */
	public function __set($strKey, $varValue)
	{
		switch ($strKey) {
			case 'maxlength':
				// Do not add as attribute (see #3094)
				$this->arrConfiguration['maxlength'] = $varValue;
				break;

			case 'mandatory':
				if ($varValue) {
					$this->arrAttributes['required'] = 'required';
				}
				else {
					unset($this->arrAttributes['required']);
				}
				parent::__set($strKey, $varValue);
				break;

			case 'multipleUpload':
				if ($varValue) {
					$this->multipleUpload = 'multiple';
				}
				break;

			case 'maxFileCount':
				if ($varValue) {
					$this->maxFileCount = $varValue;
				}
				break;
			case 'fSize':
				if ($varValue > 0) {
					$this->arrAttributes['size'] = $varValue;
				}
				break;

			default:
				parent::__set($strKey, $varValue);
				break;
		}
	}

	/**
	 * Validate whole input Field
	 */
	public function validate()
	{
		// No file specified
		if (!isset($_FILES[$this->name]) || empty($_FILES[$this->name]['name'])) {
			if ($this->mandatory) {
				if ($this->strLabel == '') {
					$this->addError($GLOBALS['TL_LANG']['ERR']['mdtryNoLabel']);
				}
				else {
					$this->addError(sprintf($GLOBALS['TL_LANG']['ERR']['mandatory'], $this->strLabel));
				}
			}

			return;
		}

		foreach ($_FILES[$this->name] as $key1 => $value1) {

			if ($key1 == 'size') {
				$this->uploadSize = array_sum($value1);
			}

			foreach ($value1 as $key2 => $value2) {
				$this->arrFiles[$key2][$key1] = $value2;
			}
		}

		if ($this->checkErrors()) {
			foreach ($this->arrFiles as $file) {
				$this->validateFile($file);
			}

			if (!$this->hasErrors()) {
				$_SESSION['FILES'] = $this->arrFiles;
				$this->log('Files from inputFiels "' . $this->name . '" uploaded successfully', __METHOD__, TL_FILES);

				$this->storeFileOnServer();
			}
		}

		unset($_FILES[$this->name]);

	}

	public function checkErrors()
	{
		$maxlength_kb = $this->getReadableSize($this->maxlength);

		// to many Files
		if (count($this->arrFiles) > $this->maxFileCount) {
			$this->addError(sprintf($GLOBALS['TL_LANG']['ERR']['fileCount'], $this->maxFileCount));
			$this->log('Files in FormField "' . $this->name . '" exceeds the maximum count of ' . $this->maxFileCount, __METHOD__, TL_ERROR);

			unset($_FILES[$this->name]);
			return false;
		}

		// Files is too big
		if ($this->maxlength > 0 && $this->uploadSize > $this->maxlength) {
			$this->addError(sprintf($GLOBALS['TL_LANG']['ERR']['filesize'], $maxlength_kb));
            $this->log('Files in FormField "'.$this->name.'" exceeds the maximum file size of '.$maxlength_kb, __METHOD__, TL_ERROR);

			unset($_FILES[$this->name]);
			return false;
		}

		foreach ($this->arrFiles as $arrFile) {

			if (!is_uploaded_file($arrFile['tmp_name'])) {
				if ($arrFile['error'] == 1 || $arrFile['error'] == 2) {
					$this->addError(sprintf($GLOBALS['TL_LANG']['ERR']['filesize'], $maxlength_kb));
					$this->log('File "' . $arrFile['name'] . '" exceeds the maximum file size of ' . $maxlength_kb, __METHOD__, TL_ERROR);
				}
				elseif ($arrFile['error'] == 3) {
					$this->addError(sprintf($GLOBALS['TL_LANG']['ERR']['filepartial'], $arrFile['name']));
					$this->log('File "' . $arrFile['name'] . '" was only partially uploaded', __METHOD__, TL_ERROR);
				}
				elseif ($arrFile['error'] > 0) {
					$this->addError(sprintf($GLOBALS['TL_LANG']['ERR']['fileerror'], $arrFile['error'], $arrFile['name']));
					$this->log('File "' . $arrFile['name'] . '" could not be uploaded (error ' . $arrFile['error'] . ')', __METHOD__, TL_ERROR);
				}

				unset($_FILES[$this->name]);
				return false;
			}
		}


		return true;
	}

	public function storeFileOnServer()
	{

		if ($this->storeFile) {
			$intUploadFolder = $this->uploadFolder;

			// Overwrite the upload folder with user's home directory
			if ($this->useHomeDir && FE_USER_LOGGED_IN) {
				$this->import('FrontendUser', 'User');

				if ($this->User->assignDir && $this->User->homeDir) {
					$intUploadFolder = $this->User->homeDir;
				}
			}

			$objUploadFolder = \FilesModel::findByUuid($intUploadFolder);

			// The upload folder could not be found
			if ($objUploadFolder === null) {
				throw new \Exception("Invalid upload folder ID $intUploadFolder");
			}

			$strUploadFolder = $objUploadFolder->path;

			// Store the file if the upload folder exists
			if ($strUploadFolder != '' && is_dir(TL_ROOT . '/' . $strUploadFolder)) {
				$this->import('Files');

				foreach ($this->arrFiles as $arrFile) {
					// Do not overwrite existing files
					if ($this->doNotOverwrite && file_exists(TL_ROOT . '/' . $strUploadFolder . '/' . $arrFile['name'])) {
						$offset   = 1;
						$pathinfo = pathinfo($arrFile['name']);
						$name     = $pathinfo['filename'];

						$arrAll   = scan(TL_ROOT . '/' . $strUploadFolder);
						$arrFiles = preg_grep('/^' . preg_quote($name, '/') . '.*\.' . preg_quote($pathinfo['extension'], '/') . '/', $arrAll);

						foreach ($arrFiles as $strFile) {
							if (preg_match('/__[0-9]+\.' . preg_quote($pathinfo['extension'], '/') . '$/', $strFile)) {
								$strFile  = str_replace('.' . $pathinfo['extension'], '', $strFile);
								$intValue = intval(substr($strFile, (strrpos($strFile, '_') + 1)));

								$offset = max($offset, $intValue);
							}
						}

						$arrFile['name'] = str_replace($name, $name . '__' . ++$offset, $arrFile['name']);
					}

					$this->Files->move_uploaded_file($arrFile['tmp_name'], $strUploadFolder . '/' . $arrFile['name']);
					$this->Files->chmod($strUploadFolder . '/' . $arrFile['name'], $GLOBALS['TL_CONFIG']['defaultFileChmod']);

					$_SESSION['FILES'][$this->name] = array
					(
						'name'     => $arrFile['name'],
						'type'     => $arrFile['type'],
						'tmp_name' => TL_ROOT . '/' . $strUploadFolder . '/' . $arrFile['name'],
						'error'    => $arrFile['error'],
						'size'     => $arrFile['size'],
						'uploaded' => true
					);

					// Generate the DB entries
					$strFile = $strUploadFolder . '/' . $arrFile['name'];
					$objFile = \FilesModel::findByPath($strFile);

					// Existing file is being replaced (see #4818)
					if ($objFile !== null) {
						$objFile->tstamp = time();
						$objFile->path   = $strFile;
						$objFile->hash   = md5_file(TL_ROOT . '/' . $strFile);
						$objFile->save();
					}
					else {
						\Dbafs::addResource($strFile);
					}

					// Update the hash of the target folder
					\Dbafs::updateFolderHashes($strUploadFolder);

					// Add a log entry
					$this->log('File "' . $arrFile['name'] . '" has been moved to "' . $strUploadFolder . '"', __METHOD__, TL_FILES);
				}
			}
		}

	}


	/**
	 * Validate each File
	 */
	public function validateFile($arrFile)
	{


		// Romanize the filename
		$arrFile['name'] = utf8_romanize($arrFile['name']);

		$strExtension = pathinfo($arrFile['name'], PATHINFO_EXTENSION);
		$uploadTypes  = trimsplit(',', $this->extensions);

		// File type is not allowed
		if (!in_array(strtolower($strExtension), $uploadTypes)) {
			$this->addError(sprintf($GLOBALS['TL_LANG']['ERR']['filetype'], $strExtension));
			$this->log('File type "' . $strExtension . '" is not allowed to be uploaded (' . $arrFile['name'] . ')', __METHOD__, TL_ERROR);

			unset($_FILES[$this->name]);
			return false;
		}

		if (($arrImageSize = @getimagesize($arrFile['tmp_name'])) != false) {
			// Image exceeds maximum image width
			if ($arrImageSize[0] > $GLOBALS['TL_CONFIG']['imageWidth']) {
				$this->addError(sprintf($GLOBALS['TL_LANG']['ERR']['filewidth'], $arrFile['name'], $GLOBALS['TL_CONFIG']['imageWidth']));
				$this->log('File "' . $arrFile['name'] . '" exceeds the maximum image width of ' . $GLOBALS['TL_CONFIG']['imageWidth'] . ' pixels', __METHOD__, TL_ERROR);

				unset($_FILES[$this->name]);
				return false;
			}

			// Image exceeds maximum image height
			if ($arrImageSize[1] > $GLOBALS['TL_CONFIG']['imageHeight']) {
				$this->addError(sprintf($GLOBALS['TL_LANG']['ERR']['fileheight'], $arrFile['name'], $GLOBALS['TL_CONFIG']['imageHeight']));
				$this->log('File "' . $arrFile['name'] . '" exceeds the maximum image height of ' . $GLOBALS['TL_CONFIG']['imageHeight'] . ' pixels', __METHOD__, TL_ERROR);

				unset($_FILES[$this->name]);
				return false;
			}
		}

		return true;
	}


	/**
	 * Generate the widget and return it as string
	 *
	 * @return string
	 */
	public function generate()
	{
		return sprintf(
			'<input type="file" name="%s" id="ctrl_%s" class="upload%s"%s %s %s',
			$this->name . (isset($this->multipleUpload) ? '[]' : ''),
			$this->strId,
			(strlen($this->strClass) ? ' ' . $this->strClass : ''),
			$this->getAttributes(),
			$this->multipleUpload,
			$this->strTagEnding
		) . $this->addSubmit();
	}
}
