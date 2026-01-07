<?php
require_once 'config.php';

// This starts the Google process with the 'select_account' instruction
header("Location: " . $client->createAuthUrl());
exit();
?>