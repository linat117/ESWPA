<?php
// register-handler.php
// This acts as an intermediary to bypass .htaccess restrictions on direct access to files in 'include' directory.
// The form posts to this file, which then includes the actual register.php handler.

include 'include/register.php';
?>

