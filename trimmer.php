<?php

    $directory = '/root/webcrawler/output/'; // The directory to the lesson text files
    $findex = 0;

    // Find all files in the directory which are .txt files
    foreach( glob( $directory . "*.txt" ) as $filename )
    {
	echo $findex++ . ": ". $filename;
	echo "\n";
	
	$string = file_get_contents($filename, FILE_USE_INCLUDE_PATH);
	$string = preg_replace('/^[ \t]*[\r\n]+/m', '', $string);


    	file_put_contents($filename, $string, LOCK_EX);
    }



    foreach( glob( $directory . "*.txt" ) as $filename )
    {
	if(filesize($filename) < 100) unlink($filename);
    }
?>
