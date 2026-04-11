<?php
// src/views/signup.php
// Creates public-role accounts only. Promotion is manual via DB UPDATE.

if (is_logged_in()) {
    header("Location: SUClubsApp.php?page=dashboard");
    exit;
}

$error = null;
$success = null;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = isset($_POST["username"]) ? trim($_POST["username"]) : "";
    $password = isset($_POST["password"]) ? $_POST["password"] : "";
    $confirm  = isset($_POST["confirm"])  ? $_POST["confirm"]  : "";

    if ($username === "" || $password === "") {
        $error = "Username and password are required.";
    } else if ($password !== $confirm) {
        $error = "Passwords do not match.";
    } else {
        $result = attempt_signup($connection, $username, $password);
        if ($result["ok"]) {
            // Don't auto-login — make them log in explicitly.
            // Simpler mental model and one less code path to test.
            $success = "Account created! You can now sign in.";
            $username = ""; // clear the form on success
        } else {
            $error = $result["error"];
        }
    }
}
?>

<div class="card" style="max-width: 420px; margin: 2rem auto;">
    <div class="card-title">Create Account</div>

    <?php if ($error): ?>
        <div class="banner error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="banner success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <p class="meta" style="margin-bottom: 0.8rem;">
        New accounts start with <strong>public</strong> access (browse clubs &amp; events).
        Contact SGA to be promoted to an e-board or admin role.
    </p>

    <form method="post" action="SUClubsApp.php?page=signup">
        <div class="form-row">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" required minlength="3" maxlength="50"
                       value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Password (min 8 chars)</label>
                <input type="password" name="password" required minlength="8">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Confirm Password</label>
                <input type="password" name="confirm" required minlength="8">
            </div>
        </div>
        <div class="form-row">
            <button type="submit" class="btn btn-gold">Create Account</button>
            <a href="SUClubsApp.php?page=login" class="btn btn-outline">Already have one?</a>
        </div>
    </form>
</div>