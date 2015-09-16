<?php

namespace AU\Extendafriend;

$user = $vars['user'];

$friend = get_user_by_username($vars['friend']);
if (!($friend instanceof \ElggUser)) {
	echo elgg_echo('extendafriend:invalid:user:guid');
	return;
}

$approve = $vars['approve'];

$body = '<label for="rtags">' . elgg_echo('extendafriend:rtags') . '</label>';

$body .= elgg_view('input/text', array(
	'name' => 'rtags',
	'id' => 'extendafriend_rtags'
		));
$body .= elgg_view('input/hidden', array(
	'name' => 'friend_guid',
	'value' => $friend->guid
		));

//get array of names of all collections owned by me
$collections = get_user_access_collections($user->guid);
usort($collections, function($a, $b) {
	return strnatcmp(strtolower($a->name), strtolower($b->name));
});

//get array of ids of collections this person is in
$friendcollections = extendafriend_get_friend_collections($user, $friend);

if ($collections) {
	$body .= '<div class="row clearfix">';
	foreach ($collections as $collection) {

		$checkbox_options = array(
			'id' => 'rtag' . $collection->id,
			'name' => 'existing_rtag[]',
			'value' => $collection->name,
			'default' => false
		);

		if (in_array($collection->id, $friendcollections)) {
			$checkbox_options['checked'] = 'checked';
		}

		$body .= "<div class=\"elgg-col elgg-col-1of3\">";
		$body .= elgg_view('input/checkbox', $checkbox_options);
		$body .= "<label for=\"rtag{$collection->id}\">" . $collection->name . "</label>";
		$body .= "</div>";
	}
	$body .= '</div>';
}



/*
 *  Notification Settings 
 * 
 * This code all seems kind of hackish
 * but it's how it's done in the core notifications plugin...
 * seems like it should be a simple view we could call with a user id right?
 * 
 * modified a few things as this only affects one user
 */

$NOTIFICATION_HANDLERS = _elgg_services()->notifications->getMethodsAsDeprecatedGlobal();
$checked_methods = array();
foreach ($NOTIFICATION_HANDLERS as $method => $foo) {
	if (check_entity_relationship($user->guid, 'notify'.$method, $friend->guid)) {
		$checked_methods[] = $method;
	}
}

// so now we have an array $subs[$method][] = $guid
$body .= elgg_view('notifications/subscriptions/jsfuncs', array());

$body .= "<label>" . elgg_echo('extendafriend:notifications:method', array($friend->name)) . "</label>";

$body .= "<table id=\"notificationstable\" cellspacing=\"0\" cellpadding=\"4\" border=\"0\" width=\"100%\"><tr>";

// header row
$body .= "<tr>";
$body .= "<td>&nbsp;</td>";

foreach ($NOTIFICATION_HANDLERS as $method => $foo) {
	$body .= "<td class='spacercolumn'>&nbsp;</td>";

	$body .= "<td class=\"{$method}togglefield\">" . elgg_echo('notification:method:' . $method) . "</td>";
}

$body .= "<td class='spacercolumn'>&nbsp;</td></tr><tr>";

$body .= "<td>" . elgg_echo('extendafriend:notifications') . "</td>";
// inputs row
foreach ($NOTIFICATION_HANDLERS as $method => $foo) {

	// checked by default if not currently friends
	// otherwise dependent on previous settings
	if (in_array($method, $checked_methods) || !$friend->isFriend($user)) {
		$checked = 'checked="checked"';
	} else {
		$checked = '';
	}

	$body .= "<td class='spacercolumn'>&nbsp;</td>";


	$body .= <<< END
<td class="{$method}togglefield">
<a border="0" id="{$method}{$friend->guid}" class="{$method}toggleOff" onclick="adjust{$method}_alt('{$method}{$friend->guid}');">
<input type="checkbox" name="subscriptions[{$method}]" id="{$method}checkbox" onclick="adjust{$method}('{$method}{$friend->guid}');" value="{$friend->guid}" {$checked} /></a></td>
END;
}

$body .= "<td class='spacercolumn'>&nbsp;</td></tr>";
$body .= "</tr></table><br><br>";

// End Notifications

$body .= elgg_view('input/hidden', array('name' => 'approve', 'value' => $approve));
$body .= elgg_view('input/submit', array('value' => elgg_echo('extendafriend:submit'))) . " ";

echo elgg_view_module('info', elgg_echo('extendafriend:form:header'), $body);

echo elgg_view('output/longtext', array(
	'value' => elgg_echo('extendafriend:form:instructions'),
	'class' => 'elgg-subtext'
));


