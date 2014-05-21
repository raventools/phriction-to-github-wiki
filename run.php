<?php
#! /usr/bin/php

// Load the bootstrap
require_once 'includes/bootstrap.php';

$phabricator_export->cleanExported();
$documents = $phabricator_export->getDocumentIndex();
foreach($documents as $document) {
	print 'Exporting ' . $document->title . ' ... ' . PHP_EOL;
	$document->saveDocument();
}