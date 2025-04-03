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
            background-image: url('https://static.vecteezy.com/system/resources/previews/040/835/804/non_2x/ai-generated-interior-of-a-hospital-corridor-with-green-walls-and-blue-floor-photo.jpg'); 
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            color: #fff;
            font-family: 'Arial', sans-serif;
            height: 100vh;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
        }
        .overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            z-index: 1;
        }
        .content {
            position: relative;
            z-index: 2;
            margin: auto;
            text-align: center;
            width: 100%;
            max-width: 900px;
            padding: 20px;
        }
        .header-section {
            margin-bottom: 30px;
        }
        .header-section h1 {
            font-size: 3rem;
            font-weight: bold;
            color: #fff;
        }
        .header-section p {
            font-size: 1.2rem;
            color: #f8f9fa;
            margin-bottom: 20px;
        }
        .user-options {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 15px;
        }
        .user-option {
            display: flex;
            align-items: center;
            justify-content: center;
            background:rgb(10, 75, 252);
            color: #fff;
            padding: 15px;
            border-radius: 10px;
            text-decoration: none;
            width: 220px;
            height: 60px;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        .user-option:hover {
            background:rgb(255, 7, 7);
            transform: scale(1.05);
        }
        .user-option i {
            font-size: 1.5rem;
            margin-right: 10px;
        }
        .description-section {
            margin-top: 40px;
            padding: 20px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            text-align: left;
        }
        .description-section h2 {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 20px;
            color: #fff;
        }
        .description-section p {
            font-size: 1rem;
            line-height: 1.5;
            color: #dcdcdc;
        }
        .btn-register {
            background:rgb(0, 0, 0);
            color: #fff;
            padding: 10px 20px;
            border-radius: 10px;
            text-decoration: none;
            font-size: 1rem;
            transition: background 0.3s ease;
            display: inline-block;
            margin-top: 15px;
        }
        .btn-register:hover {
            background: #218838;
        }
    </style>
</head>
<body>
    <div class="overlay"></div>
    <div class="content">
        <!-- Header Section -->
        <div class="header-section">
            <h1>Welcome to Ramisi Hospital</h1>
            <p>Your trusted partner in healthcare. Choose your role to proceed:</p>
        </div>

        <!-- User Options -->
        <div class="user-options">
            <a href="patient/login.php" class="user-option">
                <i class="fas fa-user"></i> Patient Login
            </a>
            <a href="doctor/login.php" class="user-option">
                <i class="fas fa-user-md"></i> Doctor Login
            </a>
            <a href="nurse/login.php" class="user-option">
                <i class="fas fa-user-nurse"></i> Nurse Login
            </a>
            <a href="receptionist/login.php" class="user-option">
                <i class="fas fa-concierge-bell"></i> Receptionist Login
            </a>
            <a href="admin/login.php" class="user-option">
                <i class="fas fa-user-shield"></i> Admin Login
            </a>
        </div>

        <!-- Description Section -->
        <div class="description-section">
            <h2>About Us</h2>
            <p><strong>Mission:</strong> To provide exceptional, compassionate healthcare services that improve the quality of life for our community.</p>
            <p><strong>Vision:</strong> To be the leading healthcare provider in the region, recognized for excellence in patient care, cutting-edge medical practices, and innovation.</p>
            <p>At Ramisi HMS, we are committed to your health and well-being. Whether you are a patient seeking care, a medical professional delivering expertise, or a staff member providing support, we ensure a seamless and caring experience for everyone.</p>
            <a href="patient/register.php" class="btn-register">Register as a New Patient</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
