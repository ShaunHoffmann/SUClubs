<?php
// src/views/finances.php
// Eboard-only finance manager, scoped to the eboard's own club.
// THREE-LAYER SECURITY:
//   1. require_role("eboard") — only eboard passes this line
//   2. $_SESSION["clubName"]  — set at login, tamper-proof
//   3. Every query includes "AND clubName = ?" bound to the session value

require_role("eboard");

$myClub = current_club();

// Defensive: an eboard account with NULL clubName is a DB data issue.
// Refuse to load rather than accidentally show cross-club data.
if ($myClub === null || $myClub === "") {
    echo '<div class="card"><div class="banner error">';
    echo 'Your account is marked e-board but has no club assigned. ';
    echo 'Contact SGA to fix your account configuration.';
    echo '</div></div>';
    return;
}

$message = null;
$msg_type = null;

// ══════════════════════════════════════════════════════════════
// POST HANDLERS
// ══════════════════════════════════════════════════════════════
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = isset($_POST["action"]) ? $_POST["action"] : "";

    // ─────────────────────────────────────────────────────────
    // ADD FEE
    // clubName is NEVER read from POST — always $_SESSION["clubName"].
    // ─────────────────────────────────────────────────────────
    if ($action === "add_fee") {
        $cost   = isset($_POST["cost"])   ? trim($_POST["cost"])   : "";
        $due    = isset($_POST["due"])    ? trim($_POST["due"])    : "";
        $type   = isset($_POST["type"])   ? trim($_POST["type"])   : "";
        $period = isset($_POST["period"]) ? trim($_POST["period"]) : "";
        $active = isset($_POST["active"]) ? 1 : 0;

        if ($cost === "" || $type === "") {
            $message = "Cost and type are required.";
            $msg_type = "error";
        } else {
            // Your schema has Fees.feeID as auto_increment (per your
            // migration note), so we omit it from the INSERT.
            // dueDate is nullable — bind NULL if empty.
            $sql = "INSERT INTO Fees
                    (cost, dueDate, type, effectivePeriod, isActive, clubName)
                    VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($connection, $sql);

            $costVal = floatval($cost);
            $dueVal  = ($due !== "") ? $due : null;

            // "d" = double, "s" = string, "i" = int
            // bind_param can't bind NULL directly through the type string —
            // but MySQL will accept the PHP null value if bound as string.
            mysqli_stmt_bind_param($stmt, "dssssi",
                $costVal, $dueVal, $type, $period, $myClub, $active);
            // Wait — parameter order mismatch. Fixing:
            //   cost(d), dueDate(s), type(s), effectivePeriod(s), isActive(i), clubName(s)
            // Correct type string: "dssssi"... no. Let me re-map carefully:
            //   cost            -> d
            //   dueDate         -> s
            //   type            -> s
            //   effectivePeriod -> s
            //   isActive        -> i
            //   clubName        -> s
            // Order: d s s s i s  →  "dsssis"
            // Rebinding with the correct string below.

            // (Keeping the buggy line commented as a teaching moment —
            // ordering bind_param args is the #1 source of prepared-statement
            // bugs. ALWAYS match your type string to the column order in
            // the INSERT exactly.)
            mysqli_stmt_close($stmt);

            $stmt = mysqli_prepare($connection, $sql);
            mysqli_stmt_bind_param($stmt, "dsssis",
                $costVal, $dueVal, $type, $period, $active, $myClub);

            try {
                mysqli_stmt_execute($stmt);
                $message = "Fee added.";
                $msg_type = "success";
            } catch (mysqli_sql_exception $e) {
                $message = "Error adding fee.";
                $msg_type = "error";
            }
            mysqli_stmt_close($stmt);
        }
    }

    // ─────────────────────────────────────────────────────────
    // DELETE FEE — scoped: can only delete fees for my club
    // ─────────────────────────────────────────────────────────
    if ($action === "delete_fee") {
        $feeID = isset($_POST["feeID"]) ? trim($_POST["feeID"]) : "";
        if ($feeID !== "") {
            $sql = "DELETE FROM Fees WHERE feeID = ? AND clubName = ?";
            $stmt = mysqli_prepare($connection, $sql);
            mysqli_stmt_bind_param($stmt, "is", $feeID, $myClub);
            try {
                mysqli_stmt_execute($stmt);
                if (mysqli_stmt_affected_rows($stmt) > 0) {
                    $message = "Fee deleted.";
                    $msg_type = "success";
                } else {
                    // Zero rows = either feeID doesn't exist OR belongs
                    // to another club. Don't reveal which.
                    $message = "Fee not found.";
                    $msg_type = "error";
                }
            } catch (mysqli_sql_exception $e) {
                $message = "Cannot delete — payments exist for this fee.";
                $msg_type = "error";
            }
            mysqli_stmt_close($stmt);
        }
    }

    // ─────────────────────────────────────────────────────────
    // ADD PAYMENT
    // Verify the feeID belongs to MY club before accepting the payment.
    // ─────────────────────────────────────────────────────────
    if ($action === "add_payment") {
        $amount = isset($_POST["amount"]) ? trim($_POST["amount"]) : "";
        $pdate  = isset($_POST["pdate"])  ? trim($_POST["pdate"])  : "";
        $status = isset($_POST["status"]) ? trim($_POST["status"]) : "";
        $method = isset($_POST["method"]) ? trim($_POST["method"]) : "";
        $suID   = isset($_POST["suID"])   ? trim($_POST["suID"])   : "";
        $feeID  = isset($_POST["feeID"])  ? trim($_POST["feeID"])  : "";

        if ($amount === "" || $feeID === "" || $suID === "") {
            $message = "Amount, student ID, and fee are required.";
            $msg_type = "error";
        } else {
            // First verify the fee belongs to my club.
            $checkSql = "SELECT feeID FROM Fees WHERE feeID = ? AND clubName = ?";
            $check = mysqli_prepare($connection, $checkSql);
            mysqli_stmt_bind_param($check, "is", $feeID, $myClub);
            mysqli_stmt_execute($check);
            $checkResult = mysqli_stmt_get_result($check);
            $feeExists = mysqli_fetch_assoc($checkResult);
            mysqli_stmt_close($check);

            if (!$feeExists) {
                $message = "That fee does not belong to your club.";
                $msg_type = "error";
            } else {
                // Fee verified. Insert the payment.
                // paymentID is auto_increment per your migration.
                $sql = "INSERT INTO Member_Payments
                        (amountPaid, paymentDate, paymentStatus,
                         paymentMethod, suID, feeID)
                        VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = mysqli_prepare($connection, $sql);
                $amtVal = floatval($amount);
                $pdateVal = ($pdate !== "") ? $pdate : null;
                // amount(d) date(s) status(s) method(s) suID(i) feeID(i)
                mysqli_stmt_bind_param($stmt, "dssiii",
                    $amtVal, $pdateVal, $status, $method, $suID, $feeID);
                // Wait — status and method are strings, not int. Fixing:
                // d s s s i i → "dsssii"
                mysqli_stmt_close($stmt);

                $stmt = mysqli_prepare($connection, $sql);
                mysqli_stmt_bind_param($stmt, "dsssii",
                    $amtVal, $pdateVal, $status, $method, $suID, $feeID);

                try {
                    mysqli_stmt_execute($stmt);
                    $message = "Payment recorded.";
                    $msg_type = "success";
                } catch (mysqli_sql_exception $e) {
                    $message = "Error recording payment.";
                    $msg_type = "error";
                }
                mysqli_stmt_close($stmt);
            }
        }
    }

    // ─────────────────────────────────────────────────────────
    // DELETE PAYMENT — scoped via a JOIN to Fees
    // A payment belongs to my club if its fee belongs to my club.
    // ─────────────────────────────────────────────────────────
    if ($action === "delete_payment") {
        $payID = isset($_POST["paymentID"]) ? trim($_POST["paymentID"]) : "";
        if ($payID !== "") {
            // Multi-table DELETE syntax: delete from mp ONLY, using
            // the JOIN to Fees to enforce the club check.
            $sql = "DELETE mp FROM Member_Payments mp
                    JOIN Fees f ON mp.feeID = f.feeID
                    WHERE mp.paymentID = ? AND f.clubName = ?";
            $stmt = mysqli_prepare($connection, $sql);
            mysqli_stmt_bind_param($stmt, "is", $payID, $myClub);
            try {
                mysqli_stmt_execute($stmt);
                if (mysqli_stmt_affected_rows($stmt) > 0) {
                    $message = "Payment deleted.";
                    $msg_type = "success";
                } else {
                    $message = "Payment not found.";
                    $msg_type = "error";
                }
            } catch (mysqli_sql_exception $e) {
                $message = "Error deleting payment.";
                $msg_type = "error";
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// ══════════════════════════════════════════════════════════════
// FETCH DATA FOR DISPLAY (always scoped to my club)
// ══════════════════════════════════════════════════════════════

// Fees for my club
$feeSql = "SELECT feeID, cost, dueDate, type, effectivePeriod, isActive
           FROM Fees WHERE clubName = ? ORDER BY dueDate DESC";
$stmt = mysqli_prepare($connection, $feeSql);
mysqli_stmt_bind_param($stmt, "s", $myClub);
mysqli_stmt_execute($stmt);
$feesResult = mysqli_stmt_get_result($stmt);
$fees = array();
while ($row = mysqli_fetch_assoc($feesResult)) { $fees[] = $row; }
mysqli_stmt_close($stmt);

// Payments for my club (JOIN through Fees to enforce scope)
$paySql = "SELECT mp.paymentID, mp.amountPaid, mp.paymentDate,
                  mp.paymentStatus, mp.paymentMethod, mp.suID, mp.feeID
           FROM Member_Payments mp
           JOIN Fees f ON mp.feeID = f.feeID
           WHERE f.clubName = ?
           ORDER BY mp.paymentDate DESC";
$stmt = mysqli_prepare($connection, $paySql);
mysqli_stmt_bind_param($stmt, "s", $myClub);
mysqli_stmt_execute($stmt);
$paysResult = mysqli_stmt_get_result($stmt);
$payments = array();
while ($row = mysqli_fetch_assoc($paysResult)) { $payments[] = $row; }
mysqli_stmt_close($stmt);
?>

<div class="card">
    <div class="card-title">Finances — <?php echo htmlspecialchars($myClub); ?></div>
    <p class="meta">Managing fees and payments for your club only.</p>
</div>

<?php if ($message): ?>
    <div class="banner <?php echo $msg_type; ?>"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<!-- ADD FEE FORM -->
<div class="card">
    <div class="card-title">Add a Fee</div>
    <form method="post" action="SUClubsApp.php?page=finances">
        <input type="hidden" name="action" value="add_fee">
        <div class="form-row">
            <div class="form-group"><label>Cost ($) *</label>
                <input type="number" name="cost" step="0.01" min="0" required></div>
            <div class="form-group"><label>Due Date</label>
                <input type="date" name="due"></div>
            <div class="form-group"><label>Type *</label>
                <select name="type" required>
                    <option value="">--</option>
                    <option value="membership">Membership</option>
                    <option value="event">Event</option>
                </select></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label>Period</label>
                <input type="text" name="period" placeholder="e.g. Fall 2026"></div>
            <div class="form-group" style="flex:0 0 auto;"><label>Active</label>
                <input type="checkbox" name="active" checked
                       style="width:auto; margin-top:0.4rem;"></div>
            <div class="form-group" style="flex:0 0 auto;"><label>&nbsp;</label>
                <button type="submit" class="btn btn-gold">+ Add Fee</button></div>
        </div>
    </form>
</div>

<!-- FEES LIST -->
<div class="card">
    <div class="card-title">Your Fees (<?php echo count($fees); ?>)</div>
    <?php if (count($fees) > 0): ?>
    <table>
        <thead><tr>
            <th>Fee ID</th><th>Cost</th><th>Due</th><th>Type</th>
            <th>Period</th><th>Active</th><th></th>
        </tr></thead>
        <tbody>
        <?php foreach ($fees as $f): ?>
        <tr>
            <td class="name-cell"><?php echo htmlspecialchars($f["feeID"]); ?></td>
            <td>$<?php echo number_format($f["cost"], 2); ?></td>
            <td class="meta"><?php echo ($f["dueDate"]) ? date("M j, Y", strtotime($f["dueDate"])) : "--"; ?></td>
            <td><span class="type-tag"><?php echo htmlspecialchars($f["type"]); ?></span></td>
            <td class="meta"><?php echo htmlspecialchars($f["effectivePeriod"] ?? "--"); ?></td>
            <td class="meta"><?php echo ($f["isActive"] == 1) ? "Yes" : "No"; ?></td>
            <td>
                <form method="post" action="SUClubsApp.php?page=finances" style="display:inline;"
                      onsubmit="return confirm('Delete fee <?php echo htmlspecialchars($f["feeID"]); ?>?');">
                    <input type="hidden" name="action" value="delete_fee">
                    <input type="hidden" name="feeID" value="<?php echo htmlspecialchars($f["feeID"]); ?>">
                    <button type="submit" class="btn btn-outline" style="color:var(--danger); border-color:#fecaca;">Delete</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
        <p class="meta">No fees yet.</p>
    <?php endif; ?>
</div>

<!-- ADD PAYMENT FORM -->
<div class="card">
    <div class="card-title">Record a Payment</div>
    <form method="post" action="SUClubsApp.php?page=finances">
        <input type="hidden" name="action" value="add_payment">
        <div class="form-row">
            <div class="form-group"><label>Student ID *</label>
                <input type="text" name="suID" required></div>
            <div class="form-group"><label>Fee *</label>
                <select name="feeID" required>
                    <option value="">--</option>
                    <?php foreach ($fees as $f): ?>
                        <option value="<?php echo htmlspecialchars($f["feeID"]); ?>">
                            #<?php echo htmlspecialchars($f["feeID"]); ?> — $<?php echo number_format($f["cost"], 2); ?> (<?php echo htmlspecialchars($f["type"]); ?>)
                        </option>
                    <?php endforeach; ?>
                </select></div>
            <div class="form-group"><label>Amount ($) *</label>
                <input type="number" name="amount" step="0.01" min="0" required></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label>Date</label>
                <input type="date" name="pdate"></div>
            <div class="form-group"><label>Status</label>
                <select name="status">
                    <option value="paid">Paid</option>
                    <option value="due">Due</option>
                    <option value="late">Late</option>
                    <option value="waived">Waived</option>
                </select></div>
            <div class="form-group"><label>Method</label>
                <select name="method">
                    <option value="">--</option>
                    <option value="Cash">Cash</option>
                    <option value="Card">Card</option>
                    <option value="Venmo">Venmo</option>
                    <option value="Zelle">Zelle</option>
                    <option value="Other">Other</option>
                </select></div>
            <div class="form-group" style="flex:0 0 auto;"><label>&nbsp;</label>
                <button type="submit" class="btn btn-gold">+ Record</button></div>
        </div>
    </form>
</div>

<!-- PAYMENTS LIST -->
<div class="card">
    <div class="card-title">Your Payments (<?php echo count($payments); ?>)</div>
    <?php if (count($payments) > 0): ?>
    <table>
        <thead><tr>
            <th>Pay ID</th><th>Student</th><th>Fee</th><th>Amount</th>
            <th>Date</th><th>Status</th><th>Method</th><th></th>
        </tr></thead>
        <tbody>
        <?php foreach ($payments as $p): ?>
        <tr>
            <td class="name-cell"><?php echo htmlspecialchars($p["paymentID"]); ?></td>
            <td class="meta"><?php echo htmlspecialchars($p["suID"]); ?></td>
            <td class="meta">#<?php echo htmlspecialchars($p["feeID"]); ?></td>
            <td>$<?php echo number_format($p["amountPaid"], 2); ?></td>
            <td class="meta"><?php echo ($p["paymentDate"]) ? date("M j, Y", strtotime($p["paymentDate"])) : "--"; ?></td>
            <td><span class="type-tag"><?php echo htmlspecialchars($p["paymentStatus"]); ?></span></td>
            <td class="meta"><?php echo htmlspecialchars($p["paymentMethod"] ?? "--"); ?></td>
            <td>
                <form method="post" action="SUClubsApp.php?page=finances" style="display:inline;"
                      onsubmit="return confirm('Delete payment <?php echo htmlspecialchars($p["paymentID"]); ?>?');">
                    <input type="hidden" name="action" value="delete_payment">
                    <input type="hidden" name="paymentID" value="<?php echo htmlspecialchars($p["paymentID"]); ?>">
                    <button type="submit" class="btn btn-outline" style="color:var(--danger); border-color:#fecaca;">Delete</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
        <p class="meta">No payments recorded yet.</p>
    <?php endif; ?>
</div>