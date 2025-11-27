<?php

session_start();
require_once 'db.php'; 


if (!isset($_SESSION['guest_token'])) {
    $_SESSION['guest_token'] = 'guest_' . bin2hex(random_bytes(16));
}

function current_identifier() {

    if (!empty($_SESSION['user_id'])) {
        return ['type' => 'user', 'id' => (int)$_SESSION['user_id']];
    }
    return ['type' => 'guest', 'id' => $_SESSION['guest_token']];
}
