<?php
/**
 * Delete device endpoint - BLOCKED FOR USERS
 * Only admins can delete devices via admin_devices.php
 * This endpoint explicitly blocks user deletion attempts
 */
require __DIR__ . '/../config.php';
$user = require_user($conn);

// Users cannot delete devices - only admins can
json_out([
  'message' => 'Gebruikers kunnen geen devices verwijderen. Neem contact op met de administrator.',
  'error' => 'device_deletion_blocked',
  'hint' => 'Alleen administrators kunnen devices verwijderen via het admin panel.'
], 403);

