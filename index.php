<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome - Ramisi HMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-image: url('assets/images/home-bg.jpg');
            background-size: cover;
            background-position: center;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: 'Arial', sans-serif;
        }
        .welcome-container {
            background: rgba(255, 255, 255, 0.95);
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.2);
            max-width: 600px;
            width: 100%;
            text-align: center;
        }
        .user-option {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #f8f9fa;
            padding: 15px;
            margin: 10px 0;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            text-decoration: none;
            color: #212529;
        }
        .user-option:hover {
            background: #007bff;
            color: #fff;
            transform: scale(1.02);
        }
        .user-option i {
            font-size: 1.5rem;
            margin-right: 15px;
            color: #007bff;
        }
        .user-option:hover i {
            color: #fff;
        }
        .user-option img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
        }
        .btn-register {
            background: #007bff;
            color: #fff;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 10px;
            font-size: 1rem;
            transition: background 0.3s ease;
        }
        .btn-register:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
    <div class="welcome-container">
        <h2 class="mb-4">Welcome to Ramisi HMS</h2>
        <p class="mb-4">Please choose your user type to log in:</p>
        <a href="patient/login.php" class="user-option">
            <div class="d-flex align-items-center">
                <i class="fas fa-user"></i>
                <span>Patient Login</span>
            </div>
            
        </a>
        <a href="doctor/login.php" class="user-option">
            <div class="d-flex align-items-center">
                <i class="fas fa-user-md"></i>
                <span>Doctor Login</span>
            </div>
            
        </a>
        <a href="nurse/login.php" class="user-option">
            <div class="d-flex align-items-center">
                <i class="fas fa-user-nurse"></i>
                <span>Nurse Login</span>
            </div>
            
        </a>
        <a href="receptionist/login.php" class="user-option">
            <div class="d-flex align-items-center">
                <i class="fas fa-concierge-bell"></i>
                <span>Receptionist Login</span>
            </div>
            
        </a>
        <a href="admin/login.php" class="user-option">
            <div class="d-flex align-items-center">
                <i class="fas fa-user-shield"></i>
                <span>Admin Login</span>
            </div>
            
        </a>
        <p class="mt-4">New patient? <a href="patient/register.php" class="btn-register">Register Here</a></p>
    </div>
</body>
</html>
