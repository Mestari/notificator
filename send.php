<?php

require_once 'db-connect.php';
require_once 'send-email.php';

$fromEmail = 'support@noreply.com';

$dbconn = get_connect();
if (!$dbconn) {
  echo "An error occurred while connecting to the database.\n";
  exit;
}

while (true) {
  echo "Start a new iteration to send notifications\n";
  $sendCount = 0;
  $checkCount = 0;

  pg_query($dbconn, "BEGIN") or die("Could not start transaction\n");
  $queryString = "
    SELECT s.email, s.type, u.username 
    FROM email_send_queue AS s
    INNER JOIN users AS u USING(email)
    ORDER BY s.created_at 
    LIMIT 1 
    FOR UPDATE
  ";
  $selectResult = pg_query($dbconn, $queryString);
 
  if (!$selectResult) {
    echo "An error occurred when querying the database.\n";
    exit;
  }

  $row = pg_fetch_assoc($selectResult);
  $email = $row['email'];
  $username = $row['username'];
  $text = "$username, your subscription is expiring soon";

  try {
    send_email($fromEmail, $email, $text);

    pg_query_params($dbconn, "DELETE FROM email_send_queue WHERE email = $1", array($email));
    pg_query_params($dbconn, "INSERT INTO sent_letters (email, type) VALUES ($1, $2)", array($email, $row['type']));
    pg_query($dbconn, "COMMIT");
    echo "Letter with text '$text' sended to $email.\n";
  } catch(Exception $e) {
    pg_query($dbconn, "ROLLBACK");
    echo "An error occurred while sending the email with text '$text': ", $e->getMessage(), "\n";
  }

  echo "End iteration\n";
  sleep(1);
}