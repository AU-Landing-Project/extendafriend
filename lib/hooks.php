<?php

namespace AU\Extendafriend;

/**
 * 
 * @param type $hook
 * @param type $type
 * @param type $return
 * @param type $params
 * @return \ElggMenuItem
 */
function user_hover_menu($hook, $type, $return, $params) {

	$user = $params['entity'];

	// but first we need to set some variables to build our form later in the page generation
	if ($user->isFriend()) {
		// set the link text
		$linktext = elgg_echo('extendafriend:edit:friend');
		$itemid = 'editfriend';
	} else {
		$linktext = elgg_echo('friend:add');
		$itemid = 'addfriend';
	}

	// replace the existing "add friend" link
	$url = elgg_get_site_url() . "extendafriend/{$user->username}";
	$url = elgg_add_action_tokens_to_url($url);
	$item = new \ElggMenuItem($itemid, $linktext, $url);
	$item->setSection('action');
	$item->setLinkClass('elgg-lightbox');

	// see if there is an "addfriend" link
	if (is_array($return) && count($return) > 0) {
		for ($i = 0; $i < count($return); $i++) {
			if (strpos($return[$i]->getHref(), "action/friends/add")) {
				// there is an addfriend link, so we want to modify it
				$return[$i] = $item;
			}
		}

		if ($user->isFriend()) {
			// we're already friends, so we'll add the edit button to the end of the array
			$return[] = $item;
		}
	}

	return $return;
}

/**
 * 
 * @param type $hook
 * @param type $type
 * @param type $return
 * @param type $params
 * @return boolean|null
 */
function permissions_check($hook, $type, $return, $params) {
	if (elgg_get_context() == "extendafriend_permissions") {
		return true;
	}

	return $return;
}

/**
 * called on friend request revoke action
 * 
 * @param type $hook
 * @param type $entity_type
 * @param type $returnvalue
 * @param type $params
 */
function fr_revoke_decline($hook, $entity_type, $returnvalue, $params) {
	$friend = get_user(get_input('guid'));

	if ($friend instanceof ElggUser) {
		if ($entity_type == "friend_request/decline" && check_entity_relationship($friend->guid, 'friendrequest', elgg_get_logged_in_user_guid())) {
			// delete their saved rtags
			$oldaccess = elgg_set_ignore_access(TRUE);
			$context = elgg_get_context();
			elgg_set_context('extendafriend_permissions');

			elgg_unset_plugin_user_setting('rtags_list_' . elgg_get_logged_in_user_guid(), $friend->guid, 'extendafriend');
			elgg_unset_plugin_user_setting('existing_rtags_' . elgg_get_logged_in_user_guid(), $friend->guid, 'extendafriend');

			elgg_set_context($context);
			elgg_set_ignore_access($oldaccess);
		}
		if ($entity_type == "friend_request/revoke" && check_entity_relationship(elgg_get_logged_in_user_guid(), 'friendrequest', $friend->guid)) {
			// delete your saved rtags
			elgg_unset_plugin_user_setting('rtags_list_' . $friend->guid, elgg_get_logged_in_user_guid(), 'extendafriend');
			elgg_unset_plugin_user_setting('existing_rtags_' . $friend->guid, elgg_get_logged_in_user_guid(), 'extendafriend');
		}
	}
}
