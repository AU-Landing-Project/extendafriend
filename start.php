<?php

namespace AU\Extendafriend;

/**
 * extendafriend
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU Public License version 2
 * @author Matt Beckett
 * 
 */

const PLUGIN_ID = 'extendafriend';
const PLUGIN_VERSION = 20150915;

include_once __DIR__ . '/lib/functions.php';
include_once __DIR__ . '/lib/hooks.php';

// call init
elgg_register_event_handler('init', 'system', __NAMESPACE__ . '\\init');


function init() {

	// Extend system CSS with our own styles
	elgg_extend_view('css/elgg.css', 'css/extendafriend.css');
	
	elgg_load_js('lightbox');
	elgg_load_css('lightbox');

	//register action to add friends with collections
	elgg_register_action("extendafriend/add", __DIR__ . "/actions/add.php");

	
	elgg_register_plugin_hook_handler('register', 'menu:user_hover', __NAMESPACE__ . '\\user_hover_menu', 1000);
	elgg_register_plugin_hook_handler('permissions_check', 'all', __NAMESPACE__ . '\\permissions_check');
	
	if (elgg_is_active_plugin('friend_request')) {
	  elgg_register_plugin_hook_handler('action', 'friend_request/decline', __NAMESPACE__ . '\\fr_revoke_decline');
	  elgg_register_plugin_hook_handler('action', 'friend_request/revoke', __NAMESPACE__ . '\\fr_revoke_decline');
	}
	
	elgg_register_page_handler('extendafriend', __NAMESPACE__ . '\\extendafriend_page_handler');
}


/**
 * 
 * @param type $page
 * @return boolean
 */
function extendafriend_page_handler($page){
		
  echo elgg_view('resources/extendafriend/extendafriend', array(
	  'friend' => $page[0],
	  'approve' => $page[1]
  ));
  
  return true;
}
