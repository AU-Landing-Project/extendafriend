<?php

namespace AU\Extendafriend;

gatekeeper();

$vars['user'] = elgg_get_logged_in_user_entity();

echo elgg_view_form('extendafriend/add', array('class' => 'extendafriend'), $vars);
