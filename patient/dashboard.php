<?php
// File: patient/dashboard.php
session_start();

// Check if user is logged in and is a patient
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header('Location: login.php');
    exit();
}

require_once '../config/database.php';

// Fetch patient details
try {
    $stmt = $pdo->prepare("SELECT * FROM patients WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);

    // Fetch payments
    $stmtPayments = $pdo->prepare("SELECT * FROM payments WHERE patient_id = ?");
    $stmtPayments->execute([$_SESSION['user_id']]);
    $payments = $stmtPayments->fetchAll(PDO::FETCH_ASSOC);

    // Fetch medical records
    $stmtRecords = $pdo->prepare("SELECT * FROM medical_records WHERE patient_id = ?");
    $stmtRecords->execute([$_SESSION['user_id']]);
    $medicalRecords = $stmtRecords->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching data: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Dashboard - Ramisi HMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f4f6f9;
            font-family: 'Arial', sans-serif;
        }
        .dashboard-container {
            margin: 30px auto;
            max-width: 1000px;
            padding: 20px;
            background: #ffffff;
            border-radius: 15px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
        }
        .navbar {
            background-color: #007bff;
            padding: 1rem;
        }
        .navbar-brand {
            color: #fff;
            font-size: 1.5rem;
            font-weight: bold;
        }
        .section-title {
            font-size: 1.5rem;
            font-weight: bold;
            color: #343a40;
            margin-bottom: 20px;
        }
        .btn-custom {
            background: linear-gradient(145deg, #007bff, #0056b3);
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 10px;
            transition: background 0.3s ease;
        }
        .btn-custom:hover {
            background: linear-gradient(145deg, #0056b3, #003d80);
        }
        .table-container {
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <a class="navbar-brand" href="#">Ramisi HMS</a>
    </nav>
    <div class="container dashboard-container">
        <h2 class="text-center mb-4">Patient Dashboard</h2>
        <p class="text-center">Welcome, <strong><?= htmlspecialchars($patient['name'] ?? 'Patient') ?></strong>!</p>

        <!-- Payments Section -->
        <div class="mb-4">
            <h3 class="section-title">Payments</h3>
            <div class="table-container">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Payment ID</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $payment): ?>
                            <tr>
                                <td><?= htmlspecialchars($payment['id']) ?></td>
                                <td><?= htmlspecialchars($payment['amount']) ?></td>
                                <td><?= htmlspecialchars(ucfirst($payment['status'])) ?></td>
                                <td><?= htmlspecialchars($payment['payment_date']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Medical Records Section -->
        <div class="mb-4">
            <h3 class="section-title">Medical Records</h3>
            <div class="table-container">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Record ID</th>
                            <th>Diagnosis</th>
                            <th>Prescription</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($medicalRecords as $record): ?>
                            <tr>
                                <td><?= htmlspecialchars($record['id']) ?></td>
                                <td><?= htmlspecialchars($record['diagnosis']) ?></td>
                                <td><?= htmlspecialchars($record['prescription']) ?></td>
                                <td><?= htmlspecialchars($record['created_at']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Actions Section -->
        <div class="text-center">
            <a href="appointments.php" class="btn btn-custom mb-2">Register for Appointments</a>
            <a href="change_password.php" class="btn btn-warning mb-2">Change Password</a>
            <a href="logout.php" class="btn btn-danger mb-2">Logout</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
