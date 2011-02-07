<?php
//Bug tracker for LotGD / Improbable Island
//Allows bugs to be visible / commented upon to other players
//Replaces Petitions system

function bugtracker_getmoduleinfo(){
	$info = array(
		"name"=>"Bug Tracker",
		"version"=>"2009-12-17",
		"author"=>"Dan Hall",
		"category"=>"Administrative",
		"download"=>"",
		"override_forced_nav"=>true,
	);
	return $info;
}
function bugtracker_install(){
	$bugs = array(
		'id'=>array('name'=>'creatureid', 'type'=>'int(11) unsigned', 'extra'=>'auto_increment'),
		'status'=>array('name'=>'status', 'type'=>'text'),
		'public'=>array('name'=>'public', 'type'=>'bool'),
		'info'=>array('name'=>'info', 'type'=>'text'),
		'key-PRIMARY'=>array('name'=>'PRIMARY', 'type'=>'primary key',	'unique'=>'1', 'columns'=>'id'),
	);
	require_once("lib/tabledescriptor.php");
	synctable(db_prefix('bugs'), $bugs, true);
	return true;
}
function bugtracker_uninstall(){
	return true;
}
function bugtracker_dohook($hookname,$args){
	return $args;
}
function bugtracker_run(){
	global $session;
	// popup_header("Bug Tracker");
	// switch($op){
		// case default:
			// output("Before reporting a bug, please check this list of known bugs.`n`n",true);
			// $sql = "SELECT id,status,info FROM " . db_prefix("bugs") ." WHERE public=1";
			// $result = $db_query($sql);
			// $c = db_num_rows($result);
			// require_once("lib/commentary.php");
			// for ($i=0; $i<$c; $i++){
				// $row = db_fetch_assoc($result);
				// $info = unserialize($row['info']);
				// output_notl("`0`b%s`b`n%s`n`n",$info['title'],$info['description']);
				// addcommentary();
				// viewcommentary("bug-".$row['id'],"Got anything to add?",10);
			// }
			// rawoutput("<form action='runmodule.php?module=bugtracker&op=submit' method='POST'>");
			
			// rawoutput("</form>");
		// break;
	// }
	// popup_footer();
}
?>
