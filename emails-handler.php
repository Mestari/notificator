<?php

/**
 * Get and handle emails for send notifications
 * @param dbconn - DB connect
 * @param funcParams - Array of parameters for sql function get_emails_for_notification()
 * @param sendCount - Counter for emails added to 'send job'
 * @param checkCount - Counter for emails added to 'check job'
 */
function emails_handler($dbconn, $funcParams, $letterType, &$sendCount, &$checkCount) {
    $selectResult = pg_execute($dbconn, "select_emails", $funcParams);
   
    if (!$selectResult) {
      echo "An error occurred when querying the database.\n";
      exit;
    }
  
    while ($row = pg_fetch_assoc($selectResult)) {
      $email = $row['email'];
      
      if ($row['valid'] === 't') {
        $result = pg_execute($dbconn, "insert_to_email_send_queue", array($email, $letterType));
        $sendCount++;
        echo "🦚";
      } else {
        $result = pg_execute($dbconn, "insert_to_email_check_queue", array($email));
        $checkCount++;
        echo "🦁";
      }
    }
  }