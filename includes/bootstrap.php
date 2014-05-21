<?php

/**
 * Loads files needed to get started
 */
define('DOCROOT', dirname(dirname(__FILE__)));
require_once DOCROOT . '/config.php';
require_once DOCROOT . '/includes/class.phabricatorexport.php';

try {
	$phabricator_export = new PhabricatorExport($config);
} catch (Exception $e) {
	print $e->getMessage();
	exit;
}