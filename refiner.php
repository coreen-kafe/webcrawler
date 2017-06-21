<?php

    $directory = '/root/webcrawler/output/'; // The directory to the lesson text files
    $stopwords = array('are', 'a', 'is', 'he', 'we', 'has', 'and', 'by', 'for', 'will', 'at', 'on', 'the', 'in', 'from', 'to', 'i', 
		 'contact', 'overview', 'page', 'it', 'been', 'not', 'no', 'was', 'were', 'week', 'weeks', 'fee', 'fees', 'och', 'mer',
		 'off', 'get', 'can', 'also', 'take', 'out', 'our', 'and', 'or', 'you', 'an', 'hours', 'policy', 'that', 'how', 'which', 
		 'about', 'with', 'like', 'mail', 'up', 'down', 'above', 'del', 'dei', 'al', 'unless', 'where', 'their', 'should', 'later', 'links', 
		 'below', 'like', 'so', 'many', 'of', 'off', 'information', 'now', 'this', 'that', 'reviewed', 'good', 'better', 'most', 
		 'us', 'let', 'what', 'terms', 'your', 'all', 'key', 'new', 'error', 'already', 'under', 'sedan', 'support', 'one', 'two', 'three',
		 'four', 'five', 'six', 'seven', 'eight', 'nine', 'ten', 'first', 'second', 'third', 'forth', 'fifth', 'sixth', 'based', 'mostly',
		 'skill', 'skills', 'more', 'handle', 'my', 'these', 'as', 'each', 'every', 'de', 'des', 'en', 'early', 'after', 'still', 
		 'vid', 'such', 'currently', 'alla', 'who', 'their', 'website', 'reports', 'welcome', 'related', 'signed', 'through', 'there', 'become',
		 'being', 'please', 'often', 'between', 'where', 'under', 'other', 'solely', 'right', 'while', 'always', 'including', 'solely',
		 'their', 'supports', 'across', 'upcoming', 'further', 'between', 'highly', 'cannot', 'regarding', 'small', 'welcome', 
		 'found', 'years', 'year', 'month', 'email', 'during', 'using', 'email', 'address', 'cookies', 'banner', 'enabled', 'surname', 'termini',
		 'cookies', 'enabled', 'might', 'either', 'would', 'offer', 'release', 'among', 'version', 'existing', 'following', 'known', 'beginning', 
		 'given', 'specifically', 'according', 'existing', 'defined', 'listed', 'subject', 'contains', 'officially', 'tool', 'tools', 'partner',
		 'shall', 'provide', 'provides', 'enable', 'allow', 'makes', 'taking', 'world', 'websites', 'pages', 'pages', 'easily', 'browser', 'browsers',
		 'agreement', 'agreements', 'offers', 'cookie', 'cookies', );
    $stopcountrywords = array('armenia','australia', 'austria', 'belarus', 'brazil', 'canada', 'chile', 'colombia', 'croatia', 'czech', 'denmark', 
			      'ecuador', 'estonia', 'finland', 'france', 'georgia', 'germany', 'greece', 'hungary', 'india', 'ireland', 'israel', 'italy',
			      'japan', 'korea', 'latvia', 'lithuania', 'luxembourg', 'macedonia', 'moldova', 'norway', 'poland', 'portugal', 'slovenia', 
			      'africa', 'spain', 'sweden', 'switzerland', 'swiss', 'netherlands', 'ukraine', 'england', 'united', 'kingdom',);

    $pspell_link = pspell_new("en");
    $findex = 0;

    // Find all files in the directory which are .txt files
    foreach( glob( $directory . "*.txt" ) as $filename )
    {
	echo $findex++ . ": ". $filename;
	echo "\n";

	if (filesize($filename) < 100){
	    unlink($filename);
	}else{	

	$string = file_get_contents($filename, FILE_USE_INCLUDE_PATH);


    	$words = explode(" ", $string);
   	$trimmedwords = array();
	foreach ($words as $word) {
            $subwords = explode(PHP_EOL, $word);
            foreach ($subwords as $sord) {

            	if(preg_match('/[^\00-\255]/', $sord)) {
            	    //non english word found
		    array_push($trimmedwords, PHP_EOL);
            	}else{
                    if( preg_match('/[&@#-_]/', $sord)){
                    //remove special chars
                    }else{
			$sord = preg_replace('/[^A-Za-z0-9\-]/', '', $sord);
			if(!in_array(trim($sord), $stopwords)) {
			    if(strlen(trim($sord)) > 4) {
				if (pspell_check($pspell_link, strtolower(trim($sord))) ) {
				    if(!in_array(strtolower(trim($sord)), $stopcountrywords) )
                    	    	    	array_push($trimmedwords, $sord);
				}
			    }
			}
                    }
            	}

            }
    	}

    	$trimmedstring = implode(" ", $trimmedwords);
    	//echo $trimmedstring;

    	file_put_contents($filename, $trimmedstring, LOCK_EX);
        }//if(is_writable)
    }
?>
