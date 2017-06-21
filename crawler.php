<?php
// jinyong.jo@gmail.com

// It may take a whils to crawl a site ...
set_time_limit(10000);

// Inculde the phpcrawl-mainclass
include("libs/PHPCrawler.class.php");
include("libs/Html2Text.php");

// Extend the class and override the handleDocumentInfo()-method 
class MyCrawler extends PHPCrawler 
{

  function handleDocumentInfo($DocInfo) 
  {
    // Just detect linebreak for output ("\n" in CLI-mode, otherwise "<br>").
    if (PHP_SAPI == "cli") $lb = "\n";
    else $lb = "<br />";

    // Print the URL and the HTTP-status-Code
    echo "Page requested: ".$DocInfo->url." (".$DocInfo->http_status_code.")".$lb;
    
    // Print the refering URL
    echo "Referer-page: ".$DocInfo->referer_url.$lb;
    echo "Referer-Id: ".$this->getEntityId() . "\n";   
    
    // Print if the content of the document was be recieved or not
    if ($DocInfo->received == true){
      echo "Content received: ".$DocInfo->bytes_received." bytes".$lb;
      $rawContent = $DocInfo->source;

      $readytocrt = new Html2Text($rawContent);
      $rmHTMLTagContent = $readytocrt->getText();
      $rmHTMLTagContent = trim(preg_replace('~\[.*?\]~', '', $rmHTMLTagContent));

      $refinedContent =  $rmHTMLTagContent;

      //  parse_url($DocInfo->referer_url)['host'] => url of SP

      $myfile = file_put_contents($this->getEntityId() . ".txt", $refinedContent.PHP_EOL, FILE_APPEND | LOCK_EX);
      // remove all tab+*
      $cmd_string =  "sed -i 's/^[ \t]*//' " . $this->getEntityId() . ".txt";
      shell_exec($cmd_string);
      $cmd_string = "sed -i '/^*/d' ". $this->getEntityId() . ".txt";
      shell_exec($cmd_string);
      // remove all empty line
      $cmd_strig  = "sed -i '/^\s*$/d' " . $this->getEntityId() . ".txt";
      shell_exec($cmd_string);
      // remove all lines of length 20 or less
      $cmd_string = "sed -ri '/^.{,20}$/d' " . $this->getEntityId() . ".txt"; 
      shell_exec($cmd_string);
      // remove all lines including http:// or https://
      $cmd_string = "sed -i '/http:\/\//d' " . $this->getEntityId() . ".txt";
      shell_exec($cmd_string);
      $cmd_string = "sed -i '/https:\/\//d' " . $this->getEntityId() . ".txt";
      shell_exec($cmd_string);
    }else
      echo "Content not received".$lb; 
    
    // Now you should do something with the content of the actual
    // received page or file ($DocInfo->source), we skip it in this example 
    
    echo $lb;
    
    flush();
  } 

}

// Now, create a instance of your class, define the behaviour
// of the crawler (see class-reference for more options and details)
// and start the crawling-process.

class Parser
{
    private $md;
    public function __construct($metadatafile)
    {
	$this->md = simplexml_load_file($metadatafile);
	if( false === $this->md) {
	    exit;
	}
	$this->md->registerXPathNamespace('md', 'urn:oasis:names:tc:SAML:2.0:metadata');
    }

    public function getCrawlInfo($string)
    {
	$keywords = preg_split("/[>,<]+/", $string);
	$language = $keywords[1];
	$result = array(
		"lang" => array(),
		"value" => array()
	);
	if(strpos($language, "xml:lang") !== false){
		$lang = preg_split("/[\"]+/", $language);
		array_push($result['lang'], $lang[1]); 	
        }
	$value = $keywords[2];
	array_push($result['value'], $value);
	return $result;
    }

    public function getSPContent($entityId)
    {
	$SPContent = array(
	    "entityID" => array(),
	    "InformationURL" => array(),
	    "description" => array(),
	    "OrganizationURL" => array()
	);
	
        $result = $this->md->xpath('//md:EntityDescriptor[@entityID="'.$entityId. '"]/md:SPSSODescriptor/md:Extensions/mdui:UIInfo/mdui:InformationURL');
	
	if(0 !== count($result)) {
            array_push($SPContent['entityID'], $entityId);
	    foreach($result as $ep) {
		$xmlstring = $ep->asXML();
		$rval =  $this->getCrawlInfo($xmlstring);
		array_push($SPContent['InformationURL'], array("Lang" => $rval['lang'], "Url" => $rval['value']));
	    }
	}

	$result = $this->md->xpath('//md:EntityDescriptor[@entityID="'.$entityId. '"]/md:SPSSODescriptor/md:Extensions/mdui:UIInfo/mdui:Description');

	if(0 !== count($result)) {
	    if(!in_array($entityId, $SPContent['entityID']))
            	array_push($SPContent['entityID'], $entityId);
            foreach($result as $ep) {
                $xmlstring = $ep->asXML();
                $rval =  $this->getCrawlInfo($xmlstring);
                array_push($SPContent['description'], array("Lang" => $rval['lang'], "Url" => $rval['value']));
            }
        }
	
        $result = $this->md->xpath('//md:EntityDescriptor[@entityID="'.$entityId. '"]/md:Organization/md:OrganizationURL');

        if(0 !== count($result)) {
            if(!in_array($entityId, $SPContent['entityID']))
                array_push($SPContent['entityID'], $entityId);
            foreach($result as $ep) {
                $xmlstring = $ep->asXML();
                $rval =  $this->getCrawlInfo($xmlstring);
                array_push($SPContent['OrganizationURL'], array("Lang" => $rval['lang'], "Url" => $rval['value']));
            }
        }
	return $SPContent;
    }
}


$xml = file_get_contents('http://magg.kreonet.net/kafe-metadata/kafe-downstream-eduGAIN-sp-intrim.xml');//./kafe-downstream-eduGAIN-sp-intrim.xml');
if ($xml === false) {
    echo "failed to loading the xml file";
    exit;
}
$dom = new DOMDocument();
$dom->loadxml($xml);



//entityDescriptorNodeList
$ednList = $dom->getElementsByTagNameNS("urn:oasis:names:tc:SAML:2.0:metadata", "EntityDescriptor");
$cnum = 0;

$crawler = new MyCrawler();
$Parser = new Parser('./kafe-downstream-eduGAIN-sp-intrim.xml');

foreach($ednList as $edNode) {
    $entityId = $edNode->getAttribute("entityID");
    $content = $Parser->getSPContent($entityId);

    if ($content["entityID"] != null) {
        $eid = $content["entityID"][0];


        $iUrl = $content["InformationURL"];
        $oUrl = $content["OrganizationURL"];
        $desc = $content["description"];
    

        if( count($iUrl) > 0 ) { 
	    if( count($iUrl) == 1) {
	        $tUrl = $content["InformationURL"][0]["Url"][0];
	    }else{
	        foreach($iUrl as $i_url){
		    if( $i_url["Lang"][0] == "en"){
		        $tUrl = $i_url["Url"][0];
		    }
	        }
	    }
        } else {  // if no iUrl, use oUrl;
	    if ( count($oUrl) > 0 ) {
		if( count($oUrl) == 1) {
		    $tUrl = $content["OrganizationURL"][0]["Url"][0];
		}else{
		    foreach($oUrl as $o_url){
			if( $o_url["Lang"][0] == "en"){
			    $tUrl = $o_url["Url"][0];
			}
		    }
		}
	    }   
        }
	
	if (count($desc)  > 0) {
	    if(count($desc) == 1) {
		$tDesc = $content["description"][0]["Url"][0];
	    }else{
		foreach($desc as $t_desc){
		    if( $t_desc["Lang"][0] == "en"){
			$tDesc = $t_desc["Url"][0];
		    }
		}
	    }

	}
	
	if($tUrl != null) {
	    $crawler->setURL($tUrl);	
	    $crawler->setEntityId($eid);
	    // Only receive content of files with content-type "text/html"
            $crawler->addContentTypeReceiveRule("#text/html#");

            // Ignore links to pictures, dont even request pictures
            $crawler->addURLFilterRule("#\.(jpg|jpeg|gif|png)$# i");

            // Store and send cookie-data like a browser does
            $crawler->enableCookieHandling(true);

            // Set the traffic-limit to 1 MB (in bytes,
            // for testing we dont want to "suck" the whole site)
            $crawler->setTrafficLimit(1000 * 1024);

            // Thats enough, now here we go
            $crawler->go();

          // At the end, after the process is finished, we print a short
            // report (see method getProcessReport() for more information)
            $report = $crawler->getProcessReport();

            if (PHP_SAPI == "cli") $lb = "\n";
            else $lb = "<br />";

            echo "Summary:".$lb;
            echo "Links followed: ".$report->links_followed.$lb;
            echo "Documents received: ".$report->files_received.$lb;
            echo "Bytes received: ".$report->bytes_received." bytes".$lb;
            echo "Process runtime: ".$report->process_runtime." sec".$lb;
	}

	if($tDesc != null) {
	    file_put_contents(base64_encode(trim($eid)) . ".txt", $tDesc,  FILE_APPEND | LOCK_EX);
	}
	echo $cnum++ . " : ".  $eid . " : " . $tUrl . " : ". $tDesc;

    } //if ($contnet[..
    
    
    $iUrl = null;
    $oUrl = null;
    $desc = null;
    $tUrl = null;
    $tDesc = null;

//    var_dump($tUrl);

}

?>
