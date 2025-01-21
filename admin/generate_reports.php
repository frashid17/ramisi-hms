<?php
// File: admin/generate_reports.php
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

require_once '../config/database.php';

// Fetch payments data
try {
    $stmtPayments = $pdo->query("
        SELECT p.id, p.amount, p.status, p.payment_date, u.name AS patient_name 
        FROM payments p 
        JOIN users u ON p.patient_id = u.id
    ");
    $payments = $stmtPayments->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching payments: " . $e->getMessage());
}

// Fetch appointments data
try {
    $stmtAppointments = $pdo->query("
        SELECT 
            a.id, 
            a.date, 
            a.time, 
            a.status, 
            p.name AS patient_name, 
            d.specialization, 
            u.name AS doctor_name 
        FROM appointments a
        JOIN users p ON a.patient_id = p.id
        JOIN doctors d ON a.doctor_id = d.id
        JOIN users u ON d.user_id = u.id
    ");
    $appointments = $stmtAppointments->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching appointments: " . $e->getMessage());
}

// Generate reports as CSV
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_csv'])) {
    $filename = 'reports_' . date('Ymd_His') . '.csv';
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename=' . $filename);

    $output = fopen('php://output', 'w');

    // Write payments section
    fputcsv($output, ['Payments Report']);
    fputcsv($output, ['ID', 'Patient', 'Amount', 'Status', 'Date']);
    foreach ($payments as $payment) {
        fputcsv($output, [
            $payment['id'],
            $payment['patient_name'],
            $payment['amount'],
            ucfirst($payment['status']),
            $payment['payment_date']
        ]);
    }

    // Add a blank row to separate sections
    fputcsv($output, []);

    // Write appointments section
    fputcsv($output, ['Appointments Report']);
    fputcsv($output, ['ID', 'Patient', 'Doctor', 'Specialization', 'Date', 'Time', 'Status']);
    foreach ($appointments as $appointment) {
        fputcsv($output, [
            $appointment['id'],
            $appointment['patient_name'],
            $appointment['doctor_name'],
            $appointment['specialization'],
            $appointment['date'],
            $appointment['time'],
            ucfirst($appointment['status'])
        ]);
    }

    fclose($output);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Reports - Ramisi HMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .reports-container {
            margin: 30px auto;
            max-width: 1100px;
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
            display: flex;
            align-items: center;
        }
        .section-title i {
            margin-right: 8px;
        }
        .table-container {
            overflow-x: auto;
        }
        .btn i {
            margin-right: 8px;
        }
    </style>
</head>
<body>
    <div class="container reports-container">
        <h2 class="text-center mb-4">Generate Reports</h2>

        <!-- Preview: Payments Report -->
        <div class="mb-4">
            <h3 class="section-title"><i class="fas fa-money-bill"></i> Payments Report</h3>
            <div class="table-container">
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
        </div>

        <!-- Preview: Appointments Report -->
        <div class="mb-4">
            <h3 class="section-title"><i class="fas fa-calendar-check"></i> Appointments Report</h3>
            <div class="table-container">
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
        </div>

        <!-- CSV Export Form -->
        <form method="POST" action="">
            <div class="text-center mb-4">
                <button type="submit" name="generate_csv" class="btn btn-success">
                    <i class="fas fa-download"></i> Download All Reports as CSV
                </button>
            </div>
        </form>

        <div class="text-center">
            <a href="dashboard.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
</body>
</html>
