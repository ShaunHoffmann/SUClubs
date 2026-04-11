<?php
// public/SUClubsApp.php
// Single entry point for the v2 app. Dispatches ?page=X to the right view.
//
// URL pattern: SUClubsApp.php?page=dashboard
//              SUClubsApp.php?page=club&name=Chess+Club
//              SUClubsApp.php?page=finances
//
// Anything unknown falls back to the dashboard.

require_once __DIR__ . "/../src/auth.php";
// auth.php already requires database.php, so $connection is available.

// ─────────────────────────────────────────────────────────────
// LOGOUT — handled inline because it's an action, not a view
// ─────────────────────────────────────────────────────────────
if (isset($_GET["page"]) && $_GET["page"] === "logout") {
    logout();
    header("Location: SUClubsApp.php?page=login");
    exit;
}

// ─────────────────────────────────────────────────────────────
// PAGE WHITELIST
// Never include a file path built from user input. Instead, map
// known page names to known view files. Anything not in this list
// is rejected and falls through to the default.
// ─────────────────────────────────────────────────────────────
$pages = array(
    "login"     => "login.php",
    "signup"    => "signup.php",
    "dashboard" => "dashboard.php",
    "club"      => "club.php",
    "finances"  => "finances.php",
);

// Pick the requested page, default to dashboard.
$requested = isset($_GET["page"]) ? $_GET["page"] : "dashboard";
if (!array_key_exists($requested, $pages)) {
    $requested = "dashboard";
}
$view_file = __DIR__ . "/../src/views/" . $pages[$requested];

// ─────────────────────────────────────────────────────────────
// RENDER: header → view → footer
// Each view file is responsible for its own auth guards
// (require_login / require_role at the top of the file).
// ─────────────────────────────────────────────────────────────
require_once __DIR__ . "/../src/views/header.php";
require_once $view_file;
require_once __DIR__ . "/../src/views/footer.php";
?>
