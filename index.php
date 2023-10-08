<?php

require_once 'db-connect.php';
require_once 'emails-handler.php';

$firstLetterDays = 3; // days left before subscription expires, for fist letter
$secondLetterDays = 1; // days left before subscription expires, for second letter
$subscriptionPeriodDays = 30; // subscription period in days
$maxRowsCount = 100; // rows limit for one iteration
$letterType1 = 'firstLetter'; // email type for first letter
$letterType2 = 'secondLetter'; // email type for second letter

$dbconn = get_connect();
if (!$dbconn) {
  echo "An error occurred while connecting to the database.\n";
  exit;
}

$result = pg_prepare($dbconn, "select_emails", "SELECT * FROM get_emails_for_notification($1, $2, $3, $4, $5)");
$result = pg_prepare($dbconn, "insert_to_email_send_queue", "INSERT INTO email_send_queue (email, type) VALUES ($1, $2)");
$result = pg_prepare($dbconn, "insert_to_email_check_queue", "INSERT INTO email_check_queue (email) VALUES ($1)");

while (true) {
  echo "Start new iteration of search for emails to send notifications\n";
  $sendCount = 0;
  $checkCount = 0;

  // Select emails for send first emeail type (3 days left before subscription expires)
  $params = [$letterType1, $firstLetterDays, $secondLetterDays, $subscriptionPeriodDays, $maxRowsCount];
  emails_handler($dbconn, $params, $letterType1, $sendCount, $checkCount);

  // Select emails for send second emeail type (1 day left before subscription expires)
  $params = [$letterType2, $secondLetterDays, 0, $subscriptionPeriodDays, $maxRowsCount];
  emails_handler($dbconn, $params, $letterType2, $sendCount, $checkCount);

  if ($sendCount || $checkCount) {
    echo "\n";
  }

  echo "$sendCount emails added to send queue\n";
  echo "$checkCount emails added to check queue\n";
 
  echo "End iteration\n";
  sleep(1);
}