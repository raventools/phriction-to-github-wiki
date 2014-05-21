<?php

class PhabricatorExport	{
	protected $config = array();

	public function __construct($config=array()) {
		$this->config = $config;
		if (!is_array($this->config)) {
			throw new Exception('Could not load the configuraiton file. Create a config.php in the root directory.');
		}
		$this->db = new PDO(
    		sprintf('mysql:host=%s;dbname=%s', $config['dbhost'], $config['dbname']),
    		$config['dbuser'],
    		$config['dbpass']
    	);
	}

	public function getDocumentIndex() {
		$query = 'SELECT * FROM phriction_document ORDER BY depth ASC';
		$rows = $this->db->query($query);
		$rows->setFetchMode(PDO::FETCH_ASSOC);

		$return = array();
		while($row = $rows->fetch()) {
			$return[] = $this->getDocument($row['id']);
		}
		return $return;
	}

	public function getDocument($id) {
		$query = sprintf('SELECT * FROM phriction_content WHERE documentID = %d ORDER BY version DESC LIMIT 1', $id);
		$row = $this->db->query($query);
		$row->setFetchMode(PDO::FETCH_CLASS, 'PhrictionDoc');
		return $row->fetch();
	}

	public function getDocumentBySlug($slug) {
		$slug = ltrim($slug, '/');
		$slug = rtrim($slug, '/');
		if (!$slug) {
			return false;
		}
		$query = sprintf('SELECT * FROM phriction_content WHERE slug = "%s" ORDER BY version DESC LIMIT 1', $slug . '/');
		$row = $this->db->query($query);
		$row->setFetchMode(PDO::FETCH_CLASS, 'PhrictionDoc');
		return $row->fetch();
	}

	public function cleanExported() {
		exec('rm -fR ' . DOCROOT . '/exported');
		exec('mkdir -p ' . DOCROOT . '/exported');
		exec('touch ' . DOCROOT . '/exported/.gitkeep');
	}

	public function getConfig() {
		if (is_array($this->config)) {
			print_r($this->config);
		} else {
			print 'No configuration data found!';
		}
	}

}

class PhrictionDoc {


	public function makeFilename() {
		if ($this->slug == '/') {
			return 'Home';
		}

		$folder = dirname($this->slug);
		$filename = str_replace(array(' ', '/'), '-', $this->title);
		if (!file_exists(DOCROOT . '/exported/' . $folder)) {
			mkdir(DOCROOT . '/exported/' . $folder, 0777, true);
		}
		return $folder . '/' . $filename;
	}

	public function saveDocument() {

		$this->cleanUpContent();

		// Ignore empty files
		if (!trim($this->content)) {
			return;
		}

		$filename = $this->makeFilename();
		$content = $this->content;
		$fh = fopen(DOCROOT . '/exported/' . $filename . '.md', 'w+');
		fwrite($fh, $content);
		fclose($fh);
	}


	private function cleanUpContent() {

		// Simple stuff right off the bat
		$this->content = str_replace('http://phabricator.raventools.com/', '/', $this->content);
		$this->content = str_replace('Phabricator', 'RavenWiki', $this->content);

		$this->fixNumberedLists();
		$this->fixUnorderedLists();
		$this->fixHeadings();
		$this->fixCodeBlocks();
		$this->fixLinks();

	}

	private function fixNumberedLists() {
		$lines = explode("\n", $this->content);
		$counter = 0;
		foreach ($lines as $i => $line) {
			if (preg_match('/^[\#]+/', $line)) {
				$counter++;
			} else {
				$counter = 0;
			}
			$lines[$i] = preg_replace('/^[\#]+/', $counter . '. ', $line, -1);
		}
		$this->content = join($lines, "\n");
	}

	private function fixUnorderedLists() {
		$lines = explode("\n", $this->content);
		foreach ($lines as $i => $line) {
			$lines[$i] = preg_replace('/^[\*\*]+/', '  *', $line, -1);
		}
		$this->content = join($lines, "\n");
	}

	private function fixHeadings() {
		$lines = explode("\n", $this->content);
		foreach ($lines as $i => $line) {
			$lines[$i] = preg_replace('/^[\=]+/', '#', $line, -1);	
		}
		$this->content = join($lines, "\n");
	}

	private function fixCodeBlocks() {
		$is_open = false;
		$lines = explode("\n", $this->content);
		foreach ($lines as $i => $line) {
			// Increase to four spaces
			if (preg_match('/^  [^\*]/', $line)) {
				$lines[$i] = '  ' . $line;
			}

			// Clean up backtick notation
			if (preg_match('/^```/', $line)) {
				$lines[$i] = '';
				if ($is_open) {
					$is_open = false;
				}
			}
			if ($is_open === true) {
				$lines[$i] = '    ' . $line;
			}
		}
		$this->content = join($lines, "\n");
	}

	private function fixLinks() {
		$lines = explode("\n", $this->content);
		foreach ($lines as $i => $line) {
			// Reverse the line notation
			$line = preg_replace_callback('/\[\[(.*?)\|(.*?)\]\]/', array($this, 'lookupNewSlug'), $line, -1);
			$lines[$i] = $line;
		}
		$this->content = join($lines, "\n");
	}

	private function lookupNewSlug($matches=array()) {

		global $phabricator_export;

		$matches[1] = trim($matches[1]);
		$matches[2] = trim($matches[2]);

		// Find the matching document by slug
		$document = $phabricator_export->getDocumentBySlug($matches[1]);
		if (is_object($document)) {
			$matches[1] = basename($document->makeFilename());
		}

		return sprintf('[[%s|%s]]', $matches[2], $matches[1]);
	}

}