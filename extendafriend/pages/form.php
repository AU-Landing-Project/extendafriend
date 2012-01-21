<?php
require_once(dirname(dirname(dirname(dirname(__FILE__)))) . "/engine/start.php");

gatekeeper();

$friend = get_user_by_username(get_input('friend'));
$approve = get_input('approve');

	//create our form
	$form = "<label>" . elgg_echo('extendafriend:rtags') . "</label><br>";
	$form .= elgg_view('input/text', array('name' => 'rtags', 'id' => 'extendafriend_rtags', 'value' => "")) . "<br>";
	$form .= elgg_view('input/hidden', array('name' => 'friend_guid', 'value' => $friend->guid));

	//get array of names of all collections owned by me
	$allcollections = get_user_access_collections(elgg_get_logged_in_user_guid());
	$collections = extendafriend_sortcollectionsbyname($allcollections);
	//get array of ids of collections this person is in
	$friendcollections = extendafriend_get_friend_collections(elgg_get_logged_in_user_entity(), $friend);
				
	if(!empty($collections[0]->id)){ // we have collections to show

		$collectioncount = count($collections);
		for($i=0; $i<$collectioncount; $i++){
			$checked = "";
			if(in_array($collections[$i]->id, $friendcollections)){
				$checked = " checked=\"checked\"";
			}
						
			$form .= "<div class=\"extendafriendcollectionlist\">";
			$form .= "<input type=\"checkbox\" id=\"" . $collections[$i]->name . $friend->guid . "\" name=\"existing_rtag[]\" value=\"" . $collections[$i]->name . "\"$checked>";
			$form .= "<label for=\"" . $collections[$i]->name . $friend->guid . "\">" . $collections[$i]->name . "</label>";
			$form .= "</div>";
		}	
	}
	
	$form .= elgg_view('input/hidden', array('name' => 'approve', 'value' => $approve));
				
	$form .= "<div class=\"extendafriendclear\"></div>";
				
	$form .= elgg_view('input/submit', array('value' => elgg_echo('extendafriend:submit'))) . " ";
				
	$html = "<div class=\"extendafriend_form\">";
			
	$html .= elgg_view('input/form', array('body' => $form, 'action' => $CONFIG->url . "action/extendafriend/add"));
				
	$html .= "<br>" . elgg_echo('extendafriend:form:instructions') . "<br><br>";
	$html .= "</div>";
				
	echo $html;