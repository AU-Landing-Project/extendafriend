<?php

namespace AU\Extendafriend;

// get our variables
$user = elgg_get_logged_in_user_entity();
$friend_guid = get_input('friend_guid');
$rtags_list = get_input('rtags');
$existing_rtags = get_input('existing_rtag');
$approve = get_input('approve');
$subscriptions = get_input('subscriptions');

//sanity check
$friend = get_user($friend_guid);

if (!($friend instanceof \ElggUser)) {
	register_error(elgg_echo('extendafriend:invalid:id'));
	forward(REFERER);
}


if (!elgg_is_active_plugin('friend_request')) {
	// we can just do our thing
	extendafriend_add_friend($friend);

	extendafriend_collections_update($user, $friend, $rtags_list, $existing_rtags);

	extendafriend_notifications_update($user, $friend, $subscriptions);

	system_message(elgg_echo('extendafriend:updated'));

	forward(REFERER);
} else {
	//  need to do some of our own logic checking to know what to do before handing it off to frend_request
	if ($approve == "approve") {
		// approve url: $vars["url"] . "action/friend_request/approve?guid=" . $entity->getGUID(),
		// check that it will be approved
		if (check_entity_relationship($friend->guid, 'friendrequest', $user->guid)) {
			//yes, this is a valid approval so we process both our collections and our friends
			$url = elgg_get_site_url() . "action/friend_request/approve?guid=" . $friend->guid;
			$url = elgg_add_action_tokens_to_url($url);

			// process our collections
			extendafriend_collections_update($user, $friend, $rtags_list, $existing_rtags);

			// process our notifications
			extendafriend_notifications_update($user, $friend, $subscriptions);

			// process friends collections and notifications - with temp permissions
			$ia = elgg_set_ignore_access(true);
			elgg_push_context('extendafriend_permissions');

			$rtags_list = elgg_get_plugin_user_setting('rtags_list_' . $user->guid, $friend->guid, PLUGIN_ID);
			$existing_rtags = elgg_get_plugin_user_setting('existing_rtags_' . $user->guid, $friend->guid, PLUGIN_ID);
			if ($existing_rtags) {
				$existing_rtags = unserialize($existing_rtags);
			}

			if (!empty($rtags_list) || !empty($existing_rtags)) {
				extendafriend_collections_update($friend, $user, $rtags_list, $existing_rtags);
				elgg_unset_plugin_user_setting('rtags_list_' . $user->guid, $friend->guid, PLUGIN_ID);
				elgg_unset_plugin_user_setting('existing_rtags_' . $user->guid, $friend->guid, PLUGIN_ID);
			}

			$friend_subscriptions = elgg_get_plugin_user_setting('subscriptions_' . $user->guid, $friend->guid, PLUGIN_ID);
			if ($friend_subscriptions) {
				$friend_subscriptions = unserialize($friend_subscriptions);
			}
			
			if (!empty($friend_subscriptions) && is_array($friend_subscriptions)) {
				extendafriend_notifications_update($friend, $user, $friend_subscriptions);
				elgg_unset_plugin_user_setting('subscriptions_' . $user->guid, $friend->guid, PLUGIN_ID);
			}

			// reset permissions
			elgg_pop_context($context);
			elgg_set_ignore_access($ia);

			forward($url);
		} else {
			register_error(elgg_echo('friend_request:approve:fail', array($friend->name)));
			forward(REFERER);
		}
	}

	if (extendafriend_reciprocal_friendship($user, $friend)) {
		// we can just do our thing

		extendafriend_collections_update($user, $friend, $rtags_list, $existing_rtags);

		extendafriend_notifications_update($user, $friend, $subscriptions);

		system_message(elgg_echo('extendafriend:updated'));

		forward(REFERER);
	}

	// ok, now we know we aren't reciprocal friends, and friend_request is active
	// determine whether we need to save our settings for later (until request is granted)
	$url = elgg_get_site_url() . "action/friends/add?friend=" . $friend->guid;
	$url = elgg_add_action_tokens_to_url($url);

	// if they are already friends with us, then it will be automatically approved
	// therefore we can process the rtags
	if (check_entity_relationship($friend->guid, "friend", $user->guid) || check_entity_relationship($friend->guid, "friendrequest", $user->guid)) {
		// either we're already their friend, or they have invited us.  So it will be approved
		// so we can do our thing
		extendafriend_collections_update($user, $friend, $rtags_list, $existing_rtags);

		extendafriend_notifications_update($user, $friend, $subscriptions);

		system_message(elgg_echo('extendafriend:updated'));
	} else {
		// we've made it this far, we're requesting friendship, so we need to save these inputs
		// we'll use them when the friendship is accepted
		elgg_set_plugin_user_setting('rtags_list_' . $friend->guid, $rtags_list, $user->guid, PLUGIN_ID);
		elgg_set_plugin_user_setting('existing_rtags_' . $friend->guid, serialize($existing_rtags), $user->guid, PLUGIN_ID);
		elgg_set_plugin_user_setting('subscriptions_' . $friend->guid, serialize($subscriptions), $user->guid, PLUGIN_ID);
	}

	forward($url);
}

// Not sure how we could get to this point
register_error('Something went wrong... sorry');
forward(REFERER);





