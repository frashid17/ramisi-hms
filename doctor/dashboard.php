<?php
// File: doctor/dashboard.php
session_start();

// Check if user is logged in and has the 'doctor' role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header('Location: login.php');
    exit();
}

require_once '../config/database.php';

// 1. Fetch the current doctor's ID from session (users table)
$doctorUserId = $_SESSION['user_id'];

// 2. Fetch the doctor record from the 'doctors' table to get the doctor’s ID
try {
    $stmtDoctor = $pdo->prepare("
        SELECT id 
        FROM doctors 
        WHERE user_id = ?
    ");
    $stmtDoctor->execute([$doctorUserId]);
    $doctorRecord = $stmtDoctor->fetch(PDO::FETCH_ASSOC);

    if (!$doctorRecord) {
        die('Error: Doctor record not found.');
    }
    $doctorId = $doctorRecord['id'];
} catch (PDOException $e) {
    die('Error fetching doctor info: ' . $e->getMessage());
}

// 3. Fetch Patients (optional)
try {
    $stmt = $pdo->query("
        SELECT p.id AS patient_id, p.user_id, u.name AS patient_name, p.dob, p.address, p.phone
        FROM patients p
        JOIN users u ON p.user_id = u.id
        ORDER BY u.name ASC
    ");
    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die('Error fetching patients: ' . $e->getMessage());
}

// 4. Fetch appointments for this doctor
try {
    $stmtAppointments = $pdo->prepare("
        SELECT 
            a.id AS appointment_id,
            a.date,
            a.time,
            a.status,
            u.name AS patient_name
        FROM appointments a
        JOIN users u ON a.patient_id = u.id
        WHERE a.doctor_id = ?
        ORDER BY a.date ASC
    ");
    $stmtAppointments->execute([$doctorId]);
    $appointments = $stmtAppointments->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die('Error fetching appointments: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard - Ramisi HMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-image: url('https://static.vecteezy.com/system/resources/previews/040/835/804/non_2x/ai-generated-interior-of-a-hospital-corridor-with-green-walls-and-blue-floor-photo.jpg');
            background-color: #f8f9fa;
        }
        .dashboard-container {
            margin: 30px auto;
            max-width: 1000px;
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
            margin-right: 10px;
        }
        .btn i {
            margin-right: 8px;
        }
    </style>
</head>
<body>
    <div class="container dashboard-container">
        <h2 class="text-center mb-4">Doctor Dashboard</h2>

        <!-- Welcome Message -->
        <p class="text-center">Welcome, Doctor!</p>

        <!-- Actions Section -->
        <div class="mb-4">
            <h3 class="section-title"><i class="fas fa-stethoscope"></i> Actions</h3>
            <div class="d-grid gap-2 d-sm-flex">
                <a href="diagnose_prescribe.php" class="btn btn-primary">
                    <i class="fas fa-notes-medical"></i> Diagnose & Prescribe
                </a>
                <a href="update_medical_records.php" class="btn btn-success">
                    <i class="fas fa-file-medical-alt"></i> Update Medical Records
                </a>
                <a href="view_patient_details.php" class="btn btn-info">
                    <i class="fas fa-user-injured"></i> View Patient Details
                </a>
            </div>
        </div>

        <!-- My Appointments Section -->
        <div class="mb-4">
            <h3 class="section-title"><i class="fas fa-calendar-check"></i> My Appointments</h3>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Appointment ID</th>
                            <th>Patient Name</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
<?php if (!empty($appointments)): ?>
    <?php foreach ($appointments as $appt): ?>
        <tr>
            <td><?= htmlspecialchars($appt['appointment_id']) ?></td>
            <td><?= htmlspecialchars($appt['patient_name']) ?></td>
            <td><?= htmlspecialchars($appt['date']) ?></td>
            <td><?= htmlspecialchars($appt['time']) ?></td>
            <td><?= ucfirst($appt['status']) ?></td>
            <td>
            <?php if ($appt['status'] === 'scheduled'): ?>

                    <form method="post" action="handle_appointment_action.php" style="display:inline;">
                        <input type="hidden" name="appointment_id" value="<?= $appt['appointment_id'] ?>">
                        <input type="hidden" name="action" value="accept">
                        <button class="btn btn-success btn-sm" type="submit">Accept</button>
                    </form>

                    <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#cancelModal<?= $appt['appointment_id'] ?>">Cancel</button>

<!-- Cancel Modal -->
<div class="modal fade" id="cancelModal<?= $appt['appointment_id'] ?>" tabindex="-1" aria-labelledby="cancelModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" action="handle_appointment_action.php">
        <input type="hidden" name="appointment_id" value="<?= $appt['appointment_id'] ?>">
        <input type="hidden" name="action" value="cancel">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Cancel Appointment</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <p>Are you sure you want to cancel this appointment?</p>
          </div>
          <div class="modal-footer">
            <button type="submit" class="btn btn-danger">Confirm Cancellation</button>
          </div>
        </div>
    </form>
  </div>
</div>

                <?php else: ?>
                    <span class="text-muted">—</span>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
<?php else: ?>
    <tr><td colspan="6">No appointments found.</td></tr>
<?php endif; ?>
</tbody>

                </table>
            </div>
        </div>

        <!-- Logout Section -->
        <div class="text-center">
            <a href="logout.php" class="btn btn-warning">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>

    <script src=\"https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js\"></script>
    <script src=\"https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js\"></script>
</body>
</html>
