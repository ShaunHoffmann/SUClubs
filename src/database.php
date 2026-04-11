<?php
// src/database.php
// Single source of truth for the database connection.
// Every view/handler includes this instead of re-declaring credentials.

// Turn mysqli errors into exceptions so bugs surface immediately
// instead of silently returning false and causing weird downstream errors.
// This is the biggest upgrade over SUClubs1.php's silent-failure pattern.
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// --- Credentials (same as SUClubs1.php) ---
$db_host = "localhost";
$db_user = "shoffmann2";
$db_pass = "shoffmann2";
$db_name = "SUClubsDB";

try {
    // Open the connection. If this fails, mysqli throws because of
    // the report mode set above.
    $connection = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

    // Use utf8mb4 so unicode (emoji, accented chars in club names,
    // non-English descriptions) stores and retrieves correctly.
    mysqli_set_charset($connection, "utf8mb4");

} catch (mysqli_sql_exception $e) {
    // Clean error page — NEVER echo $e->getMessage() to the user in
    // production because it can leak the DB host/user. For a graded
    // school project showing a generic message is the right call.
    http_response_code(500);
    die("Database connection failed. Please contact the administrator.");
}
?>