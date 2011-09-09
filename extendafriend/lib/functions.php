<?php

// takes an array of collections names
// returns an array of corresponding ids
function extendafriend_collection_names_to_ids($names_array){
	if(!is_array($names_array)){
		return false;
	}
	
	$collections = get_user_access_collections(get_loggedin_userid());
	
	$collectionscount = count($collections);
	$ids = array();
	for($i=0; $i<$collectionscount; $i++){
		if(in_array($collections[$i]->name, $names_array)){
			$ids[] = $collections[$i]->id;
		}
	}

	return $ids;
}


// called by menu:user_hover plugin hook
// $params['entity'] is the user
// $params['name'] is the menu name = "user_hover"
// $return is an array of items that are already registered to the menu
function extendafriend_hover_menu($hook, $type, $return, $params) {
	global $CONFIG;
	$user = $params['entity'];
	
	// see if there is an "addfriend" link
	if(is_array($return) && count($return) > 0){
		for($i=0; $i<count($return); $i++){
			if($return[$i]->getName() == "addfriend"){
							
				// there is an addfriend link, so we want to modify it
				// but first we need to set some variables to build our form later in the page generation
				if($user->isFriend()){
					// set the link text
					$linktext = elgg_echo('extendafriend:edit:friend');
					$itemid = 'editfriend';
				}
				else{
					$linktext = elgg_echo('friend:add');
					$itemid = 'addfriend';
				}
				
				// replace the existing "add friend" link
				$url = $CONFIG->url . "extendafriend/$user->guid";
				$item = new ElggMenuItem($itemid, $linktext, $url);
				$item->setSection('action');
				$item->setLinkClass('elgg-lightbox');

				// if already a friend, then the addfriend is actually the remove friend link
				// so we want to leave it alone and add our "edit friend" seperately
				if(!$user->isFriend()){
					$return[$i] = $item;
				}
				else{
					$return[] = $item;
				}
			}
		}	
	}
	
	return $return;
}


// get array of collection ids that the friend is already tagged as
function extendafriend_get_friend_collections($friend_guid, $names = false){
	$collections = get_user_access_collections(get_loggedin_userid());
	$collectionscount = count($collections);
	
	$friend_collections = array();
	for($i=0; $i<$collectionscount; $i++){
		$cur_members = get_members_of_access_collection($collections[$i]->id, true);
		if(!is_array($cur_members)){ $cur_members = array(); }
		if(in_array($friend_guid, $cur_members)){
			if($names){
				$friend_collections[] = $collections[$i]->name;
			}
			else{
				$friend_collections[] = $collections[$i]->id;
			}
		}
	}

	return $friend_collections;
}

function extendafriendSortByName($a, $b) {
	return strnatcmp(strtolower($a->name), strtolower($b->name));
}

function extendafriend_sortcollectionsbyname($objectArray) {
        usort($objectArray, "extendafriendSortByName");
        return $objectArray;
} 

//
//	removes a single item from an array
//	resets keys
//
function extendafriend_removeFromArray($value, $array){
	if(!is_array($array)){ return $array; }
	if(!in_array($value, $array)){ return $array; }
	
	for($i=0; $i<count($array); $i++){
		if($value == $array[$i]){
			unset($array[$i]);
			$array = array_values($array);
		}
	}
	
	return $array;
}