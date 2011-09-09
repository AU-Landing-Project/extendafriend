<?php

gatekeeper();

// get our variables
$friend_guid = get_input('friend_guid');
$rtags_list = get_input('rtags');
$existing_rtags = get_input('existing_rtag');


//sanity check
$friend = get_user($friend_guid);

if(!($friend instanceof ElggUser)){
	register_error(elgg_echo('extendafriend:invalid:id'));
	forward(REFERRER);
}


// if not a friend already, add as friend
if(!$_SESSION['user']->isFriendsWith($friend_guid)){
	$errors = false;

	// 	Get the user
	try {
		if (!get_loggedin_user()->addFriend($friend_guid)) {
			$errors = true;
		}
	} catch (Exception $e) {
		register_error(sprintf(elgg_echo("friends:add:failure"),$friend->name));
		$errors = true;
	}
	if (!$errors){
		// 	add to river
		add_to_river('friends/river/create','friend',get_loggedin_userid(),$friend_guid);
		system_message(sprintf(elgg_echo("friends:add:successful"),$friend->name));
	}
}

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
$ids_existing = extendafriend_get_friend_collections($friend_guid);


//	$ids_submitted = array of ids submitted THAT EXIST AS A COLLECTION ALREADY
//	$ids_existing = array of ids of collections that our friend is already in

//we need to find what rtags are new, create that collection

//get array of names of all collections owned by me
$collections = get_user_access_collections(get_loggedin_userid());
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
	$new_collection_ids[] = create_access_collection($newcollections[$i], get_loggedin_userid());
}

// now we should be able to get ids for everything
$ids_submitted = extendafriend_collection_names_to_ids($rtags_submitted);

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
	remove_user_from_access_collection($friend_guid, $removeids[$i]);
} 


//create array of differences $ids_submitted - $ids_existing
// will tell us what collections(that exist) friend needs to be added to
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
	$addresult[] = add_user_to_access_collection($friend_guid, $addids[$i]);
} 


system_message(elgg_echo('extendafriend:updated'));

forward(REFERRER);