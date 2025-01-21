<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome - Ramisi HMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-image: url('assets/images/home-bg.jpg');
            background-size: cover;
            background-position: center;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .welcome-container {
            background: rgba(255, 255, 255, 0.9);
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            max-width: 600px;
            width: 100%;
            text-align: center;
        }
        .btn-user {
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="welcome-container">
        <h2 class="mb-4">Welcome to Ramisi HMS</h2>
        <p>Please choose your user type to log in:</p>
        <a href="patient/login.php" class="btn btn-primary btn-user w-100">Patient Login</a>
        <a href="doctor/login.php" class="btn btn-secondary btn-user w-100">Doctor Login</a>
        <a href="nurse/login.php" class="btn btn-success btn-user w-100">Nurse Login</a>
        <a href="receptionist/login.php" class="btn btn-info btn-user w-100">Receptionist Login</a>
        <a href="admin/login.php" class="btn btn-warning btn-user w-100">Admin Login</a>
        <p class="mt-3">New patient? <a href="patient/register.php">Register here</a></p>
    </div>
</body>
</html>
