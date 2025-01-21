<?php
// File: admin/dashboard.php
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

require_once '../config/database.php';

// Fetch payments sent to patients
try {
    $stmtPayments = $pdo->query("SELECT p.id, p.amount, p.status, p.payment_date, u.name AS patient_name FROM payments p JOIN users u ON p.patient_id = u.id");
    $payments = $stmtPayments->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching payments: " . $e->getMessage());
}

// Fetch all appointments
try {
    $stmtAppointments = $pdo->query("SELECT a.id, a.date, a.time, a.status, p.name AS patient_name, d.specialization, u.name AS doctor_name 
        FROM appointments a
        JOIN users p ON a.patient_id = p.id
        JOIN doctors d ON a.doctor_id = d.id
        JOIN users u ON d.user_id = u.id
        ORDER BY a.date DESC");
    $appointments = $stmtAppointments->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching appointments: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Ramisi HMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .dashboard-container {
            margin: 30px auto;
            max-width: 1200px;
            padding: 20px;
            background: #ffffff;
            border-radius: 15px;
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }
        .section-title {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 20px;
            color: #343a40;
        }
        .btn {
            display: flex;
            align-items: center;
        }
        .btn i {
            margin-right: 8px;
        }
    </style>
</head>
<body>
    <div class="container dashboard-container">
        <h2 class="text-center mb-4">Admin Dashboard</h2>

        <!-- Payments Section -->
        <div class="mb-4">
            <h3 class="section-title"><i class="fas fa-money-bill"></i> Payments Sent to Patients</h3>
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Patient</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payments as $payment): ?>
                        <tr>
                            <td><?= htmlspecialchars($payment['id']) ?></td>
                            <td><?= htmlspecialchars($payment['patient_name']) ?></td>
                            <td><?= htmlspecialchars($payment['amount']) ?></td>
                            <td><?= htmlspecialchars(ucfirst($payment['status'])) ?></td>
                            <td><?= htmlspecialchars($payment['payment_date']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Appointments Section -->
        <div class="mb-4">
            <h3 class="section-title"><i class="fas fa-calendar-check"></i> Appointments</h3>
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Patient</th>
                        <th>Doctor</th>
                        <th>Specialization</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($appointments as $appointment): ?>
                        <tr>
                            <td><?= htmlspecialchars($appointment['id']) ?></td>
                            <td><?= htmlspecialchars($appointment['patient_name']) ?></td>
                            <td><?= htmlspecialchars($appointment['doctor_name']) ?></td>
                            <td><?= htmlspecialchars($appointment['specialization']) ?></td>
                            <td><?= htmlspecialchars($appointment['date']) ?></td>
                            <td><?= htmlspecialchars($appointment['time']) ?></td>
                            <td><?= htmlspecialchars(ucfirst($appointment['status'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Manage Staff Section -->
        <div class="mb-4">
            <h3 class="section-title"><i class="fas fa-users-cog"></i> Manage Staff</h3>
            <a href="manage_staff.php" class="btn btn-primary"><i class="fas fa-user-plus"></i> Add/Manage Staff</a>
        </div>

        <!-- Manage Doctor Schedule Section -->
        <div class="mb-4">
            <h3 class="section-title"><i class="fas fa-calendar-alt"></i> Manage Doctor Schedule</h3>
            <a href="manage_schedule.php" class="btn btn-secondary"><i class="fas fa-calendar"></i> Manage Schedule</a>
        </div>

        <!-- Generate Reports Section -->
        <div class="mb-4">
            <h3 class="section-title"><i class="fas fa-chart-line"></i> Generate Reports</h3>
            <a href="generate_reports.php" class="btn btn-danger"><i class="fas fa-file-alt"></i> Generate Reports</a>
        </div>

        <div class="text-center">
            <a href="logout.php" class="btn btn-warning"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
</body>
</html>
