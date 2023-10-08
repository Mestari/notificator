<?php

/**
 * Get DB connect
 * @param from - Sender's email
 * @param to - Recipient's email
 * @param text - Text of letter
 */
function get_connect() {
  $env = parse_ini_file('.env');
  $dbHost = $env["DB_HOST"];
  $dbPort = $env["DB_PORT"];
  $dbName = $env["DB_NAME"];
  $dbUser = $env["DB_USER"];
  $dbPass = $env["DB_PASS"];

  $connString = "host=$dbHost port=$dbPort dbname=$dbName user=$dbUser password=$dbPass";

  return pg_connect($connString);
}