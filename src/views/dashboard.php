<?php
// src/views/dashboard.php
// Public-facing club browser. No auth guard — anyone can view.
// Features: search (name/description/email/type) + type filter dropdown.

// ─────────────────────────────────────────────────────────────
// READ USER INPUT
// ─────────────────────────────────────────────────────────────
$search = isset($_GET["search"]) ? trim($_GET["search"]) : "";
$typeFilter = isset($_GET["type"]) ? trim($_GET["type"]) : "";

// ─────────────────────────────────────────────────────────────
// POPULATE TYPE FILTER DROPDOWN
// Pulled live from the DB so new club types appear automatically.
// No user input, so a plain query is fine here.
// ─────────────────────────────────────────────────────────────
$typeQuery = "SELECT DISTINCT type FROM Club
              WHERE type IS NOT NULL AND type != ''
              ORDER BY type ASC";
$typeResult = mysqli_query($connection, $typeQuery);

// ─────────────────────────────────────────────────────────────
// BUILD THE MAIN QUERY
//
// We don't know in advance which filters the user applied, so we
// build the WHERE clause piece by piece. Every piece adds:
//   (1) a "?" to the SQL template
//   (2) a type code character to $types
//   (3) a value to $params
//
// Then we bind them all at once using the ... spread operator.
// This is THE pattern for "variable number of prepared-statement params."
// ─────────────────────────────────────────────────────────────
$sql = "SELECT name, description, email, type, foundationDate FROM Club";
$conditions = array();
$types = "";
$params = array();

if ($search !== "") {
    // LIKE wildcards go in the PHP value, NOT the SQL template.
    // Template stays as "?", value becomes "%search%".
    // MySQL still treats it as a pure value — injection impossible.
    $conditions[] = "(name LIKE ? OR description LIKE ? OR email LIKE ? OR type LIKE ?)";
    $likeValue = "%" . $search . "%";
    $types .= "ssss";
    $params[] = $likeValue;
    $params[] = $likeValue;
    $params[] = $likeValue;
    $params[] = $likeValue;
}

if ($typeFilter !== "") {
    $conditions[] = "type = ?";
    $types .= "s";
    $params[] = $typeFilter;
}

if (count($conditions) > 0) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}
$sql .= " ORDER BY name ASC";

// ─────────────────────────────────────────────────────────────
// EXECUTE
// ─────────────────────────────────────────────────────────────
$stmt = mysqli_prepare($connection, $sql);

// Only bind if we actually have parameters. bind_param errors on empty.
if (count($params) > 0) {
    // The ... (spread) operator passes array elements as separate args.
    // Equivalent to bind_param($stmt, $types, $params[0], $params[1], ...)
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row_count = mysqli_num_rows($result);
?>

<!-- ───────────────────────────────────────────────────────── -->
<!-- SEARCH + FILTER FORM -->
<!-- ───────────────────────────────────────────────────────── -->
<div class="card">
    <div class="card-title">Browse Clubs</div>
    <form method="get" action="SUClubsApp.php">
        <input type="hidden" name="page" value="dashboard">
        <div class="form-row">
            <div class="form-group">
                <label>Search</label>
                <input type="text" name="search"
                       placeholder="Name, description, email, or type..."
                       value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="form-group">
                <label>Type</label>
                <select name="type">
                    <option value="">All types</option>
                    <?php while ($t = mysqli_fetch_assoc($typeResult)): ?>
                        <option value="<?php echo htmlspecialchars($t["type"]); ?>"
                            <?php if ($typeFilter === $t["type"]) echo "selected"; ?>>
                            <?php echo htmlspecialchars($t["type"]); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group" style="flex: 0 0 auto;">
                <label>&nbsp;</label>
                <button type="submit" class="btn btn-primary">Filter</button>
            </div>
            <?php if ($search !== "" || $typeFilter !== ""): ?>
                <div class="form-group" style="flex: 0 0 auto;">
                    <label>&nbsp;</label>
                    <a href="SUClubsApp.php?page=dashboard" class="btn btn-outline">Clear</a>
                </div>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- ───────────────────────────────────────────────────────── -->
<!-- RESULTS -->
<!-- ───────────────────────────────────────────────────────── -->
<div class="card">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.6rem; font-size:0.8rem; color: var(--ink-light);">
        <span>
            <?php if ($search !== "" || $typeFilter !== ""): ?>
                Filtered results
            <?php else: ?>
                <strong>All Clubs</strong>
            <?php endif; ?>
        </span>
        <span><?php echo $row_count; ?> club<?php if ($row_count != 1) echo "s"; ?></span>
    </div>

    <?php if ($row_count > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Type</th>
                    <th>Description</th>
                    <th>Contact</th>
                    <th>Founded</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                <tr style="cursor:pointer;"
                    onclick="window.location='SUClubsApp.php?page=club&name=<?php echo urlencode($row["name"]); ?>'">
                    <td class="name-cell"><?php echo htmlspecialchars($row["name"]); ?></td>
                    <td>
                        <?php if ($row["type"] !== null && $row["type"] !== ""): ?>
                            <span class="type-tag"><?php echo htmlspecialchars($row["type"]); ?></span>
                        <?php else: ?>
                            <span class="meta">--</span>
                        <?php endif; ?>
                    </td>
                    <td class="meta">
                        <?php
                        // Truncate long descriptions so the table stays readable.
                        $desc = $row["description"];
                        if ($desc === null || $desc === "") {
                            echo "--";
                        } else if (strlen($desc) > 80) {
                            echo htmlspecialchars(substr($desc, 0, 80)) . "...";
                        } else {
                            echo htmlspecialchars($desc);
                        }
                        ?>
                    </td>
                    <td class="meta">
                        <?php echo ($row["email"] !== null && $row["email"] !== "")
                            ? htmlspecialchars($row["email"]) : "--"; ?>
                    </td>
                    <td class="meta">
                        <?php echo ($row["foundationDate"] !== null && $row["foundationDate"] !== "")
                            ? date("M Y", strtotime($row["foundationDate"])) : "--"; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div style="text-align:center; padding:2rem 1rem; color: var(--ink-light);">
            <p>No clubs match your filters.</p>
        </div>
    <?php endif; ?>
</div>

<?php
mysqli_stmt_close($stmt);
?>