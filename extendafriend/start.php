<?php
/**
 * extendafriend 1.8
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU Public License version 2
 * @author Matt Beckett
 * 
 */

include_once 'lib/functions.php';

function extendafriend_init() {

	// Load system configuration
	global $CONFIG;

	// Extend system CSS with our own styles
	elgg_extend_view('css', 'extendafriend/css');
	
	elgg_load_js('lightbox');
	elgg_load_css('lightbox');


	// Load the language file
	register_translations($CONFIG->pluginspath . "extendafriend/languages/");

	//register action to add friends with collections
	register_action("extendafriend/add", true, $CONFIG->pluginspath . "extendafriend/actions/add.php");
	
	elgg_register_plugin_hook_handler('register', 'menu:user_hover', 'extendafriend_hover_menu', 1000);
	
	//register_page_handler('extendafriend','extendafriend_page_handler');
}

function extendafriend_page_handler($page){
		global $CONFIG;
		
		set_input('friend_id', $page[0]);
		include($CONFIG->pluginspath . "extendafriend/pages/form.php");
}


// call init
register_elgg_event_handler('init','system','extendafriend_init');
?>
