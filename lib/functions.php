<?php

namespace AU\Extendafriend;

/**
 * Add a new friend
 * 
 * @param type $friend
 * @return boolean
 */
function extendafriend_add_friend($friend) {
	$user = elgg_get_logged_in_user_entity();
	if (!$user) {
		return false;
	}

	// if not a friend already, add as friend
	if (!$user->isFriendsWith($friend->guid)) {
		$errors = false;

		// 	Get the user
		try {
			if (!$user->addFriend($friend->guid)) {
				$errors = true;
			}
		} catch (Exception $e) {
			register_error(elgg_echo("friends:add:failure", array($friend->name)));
			$errors = true;
		}
		if (!$errors) {
			// 	add to river
			// add to river
			elgg_create_river_item(array(
				'view' => 'river/relationship/friend/create',
				'action_type' => 'friend',
				'subject_guid' => $user->guid,
				'object_guid' => $friend->guid,
			));
			system_message(elgg_echo("friends:add:successful", array($friend->name)));
		}
	}
}

/**
 * takes an array of collections names
 * returns an array of corresponding ids
 * 
 * @param type $user
 * @param type $names_array
 * @return boolean
 */
function extendafriend_collection_names_to_ids($user, $names_array) {
	if (!is_array($names_array)) {
		return false;
	}

	$collections = get_user_access_collections($user->guid);

	$ids = array();
	foreach ($collections as $c) {
		if (in_array($c->name, $names_array)) {
			$ids[] = $c->id;
		}
	}

	return $ids;
}

/**
 * get array of collection ids that the friend is already tagged as
 * 
 * @param type $user
 * @param type $friend
 * @param type $names
 * @return array
 */
function extendafriend_get_friend_collections($user, $friend, $names = false) {
	if (!($user instanceof \ElggUser) || !($friend instanceof \ElggUser)) {
		return array();
	}

	// there's no good api for this that's scalable
	// so lets cheat and do direct queries
	$dbprefix = elgg_get_config('dbprefix');
	$q = "SELECT ac.id as id, ac.name as name FROM {$dbprefix}access_collections ac"
			. " JOIN {$dbprefix}access_collection_membership m ON m.access_collection_id = ac.id"
			. " WHERE ac.owner_guid = {$user->guid} AND m.user_guid = {$friend->guid}";

	$data = get_data($q);

	if (!$data) {
		return array();
	}

	$friend_collections = array();
	foreach ($data as $d) {
		if ($names) {
			$friend_collections[] = $d->name;
		} else {
			$friend_collections[] = $d->id;
		}
	}

	return $friend_collections;
}

/**
 * checks if 2 users are reciprocal friends
 * 
 * @param \ElggUser $user1
 * @param \ElggUser $user2
 * @return boolean
 */
function extendafriend_reciprocal_friendship($user1, $user2) {
	if (!($user1 instanceof \ElggUser) || !($user2 instanceof \ElggUser)) {
		return false;
	}

	$forward = check_entity_relationship($user1->guid, "friend", $user2->guid);
	$backward = check_entity_relationship($user2->guid, "friend", $user1->guid);

	return ($forward && $backward);
}

/**
 * removes a single item from an array
 * resets keys
 * 
 * @param type $value
 * @param type $array
 * @return type
 */
function extendafriend_removeFromArray($value, $array) {
	if (!is_array($array)) {
		return $array;
	}
	if (!in_array($value, $array)) {
		return $array;
	}

	for ($i = 0; $i < count($array); $i++) {
		if ($value == $array[$i]) {
			unset($array[$i]);
			$array = array_values($array);
		}
	}

	return $array;
}

/**
 * 
 * @param type $user
 * @param type $friend
 * @param type $rtags_list
 * @param type $existing_rtags
 */
function extendafriend_collections_update($user, $friend, $rtags_list, $existing_rtags) {
	//notifications are automagically taken care of
	// array of rtags submitted
	$rtags_submitted = explode(',', $rtags_list);

	$rtags_submitted = array_map('trim', $rtags_submitted);

	//add in the checkbox array, and remove duplicate tags
	if (is_array($existing_rtags) && count($existing_rtags) > 0) {
		$rtags_submitted = array_merge($rtags_submitted, $existing_rtags);
	}
	$rtags_submitted = array_unique($rtags_submitted);
	$rtags_submitted = array_values($rtags_submitted);



	// get array of rtags that the friend is already tagged as
	$ids_existing = extendafriend_get_friend_collections($user, $friend);


	//	$ids_submitted = array of ids submitted THAT EXIST AS A COLLECTION ALREADY
	//	$ids_existing = array of ids of collections that our friend is already in
	//  we need to find what rtags are new, create that collection
	//  get array of names of all collections owned by me
	$collections = get_user_access_collections($user->guid);
	$rtag_all = array();
	foreach ($collections as $c) {
		$rtag_all[] = $c->name;
	}

	//find names of collections to create
	$newcollections = array_diff($rtags_submitted, $rtag_all);


	//create new collections
	foreach ($newcollections as $n) {
		$id = create_access_collection($n, $user->guid);
		add_user_to_access_collection($friend->guid, $id);
	}

	// now we should be able to get ids for everything
	$ids_submitted = extendafriend_collection_names_to_ids($user, $rtags_submitted);

	foreach ($ids_existing as $id) {
		if (!in_array($id, $ids_submitted)) {
			remove_user_from_access_collection($friend->guid, $id);
		}
	}

	foreach ($ids_submitted as $id) {
		add_user_to_access_collection($friend->guid, $id);
	}
}


/**
 * subscribes or unsubscribes 
 * 
 * @param type $user
 * @param type $friend
 * @param type $subscriptions
 */
function extendafriend_notifications_update($user, $friend, $subscriptions) {
	$NOTIFICATION_HANDLERS = _elgg_services()->notifications->getMethodsAsDeprecatedGlobal();
	foreach ($NOTIFICATION_HANDLERS as $method => $foo) {

		// add in chosen notifications methods
		if ($subscriptions[$method] == $friend->guid) {
			add_entity_relationship($user->guid, 'notify' . $method, $friend->guid);
		} else {
			// remove pre-existing methods
			remove_entity_relationship($user->guid, 'notify' . $method, $friend->guid);
		}
	}
}
