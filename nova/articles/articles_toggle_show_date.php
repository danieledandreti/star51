<?php
// Nova Articles Toggle Show Date - Toggle show_publish_date via universal handler
include '../inc/inc_nova_session.php';

$toggle_table = 'ns_articles';
$toggle_id_field = 'id_article';
$toggle_id_value = $_GET['id'] ?? 0;
$toggle_field = 'show_publish_date';
$toggle_success_session = 'articles_success';
$toggle_msg_on = 'articles.msg.toggled_date_on';
$toggle_msg_off = 'articles.msg.toggled_date_off';

include '../inc/inc_nova_toggle.php';
