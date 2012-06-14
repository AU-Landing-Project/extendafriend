<?php

gatekeeper();

// get our variables
$friend_guid = get_input('friend_guid');
$rtags_list = get_input('rtags');
$existing_rtags = get_input('existing_rtag');
$approve = get_input('approve');
$subscriptions = get_input('subscriptions');

//sanity check
$friend = get_user($friend_guid);

if(!($friend instanceof ElggUser)){
	register_error(elgg_echo('extendafriend:invalid:id'));
	forward(REFERER);
}


if(!elgg_is_active_plugin('friend_request')){
  // we can just do our thing
  extendafriend_add_friend($friend);

  extendafriend_collections_update(elgg_get_logged_in_user_entity(), $friend, $rtags_list, $existing_rtags);
  
  extendafriend_notifications_update(elgg_get_logged_in_user_entity(), $friend, $subscriptions);

  system_message(elgg_echo('extendafriend:updated'));

  forward(REFERER);   
}
else{
  //  need to do some of our own logic checking to know what to do before handing it off to frend_request
  if($approve == "approve"){
    // approve url: $vars["url"] . "action/friend_request/approve?guid=" . $entity->getGUID(),
    // check that it will be approved
    if(check_entity_relationship($friend->guid, 'friendrequest', elgg_get_logged_in_user_guid())){
      //yes, this is a valid approval so we process both our collections and our friends
      $url = elgg_get_site_url() . "action/friend_request/approve?guid=" . $friend->guid;
      $url = elgg_add_action_tokens_to_url($url);
      
      // process our collections
      extendafriend_collections_update(elgg_get_logged_in_user_entity(), $friend, $rtags_list, $existing_rtags);
      
      // process our notifications
      extendafriend_notifications_update(elgg_get_logged_in_user_entity(), $friend, $subscriptions);
      
      // process friends collections and notifications - with temp permissions
      $oldaccess = elgg_set_ignore_access(TRUE);
      $context = elgg_get_context();
      elgg_set_context('extendafriend_permissions');
      
      $rtags_list = elgg_get_plugin_user_setting('rtags_list_'.elgg_get_logged_in_user_guid(), $friend->guid, 'extendafriend');
      $existing_rtags = unserialize(elgg_get_plugin_user_setting('existing_rtags_'.elgg_get_logged_in_user_guid(), $friend->guid, 'extendafriend'));
      
      if(!empty($rtags_list) || !empty($existing_rtags)){
        extendafriend_collections_update($friend, elgg_get_logged_in_user_entity(), $rtags_list, $existing_rtags);
        elgg_unset_plugin_user_setting('rtags_list_'.elgg_get_logged_in_user_guid(), $friend->guid, 'extendafriend');
        elgg_unset_plugin_user_setting('existing_rtags_'.elgg_get_logged_in_user_guid(), $friend->guid, 'extendafriend');
      }
      
      $friend_subscriptions = unserialize(elgg_get_plugin_user_setting('subscriptions_'.elgg_get_logged_in_user_guid(), $friend->guid, 'extendafriend'));
      if(!empty($friend_subscriptions) && is_array($friend_subscriptions)){
        extendafriend_notifications_update($friend, elgg_get_logged_in_user_entity(), $friend_subscriptions);
        elgg_unset_plugin_user_setting('subscriptions_'.elgg_get_logged_in_user_guid(), $friend->guid, 'extendafriend');
      }
      
      // reset permissions
      elgg_set_context($context);
      elgg_set_ignore_access($oldaccess);
      
      forward($url);
    }
    else{
      register_error(elgg_echo('friend_request:approve:fail', array($friend->name)));
      forward(REFERER);
    }
  }
  
  if(extendafriend_reciprocal_friendship(elgg_get_logged_in_user_entity(), $friend)){
    // we can just do our thing
    
    extendafriend_collections_update(elgg_get_logged_in_user_entity(), $friend, $rtags_list, $existing_rtags);
    
    extendafriend_notifications_update(elgg_get_logged_in_user_entity(), $friend, $subscriptions);

    system_message(elgg_echo('extendafriend:updated'));

    forward(REFERER);  
  }
  
  // ok, now we know we aren't reciprocal friends, and friend_request is active
  // determine whether we need to save our settings for later (until request is granted)
  $url = elgg_get_site_url() . "action/friends/add?friend=" . $friend->guid;
  $url = elgg_add_action_tokens_to_url($url);
  
  // if they are already friends with us, then it will be automatically approved
  // therefore we can process the rtags
  if(check_entity_relationship($friend->guid, "friend", elgg_get_logged_in_user_guid())
    || check_entity_relationship($friend->guid, "friendrequest", elgg_get_logged_in_user_guid())){ 
    // either we're already their friend, or they have invited us.  So it will be approved
    // so we can do our thing
    extendafriend_collections_update(elgg_get_logged_in_user_entity(), $friend, $rtags_list, $existing_rtags);
    
    extendafriend_notifications_update(elgg_get_logged_in_user_entity(), $friend, $subscriptions);

    system_message(elgg_echo('extendafriend:updated'));
   
  }
  else{
    // we've made it this far, we're requesting friendship, so we need to save these inputs
    // we'll use them when the friendship is accepted
    elgg_set_plugin_user_setting('rtags_list_'.$friend->guid, $rtags_list, elgg_get_logged_in_user_guid(), 'extendafriend');
    elgg_set_plugin_user_setting('existing_rtags_'.$friend->guid, serialize($existing_rtags), elgg_get_logged_in_user_guid(), 'extendafriend');
    elgg_set_plugin_user_setting('subscriptions_'.$friend->guid, serialize($subscriptions), elgg_get_logged_in_user_guid(), 'extendafriend');
  }
  
  forward($url);
}

// Not sure how we could get to this point
register_error('Something went wrong... sorry');
forward(REFERER);





