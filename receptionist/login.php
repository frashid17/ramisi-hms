<?php
// File: receptionist/login.php
session_start();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    try {
        // Check if the user exists in the database with the role 'receptionist'
        $stmt = $pdo->prepare("
            SELECT *
            FROM users
            WHERE email = ? AND role = 'receptionist'
        ");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            // Login successful
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = 'receptionist';
            header('Location: dashboard.php'); // Redirect to receptionist dashboard
            exit();
        } else {
            // Invalid credentials
            $error = "Invalid email or password!";
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
    <title>Receptionist Login - Ramisi HMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-image: url('https://static.vecteezy.com/system/resources/previews/040/835/804/non_2x/ai-generated-interior-of-a-hospital-corridor-with-green-walls-and-blue-floor-photo.jpg'); 
            background-size: cover;
            background-position: center;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Arial', sans-serif;
        }
        .login-container {
            background: rgba(255, 255, 255, 0.9);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.2);
            max-width: 400px;
            width: 100%;
        }
        .login-header {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
        }
        .login-header i {
            font-size: 2rem;
            margin-right: 10px;
            color: #0d6efd;
        }
        .btn-home {
            margin-top: 15px;
            background-color: #6c757d;
            color: white;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <i class="fas fa-concierge-bell"></i>
            <h2>Receptionist Login</h2>
        </div>
        <?php if (isset($error)): ?>
            <div class="alert alert-danger text-center"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST" action="">
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input 
                    type="email" 
                    name="email" 
                    id="email" 
                    class="form-control" 
                    placeholder="Enter your email" 
                    required
                >
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input 
                    type="password" 
                    name="password" 
                    id="password" 
                    class="form-control" 
                    placeholder="Enter your password" 
                    required
                >
            </div>
            <button type="submit" class="btn btn-primary w-100">Login</button>
        </form>
        <a href="../index.php" class="btn btn-home w-100 text-center mt-3">Back to Main Page</a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
</body>
</html>
