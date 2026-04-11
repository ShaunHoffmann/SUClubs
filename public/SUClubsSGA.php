<?php
// ═══════════════════════════════════════════════════════════
// AUTH GATE — sga_admin only.
// MUST be at the very top, before any HTML output and before
// the DB connection. If anything (even whitespace) prints
// before this, the header() redirect will fail.
// ═══════════════════════════════════════════════════════════
require_once __DIR__ . "/../src/auth.php";
require_role("sga_admin");
?>
<html>
<body>
<?php
// ─── DATABASE CONFIG ────────────────────────────────────────
$db_host = "localhost";
$db_user = "shoffmann2";
$db_pass = "shoffmann2";
$db_name = "SUClubsDB";

error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($connection = @mysqli_connect($db_host, $db_user, $db_pass, $db_name)) {
    // connected
} else {
    die('Could not connect: ' . mysqli_error($connection));
}

// ─── ACTIVE TAB ─────────────────────────────────────────────
$valid_tabs = array("clubs","members","membership","officers","communication","events","locations");
if (isset($_GET["tab"])) {
    $tab = $_GET["tab"];
} else {
    $tab = "clubs";
}
if (!in_array($tab, $valid_tabs)) {
    $tab = "clubs";
}

$message = "";
$msg_type = "";

// ══════════════════════════════════════════════════════════════
// DELETE HANDLERS
// ══════════════════════════════════════════════════════════════
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "delete") {

    if (isset($_POST["delete_club"])) {
        $val = mysqli_real_escape_string($connection, $_POST["delete_club"]);
        $sql = "DELETE FROM Club WHERE name = '$val'";
        if (mysqli_query($connection, $sql)) {
            $message = "Club deleted.";
            $msg_type = "success";
        } else { $message = "Error: " . mysqli_error($connection); $msg_type = "error"; }
        $tab = "clubs";
    }

    if (isset($_POST["delete_member"])) {
        $val = mysqli_real_escape_string($connection, $_POST["delete_member"]);
        $sql = "DELETE FROM Members WHERE suID = '$val'";
        if (mysqli_query($connection, $sql)) {
            $message = "Member deleted.";
            $msg_type = "success";
        } else { $message = "Error: " . mysqli_error($connection); $msg_type = "error"; }
        $tab = "members";
    }

    if (isset($_POST["delete_membership_suid"]) && isset($_POST["delete_membership_club"])) {
        $v1 = mysqli_real_escape_string($connection, $_POST["delete_membership_suid"]);
        $v2 = mysqli_real_escape_string($connection, $_POST["delete_membership_club"]);
        $sql = "DELETE FROM Membership WHERE suID = '$v1' AND clubName = '$v2'";
        if (mysqli_query($connection, $sql)) {
            $message = "Membership removed.";
            $msg_type = "success";
        } else { $message = "Error: " . mysqli_error($connection); $msg_type = "error"; }
        $tab = "membership";
    }

    if (isset($_POST["delete_officer_suid"]) && isset($_POST["delete_officer_club"])) {
        $v1 = mysqli_real_escape_string($connection, $_POST["delete_officer_suid"]);
        $v2 = mysqli_real_escape_string($connection, $_POST["delete_officer_club"]);
        $sql = "DELETE FROM Officers WHERE suID = '$v1' AND clubName = '$v2'";
        if (mysqli_query($connection, $sql)) {
            $message = "Officer removed.";
            $msg_type = "success";
        } else { $message = "Error: " . mysqli_error($connection); $msg_type = "error"; }
        $tab = "officers";
    }

    if (isset($_POST["delete_comm"])) {
        $val = mysqli_real_escape_string($connection, $_POST["delete_comm"]);
        $sql = "DELETE FROM Communication WHERE linkToJoin = '$val'";
        if (mysqli_query($connection, $sql)) {
            $message = "Communication channel deleted.";
            $msg_type = "success";
        } else { $message = "Error: " . mysqli_error($connection); $msg_type = "error"; }
        $tab = "communication";
    }

    if (isset($_POST["delete_event_name"]) && isset($_POST["delete_event_date"])) {
        $v1 = mysqli_real_escape_string($connection, $_POST["delete_event_name"]);
        $v2 = mysqli_real_escape_string($connection, $_POST["delete_event_date"]);
        $sql = "DELETE FROM Events WHERE name = '$v1' AND date = '$v2'";
        if (mysqli_query($connection, $sql)) {
            $message = "Event deleted.";
            $msg_type = "success";
        } else { $message = "Error: " . mysqli_error($connection); $msg_type = "error"; }
        $tab = "events";
    }

    if (isset($_POST["delete_location"])) {
        $val = mysqli_real_escape_string($connection, $_POST["delete_location"]);
        $sql = "DELETE FROM Location WHERE address = '$val'";
        if (mysqli_query($connection, $sql)) {
            $message = "Location deleted.";
            $msg_type = "success";
        } else { $message = "Error: " . mysqli_error($connection); $msg_type = "error"; }
        $tab = "locations";
    }

    if (isset($_POST["delete_fee"])) {
        $val = mysqli_real_escape_string($connection, $_POST["delete_fee"]);
        $sql = "DELETE FROM Fees WHERE feeID = '$val'";
        if (mysqli_query($connection, $sql)) {
            $message = "Fee deleted.";
            $msg_type = "success";
        } else { $message = "Error: " . mysqli_error($connection); $msg_type = "error"; }
        $tab = "fees";
    }

    if (isset($_POST["delete_payment"])) {
        $val = mysqli_real_escape_string($connection, $_POST["delete_payment"]);
        $sql = "DELETE FROM Member_Payments WHERE paymentID = '$val'";
        if (mysqli_query($connection, $sql)) {
            $message = "Payment deleted.";
            $msg_type = "success";
        } else { $message = "Error: " . mysqli_error($connection); $msg_type = "error"; }
        $tab = "payments";
    }
}

// ══════════════════════════════════════════════════════════════
// INSERT HANDLERS
// ══════════════════════════════════════════════════════════════
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "insert") {

    // INSERT CLUB
    if (isset($_POST["insert_club"])) {
        $name = mysqli_real_escape_string($connection, trim($_POST["club_name"]));
        $desc = mysqli_real_escape_string($connection, trim($_POST["club_description"]));
        $link = mysqli_real_escape_string($connection, trim($_POST["club_link"]));
        $fd   = mysqli_real_escape_string($connection, trim($_POST["club_foundationDate"]));
        $phone = mysqli_real_escape_string($connection, trim($_POST["club_phone"]));
        $email = mysqli_real_escape_string($connection, trim($_POST["club_email"]));
        $type = mysqli_real_escape_string($connection, trim($_POST["club_type"]));
        if ($name == "") { $message = "Club name is required."; $msg_type = "error"; }
        else {
            $fd_val = ($fd != "") ? "'$fd'" : "NULL";
            $sql = "INSERT INTO Club (name, description, link, foundationDate, phone, email, type) VALUES ('$name', '$desc', '$link', $fd_val, '$phone', '$email', '$type')";
            $rs = mysqli_query($connection, $sql);
            if (mysqli_affected_rows($connection) == 1) { $message = "Club \"$name\" registered!"; $msg_type = "success"; }
            else { $message = "Error: " . mysqli_error($connection); $msg_type = "error"; }
        }
        $tab = "clubs";
    }

    // INSERT MEMBER
    if (isset($_POST["insert_member"])) {
        $suID   = mysqli_real_escape_string($connection, trim($_POST["member_suid"]));
        $mname  = mysqli_real_escape_string($connection, trim($_POST["member_name"]));
        $age    = trim($_POST["member_age"]);
        $email  = mysqli_real_escape_string($connection, trim($_POST["member_email"]));
        $class  = mysqli_real_escape_string($connection, trim($_POST["member_class"]));
        $gender = mysqli_real_escape_string($connection, trim($_POST["member_gender"]));
        if ($suID == "") { $message = "Student ID is required."; $msg_type = "error"; }
        else {
            $age_val = ($age != "") ? intval($age) : "NULL";
            $sql = "INSERT INTO Members (suID, name, age, email, class, gender) VALUES ('$suID', '$mname', $age_val, '$email', '$class', '$gender')";
            $rs = mysqli_query($connection, $sql);
            if (mysqli_affected_rows($connection) == 1) { $message = "Member added!"; $msg_type = "success"; }
            else { $message = "Error: " . mysqli_error($connection); $msg_type = "error"; }
        }
        $tab = "members";
    }

    // INSERT MEMBERSHIP
    if (isset($_POST["insert_membership"])) {
        $suID = mysqli_real_escape_string($connection, trim($_POST["ms_suid"]));
        $club = mysqli_real_escape_string($connection, trim($_POST["ms_club"]));
        $dj   = mysqli_real_escape_string($connection, trim($_POST["ms_datejoined"]));
        if ($suID == "" || $club == "") { $message = "Student ID and Club are required."; $msg_type = "error"; }
        else {
            $dj_val = ($dj != "") ? "'$dj'" : "NULL";
            $sql = "INSERT INTO Membership (suID, clubName, dateJoined) VALUES ('$suID', '$club', $dj_val)";
            $rs = mysqli_query($connection, $sql);
            if (mysqli_affected_rows($connection) == 1) { $message = "Membership added!"; $msg_type = "success"; }
            else { $message = "Error: " . mysqli_error($connection); $msg_type = "error"; }
        }
        $tab = "membership";
    }

    // INSERT OFFICER
    if (isset($_POST["insert_officer"])) {
        $suID = mysqli_real_escape_string($connection, trim($_POST["off_suid"]));
        $club = mysqli_real_escape_string($connection, trim($_POST["off_club"]));
        $rank = mysqli_real_escape_string($connection, trim($_POST["off_ranking"]));
        if ($suID == "" || $club == "") { $message = "Student ID and Club are required."; $msg_type = "error"; }
        else {
            $sql = "INSERT INTO Officers (suID, clubName, ranking) VALUES ('$suID', '$club', '$rank')";
            $rs = mysqli_query($connection, $sql);
            if (mysqli_affected_rows($connection) == 1) { $message = "Officer added!"; $msg_type = "success"; }
            else { $message = "Error: " . mysqli_error($connection); $msg_type = "error"; }
        }
        $tab = "officers";
    }

    // INSERT COMMUNICATION
    if (isset($_POST["insert_comm"])) {
        $link = mysqli_real_escape_string($connection, trim($_POST["comm_link"]));
        $app  = mysqli_real_escape_string($connection, trim($_POST["comm_app"]));
        $club = mysqli_real_escape_string($connection, trim($_POST["comm_club"]));
        if ($link == "" || $club == "") { $message = "Link and Club are required."; $msg_type = "error"; }
        else {
            $sql = "INSERT INTO Communication (linkToJoin, primaryApp, clubName) VALUES ('$link', '$app', '$club')";
            $rs = mysqli_query($connection, $sql);
            if (mysqli_affected_rows($connection) == 1) { $message = "Channel added!"; $msg_type = "success"; }
            else { $message = "Error: " . mysqli_error($connection); $msg_type = "error"; }
        }
        $tab = "communication";
    }

    // INSERT EVENT
    if (isset($_POST["insert_event"])) {
        $ename = mysqli_real_escape_string($connection, trim($_POST["event_name"]));
        $edate = mysqli_real_escape_string($connection, trim($_POST["event_date"]));
        $edesc = mysqli_real_escape_string($connection, trim($_POST["event_description"]));
        $est   = mysqli_real_escape_string($connection, trim($_POST["event_starttime"]));
        $eet   = mysqli_real_escape_string($connection, trim($_POST["event_endtime"]));
        $eatt  = trim($_POST["event_attendance"]);
        $etype = mysqli_real_escape_string($connection, trim($_POST["event_type"]));
        $eclub = mysqli_real_escape_string($connection, trim($_POST["event_club"]));
        $eaddr = mysqli_real_escape_string($connection, trim($_POST["event_address"]));
        if ($ename == "" || $edate == "") { $message = "Event name and date are required."; $msg_type = "error"; }
        else {
            $att_val = ($eatt != "") ? intval($eatt) : "NULL";
            $est_val = ($est != "") ? "'$est'" : "NULL";
            $eet_val = ($eet != "") ? "'$eet'" : "NULL";
            $eaddr_val = ($eaddr != "") ? "'$eaddr'" : "NULL";
            $sql = "INSERT INTO Events (name, date, description, startTime, endTime, attendance, type, clubName, address) VALUES ('$ename', '$edate', '$edesc', $est_val, $eet_val, $att_val, '$etype', '$eclub', $eaddr_val)";
            $rs = mysqli_query($connection, $sql);
            if (mysqli_affected_rows($connection) == 1) { $message = "Event added!"; $msg_type = "success"; }
            else { $message = "Error: " . mysqli_error($connection); $msg_type = "error"; }
        }
        $tab = "events";
    }

    // INSERT LOCATION
    if (isset($_POST["insert_location"])) {
        $addr = mysqli_real_escape_string($connection, trim($_POST["loc_address"]));
        $room = mysqli_real_escape_string($connection, trim($_POST["loc_room"]));
        $bldg = mysqli_real_escape_string($connection, trim($_POST["loc_building"]));
        if ($addr == "") { $message = "Address is required."; $msg_type = "error"; }
        else {
            $sql = "INSERT INTO Location (address, roomNumber, building) VALUES ('$addr', '$room', '$bldg')";
            $rs = mysqli_query($connection, $sql);
            if (mysqli_affected_rows($connection) == 1) { $message = "Location added!"; $msg_type = "success"; }
            else { $message = "Error: " . mysqli_error($connection); $msg_type = "error"; }
        }
        $tab = "locations";
    }

    // INSERT FEE
    if (isset($_POST["insert_fee"])) {
        $fid   = mysqli_real_escape_string($connection, trim($_POST["fee_id"]));
        $cost  = trim($_POST["fee_cost"]);
        $due   = mysqli_real_escape_string($connection, trim($_POST["fee_duedate"]));
        $ftype = mysqli_real_escape_string($connection, trim($_POST["fee_type"]));
        $fper  = mysqli_real_escape_string($connection, trim($_POST["fee_period"]));
        $fact  = isset($_POST["fee_active"]) ? 1 : 0;
        $fclub = mysqli_real_escape_string($connection, trim($_POST["fee_club"]));
        $fevent = mysqli_real_escape_string($connection, trim($_POST["fee_eventname"]));
        $fedate = mysqli_real_escape_string($connection, trim($_POST["fee_eventdate"]));
        if ($fid == "") { $message = "Fee ID is required."; $msg_type = "error"; }
        else {
            $cost_val = ($cost != "") ? floatval($cost) : "NULL";
            $due_val = ($due != "") ? "'$due'" : "NULL";
            $fevent_val = ($fevent != "") ? "'$fevent'" : "NULL";
            $fedate_val = ($fedate != "") ? "'$fedate'" : "NULL";
            $fclub_val = ($fclub != "") ? "'$fclub'" : "NULL";
            $sql = "INSERT INTO Fees (feeID, cost, dueDate, type, effectivePeriod, isActive, clubName, eventName, eventDate) VALUES ('$fid', $cost_val, $due_val, '$ftype', '$fper', $fact, $fclub_val, $fevent_val, $fedate_val)";
            $rs = mysqli_query($connection, $sql);
            if (mysqli_affected_rows($connection) == 1) { $message = "Fee added!"; $msg_type = "success"; }
            else { $message = "Error: " . mysqli_error($connection); $msg_type = "error"; }
        }
        $tab = "fees";
    }

    // INSERT PAYMENT
    if (isset($_POST["insert_payment"])) {
        $pid    = mysqli_real_escape_string($connection, trim($_POST["pay_id"]));
        $amt    = trim($_POST["pay_amount"]);
        $pdate  = mysqli_real_escape_string($connection, trim($_POST["pay_date"]));
        $pstat  = mysqli_real_escape_string($connection, trim($_POST["pay_status"]));
        $pmeth  = mysqli_real_escape_string($connection, trim($_POST["pay_method"]));
        $psuid  = mysqli_real_escape_string($connection, trim($_POST["pay_suid"]));
        $pfee   = mysqli_real_escape_string($connection, trim($_POST["pay_feeid"]));
        if ($pid == "") { $message = "Payment ID is required."; $msg_type = "error"; }
        else {
            $amt_val = ($amt != "") ? floatval($amt) : "NULL";
            $pdate_val = ($pdate != "") ? "'$pdate'" : "NULL";
            $sql = "INSERT INTO Member_Payments (paymentID, amountPaid, paymentDate, paymentStatus, paymentMethod, suID, feeID) VALUES ('$pid', $amt_val, $pdate_val, '$pstat', '$pmeth', '$psuid', '$pfee')";
            $rs = mysqli_query($connection, $sql);
            if (mysqli_affected_rows($connection) == 1) { $message = "Payment recorded!"; $msg_type = "success"; }
            else { $message = "Error: " . mysqli_error($connection); $msg_type = "error"; }
        }
        $tab = "payments";
    }
}

// ══════════════════════════════════════════════════════════════
// SEARCH & FETCH
// ══════════════════════════════════════════════════════════════
if (isset($_GET["search"])) { $search = trim($_GET["search"]); } else { $search = ""; }
$is_searching = ($search != "");
$s = mysqli_real_escape_string($connection, $search);

if ($tab == "clubs") {
    $query = $is_searching
        ? "SELECT * FROM Club WHERE name LIKE '%$s%' OR description LIKE '%$s%' OR email LIKE '%$s%' OR type LIKE '%$s%' ORDER BY name ASC"
        : "SELECT * FROM Club ORDER BY name ASC";
}
if ($tab == "members") {
    $query = $is_searching
        ? "SELECT * FROM Members WHERE suID LIKE '%$s%' OR name LIKE '%$s%' OR email LIKE '%$s%' ORDER BY name ASC"
        : "SELECT * FROM Members ORDER BY name ASC";
}
if ($tab == "membership") {
    $query = $is_searching
        ? "SELECT * FROM Membership WHERE suID LIKE '%$s%' OR clubName LIKE '%$s%' ORDER BY clubName ASC"
        : "SELECT * FROM Membership ORDER BY clubName ASC";
}
if ($tab == "officers") {
    $query = $is_searching
        ? "SELECT * FROM Officers WHERE suID LIKE '%$s%' OR clubName LIKE '%$s%' OR ranking LIKE '%$s%' ORDER BY clubName ASC"
        : "SELECT * FROM Officers ORDER BY clubName ASC";
}
if ($tab == "communication") {
    $query = $is_searching
        ? "SELECT * FROM Communication WHERE clubName LIKE '%$s%' OR primaryApp LIKE '%$s%' ORDER BY clubName ASC"
        : "SELECT * FROM Communication ORDER BY clubName ASC";
}
if ($tab == "events") {
    $query = $is_searching
        ? "SELECT * FROM Events WHERE name LIKE '%$s%' OR clubName LIKE '%$s%' OR type LIKE '%$s%' ORDER BY date DESC"
        : "SELECT * FROM Events ORDER BY date DESC";
}
if ($tab == "locations") {
    $query = $is_searching
        ? "SELECT * FROM Location WHERE address LIKE '%$s%' OR building LIKE '%$s%' ORDER BY building ASC"
        : "SELECT * FROM Location ORDER BY building ASC";
}
if ($tab == "fees") {
    $query = $is_searching
        ? "SELECT * FROM Fees WHERE feeID LIKE '%$s%' OR type LIKE '%$s%' OR clubName LIKE '%$s%' ORDER BY dueDate DESC"
        : "SELECT * FROM Fees ORDER BY dueDate DESC";
}
if ($tab == "payments") {
    $query = $is_searching
        ? "SELECT * FROM Member_Payments WHERE paymentID LIKE '%$s%' OR suID LIKE '%$s%' OR paymentStatus LIKE '%$s%' ORDER BY paymentDate DESC"
        : "SELECT * FROM Member_Payments ORDER BY paymentDate DESC";
}

$r = mysqli_query($connection, $query);
$row_count = mysqli_num_rows($r);

// Helper dropdowns
$club_list = mysqli_query($connection, "SELECT name FROM Club ORDER BY name ASC");
$member_list = mysqli_query($connection, "SELECT suID, fname, lname FROM Members ORDER BY lname ASC, fname ASC");
$location_list = mysqli_query($connection, "SELECT address, building FROM Location ORDER BY building ASC");
$fee_list = mysqli_query($connection, "SELECT feeID FROM Fees ORDER BY feeID ASC");

// Tab display names
$tab_labels = array(
    "clubs" => "Clubs",
    "members" => "Members",
    "membership" => "Membership",
    "officers" => "Officers",
    "communication" => "Comms",
    "events" => "Events",
    "locations" => "Locations"
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SU: Clubs and Organizations</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Source+Sans+3:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --maroon: #4a1a2e; --maroon-deep: #3a1022; --gold: #c4a535;
            --gold-light: #f5ecc7; --gold-pale: #faf6e6; --bg: #f7f5f0;
            --surface: #ffffff; --ink: #1e1e1e; --ink-light: #6b645d;
            --border: #e4ded5; --danger: #b91c1c; --danger-bg: #fef2f2;
            --success: #15803d; --success-bg: #f0fdf4; --radius: 8px;
            --shadow: 0 1px 3px rgba(0,0,0,0.05), 0 4px 16px rgba(0,0,0,0.04);
        }
        body { font-family: 'Source Sans 3', sans-serif; background: var(--bg); color: var(--ink); min-height: 100vh; line-height: 1.6; }

        .header { background: var(--maroon); padding: 1.6rem 1rem; text-align: center; position: relative; overflow: hidden; }
        .header::before { content: ""; position: absolute; top: -40%; left: -10%; width: 120%; height: 180%; background: radial-gradient(ellipse at 30% 50%, rgba(196,165,53,0.12) 0%, transparent 60%); pointer-events: none; }
        .header-label { font-size: 0.7rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.15em; color: var(--gold); position: relative; }
        .header h1 { font-family: 'Playfair Display', serif; font-size: 2rem; font-weight: 700; color: #fff; position: relative; }
        .header p { color: rgba(255,255,255,0.55); font-size: 0.88rem; margin-top: 0.2rem; position: relative; }

        .tab-bar { background: var(--surface); border-bottom: 1px solid var(--border); padding: 0.5rem 0.5rem; display: flex; justify-content: center; gap: 0.2rem; flex-wrap: wrap; }
        .tab-link { padding: 0.35rem 0.8rem; border-radius: 20px; font-size: 0.78rem; font-weight: 500; color: var(--ink-light); text-decoration: none; transition: all 0.2s; }
        .tab-link:hover { background: var(--gold-pale); color: var(--ink); }
        .tab-link.active { background: var(--maroon); color: #fff; }

        .container { max-width: 980px; margin: 0 auto; padding: 1.5rem 1.25rem 4rem; }

        .banner { padding: 0.7rem 1rem; border-radius: var(--radius); font-size: 0.85rem; font-weight: 500; margin-bottom: 1.2rem; display: flex; align-items: center; gap: 0.5rem; }
        .banner.success { background: var(--success-bg); color: var(--success); border: 1px solid #bbf7d0; }
        .banner.error { background: var(--danger-bg); color: var(--danger); border: 1px solid #fecaca; }

        .card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); padding: 1.2rem 1.3rem; box-shadow: var(--shadow); margin-bottom: 1.2rem; }
        .card-title { font-family: 'Playfair Display', serif; font-size: 1.1rem; font-weight: 600; margin-bottom: 0.8rem; color: var(--maroon); }

        .form-row { display: flex; gap: 0.5rem; flex-wrap: wrap; align-items: flex-end; margin-bottom: 0.5rem; }
        .form-group { flex: 1; min-width: 110px; }
        .form-group.small { flex: 0.5; min-width: 80px; }
        label { display: block; font-size: 0.67rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.06em; color: var(--ink-light); margin-bottom: 0.2rem; }
        input[type="text"], input[type="number"], input[type="email"], input[type="url"], input[type="tel"], input[type="date"], input[type="time"], select, textarea {
            width: 100%; padding: 0.45rem 0.65rem; border: 1px solid var(--border); border-radius: 5px; font-family: inherit; font-size: 0.88rem; color: var(--ink); background: var(--bg);
        }
        textarea { resize: vertical; min-height: 50px; }
        input:focus, select:focus, textarea:focus { outline: none; border-color: var(--gold); box-shadow: 0 0 0 3px rgba(196,165,53,0.15); }

        .btn { display: inline-flex; align-items: center; gap: 0.3rem; padding: 0.45rem 0.9rem; border: none; border-radius: 5px; font-family: inherit; font-size: 0.8rem; font-weight: 600; cursor: pointer; white-space: nowrap; }
        .btn:active { transform: scale(0.97); }
        .btn-primary { background: var(--maroon); color: #fff; }
        .btn-primary:hover { background: var(--maroon-deep); }
        .btn-gold { background: var(--gold); color: #fff; }
        .btn-gold:hover { background: #a88c28; }
        .btn-outline { background: transparent; color: var(--ink-light); border: 1px solid var(--border); }
        .btn-outline:hover { background: var(--bg); }
        .btn-danger { background: transparent; color: var(--danger); border: 1px solid #fecaca; padding: 0.25rem 0.55rem; font-size: 0.74rem; }
        .btn-danger:hover { background: var(--danger-bg); }

        .search-row { display: flex; gap: 0.5rem; }
        .search-row input { flex: 1; }

        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        thead th { text-align: left; font-size: 0.68rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.06em; color: var(--ink-light); padding: 0.5rem 0.6rem; border-bottom: 2px solid var(--border); }
        tbody td { padding: 0.55rem 0.6rem; border-bottom: 1px solid var(--border); font-size: 0.85rem; }
        tbody tr:hover { background: #faf8f4; }
        .name-cell { font-weight: 600; color: var(--maroon); }
        .type-tag { display: inline-block; padding: 0.1rem 0.45rem; background: var(--gold-pale); color: #8a7520; border: 1px solid var(--gold-light); border-radius: 20px; font-size: 0.72rem; font-weight: 600; }
        .meta { font-size: 0.78rem; color: var(--ink-light); }
        .count-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.6rem; font-size: 0.8rem; color: var(--ink-light); }
        .count-bar strong { color: var(--ink); }
        .empty { text-align: center; padding: 2rem 1rem; color: var(--ink-light); }
        .footer { text-align: center; padding: 1.5rem 1rem; font-size: 0.75rem; color: var(--ink-light); }
        .footer a { color: var(--maroon); text-decoration: none; }

        @media (max-width: 640px) {
            .form-row { flex-direction: column; }
            .form-group, .form-group.small { min-width: 100%; }
            .header h1 { font-size: 1.5rem; }
            .tab-link { font-size: 0.72rem; padding: 0.3rem 0.6rem; }
        }
    </style>
</head>
<body>

<div class="header">
    <div class="header-label">Salisbury University</div>
    <h1>Clubs &amp; Organizations</h1>
    <p>Manage clubs, members, events, fees, and payments.</p>
</div>

<div class="tab-bar">
    <?php
    foreach ($tab_labels as $key => $label) {
        $active = ($tab == $key) ? "active" : "";
        echo "<a href='?tab=$key' class='tab-link $active'>$label</a>";
    }
    ?>
</div>

<div class="container">

<?php if ($message != ""): ?>
    <div class="banner <?php echo $msg_type; ?>"><?php echo $message; ?></div>
<?php endif; ?>

<!-- ═══════════════════════════════════════════════════════════ -->
<!-- CLUBS TAB -->
<!-- ═══════════════════════════════════════════════════════════ -->
<?php if ($tab == "clubs"): ?>
<div class="card">
    <div class="card-title">Register a New Club</div>
    <form method="post" action="?tab=clubs">
        <input type="hidden" name="action" value="insert">
        <input type="hidden" name="insert_club" value="1">
        <div class="form-row">
            <div class="form-group"><label>Club Name *</label><input type="text" name="club_name" required placeholder="e.g. Computer Science Club"></div>
            <div class="form-group">
                <label>Type</label>
                <select name="club_type">
                    <option value="">--</option>
                    <option value="Academic">Academic</option><option value="Sports">Sports</option>
                    <option value="Social">Social</option><option value="Arts">Arts</option>
                    <option value="Cultural">Cultural</option><option value="Service">Service</option>
                    <option value="Professional">Professional</option><option value="Religious">Religious</option>
                    <option value="Other">Other</option>
                </select>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group"><label>Description</label><textarea name="club_description" placeholder="About this club..."></textarea></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label>Email</label><input type="email" name="club_email" placeholder="club@salisbury.edu"></div>
            <div class="form-group"><label>Phone</label><input type="tel" name="club_phone" placeholder="(410) 555-0100"></div>
            <div class="form-group"><label>Founded</label><input type="date" name="club_foundationDate"></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label>Website</label><input type="url" name="club_link" placeholder="https://..."></div>
            <div class="form-group small" style="flex:0 0 auto;"><label>&nbsp;</label><button type="submit" class="btn btn-gold">+ Register</button></div>
        </div>
    </form>
</div>
<?php endif; ?>

<!-- MEMBERS TAB -->
<?php if ($tab == "members"): ?>
<div class="card">
    <div class="card-title">Add a Member</div>
    <form method="post" action="?tab=members">
        <input type="hidden" name="action" value="insert"><input type="hidden" name="insert_member" value="1">
        <div class="form-row">
            <div class="form-group"><label>Student ID *</label><input type="text" name="member_suid" required placeholder="e.g. S00123456"></div>
            <div class="form-group"><label>Name</label><input type="text" name="member_name" placeholder="Full name"></div>
            <div class="form-group small"><label>Age</label><input type="number" name="member_age" min="16" max="99"></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label>Email</label><input type="email" name="member_email" placeholder="student@salisbury.edu"></div>
            <div class="form-group"><label>Class</label>
                <select name="member_class"><option value="">--</option><option value="Freshman">Freshman</option><option value="Sophomore">Sophomore</option><option value="Junior">Junior</option><option value="Senior">Senior</option><option value="Graduate">Graduate</option></select>
            </div>
            <div class="form-group"><label>Gender</label>
                <select name="member_gender"><option value="">--</option><option value="Male">Male</option><option value="Female">Female</option><option value="Non-binary">Non-binary</option><option value="Other">Other</option></select>
            </div>
            <div class="form-group small" style="flex:0 0 auto;"><label>&nbsp;</label><button type="submit" class="btn btn-gold">+ Add</button></div>
        </div>
    </form>
</div>
<?php endif; ?>

<!-- MEMBERSHIP TAB -->
<?php if ($tab == "membership"): ?>
<div class="card">
    <div class="card-title">Add Membership</div>
    <form method="post" action="?tab=membership">
        <input type="hidden" name="action" value="insert"><input type="hidden" name="insert_membership" value="1">
        <div class="form-row">
            <div class="form-group"><label>Student ID *</label><input type="text" name="ms_suid" required></div>
            <div class="form-group"><label>Club *</label>
                <select name="ms_club" required><option value="">--</option>
                <?php mysqli_data_seek($club_list, 0); while($c = mysqli_fetch_array($club_list)) { echo "<option value='" . htmlspecialchars($c["name"]) . "'>" . htmlspecialchars($c["name"]) . "</option>"; } ?>
                </select></div>
            <div class="form-group"><label>Date Joined</label><input type="date" name="ms_datejoined"></div>
            <div class="form-group small" style="flex:0 0 auto;"><label>&nbsp;</label><button type="submit" class="btn btn-gold">+ Add</button></div>
        </div>
    </form>
</div>
<?php endif; ?>

<!-- OFFICERS TAB -->
<?php if ($tab == "officers"): ?>
<div class="card">
    <div class="card-title">Add an Officer</div>
    <form method="post" action="?tab=officers">
        <input type="hidden" name="action" value="insert"><input type="hidden" name="insert_officer" value="1">
        <div class="form-row">
            <div class="form-group"><label>Student ID *</label><input type="text" name="off_suid" required></div>
            <div class="form-group"><label>Club *</label>
                <select name="off_club" required><option value="">--</option>
                <?php mysqli_data_seek($club_list, 0); while($c = mysqli_fetch_array($club_list)) { echo "<option value='" . htmlspecialchars($c["name"]) . "'>" . htmlspecialchars($c["name"]) . "</option>"; } ?>
                </select></div>
            <div class="form-group"><label>Ranking</label>
                <select name="off_ranking"><option value="">--</option><option value="President">President</option><option value="Vice President">Vice President</option><option value="Treasurer">Treasurer</option><option value="Secretary">Secretary</option><option value="Other">Other</option></select></div>
            <div class="form-group small" style="flex:0 0 auto;"><label>&nbsp;</label><button type="submit" class="btn btn-gold">+ Add</button></div>
        </div>
    </form>
</div>
<?php endif; ?>

<!-- COMMUNICATION TAB -->
<?php if ($tab == "communication"): ?>
<div class="card">
    <div class="card-title">Add Communication Channel</div>
    <form method="post" action="?tab=communication">
        <input type="hidden" name="action" value="insert"><input type="hidden" name="insert_comm" value="1">
        <div class="form-row">
            <div class="form-group"><label>Club *</label>
                <select name="comm_club" required><option value="">--</option>
                <?php mysqli_data_seek($club_list, 0); while($c = mysqli_fetch_array($club_list)) { echo "<option value='" . htmlspecialchars($c["name"]) . "'>" . htmlspecialchars($c["name"]) . "</option>"; } ?>
                </select></div>
            <div class="form-group"><label>App</label>
                <select name="comm_app"><option value="">--</option><option value="Discord">Discord</option><option value="GroupMe">GroupMe</option><option value="Slack">Slack</option><option value="WhatsApp">WhatsApp</option><option value="Instagram">Instagram</option><option value="Other">Other</option></select></div>
            <div class="form-group"><label>Link to Join *</label><input type="text" name="comm_link" required placeholder="https://..."></div>
            <div class="form-group small" style="flex:0 0 auto;"><label>&nbsp;</label><button type="submit" class="btn btn-gold">+ Add</button></div>
        </div>
    </form>
</div>
<?php endif; ?>

<!-- EVENTS TAB -->
<?php if ($tab == "events"): ?>
<div class="card">
    <div class="card-title">Add an Event</div>
    <form method="post" action="?tab=events">
        <input type="hidden" name="action" value="insert"><input type="hidden" name="insert_event" value="1">
        <div class="form-row">
            <div class="form-group"><label>Event Name *</label><input type="text" name="event_name" required placeholder="e.g. Fall Mixer"></div>
            <div class="form-group"><label>Date *</label><input type="date" name="event_date" required></div>
            <div class="form-group"><label>Club</label>
                <select name="event_club"><option value="">--</option>
                <?php mysqli_data_seek($club_list, 0); while($c = mysqli_fetch_array($club_list)) { echo "<option value='" . htmlspecialchars($c["name"]) . "'>" . htmlspecialchars($c["name"]) . "</option>"; } ?>
                </select></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label>Start Time</label><input type="time" name="event_starttime"></div>
            <div class="form-group"><label>End Time</label><input type="time" name="event_endtime"></div>
            <div class="form-group"><label>Type</label>
                <select name="event_type"><option value="">--</option><option value="Fundraiser">Fundraiser</option><option value="Competition">Competition</option><option value="Social Mixer">Social Mixer</option><option value="Meeting">Meeting</option><option value="Workshop">Workshop</option><option value="Other">Other</option></select></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label>Location</label>
                <select name="event_address"><option value="">--</option>
                <?php mysqli_data_seek($location_list, 0); while($l = mysqli_fetch_array($location_list)) { echo "<option value='" . htmlspecialchars($l["address"]) . "'>" . htmlspecialchars($l["building"] . " - " . $l["address"]) . "</option>"; } ?>
                </select></div>
            <div class="form-group small"><label>Attendance</label><input type="number" name="event_attendance" min="0"></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label>Description</label><textarea name="event_description" placeholder="Event details..."></textarea></div>
            <div class="form-group small" style="flex:0 0 auto; align-self:flex-end;"><button type="submit" class="btn btn-gold">+ Add</button></div>
        </div>
    </form>
</div>
<?php endif; ?>

<!-- LOCATIONS TAB -->
<?php if ($tab == "locations"): ?>
<div class="card">
    <div class="card-title">Add a Location</div>
    <form method="post" action="?tab=locations">
        <input type="hidden" name="action" value="insert"><input type="hidden" name="insert_location" value="1">
        <div class="form-row">
            <div class="form-group"><label>Address *</label><input type="text" name="loc_address" required placeholder="e.g. 1101 Camden Ave"></div>
            <div class="form-group"><label>Building</label><input type="text" name="loc_building" placeholder="e.g. Henson Hall"></div>
            <div class="form-group small"><label>Room #</label><input type="text" name="loc_room" placeholder="e.g. 234"></div>
            <div class="form-group small" style="flex:0 0 auto;"><label>&nbsp;</label><button type="submit" class="btn btn-gold">+ Add</button></div>
        </div>
    </form>
</div>
<?php endif; ?>

<!-- FEES TAB -->
<?php if ($tab == "fees"): ?>
<div class="card">
    <div class="card-title">Add a Fee</div>
    <form method="post" action="?tab=fees">
        <input type="hidden" name="action" value="insert"><input type="hidden" name="insert_fee" value="1">
        <div class="form-row">
            <div class="form-group"><label>Fee ID *</label><input type="text" name="fee_id" required placeholder="e.g. FEE001"></div>
            <div class="form-group small"><label>Cost ($)</label><input type="number" name="fee_cost" step="0.01" min="0"></div>
            <div class="form-group"><label>Due Date</label><input type="date" name="fee_duedate"></div>
            <div class="form-group"><label>Type</label>
                <select name="fee_type"><option value="">--</option><option value="membership">Membership</option><option value="event">Event</option></select></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label>Club</label>
                <select name="fee_club"><option value="">--</option>
                <?php mysqli_data_seek($club_list, 0); while($c = mysqli_fetch_array($club_list)) { echo "<option value='" . htmlspecialchars($c["name"]) . "'>" . htmlspecialchars($c["name"]) . "</option>"; } ?>
                </select></div>
            <div class="form-group"><label>Period</label><input type="text" name="fee_period" placeholder="e.g. Fall 2026"></div>
            <div class="form-group small"><label>Active</label><select name="fee_active"><option value="1">Yes</option><option value="0">No</option></select></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label>Event Name (optional)</label><input type="text" name="fee_eventname"></div>
            <div class="form-group"><label>Event Date (optional)</label><input type="date" name="fee_eventdate"></div>
            <div class="form-group small" style="flex:0 0 auto;"><label>&nbsp;</label><button type="submit" class="btn btn-gold">+ Add</button></div>
        </div>
    </form>
</div>
<?php endif; ?>

<!-- PAYMENTS TAB -->
<?php if ($tab == "payments"): ?>
<div class="card">
    <div class="card-title">Record a Payment</div>
    <form method="post" action="?tab=payments">
        <input type="hidden" name="action" value="insert"><input type="hidden" name="insert_payment" value="1">
        <div class="form-row">
            <div class="form-group"><label>Payment ID *</label><input type="text" name="pay_id" required placeholder="e.g. PAY001"></div>
            <div class="form-group small"><label>Amount ($)</label><input type="number" name="pay_amount" step="0.01" min="0"></div>
            <div class="form-group"><label>Date</label><input type="date" name="pay_date"></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label>Student ID</label><input type="text" name="pay_suid"></div>
            <div class="form-group"><label>Fee ID</label><input type="text" name="pay_feeid"></div>
            <div class="form-group"><label>Status</label>
                <select name="pay_status"><option value="">--</option><option value="paid">Paid</option><option value="due">Due</option><option value="late">Late</option><option value="waived">Waived</option></select></div>
            <div class="form-group"><label>Method</label>
                <select name="pay_method"><option value="">--</option><option value="Cash">Cash</option><option value="Card">Card</option><option value="Venmo">Venmo</option><option value="Zelle">Zelle</option><option value="Other">Other</option></select></div>
            <div class="form-group small" style="flex:0 0 auto;"><label>&nbsp;</label><button type="submit" class="btn btn-gold">+ Add</button></div>
        </div>
    </form>
</div>
<?php endif; ?>

<!-- ═══════════════════════════════════════════════════════════ -->
<!-- SEARCH (all tabs) -->
<!-- ═══════════════════════════════════════════════════════════ -->
<div class="card">
    <div class="card-title">Search <?php echo $tab_labels[$tab]; ?></div>
    <form method="get">
        <input type="hidden" name="tab" value="<?php echo $tab; ?>">
        <div class="search-row">
            <input type="text" name="search" placeholder="Search..." value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit" class="btn btn-primary">Search</button>
            <?php if ($is_searching): ?>
                <a href="?tab=<?php echo $tab; ?>" class="btn btn-outline">Clear</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- ═══════════════════════════════════════════════════════════ -->
<!-- RESULTS TABLE (all tabs) -->
<!-- ═══════════════════════════════════════════════════════════ -->
<div class="card">
    <div class="count-bar">
        <span><?php if ($is_searching) { echo 'Results for "<strong>' . htmlspecialchars($search) . '</strong>"'; } else { echo '<strong>All ' . $tab_labels[$tab] . '</strong>'; } ?></span>
        <span><?php echo $row_count; ?> record<?php if ($row_count != 1) echo 's'; ?></span>
    </div>

    <?php if ($row_count > 0): ?>
    <div class="table-wrap">
        <table>
            <thead><tr>
            <?php
            // DYNAMIC TABLE HEADERS
            if ($tab == "clubs") { echo "<th>Name</th><th>Type</th><th>Email</th><th>Phone</th><th>Founded</th><th></th>"; }
            if ($tab == "members") { echo "<th>SU ID</th><th>Name</th><th>Age</th><th>Email</th><th>Class</th><th>Gender</th><th></th>"; }
            if ($tab == "membership") { echo "<th>Student ID</th><th>Club</th><th>Date Joined</th><th></th>"; }
            if ($tab == "officers") { echo "<th>Student ID</th><th>Club</th><th>Ranking</th><th></th>"; }
            if ($tab == "communication") { echo "<th>Club</th><th>App</th><th>Link</th><th></th>"; }
            if ($tab == "events") { echo "<th>Event</th><th>Date</th><th>Club</th><th>Time</th><th>Type</th><th>Attendance</th><th></th>"; }
            if ($tab == "locations") { echo "<th>Address</th><th>Building</th><th>Room</th><th></th>"; }
            if ($tab == "fees") { echo "<th>Fee ID</th><th>Cost</th><th>Due Date</th><th>Type</th><th>Club</th><th>Active</th><th></th>"; }
            if ($tab == "payments") { echo "<th>Pay ID</th><th>Amount</th><th>Date</th><th>Status</th><th>Method</th><th>Student</th><th>Fee</th><th></th>"; }
            ?>
            </tr></thead>
            <tbody>
            <?php while ($row = mysqli_fetch_array($r)):

                // CLUBS
                if ($tab == "clubs"): ?>
                <tr>
                    <td class="name-cell"><?php echo htmlspecialchars($row["name"]); ?></td>
                    <td><?php echo ($row["type"] != "") ? '<span class="type-tag">' . htmlspecialchars($row["type"]) . '</span>' : '<span class="meta">--</span>'; ?></td>
                    <td class="meta"><?php echo ($row["email"] != "") ? htmlspecialchars($row["email"]) : "--"; ?></td>
                    <td class="meta"><?php echo ($row["phone"] != "") ? htmlspecialchars($row["phone"]) : "--"; ?></td>
                    <td class="meta"><?php echo ($row["foundationDate"] != "") ? date("M Y", strtotime($row["foundationDate"])) : "--"; ?></td>
                    <td><form method="post" action="?tab=clubs" style="display:inline;" onsubmit="return confirm('Delete this club?');"><input type="hidden" name="action" value="delete"><input type="hidden" name="delete_club" value="<?php echo htmlspecialchars($row["name"]); ?>"><button type="submit" class="btn btn-danger">Delete</button></form></td>
                </tr>

                <?php elseif ($tab == "members"): ?>
                <tr>
                    <td class="name-cell"><?php echo htmlspecialchars($row["suID"]); ?></td>
                    <td><?php echo htmlspecialchars($row["name"]); ?></td>
                    <td class="meta"><?php echo ($row["age"] != "") ? $row["age"] : "--"; ?></td>
                    <td class="meta"><?php echo ($row["email"] != "") ? htmlspecialchars($row["email"]) : "--"; ?></td>
                    <td><?php echo ($row["class"] != "") ? '<span class="type-tag">' . htmlspecialchars($row["class"]) . '</span>' : '<span class="meta">--</span>'; ?></td>
                    <td class="meta"><?php echo ($row["gender"] != "") ? htmlspecialchars($row["gender"]) : "--"; ?></td>
                    <td><form method="post" action="?tab=members" style="display:inline;" onsubmit="return confirm('Delete this member?');"><input type="hidden" name="action" value="delete"><input type="hidden" name="delete_member" value="<?php echo htmlspecialchars($row["suID"]); ?>"><button type="submit" class="btn btn-danger">Delete</button></form></td>
                </tr>

                <?php elseif ($tab == "membership"): ?>
                <tr>
                    <td class="name-cell"><?php echo htmlspecialchars($row["suID"]); ?></td>
                    <td><?php echo htmlspecialchars($row["clubName"]); ?></td>
                    <td class="meta"><?php echo ($row["dateJoined"] != "") ? date("M j, Y", strtotime($row["dateJoined"])) : "--"; ?></td>
                    <td><form method="post" action="?tab=membership" style="display:inline;" onsubmit="return confirm('Remove?');"><input type="hidden" name="action" value="delete"><input type="hidden" name="delete_membership_suid" value="<?php echo htmlspecialchars($row["suID"]); ?>"><input type="hidden" name="delete_membership_club" value="<?php echo htmlspecialchars($row["clubName"]); ?>"><button type="submit" class="btn btn-danger">Delete</button></form></td>
                </tr>

                <?php elseif ($tab == "officers"): ?>
                <tr>
                    <td class="name-cell"><?php echo htmlspecialchars($row["suID"]); ?></td>
                    <td><?php echo htmlspecialchars($row["clubName"]); ?></td>
                    <td><?php echo ($row["ranking"] != "") ? '<span class="type-tag">' . htmlspecialchars($row["ranking"]) . '</span>' : '<span class="meta">--</span>'; ?></td>
                    <td><form method="post" action="?tab=officers" style="display:inline;" onsubmit="return confirm('Remove?');"><input type="hidden" name="action" value="delete"><input type="hidden" name="delete_officer_suid" value="<?php echo htmlspecialchars($row["suID"]); ?>"><input type="hidden" name="delete_officer_club" value="<?php echo htmlspecialchars($row["clubName"]); ?>"><button type="submit" class="btn btn-danger">Delete</button></form></td>
                </tr>

                <?php elseif ($tab == "communication"): ?>
                <tr>
                    <td class="name-cell"><?php echo htmlspecialchars($row["clubName"]); ?></td>
                    <td><?php echo ($row["primaryApp"] != "") ? '<span class="type-tag">' . htmlspecialchars($row["primaryApp"]) . '</span>' : '<span class="meta">--</span>'; ?></td>
                    <td class="meta"><?php echo htmlspecialchars($row["linkToJoin"]); ?></td>
                    <td><form method="post" action="?tab=communication" style="display:inline;" onsubmit="return confirm('Delete?');"><input type="hidden" name="action" value="delete"><input type="hidden" name="delete_comm" value="<?php echo htmlspecialchars($row["linkToJoin"]); ?>"><button type="submit" class="btn btn-danger">Delete</button></form></td>
                </tr>

                <?php elseif ($tab == "events"): ?>
                <tr>
                    <td class="name-cell"><?php echo htmlspecialchars($row["name"]); ?></td>
                    <td class="meta"><?php echo ($row["date"] != "") ? date("M j, Y", strtotime($row["date"])) : "--"; ?></td>
                    <td><?php echo ($row["clubName"] != "") ? htmlspecialchars($row["clubName"]) : "--"; ?></td>
                    <td class="meta"><?php
                        $st = ($row["startTime"] != "") ? date("g:iA", strtotime($row["startTime"])) : "";
                        $et = ($row["endTime"] != "") ? date("g:iA", strtotime($row["endTime"])) : "";
                        echo ($st != "") ? $st . ($et != "" ? " - " . $et : "") : "--";
                    ?></td>
                    <td><?php echo ($row["type"] != "") ? '<span class="type-tag">' . htmlspecialchars($row["type"]) . '</span>' : '<span class="meta">--</span>'; ?></td>
                    <td class="meta"><?php echo ($row["attendance"] != "") ? $row["attendance"] : "--"; ?></td>
                    <td><form method="post" action="?tab=events" style="display:inline;" onsubmit="return confirm('Delete?');"><input type="hidden" name="action" value="delete"><input type="hidden" name="delete_event_name" value="<?php echo htmlspecialchars($row["name"]); ?>"><input type="hidden" name="delete_event_date" value="<?php echo htmlspecialchars($row["date"]); ?>"><button type="submit" class="btn btn-danger">Delete</button></form></td>
                </tr>

                <?php elseif ($tab == "locations"): ?>
                <tr>
                    <td class="name-cell"><?php echo htmlspecialchars($row["address"]); ?></td>
                    <td><?php echo ($row["building"] != "") ? htmlspecialchars($row["building"]) : "--"; ?></td>
                    <td class="meta"><?php echo ($row["roomNumber"] != "") ? htmlspecialchars($row["roomNumber"]) : "--"; ?></td>
                    <td><form method="post" action="?tab=locations" style="display:inline;" onsubmit="return confirm('Delete?');"><input type="hidden" name="action" value="delete"><input type="hidden" name="delete_location" value="<?php echo htmlspecialchars($row["address"]); ?>"><button type="submit" class="btn btn-danger">Delete</button></form></td>
                </tr>

                <?php elseif ($tab == "fees"): ?>
                <tr>
                    <td class="name-cell"><?php echo htmlspecialchars($row["feeID"]); ?></td>
                    <td><?php echo ($row["cost"] != "") ? "$" . number_format($row["cost"], 2) : "--"; ?></td>
                    <td class="meta"><?php echo ($row["dueDate"] != "") ? date("M j, Y", strtotime($row["dueDate"])) : "--"; ?></td>
                    <td><?php echo ($row["type"] != "") ? '<span class="type-tag">' . htmlspecialchars($row["type"]) . '</span>' : '<span class="meta">--</span>'; ?></td>
                    <td class="meta"><?php echo ($row["clubName"] != "") ? htmlspecialchars($row["clubName"]) : "--"; ?></td>
                    <td class="meta"><?php echo ($row["isActive"] == 1) ? "Yes" : "No"; ?></td>
                    <td><form method="post" action="?tab=fees" style="display:inline;" onsubmit="return confirm('Delete?');"><input type="hidden" name="action" value="delete"><input type="hidden" name="delete_fee" value="<?php echo htmlspecialchars($row["feeID"]); ?>"><button type="submit" class="btn btn-danger">Delete</button></form></td>
                </tr>

                <?php elseif ($tab == "payments"): ?>
                <tr>
                    <td class="name-cell"><?php echo htmlspecialchars($row["paymentID"]); ?></td>
                    <td><?php echo ($row["amountPaid"] != "") ? "$" . number_format($row["amountPaid"], 2) : "--"; ?></td>
                    <td class="meta"><?php echo ($row["paymentDate"] != "") ? date("M j, Y", strtotime($row["paymentDate"])) : "--"; ?></td>
                    <td><?php
                        $ps = strtolower($row["paymentStatus"]);
                        if ($ps == "paid") echo '<span class="type-tag" style="background:#eefbf3;color:#15803d;border-color:#bbf7d0;">Paid</span>';
                        else if ($ps == "late") echo '<span class="type-tag" style="background:#fef2f2;color:#b91c1c;border-color:#fecaca;">Late</span>';
                        else if ($ps == "due") echo '<span class="type-tag" style="background:#fef9e7;color:#b7950b;border-color:#f5ecc7;">Due</span>';
                        else if ($ps == "waived") echo '<span class="type-tag">Waived</span>';
                        else echo '<span class="meta">--</span>';
                    ?></td>
                    <td class="meta"><?php echo ($row["paymentMethod"] != "") ? htmlspecialchars($row["paymentMethod"]) : "--"; ?></td>
                    <td class="meta"><?php echo ($row["suID"] != "") ? htmlspecialchars($row["suID"]) : "--"; ?></td>
                    <td class="meta"><?php echo ($row["feeID"] != "") ? htmlspecialchars($row["feeID"]) : "--"; ?></td>
                    <td><form method="post" action="?tab=payments" style="display:inline;" onsubmit="return confirm('Delete?');"><input type="hidden" name="action" value="delete"><input type="hidden" name="delete_payment" value="<?php echo htmlspecialchars($row["paymentID"]); ?>"><button type="submit" class="btn btn-danger">Delete</button></form></td>
                </tr>
                <?php endif; ?>

            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
        <div class="empty"><p><?php echo $is_searching ? "No results found." : "No records yet."; ?></p></div>
    <?php endif; ?>
</div>

</div>

<div class="footer">
    SU Club Organizations &middot; COSC 386 &middot; <a href="https://www.salisbury.edu">Salisbury University</a>
</div>

<?php mysqli_close($connection); ?>
</body>
</html>