<?php

/**
 * Mock for sending email function
 * @param from - Sender's email
 * @param to - Recipient's email
 * @param text - Text of letter
 */
function send_email($from, $to, $text) {
  sleep(rand(1, 10));

  $chanceOfError = rand(0, 50);
  if ($chanceOfError < 1) {
    throw new Exception('Send email error.');
  }
}