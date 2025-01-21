<?php
// File: patient/change_password.php
session_start();

// Check if user is logged in and is a patient
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header('Location: login.php');
    exit();
}

require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];

    try {
        // Fetch current password from database
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($currentPassword, $user['password'])) {
            if ($newPassword === $confirmPassword) {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $updateStmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $updateStmt->execute([$hashedPassword, $_SESSION['user_id']]);
                $success = "Password changed successfully!";
            } else {
                $error = "New password and confirmation do not match.";
            }
        } else {
            $error = "Current password is incorrect.";
        }
    } catch (PDOException $e) {
        $error = "An error occurred: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - Ramisi HMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-image: url('https://static.vecteezy.com/system/resources/previews/040/835/804/non_2x/ai-generated-interior-of-a-hospital-corridor-with-green-walls-and-blue-floor-photo.jpg');
            background-color: #f8f9fa;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .password-container {
            background: #ffffff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            max-width: 400px;
            width: 100%;
        }
    </style>
</head>
<body>
    <div class="password-container">
        <h2 class="text-center mb-4">Change Password</h2>
        <?php if (isset($success)): ?>
            <div class="alert alert-success text-center"> <?= htmlspecialchars($success) ?> </div>
        <?php elseif (isset($error)): ?>
            <div class="alert alert-danger text-center"> <?= htmlspecialchars($error) ?> </div>
        <?php endif; ?>
        <form method="POST" action="">
            <div class="mb-3">
                <label for="current_password" class="form-label">Current Password</label>
                <input type="password" name="current_password" id="current_password" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="new_password" class="form-label">New Password</label>
                <input type="password" name="new_password" id="new_password" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="confirm_password" class="form-label">Confirm New Password</label>
                <input type="password" name="confirm_password" id="confirm_password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Change Password</button>
        </form>
        <div class="text-center mt-3">
            <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>
    </div>
</body>
</html>
