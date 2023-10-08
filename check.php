<?php

require_once 'db-connect.php';
require_once 'check-email.php';

$dbconn = get_connect();
if (!$dbconn) {
  echo "An error occurred while connecting to the database.\n";
  exit;
}

while (true) {
  echo "Start a new iteration to check email\n";
  $sendCount = 0;
  $checkCount = 0;

  pg_query($dbconn, "BEGIN") or die("Could not start transaction\n");
  $queryString = "
    SELECT email FROM email_check_queue 
    ORDER BY created_at LIMIT 1 FOR UPDATE
  ";
  $selectResult = pg_query($dbconn, $queryString);
 
  if (!$selectResult) {
    echo "An error occurred when querying the database.\n";
    exit;
  }

  $row = pg_fetch_assoc($selectResult);
  $email = $row['email'];

  try {
    $emailCheckResult = check_email($email);

    pg_query_params($dbconn, "DELETE FROM email_check_queue WHERE email = $1", array($email));

    if ($emailCheckResult) {
      pg_query_params($dbconn, "UPDATE users SET checked = TRUE, valid = TRUE WHERE email = $1", array($email));
    } else {
      pg_query_params($dbconn, "UPDATE users SET checked = TRUE, valid = FALSE WHERE email = $1", array($email));
    }
    
    pg_query($dbconn, "COMMIT");
    echo "Email '$email' checked ($emailCheckResult).\n";
  } catch(Exception $e) {
    pg_query($dbconn, "ROLLBACK");
    echo "An error occurred while checkoing the email '$email': ", $e->getMessage(), "\n";
  }

  echo "End iteration\n";
  sleep(1);
}