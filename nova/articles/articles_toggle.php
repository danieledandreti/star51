<?php
// Nova Articles Toggle - Toggle is_active via universal handler
include '../inc/inc_nova_session.php';

$toggle_table = 'ns_articles';
$toggle_id_field = 'id_article';
$toggle_id_value = $_GET['id'] ?? 0;
$toggle_success_session = 'nova_success';
$toggle_msg_on = 'articles.msg.toggled_active';
$toggle_msg_off = 'articles.msg.toggled_inactive';

include '../inc/inc_nova_toggle.php';
