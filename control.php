<?php
// $Id control.module

// Called on new playnodes
//function playnode_insert($node) {
//	$playNodeNID = $node->nid;
//	$playNodeUDID = $node->field_udid["und"][0]["value"];
//	$outputDir = "/opt/jail/home/controller/";
//	portAliasSetup($playNodeUDID,$outputDir);
//	btConfFromPlaynodes($playNodeUDID,$playNodeNID,$outputDir);
//}

// Called on all new nodes
function control_node_presave($node){
	if ($node->type == "playnode" && isset($node->nid) && $node->uid == "38") {
		$playNodeNID = $node->nid;
		$playNodeUDID = $node->field_udid["und"][0]["value"];
		$outputDir = "/opt/jail/home/danny/";
		portAliasSetup($playNodeUDID,$outputDir);
		btConfFromPlaynodes($playNodeUDID,$playNodeNID,$outputDir);
	} elseif ($node->type == "playnode" && isset($node->nid)) {
                $playNodeNID = $node->nid;
                $playNodeUDID = $node->field_udid["und"][0]["value"];
		$outputDir = "/opt/jail/home/controller/";
                $node->field_portalias[LANGUAGE_NONE][0]['value']=portAliasSetup($playNodeUDID,$outputDir);
//		node_save($node)u;
		return $node;
                btConfFromPlaynodes($playNodeUDID,$playNodeNID,$outputDir);
        }
	if ($node->type == "folder" && isset($node->nid) && $node->uid == "38") {
		$outputDir = "/opt/jail/home/danny/";
                fromFolders($node->uid,$outputDir);
        } elseif ($node->type == "folder" && isset($node->nid)) {
		$outputDir = "/opt/jail/home/controller/";
		fromFolders($node->uid,$outputDir);
	}
	// Bug - the folders don't yet reference bc of the reverse ref thing
	// make this fromPlaynodes instead

	serverListSetup($node->uid);
}

// Called on all updated nodes
function control_node_update($node) {
	if ($node->type == "playnode" && isset($node->nid) && $node->uid == "38") {
                $playNodeNID = $node->nid;
                $playNodeUDID = $node->field_udid["und"][0]["value"];
		$outputDir = "/opt/jail/home/danny/";
                btConfFromPlaynodes($playNodeUDID,$playNodeNID,$outputDir);
        } elseif ($node->type == "playnode" && isset($node->nid)) {
		$playNodeNID = $node->nid;
		$playNodeUDID = $node->field_udid["und"][0]["value"];
		$outputDir = "/opt/jail/home/controller/";
		btConfFromPlaynodes($playNodeUDID,$playNodeNID,$outputDir);
	}
	if ($node->type == "folder" && isset($node->nid) && $node->uid == "38") {
                $outputDir = "/opt/jail/home/danny/";
                fromFolders($node->uid,$outputDir);
        } elseif ($node->type == "folder" && isset($node->nid)) {
                $outputDir = "/opt/jail/home/controller/";
                fromFolders($node->uid,$outputDir);
        }
	serverListSetup($node->uid);
}

// After node deleted:
// Called AFTER database transaction completes
function control_node_postdelete($node) {
	// Use trailing slash
	$cache_dir = "/opt/jail/home/controller/";
	if ($node->uid == "38"){ $cache_dir = "/opt/jail/home/danny/";}
	$UDID = $node->field_udid["und"][0]["value"];
	if ($node->type == "playnode" && isset($UDID)) {
		if (file_exists($cache_dir  . $UDID . ".btsync")) {
			unlink($cache_dir  . $UDID . ".btsync");
		}
	}

	if ($node->type == "mobile_device" && isset($UDID)) {
		if (file_exists($cache_dir  . $UDID . ".serverList")) {
			unlink($cache_dir  . $UDID . ".serverList");
		}
	}
	if ($node->type == "folder") {
		fromFolders($node->uid);
	}
	serverListSetup($node->uid);
}

// Is this function obsolete?
function playnodeNameSetup() {
	// Use trailing slash
	$cache_dir = "/var/www/control.neilscudder.com/controlapp/cache/";
	// Select all the PlayNodes
	$sqlPlayNodes = db_query("
		SELECT field_udid_value, title
		FROM field_data_field_udid udid, node n
		WHERE n.type = 'playnode'
		AND udid.entity_id = n.nid
		AND udid.revision_id = n.vid
	");
	if (file_exists($cache_dir  . "playnodeNames.php")) {
		unlink($cache_dir  . "playnodeNames.php");
	}
	// For each playnode, add a line enabling app control:
	foreach ($sqlPlayNodes as $myPlayNode) {
		$myUDID = $myPlayNode->field_udid_value;
		$myName = $myPlayNode->title;
		$regCmd="echo '${myUDID}^${myName}' >> " . $cache_dir  . "playnodeNames.php";
		$register=shell_exec($regCmd);
	} // End PlayNode loop
}


function portAliasSetup($playNodeUDID,$outputDir) {
	// Use trailing slash
	$cache_dir = "/var/www/control.neilscudder.com/controlapp/cache/";

	// Test if alias already exists for UUID
	if (strpos(file_get_contents("${cache_dir}portalias-ssh.php"),$playNodeUDID) == false) {
		// Get last mpd port, increment by one
		$mpdportCmd="cat ${cache_dir}portalias-mpd.php | tail -n1 | cut -d^ -f2 | xargs echo -n";
		$mpdport=shell_exec($mpdportCmd);
		++$mpdport;
		// Repeat for ssh ports
		$sshportCmd="cat ${cache_dir}portalias-ssh.php | tail -n1 | cut -d^ -f2 | xargs echo -n";
		$sshport=shell_exec($sshportCmd);
		++$sshport;
		//Write to database
//		$node->field_portalias["und"][0]["value"]=$mpdport;
		return $mpdport;
//                node_save($node);

		// Write to portalias file
		if ( isset($outputDir) ) {
			$regCmd="echo '${playNodeUDID}^${mpdport}' >> " . $outputDir  . "portalias-mpd.php";
			$register=shell_exec($regCmd);
			$sshregCmd="echo '${playNodeUDID}^${sshport}' >> " . $outputDir  . "portalias-ssh.php";
			$sshregister=shell_exec($sshregCmd);
			$regCmd="echo '${playNodeUDID}^${mpdport}' >> " . $cache_dir  . "portalias-mpd.php";
                        $register=shell_exec($regCmd);
                        $sshregCmd="echo '${playNodeUDID}^${sshport}' >> " . $cache_dir  . "portalias-ssh.php";
                        $sshregister=shell_exec($sshregCmd);
		} else {
			$regCmd="echo '${playNodeUDID}^${mpdport}' >> " . $cache_dir  . "portalias-mpd.php";
                        $register=shell_exec($regCmd);
                        $sshregCmd="echo '${playNodeUDID}^${sshport}' >> " . $cache_dir  . "portalias-ssh.php";
                        $sshregister=shell_exec($sshregCmd);
		}
	} else {
		// Retrieve existing alias
                $mpdportCmd="cat ${cache_dir}portalias-mpd.php | grep ${playNodeUDID} | cut -d^ -f2 | xargs echo -n";
                $mpdport=shell_exec($mpdportCmd);
//		$node->field_portalias["und"][0]["value"]=$mpdport;
		return $mpdport;
//		node_save($node);
	}
}

function serverListSetup($thisUser) {
	// Link a user's PlayNodes to his Mobile Devices for app-based control
	// Currently all Mobiles may control all PlayNodes w/o restriction
	// Every time a playnode or mobile_device is saved, a serverList file is created.
	// Serverlist file is retrieved by mobile app identified by UDID.

	// Use trailing slash
	$cache_dir = "/var/www/control.neilscudder.com/controlapp/cache/";

	// Select all the Mobile Devices this guy owns
//	$sqlMobiles = db_query("
//		SELECT field_udid_value
//		FROM field_data_field_udid udid, node n
//		WHERE n.type = 'mobile_device'
//		AND udid.entity_id = n.nid
//		AND udid.revision_id = n.vid
//		AND n.uid = " . $thisUser . "
//	");

	// For each mobile device write serverlist to file:
//	foreach ($sqlMobiles as $myMobile) {
		// Set up first line of serverlist file
//		$serverList = "<h3>Select a playnode to control:</h3>\n";
		$serverList = "<ul class='serverList'>\n";

		// Select all the PlayNodes this guy owns
		$sqlPlayNodes = db_query("
			SELECT field_udid_value, title, nid
			FROM field_data_field_udid udid, node n
			WHERE n.type = 'playnode'
			AND udid.entity_id = n.nid
			AND udid.revision_id = n.vid
			AND n.uid = " . $thisUser . "
		");
		// For each playnode, add a line enabling app control:
		foreach ($sqlPlayNodes as $myPlayNode) {
			$mpdportCmd="cat ${cache_dir}portalias-mpd.php | grep '^" . $myPlayNode->field_udid_value . "' | /usr/bin/cut -d^ -f2 | xargs echo -n";
			$mpdport=shell_exec($mpdportCmd);
			$encTitle=urlencode($myPlayNode->title);
			$editLink="node/$myPlayNode->nid/edit?destination=home";
			// Testing for results
			if ($myPlayNode) {
$serverList .= "<li class='serverList'>";
$serverList .= "<a href='$editLink'>\n";
$serverList .= "<strong>" . $myPlayNode->title . "</strong></a>";
$serverList .= "<a href='app/index-dev.php?portAlias=$mpdport&playnode=$encTitle'>\n";
$serverList .= " [control]</a></li>\n";
			} else {
				$serverList .= "<li><h2>You have no PlayNodes! Make one in the control panel.</h2></li>";
			}
		} // End PlayNode loop

		// Add the Demo Playnode:
		// $serverList .= "<li><a href='#' onClick='setAlias(\"1033\",\"Demo Playnode\");'><h2 style='font-size: 1.5em;'>Demo Playnode</h2></a></li>";
		$serverList .= "<a href='node/add/playnode'>+ Add Playnode</a>";
		// Add footer line for serverlist:
		$serverList .= "</ul>";
		// Construct the filename for the serverlist (NOTE CASE 'serverList')
//		$fname = $cache_dir . $myMobile->field_udid_value . ".serverList";
		$fname = $cache_dir . "user-" . $thisUser . ".serverList";
		if (file_exists($fname)) {
			unlink($fname);
		}
		// Write the new serverList file for this mobile_device udid
		$fh = fopen($fname, 'w') or die("can't open file");
		fwrite($fh, $serverList);
		fclose($fh);
//	} // End Mobile loop
}


function fromFolders($thisUser){
	// Select the playnodes this folder refs and run btConfFromFolders for each
	$sqlPlayNodes = db_query("
			SELECT field_udid_value, nid
			FROM field_data_field_udid udid, node n
			WHERE n.type = 'playnode'
			AND udid.entity_id = n.nid
			AND udid.revision_id = n.vid
			AND n.uid = " . $thisUser . "
		");
	foreach ($sqlPlayNodes as $fetchPlaynodes) {
		btConfFromFolders($fetchPlaynodes->field_udid_value,$fetchPlaynodes->nid,$thisUser);
	}

}

// TEST for btsecret if no folders linked
function btConfFromFolders($playNodeUDID,$playNodeNID,$thisUser) {
        // Regenerate a node's btsync conf with current music folders
        $cache_dir = "/opt/jail/home/controller/";
	if ($thisUser == "38"){ $cache_dir = "/opt/jail/home/danny/";}
        // Get secrets and names from folders that ref this playnode
        $sqlSecrets = db_query("
                SELECT field_udid_value, field_name_value
				FROM field_data_field_ref_playnodes p, field_data_field_udid u, field_data_field_name n
				WHERE p.field_ref_playnodes_nid = " . $playNodeNID . "
				AND p.entity_id = u.entity_id
				AND n.entity_id = u.entity_id
        ");
        // Read header template into confCode
        $headerTemplate=$cache_dir . "btsync.header";
        $confCode=file_get_contents($headerTemplate);
        $numItems = $sqlSecrets->rowCount();
        $i = 0;
        foreach ($sqlSecrets as $fetchSecrets) {
                $btsecret=$fetchSecrets->field_udid_value;
                $folder=$fetchSecrets->field_name_value;
                // Retrieve markup from a function to keep code formatting here
                $confCode .= confSnipper(0,$btsecret,$folder);
                if(++$i === $numItems) {
                        // last index
                        $confCode .= confSnipper(1,$btsecret,$folder);
                }else{
                        $confCode .= confSnipper(2,$btsecret,$folder);
                }
        }// End looping through ref'd folders
        // Write the new btsyncconf file for this mobile_device udid
        $fname = $cache_dir . $playNodeUDID . ".btsync";
        $confCode .= confSnipper(3,$btsecret,$folder);
        $fh = fopen($fname, 'w') or die("can't open file");
        fwrite($fh, $confCode);
        fclose($fh);
}// End btsyncConf



function btConfFromPlaynodes($playNodeUDID,$playNodeNID,$outputDir) {
        // Regenerate a node's btsync conf with current music folders
        $cache_dir = "/opt/jail/home/controller/";

        // Get secrets and names from folders this playnodes refs
        $sqlSecrets = db_query("
			SELECT field_udid_value, field_name_value
			FROM field_data_field_ref_folders f, field_data_field_udid u, field_data_field_name n
			WHERE f.entity_id = " . $playNodeNID . "
			AND u.entity_id = f.field_ref_folders_nid
			AND n.entity_id = f.field_ref_folders_nid
		");
        // Read header template into confCode
        $headerTemplate=$cache_dir . "btsync.header";
        $confCode=file_get_contents($headerTemplate);
        $numItems = $sqlSecrets->rowCount();
        $i = 0;
        // WHY DOESNT THIS WORK???
        if (is_int($numItems)) {
        	foreach ($sqlSecrets as $fetchSecrets) {
                	$btsecret=$fetchSecrets->field_udid_value;
                	$folder=$fetchSecrets->field_name_value;
                	// Retrieve markup from a function to keep code formatting here
                	$confCode .= confSnipper(0,$btsecret,$folder);
                	if(++$i === $numItems) {
                        	// last index
                        	$confCode .= confSnipper(1,$btsecret,$folder);
                	}else{
                        	$confCode .= confSnipper(2,$btsecret,$folder);
                	}
        	}// End looping through ref'd folders
        }
        // Write the new btsyncconf file for this mobile_device udid
        $fname = $outputDir . $playNodeUDID . ".btsync";
        $confCode .= confSnipper(3,$btsecret,$folder);
        $fh = fopen($fname, 'w') or die("can't open file");
        fwrite($fh, $confCode);
        fclose($fh);
}// End btsyncConf


function confSnipper($whichPiece,$btsecret,$folder) {
	// Snippets for the btSyncConf file, cleaner to keep here
	switch ($whichPiece) {
		case 0:
			$confSnippet = <<<EOD

    {
      "secret" : "${btsecret}",
      "dir" : "/var/lib/paradigm/music/${folder}",
      "use_relay_server" : true,
      "use_tracker" : true,
      "use_dht" : true,
      "search_lan" : true,
      "use_sync_trash" : false,
      "overwrite_changes" : false,
      "known_hosts" :
      [
      ]
EOD;

			return $confSnippet;
		break;
		case 1:
			$confSnippet = <<<EOD

    }
EOD;
			return $confSnippet;
		break;
		case 2:
			$confSnippet = <<<EOD

    },
EOD;
			return $confSnippet;
		break;
		case 3:
			$confSnippet= <<<EOD

  ]

}
EOD;
			return $confSnippet;
		break;
	}

}




