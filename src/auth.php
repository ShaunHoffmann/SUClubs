<?php
// src/auth.php
// Session management, login/signup/logout, role checks.
// ALL queries in this file use prepared statements because auth is
// the #1 SQL injection target on any web app.

// Start the session exactly once. session_status() prevents the
// "session already started" warning if a caller already started one.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/database.php";

// ─────────────────────────────────────────────────────────────
// SESSION STATE HELPERS
// ─────────────────────────────────────────────────────────────

// Is anyone logged in right now?
function is_logged_in() {
    return isset($_SESSION["accountID"]);
}

// What role does the current user have? Returns null if not logged in.
function current_role() {
    return isset($_SESSION["role"]) ? $_SESSION["role"] : null;
}

// For eboard users: which club are they scoped to?
// This is the value the finance handler compares against POST data
// to prevent one eboard from editing another club's finances.
function current_club() {
    return isset($_SESSION["clubName"]) ? $_SESSION["clubName"] : null;
}

// ─────────────────────────────────────────────────────────────
// GUARDS — call these at the top of protected views
// ─────────────────────────────────────────────────────────────

// Bounce to login if not authenticated.
function require_login() {
    if (!is_logged_in()) {
        header("Location: SUClubsApp.php?page=login");
        exit;
    }
}

// Bounce if logged in but wrong role.
// Pass a single role string OR an array of allowed roles.
function require_role($allowed) {
    require_login();
    $allowed = is_array($allowed) ? $allowed : array($allowed);
    if (!in_array(current_role(), $allowed)) {
        http_response_code(403);
        die("Access denied.");
    }
}

// The eboard scoping check. Finance handler uses this.
// Returns true only if the current user is eboard AND the club they
// manage matches the club name passed in.
function is_eboard_for_club($clubName) {
    return current_role() === "eboard"
        && current_club() === $clubName;
}

// ─────────────────────────────────────────────────────────────
// LOGIN
// ─────────────────────────────────────────────────────────────

// Returns true on success, false on failure.
// On success, populates $_SESSION with accountID, username, role, clubName.
function attempt_login($connection, $username, $password) {

    // Prepared statement: username is user input, never goes into
    // the query string directly. The "?" is a placeholder.
    $sql = "SELECT accountID, username, passwordHash, role, clubName, isActive
            FROM Accounts
            WHERE username = ?";
    $stmt = mysqli_prepare($connection, $sql);

    // "s" = string. bind_param sends $username to MySQL as a parameter,
    // completely separate from the SQL text. SQL injection is impossible here.
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $account = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    // No such username → fail. (We intentionally don't tell the user
    // WHICH part failed — "username or password incorrect" — so
    // attackers can't enumerate valid usernames.)
    if (!$account) {
        return false;
    }

    // Account disabled by admin → fail.
    if ($account["isActive"] != 1) {
        return false;
    }

    // password_verify does constant-time comparison against the stored
    // bcrypt hash. This prevents timing attacks where an attacker
    // measures response time to guess the password character by character.
    if (!password_verify($password, $account["passwordHash"])) {
        return false;
    }

    // Success. Regenerate session ID to prevent session fixation —
    // if an attacker planted a session ID on the victim's browser
    // before login, this invalidates it.
    session_regenerate_id(true);

    $_SESSION["accountID"] = $account["accountID"];
    $_SESSION["username"]  = $account["username"];
    $_SESSION["role"]      = $account["role"];
    $_SESSION["clubName"]  = $account["clubName"]; // NULL for public/sga_admin

    // Update lastLogin timestamp. Prepared statement again.
    $sql2 = "UPDATE Accounts SET lastLogin = NOW() WHERE accountID = ?";
    $stmt2 = mysqli_prepare($connection, $sql2);
    mysqli_stmt_bind_param($stmt2, "i", $account["accountID"]); // "i" = int
    mysqli_stmt_execute($stmt2);
    mysqli_stmt_close($stmt2);

    return true;
}

// ─────────────────────────────────────────────────────────────
// SIGNUP — creates public-role account only.
// Promotion to eboard/sga_admin is manual via direct DB UPDATE.
// ─────────────────────────────────────────────────────────────

// Returns array: ["ok" => bool, "error" => string|null]
function attempt_signup($connection, $username, $password) {

    // Basic validation. Don't let empty or absurdly long usernames through.
    if (strlen($username) < 3 || strlen($username) > 50) {
        return array("ok" => false, "error" => "Username must be 3–50 characters.");
    }
    if (strlen($password) < 8) {
        return array("ok" => false, "error" => "Password must be at least 8 characters.");
    }

    // Hash the password. PASSWORD_DEFAULT = bcrypt currently; PHP may
    // upgrade the default in future versions, which is why passwordHash
    // is varchar(255) instead of a tight bcrypt-sized column.
    $hash = password_hash($password, PASSWORD_DEFAULT);

    // Prepared INSERT. Even though we control the role literal,
    // username/hash are user-adjacent and must be parameterized.
    $sql = "INSERT INTO Accounts (username, passwordHash, role)
            VALUES (?, ?, 'public')";
    $stmt = mysqli_prepare($connection, $sql);
    mysqli_stmt_bind_param($stmt, "ss", $username, $hash);

    try {
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return array("ok" => true, "error" => null);
    } catch (mysqli_sql_exception $e) {
        mysqli_stmt_close($stmt);
        // Error 1062 = duplicate key. Username already taken.
        if ($e->getCode() == 1062) {
            return array("ok" => false, "error" => "Username already taken.");
        }
        return array("ok" => false, "error" => "Signup failed. Try again.");
    }
}

// ─────────────────────────────────────────────────────────────
// LOGOUT
// ─────────────────────────────────────────────────────────────

function logout() {
    // Wipe all session data, then destroy the session file on disk.
    $_SESSION = array();
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), "", time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]);
    }
    session_destroy();
}
?>