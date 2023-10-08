<?php

/**
 * Mock for checking email function
 * @param email - Recipient's email address
 */
function check_email($email) {
  sleep(rand(1, 60));

  $chanceOfError = rand(0, 50);
  if ($chanceOfError < 1) {
    throw new Exception('Check email error.');
  }

  return rand(0, 1);
}