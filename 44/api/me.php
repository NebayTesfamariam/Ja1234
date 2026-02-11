<?php
require __DIR__ . '/../config.php';
$user = require_user($conn);
json_out(['id' => (int)$user['id'], 'email' => $user['email']]);
