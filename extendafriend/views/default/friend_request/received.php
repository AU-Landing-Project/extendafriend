<?php 
	$entities = $vars["entities"];
	
	$content = "";
	
	if(!empty($entities)){
		foreach($entities as $entity){
		  $approveurl = elgg_get_site_url() . "extendafriend/{$entity->username}/approve";
		  
			$icon = elgg_view_entity_icon($entity, "small");
			
			$info = elgg_view("output/url", array("href" => $entity->getURL(), "text" => $entity->name));
			$info .= "<br />";
			$info .= elgg_view("output/url", array("href" => $approveurl, 
													"text" => elgg_echo("friend_request:approve"), 
													"is_action" => true,
			                                        "class" => 'elgg-lightbox'));
			$info .= "&nbsp;|&nbsp;";
			$info .= elgg_view("output/url", array("href" => $vars["url"] . "action/friend_request/decline?guid=" . $entity->getGUID(), 
													"text" => elgg_echo("friend_request:decline"), 
													"is_action" => true));
			
			$content .= elgg_view_image_block($icon, $info);
		}
	} else {
		$content = elgg_echo("friend_request:received:none");
	}
	
	echo elgg_view_module("aside", elgg_echo("friend_request:received:title"), $content, array("id" => "friend_request_received_listing"));
	
	
	
	
	
	
	
	
	
	
	
	