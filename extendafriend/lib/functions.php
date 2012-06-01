<?php


function extendafriend_add_friend($friend){
  // if not a friend already, add as friend
  if(!elgg_get_logged_in_user_entity()->isFriendsWith($friend->guid)){
	$errors = false;

	// 	Get the user
	try {
		if (!elgg_get_logged_in_user_entity()->addFriend($friend->guid)) {
			$errors = true;
		}
	} catch (Exception $e) {
		register_error(sprintf(elgg_echo("friends:add:failure"),$friend->name));
		$errors = true;
	}
	if (!$errors){
		// 	add to river
		add_to_river('river/relationship/friend/create','friend',elgg_get_logged_in_user_guid(),$friend->guid);
		system_message(sprintf(elgg_echo("friends:add:successful"),$friend->name));


    }
  }
}

// takes an array of collections names
// returns an array of corresponding ids
function extendafriend_collection_names_to_ids($user, $names_array){
	if(!is_array($names_array)){
		return false;
	}
	
	$collections = get_user_access_collections($user->guid);
	
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
	
	$user = $params['entity'];
	
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
	$url = elgg_get_site_url() . "extendafriend/{$user->username}";
	$url = elgg_add_action_tokens_to_url($url);
	$item = new ElggMenuItem($itemid, $linktext, $url);
	$item->setSection('action');
	$item->setLinkClass('elgg-lightbox');
	
	// see if there is an "addfriend" link
	if(is_array($return) && count($return) > 0){
		for($i=0; $i<count($return); $i++){
			if(strpos($return[$i]->getHref(), "action/friends/add")){			
				// there is an addfriend link, so we want to modify it
				$return[$i] = $item;
			}
		}
		
		if($user->isFriend()){
			// we're already friends, so we'll add the edit button to the end of the array
			$return[] = $item;
		}
	}
	
	return $return;
}


// get array of collection ids that the friend is already tagged as
function extendafriend_get_friend_collections($user, $friend, $names = false){
	$collections = get_user_access_collections($user->guid);
	$collectionscount = count($collections);
	
	$friend_collections = array();
	for($i=0; $i<$collectionscount; $i++){
		$cur_members = get_members_of_access_collection($collections[$i]->id, true);
		if(!is_array($cur_members)){ $cur_members = array(); }
		if(in_array($friend->guid, $cur_members)){
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

// checks if 2 users are reciprocal friends
// returns bool true/false
function extendafriend_reciprocal_friendship($user1, $user2){
  if(!($user1 instanceof ElggUser) || !($user2 instanceof ElggUser)){
    return FALSE;
  }
  
  $forward = check_entity_relationship($user1->guid, "friend", $user2->guid);
  $backward = check_entity_relationship($user2->guid, "friend", $user1->guid);
  
  if(!$forward || !$backward){
    return FALSE;
  }
  
  return TRUE;
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


function extendafriend_collections_update($user, $friend, $rtags_list, $existing_rtags){
        //notifications are automagically taken care of

      // array of rtags submitted
      $rtags_submitted = explode(',', $rtags_list);

      $count = count($rtags_submitted);
      for($i=0; $i<$count; $i++){
	    $rtags_submitted[$i] = trim($rtags_submitted[$i]);
      }

      //add in the checkbox array, and remove duplicate tags
      if(is_array($existing_rtags) && count($existing_rtags) > 0){
        $rtags_submitted = array_merge($rtags_submitted, $existing_rtags);
      }
      $rtags_submitted = array_unique($rtags_submitted);
      $rtags_submitted = array_values($rtags_submitted);



      // get array of rtags that the friend is already tagged as
      $ids_existing = extendafriend_get_friend_collections($user, $friend);


      //	$ids_submitted = array of ids submitted THAT EXIST AS A COLLECTION ALREADY
      //	$ids_existing = array of ids of collections that our friend is already in

      //we need to find what rtags are new, create that collection

      //get array of names of all collections owned by me
      $collections = get_user_access_collections($user->guid);
      $rtag_all = array();
      for($i=0; $i<count($collections); $i++){
	    $rtag_all[] = $collections[$i]->name;
      }

      //find names of collections to create
      $allcount = count($rtag_all);
      $submitted_count = count($rtags_submitted);
      $newcollections = $rtags_submitted;

      for($i=0; $i<$submitted_count; $i++){
	    for($j=0; $j<$allcount; $j++){
		  $newcollections = extendafriend_removeFromArray($rtag_all[$j], $newcollections);
	    }
      }


      //create new collections
      $new_collection_ids = array();
      for($i=0; $i<count($newcollections); $i++){
	    $new_collection_ids[] = create_access_collection($newcollections[$i], $user->guid);
      }

      // now we should be able to get ids for everything
      $ids_submitted = extendafriend_collection_names_to_ids($user, $rtags_submitted);

      //create array of differences $ids_existing - $ids_submitted
      $removeids = $ids_existing;
      $existing_count = count($ids_existing);
      $submitted_count = count($ids_submitted);
      for($i=0; $i<existing_count; $i++){
	    for($j=0; $j<$submitted_count; $j++){
    	  $removeids = extendafriend_removeFromArray($ids_submitted[$j], $removeids);
	    }
      }

      // now we can remove the friend from any collections they were previously in but not anymore
      for($i=0; $i<count($removeids); $i++){
	    remove_user_from_access_collection($friend->guid, $removeids[$i]);
      } 


      //create array of differences $ids_submitted - $ids_existing
      //  will tell us what collections(that exist) friend needs to be added to
      $addids = $ids_submitted;
      $existing_count = count($ids_existing);
      $submitted_count = count($ids_submitted);
      for($i=0; $i<submitted_count; $i++){
	    for($j=0; $j<$existing_count; $j++){
		  $addids = extendafriend_removeFromArray($ids_existing[$j], $addids);
	    }
      }

      // now we can add our friend to existing collections
      $addresult = array();
      for($i=0; $i<count($addids); $i++){
	    $addresult[] = add_user_to_access_collection($friend->guid, $addids[$i]);
      }
}



function extendafriend_revoke_decline($hook, $entity_type, $returnvalue, $params){ 
  $friend = get_user(get_input('guid'));

  if($friend instanceof ElggUser){
    if($entity_type == "friend_request/decline" && check_entity_relationship($friend->guid, 'friendrequest', elgg_get_logged_in_user_guid())){
      // delete their saved rtags
      $oldaccess = elgg_set_ignore_access(TRUE);
      $context = elgg_get_context();
      elgg_set_context('extendafriend_permissions');
      
      elgg_unset_plugin_user_setting('rtags_list_'.elgg_get_logged_in_user_guid(), $friend->guid, 'extendafriend');
      elgg_unset_plugin_user_setting('existing_rtags_'.elgg_get_logged_in_user_guid(), $friend->guid, 'extendafriend');
      
      elgg_set_context($context);
      elgg_set_ignore_access($oldaccess);
    }
    if($entity_type == "friend_request/revoke" && check_entity_relationship(elgg_get_logged_in_user_guid(), 'friendrequest', $friend->guid)){
      // delete your saved rtags
      elgg_unset_plugin_user_setting('rtags_list_'.$friend->guid, elgg_get_logged_in_user_guid(), 'extendafriend');
      elgg_unset_plugin_user_setting('existing_rtags_'.$friend->guid, elgg_get_logged_in_user_guid(), 'extendafriend');
    }
  }
}


/*
 *  subscribes or unsubscribes 
 */
function extendafriend_notifications_update($user, $friend, $subscriptions){
  global $NOTIFICATION_HANDLERS;
  foreach($NOTIFICATION_HANDLERS as $method => $foo) {
      
    // add in chosen notifications methods
    if($subscriptions[$method] == $friend->guid){
      add_entity_relationship($user->guid, 'notify' . $method, $friend->guid);
    }
    else{
      // remove pre-existing methods
      remove_entity_relationship($user->guid, 'notify' . $method, $friend->guid);
    }
    
  }
}


function extendafriend_permissions_check(){
	$context = elgg_get_context();
	if($context == "extendafriend_permissions"){
		return TRUE;
	}
	
	return NULL;
}