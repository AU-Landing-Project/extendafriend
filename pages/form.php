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


/*
 *  Notification Settings 
 * 
 * This code all seems kind of hackish
 * but it's how it's done in the core notifications plugin...
 * seems like it should be a simple view we could call with a user id right?
 * 
 * modified a few things as this only affects one user
 */  
  
global $NOTIFICATION_HANDLERS;
foreach($NOTIFICATION_HANDLERS as $method => $foo) {
	$subsbig[$method] = elgg_get_entities_from_relationship(array('relationship' => 'notify' . $method, 'relationship_guid' => elgg_get_logged_in_user_guid(), 'types' => 'user', 'limit' => 0));
}

$subs = array();
foreach($subsbig as $method => $big) {
	if (is_array($subsbig[$method]) && sizeof($subsbig[$method])) {
		foreach($subsbig[$method] as $u) { 
			$subs[$method][] = $u->guid;
		}
	}
}

// so now we have an array $subs[$method][] = $guid
$form .= elgg_view('notifications/subscriptions/jsfuncs', array());

$form .= "<label>" . elgg_echo('extendafriend:notifications:method', array($friend->name)) . "</label>";

$form .= "<table id=\"notificationstable\" cellspacing=\"0\" cellpadding=\"4\" border=\"0\" width=\"100%\"><tr>";

// header row
$form .= "<tr>";
$form .= "<td>&nbsp;</td>";

foreach($NOTIFICATION_HANDLERS as $method => $foo) {
  $form .= "<td class='spacercolumn'>&nbsp;</td>";

	$form .= "<td class=\"{$method}togglefield\">" . elgg_echo('notification:method:'.$method) . "</td>";
}

$form .= "<td class='spacercolumn'>&nbsp;</td></tr><tr>";

$form .= "<td>" . elgg_echo('extendafriend:notifications') . "</td>";
// inputs row
foreach($NOTIFICATION_HANDLERS as $method => $foo) {

  // checked by default if not currently friends
  // otherwise dependent on previous settings
  if ((isset($subs[$method]) && in_array($friend->guid,$subs[$method])) || !$friend->isFriend()) {
    $checked = 'checked="checked"';
	} else {
    $checked = '';
	}
  
  $form .= "<td class='spacercolumn'>&nbsp;</td>";

	
  $form .= <<< END
<td class="{$method}togglefield">
<a border="0" id="{$method}{$friend->guid}" class="{$method}toggleOff" onclick="adjust{$method}_alt('{$method}{$friend->guid}');">
<input type="checkbox" name="subscriptions[{$method}]" id="{$method}checkbox" onclick="adjust{$method}('{$method}{$friend->guid}');" value="{$friend->guid}" {$checked} /></a></td>
END;

$i++;
}

$form .= "<td class='spacercolumn'>&nbsp;</td></tr>";
$form .= "</tr></table><br><br>";

// End Notifications
				
$form .= elgg_view('input/submit', array('value' => elgg_echo('extendafriend:submit'))) . " ";
				
$html = "<div class=\"extendafriend_form\">";
			
$html .= elgg_view('input/form', array('body' => $form, 'action' => $CONFIG->url . "action/extendafriend/add"));
				
$html .= "<br>" . elgg_echo('extendafriend:form:instructions') . "<br><br>";
$html .= "</div>";
  
  


//$html .= "<pre>" . print_r($subs,1) . "</pre>";
echo $html;