<?php

/**
 * Unit test for the PurgeObseleteStaticCacheTask.
 *
 * Creates cache files in a randomly generated folder name in ASSETS_DIR. User running the test will need to have write
 * access to the assets/ folder.
 */

class StaticPagesPurgeObseleteTest extends SapphireTest {

	protected $usesDatabase = true;

	protected $destFolder = '/tmp/';

	protected $fsp_original = false;

	public function setUp() {
		parent::setUp();

		// Find out the current base assets directory
		$assets_dir = defined('ASSETS_DIR') ? ASSETS_DIR : 'assets/';

		// Create the test folders
		$uniqid = uniqid();
		$this->destFolder = Director::baseFolder() . '/' . $assets_dir . '/' . $uniqid . '/';
		mkdir($this->destFolder . 'folder/', 0777, true);

		// Use test folder for FilesystemPublisher
		if(Object::has_extension('SiteTree', 'FilesystemPublisher')) {
			foreach(Config::inst()->get('SiteTree', 'extensions') as $extension) {
				if(preg_match('/FilesystemPublisher\(\'(\w+)\',\s?\'(\w+)\'\)/', $extension, $matches)) {
					$this->fsp_original = $matches[0];
					break;
				}
			}
		}
		SiteTree::remove_extension('FilesystemPublisher');
		SiteTree::add_extension("FilesystemPublisher('" . $assets_dir . '/' . $uniqid . "', 'html')");

		// Create test files
		touch($this->destFolder . 'notexisting.html');
		touch($this->destFolder . 'folder/notexisting.html');
	}

	public function tearDown() {
		parent::tearDown();
		self::empty_temp_db();

		// Revert to using normal FilesystemPublisher configuration
		SiteTree::remove_extension("FilesystemPublisher('" . $this->destFolder . "')");
		if(!empty($this->fsp_original)) SiteTree::add_extension($this->fsp_original);

		// Remove the test files and folders
		/*unlink($this->destFolder . 'notexisting.html');
		unlink($this->destFolder . 'folder/notexisting.html');
		rmdir($this->destFolder . 'folder/');
		rmdir($this->destFolder);*/
	}

	public function testPurge() {
		// Check that test files exist
		$this->assertTrue(file_exists($this->destFolder . 'notexisting.html'));
		$this->assertTrue(file_exists($this->destFolder . 'folder/notexisting.html'));

		$task = new PurgeObseleteStaticCacheTask(array($this->destFolder, 'html'));
		$task->run();

		// Check that test files exist
		$this->assertFalse(file_exists($this->destFolder . 'notexisting.html'));
		$this->assertFalse(file_exists($this->destFolder . 'folder/notexisting.html'));
	}

}