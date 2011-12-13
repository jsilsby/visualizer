<?php

/**
 * Example file to demonstrate use of directory_info class
 *
 * File: example/example.php
 * @package directory_info
 */

	include( '..\libs\directoryinfo.inc.php' );

	//require_once('..\libs\FirePHPCore\FirePHP.class.php');
	//$firephp = FirePHP::getInstance(true);
	try {	
		// Directory path relative to the location of this file
		if (isset($_GET["directory"])){
			$pathtodir = '..\\' . $_GET["directory"];	   
			$dirobj = new directory_info();

			$mList = $dirobj->getJSObjectMap($pathtodir);
			$rList = $dirobj->get_ext_based_filelist(null, $pathtodir, true);
			//$firephp->log($rList, "rlist");
		
			foreach ($mList as $item) {
				echo '<div name="file_name" title="' . $item["name"] . '">';
				echo '<div id="src_code">' . $item["source"] . "</div></div>";
			}
			
			echo '<div id="resource_list">';
			for ($i = 0; $i < count($rList); $i++) {
			//	$firephp->log($rList[$i], "resource");
				echo '<div name="resource_name">' . $rList[$i] . "</div>";
			}
			echo '</div>';
		}
	} catch (Exception $e) {
			die ('Failed: ' . $e->getMessage());
	}
?>