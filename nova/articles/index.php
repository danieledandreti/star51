<?php
// Nova Directory Protection System - Session Aware
// Prevents unauthorized directory scanning by routing authenticated requests.

require_once '../inc/inc_nova_session.php';

header('Location: ../home.php');
exit();
