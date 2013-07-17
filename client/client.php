<?php
// This client for local_wstemplate is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//

/**
 * XMLRPC client for Moodle 2 - local_wstemplate
 *
 * This script does not depend of any Moodle code,
 * and it can be called from a browser.
 *
 * @authorr Jerome Mouneyrac
 */

/// MOODLE ADMINISTRATION SETUP STEPS
// 1- Install the plugin
// 2- Enable web service advance feature (Admin > Advanced features)
// 3- Enable XMLRPC protocol (Admin > Plugins > Web services > Manage protocols)
// 4- Create a token for a specific user and for the service 'My service' (Admin > Plugins > Web services > Manage tokens)
// 5- Run this script directly from your browser: you should see 'Hello, FIRSTNAME'

/// SETUP - NEED TO BE CHANGED
//$token = '1bf238130a6a2a88f95b87b302bc7dd5'; oncampus 
$token = '0f8e18391269ad289f4e10c31a3756f4'; //portal

error_reporting(E_ALL);
ini_set("display_errors",1);

ini_set('include_path', '/usr/local/www/oc-ssl/portal/soaptest/zend' . PATH_SEPARATOR . ini_get('include_path'));
ini_set('include_path', '/usr/local/www/oc-ssl/portal/soaptest' . PATH_SEPARATOR . ini_get('include_path'));
#ini_set('include_path', '/usr/local/www/oc-ssl/portal/soaptest/zend/Zend' . PATH_SEPARATOR . ini_get('include_path'));
require_once 'lib.php';

define ("MOODLE_URL",		"http://dev.oncampus.de/moodle2");
define ("TOKEN",				"0f8e18391269ad289f4e10c31a3756f4");
#define ("TOKEN",				"6733b92bd57fe24a6bfe090f45832627");

#define ("TOKEN",				"a7dffa99cd86619ca16ead5bafbd5837");


# a7dffa99cd86619ca16ead5bafbd5837  dev.oncampus.de/moodle2
$domainname = 'http://dev.oncampus.de/moodle2';

/// FUNCTION NAME
//$functionname = 'local_oncampus_createcourse';


/// PARAMETERS
$categoryname = 'MIM';
$fullname='Kursname voll';
$shortname='Kursname kurz';
$idnumber='newidnumber';
$backupfile='backup.mbz';



$usernames=array('usernames'=>array('floegeri'));
$function = 'local_oncampus_update_users';


///// XML-RPC CALL
header('Content-Type: text/plain');
$serverurl = $domainname . '/webservice/xmlrpc/server.php'. '?wstoken=' . $token;
require_once('./curl.php');
$curl = new curl;
//$post = xmlrpc_encode_request($functionname, array($categoryname,$fullname,$shortname,$idnumber,$backupfile));
#$post = xmlrpc_encode_request($functionname, $usernames);
#$resp = xmlrpc_decode($curl->post($serverurl, $post));
		 # $soapclient = new webservice_soap_client(MOODLE_URL.'/webservice/soap/server.php', TOKEN , array("features" => SOAP_WAIT_ONE_WAY_CALLS));
		
		
		#	$soapclient = new webservice_soap_client(MOODLE_URL.'/webservice/soap/server.php', TOKEN , array( ));
		#$soapclient->setWsdlCache(false);


$soapclient = new webservice_soap_client(MOODLE_URL .'/webservice/soap/server.php', $token, array("features" => SOAP_WAIT_ONE_WAY_CALLS));
$soapclient->setWsdlCache(false);


	
			$params_detailed = array (
					"roleid" 							=> "3",
					"username" 						=> "floegeri",
					"coursename"					=> "FHL-S-WIG-1200-11W-01",
					"courseFullname"			=> "FHL-S-WIG-1200-11W-01"
			);
					                                         
			 $params = array ("enrolment" => $params_detailed);
		   $function = 'local_oncampus_courseuser_delete';
		   $resp = $soapclient->call($function, $params);
	 		 print_r($resp);
	 		
	 		echo "---------------------------------------------------------------------------";
	 	 	$params = array ("enrolment" => $params_detailed);
	   $function = 'local_oncampus_courseuser_insert';
		  
	  $resp = $soapclient->call($function, $params);
		  
		  
		  
		  $params=array(
		'enrolment' => array(
			'roleid'=>3,
			'username'=>'biniama',
			'coursename'=>'FHL-S-test-Demo_ir220-07S-01',
			'courseFullname'=>'Demo_ir220 (FHL test SS07 [01])'
			)
		);
	$function = 'local_oncampus_courseuser_insert';
	$delresult = $soapclient->call($function, $params);
	
	var_dump($delresult);
	
		  #$lh = $soapclient->getLastResponseHeaders();
		  #$lr = $soapclient->getLastRequest();
		  #$lresp = $soapclient->getLastResponse();
		  #echo $lh."
		 # -----
		 # ".$lr."
		 # ----
		  #".$lresp;
	 	#	print_r($resp);
	 		
	 		
echo "

hello world!";
