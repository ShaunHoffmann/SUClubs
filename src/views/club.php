<?php
// src/views/club.php
// Detail page for one club. URL: ?page=club&name=Chess+Club
// Public view — no login required.
// Renders: club info, officers (fname/lname/email/ranking),
//          members (fname/lname/class/dateJoined), events (all, date DESC).

// ─────────────────────────────────────────────────────────────
// READ & VALIDATE INPUT
// ─────────────────────────────────────────────────────────────
$clubName = isset($_GET["name"]) ? trim($_GET["name"]) : "";

if ($clubName === "") {
    echo '<div class="card"><div class="banner error">No club specified.</div>';
    echo '<a href="SUClubsApp.php?page=dashboard" class="btn btn-outline">Back to Dashboard</a></div>';
    return; // return from the included file — router moves to footer
}

// ─────────────────────────────────────────────────────────────
// FETCH THE CLUB ITSELF
// Prepared statement because $clubName came from the URL.
// ─────────────────────────────────────────────────────────────
$sql = "SELECT name, description, link, foundationDate, phone, email, type
        FROM Club WHERE name = ?";
$stmt = mysqli_prepare($connection, $sql);
mysqli_stmt_bind_param($stmt, "s", $clubName);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$club = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$club) {
    echo '<div class="card"><div class="banner error">Club not found: '
         . htmlspecialchars($clubName) . '</div>';
    echo '<a href="SUClubsApp.php?page=dashboard" class="btn btn-outline">Back to Dashboard</a></div>';
    return;
}

// ─────────────────────────────────────────────────────────────
// FETCH OFFICERS
// JOIN Members on suID so we get fname/lname/email, not just the ID.
// ─────────────────────────────────────────────────────────────
$sql = "SELECT m.fname, m.lname, m.email, o.ranking
        FROM Officers o
        JOIN Members m ON o.suID = m.suID
        WHERE o.clubName = ?
        ORDER BY
          CASE o.ranking
            WHEN 'President'      THEN 1
            WHEN 'Vice President' THEN 2
            WHEN 'Treasurer'      THEN 3
            WHEN 'Secretary'      THEN 4
            ELSE 5
          END, m.lname ASC";
$stmt = mysqli_prepare($connection, $sql);
mysqli_stmt_bind_param($stmt, "s", $clubName);
mysqli_stmt_execute($stmt);
$officers = mysqli_stmt_get_result($stmt);
$officerCount = mysqli_num_rows($officers);

// NOTE: intentionally not closing $stmt yet — we'll reuse the variable
// after we've consumed the results. Actually, safer to close now and
// make a fresh one. Doing that.

// ─────────────────────────────────────────────────────────────
// FETCH MEMBERS
// JOIN Members on suID via Membership. No email — privacy per Option B.
// ─────────────────────────────────────────────────────────────
$sql = "SELECT m.fname, m.lname, m.class, ms.dateJoined
        FROM Membership ms
        JOIN Members m ON ms.suID = m.suID
        WHERE ms.clubName = ?
        ORDER BY m.lname ASC, m.fname ASC";
$stmt2 = mysqli_prepare($connection, $sql);
mysqli_stmt_bind_param($stmt2, "s", $clubName);
mysqli_stmt_execute($stmt2);
$members = mysqli_stmt_get_result($stmt2);
$memberCount = mysqli_num_rows($members);

// ─────────────────────────────────────────────────────────────
// FETCH EVENTS
// ─────────────────────────────────────────────────────────────
$sql = "SELECT name, date, startTime, endTime, type, attendance, description
        FROM Events
        WHERE clubName = ?
        ORDER BY date DESC";
$stmt3 = mysqli_prepare($connection, $sql);
mysqli_stmt_bind_param($stmt3, "s", $clubName);
mysqli_stmt_execute($stmt3);
$events = mysqli_stmt_get_result($stmt3);
$eventCount = mysqli_num_rows($events);
?>

<!-- ───────────────────────────────────────────────────────── -->
<!-- BACK LINK -->
<!-- ───────────────────────────────────────────────────────── -->
<div style="margin-bottom: 1rem;">
    <a href="SUClubsApp.php?page=dashboard" class="btn btn-outline">&larr; Back to Dashboard</a>
</div>

<!-- ───────────────────────────────────────────────────────── -->
<!-- CLUB HEADER CARD -->
<!-- ───────────────────────────────────────────────────────── -->
<div class="card">
    <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:0.5rem;">
        <div>
            <div class="card-title" style="margin-bottom:0.3rem;">
                <?php echo htmlspecialchars($club["name"]); ?>
            </div>
            <?php if ($club["type"] !== null && $club["type"] !== ""): ?>
                <span class="type-tag"><?php echo htmlspecialchars($club["type"]); ?></span>
            <?php endif; ?>
        </div>
        <div class="meta" style="text-align:right;">
            <?php if ($club["foundationDate"] !== null && $club["foundationDate"] !== ""): ?>
                Founded <?php echo date("F Y", strtotime($club["foundationDate"])); ?><br>
            <?php endif; ?>
            <?php if ($club["email"] !== null && $club["email"] !== ""): ?>
                <?php echo htmlspecialchars($club["email"]); ?><br>
            <?php endif; ?>
            <?php if ($club["phone"] !== null && $club["phone"] !== ""): ?>
                <?php echo htmlspecialchars($club["phone"]); ?>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($club["description"] !== null && $club["description"] !== ""): ?>
        <p style="margin-top: 0.8rem;"><?php echo htmlspecialchars($club["description"]); ?></p>
    <?php endif; ?>

    <?php if ($club["link"] !== null && $club["link"] !== ""): ?>
        <p style="margin-top: 0.5rem;" class="meta">
            Website:
            <a href="<?php echo htmlspecialchars($club["link"]); ?>"
               target="_blank" rel="noopener"
               style="color: var(--maroon);">
                <?php echo htmlspecialchars($club["link"]); ?>
            </a>
        </p>
    <?php endif; ?>
</div>

<!-- ───────────────────────────────────────────────────────── -->
<!-- OFFICERS -->
<!-- ───────────────────────────────────────────────────────── -->
<div class="card">
    <div class="card-title">Officers (<?php echo $officerCount; ?>)</div>
    <?php if ($officerCount > 0): ?>
        <table>
            <thead>
                <tr><th>Name</th><th>Ranking</th><th>Email</th></tr>
            </thead>
            <tbody>
            <?php while ($o = mysqli_fetch_assoc($officers)): ?>
                <tr>
                    <td class="name-cell">
                        <?php echo htmlspecialchars($o["fname"] . " " . $o["lname"]); ?>
                    </td>
                    <td>
                        <?php if ($o["ranking"] !== null && $o["ranking"] !== ""): ?>
                            <span class="type-tag"><?php echo htmlspecialchars($o["ranking"]); ?></span>
                        <?php else: ?>
                            <span class="meta">--</span>
                        <?php endif; ?>
                    </td>
                    <td class="meta">
                        <?php echo ($o["email"] !== null && $o["email"] !== "")
                            ? htmlspecialchars($o["email"]) : "--"; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p class="meta">No officers listed.</p>
    <?php endif; ?>
</div>

<!-- ───────────────────────────────────────────────────────── -->
<!-- MEMBERS -->
<!-- ───────────────────────────────────────────────────────── -->
<div class="card">
    <div class="card-title">Members (<?php echo $memberCount; ?>)</div>
    <?php if ($memberCount > 0): ?>
        <table>
            <thead>
                <tr><th>Name</th><th>Class</th><th>Joined</th></tr>
            </thead>
            <tbody>
            <?php while ($m = mysqli_fetch_assoc($members)): ?>
                <tr>
                    <td class="name-cell">
                        <?php echo htmlspecialchars($m["fname"] . " " . $m["lname"]); ?>
                    </td>
                    <td>
                        <?php if ($m["class"] !== null && $m["class"] !== ""): ?>
                            <span class="type-tag"><?php echo htmlspecialchars($m["class"]); ?></span>
                        <?php else: ?>
                            <span class="meta">--</span>
                        <?php endif; ?>
                    </td>
                    <td class="meta">
                        <?php echo ($m["dateJoined"] !== null && $m["dateJoined"] !== "")
                            ? date("M j, Y", strtotime($m["dateJoined"])) : "--"; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p class="meta">No members listed.</p>
    <?php endif; ?>
</div>

<!-- ───────────────────────────────────────────────────────── -->
<!-- EVENTS -->
<!-- ───────────────────────────────────────────────────────── -->
<div class="card">
    <div class="card-title">Events (<?php echo $eventCount; ?>)</div>
    <?php if ($eventCount > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Event</th><th>Date</th><th>Time</th><th>Type</th><th>Attendance</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($e = mysqli_fetch_assoc($events)): ?>
                <tr>
                    <td class="name-cell"><?php echo htmlspecialchars($e["name"]); ?></td>
                    <td class="meta">
                        <?php echo ($e["date"] !== null && $e["date"] !== "")
                            ? date("M j, Y", strtotime($e["date"])) : "--"; ?>
                    </td>
                    <td class="meta">
                        <?php
                        $st = ($e["startTime"] !== null && $e["startTime"] !== "")
                              ? date("g:iA", strtotime($e["startTime"])) : "";
                        $et = ($e["endTime"] !== null && $e["endTime"] !== "")
                              ? date("g:iA", strtotime($e["endTime"])) : "";
                        if ($st !== "") {
                            echo $st . ($et !== "" ? " - " . $et : "");
                        } else {
                            echo "--";
                        }
                        ?>
                    </td>
                    <td>
                        <?php if ($e["type"] !== null && $e["type"] !== ""): ?>
                            <span class="type-tag"><?php echo htmlspecialchars($e["type"]); ?></span>
                        <?php else: ?>
                            <span class="meta">--</span>
                        <?php endif; ?>
                    </td>
                    <td class="meta">
                        <?php echo ($e["attendance"] !== null && $e["attendance"] !== "")
                            ? $e["attendance"] : "--"; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p class="meta">No events posted.</p>
    <?php endif; ?>
</div>

<?php
mysqli_stmt_close($stmt);  // officers
mysqli_stmt_close($stmt2); // members
mysqli_stmt_close($stmt3); // events
?>