<?php

/**
 * Example file to demonstrate use of directory_info class
 *
 * File: example/example.php
 * @package directory_info
 */

	include( '.\libs\directoryinfo.inc.php' );

	// Directory path relative to the location of this file
	if (isset($_POST["directory"])){
		$pathtodir = $_POST["directory"];
	
		$dirobj = new directory_info();
		$mList = $dirobj->getJSObjectMap($pathtodir);
	}
?>