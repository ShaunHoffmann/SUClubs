<?php
// src/views/login.php
// GET: render the login form.
// POST: call attempt_login(); on success redirect to dashboard.

// Already logged in? Bounce to dashboard — no reason to show login form.
if (is_logged_in()) {
    header("Location: SUClubsApp.php?page=dashboard");
    exit;
}

$error = null;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = isset($_POST["username"]) ? trim($_POST["username"]) : "";
    $password = isset($_POST["password"]) ? $_POST["password"] : "";
    // Note: password is NOT trimmed. Trailing spaces are legal in a password.

    if ($username === "" || $password === "") {
        $error = "Username and password are required.";
    } else if (attempt_login($connection, $username, $password)) {
        // Success. POST-Redirect-GET so refresh doesn't resubmit.
        header("Location: SUClubsApp.php?page=dashboard");
        exit;
    } else {
        // Generic message — don't reveal which part was wrong.
        $error = "Invalid username or password.";
    }
}
?>

<div class="card" style="max-width: 420px; margin: 2rem auto;">
    <div class="card-title">Sign In</div>

    <?php if ($error): ?>
        <div class="banner error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="post" action="SUClubsApp.php?page=login">
        <div class="form-row">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" required autofocus
                       value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
        </div>
        <div class="form-row">
            <button type="submit" class="btn btn-primary">Sign In</button>
            <a href="SUClubsApp.php?page=signup" class="btn btn-outline">Need an account?</a>
        </div>
    </form>
</div>