<?php
// File: receptionist/dashboard.php
session_start();

// Check if user is logged in and has the 'receptionist' role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'receptionist') {
    header('Location: login.php');
    exit();
}

require_once '../config/database.php';

$success = '';
$error   = '';

// 1. Fetch Registered Patients
try {
    $stmtPatients = $pdo->query("
        SELECT p.id AS patient_id, u.name AS patient_name
        FROM patients p
        JOIN users u ON p.user_id = u.id
        ORDER BY u.name ASC
    ");
    $registeredPatients = $stmtPatients->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die('Error fetching registered patients: ' . $e->getMessage());
}

// 2. Fetch Guest Patients
try {
    $stmtGuests = $pdo->query("
        SELECT id AS guest_id, name AS guest_name
        FROM guest_patients
        ORDER BY name ASC
    ");
    $guestPatients = $stmtGuests->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die('Error fetching guest patients: ' . $e->getMessage());
}

// 3. Handle receptionist actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 3A. Schedule an Appointment
    if ($action === 'schedule_appointment') {
        $patientType     = $_POST['patient_type']      ?? '';
        $appointmentDate = $_POST['appointment_date']  ?? '';
        $appointmentTime = $_POST['appointment_time']  ?? '';

        if ($patientType === 'registered') {
            $patientId = $_POST['registered_patient_id'] ?? null;
            if (!$patientId) {
                $error = "No registered patient selected!";
            } else {
                try {
                    $stmtInsert = $pdo->prepare("
                        INSERT INTO appointments (patient_id, doctor_id, date, time, status)
                        VALUES (?, NULL, ?, ?, 'scheduled')
                    ");
                    // doctor_id can be set later
                    $stmtInsert->execute([$patientId, $appointmentDate, $appointmentTime]);
                    $success = "Appointment scheduled for registered patient!";
                } catch (PDOException $e) {
                    $error = "Error scheduling appointment: " . $e->getMessage();
                }
            }
        } elseif ($patientType === 'guest') {
            $guestId = $_POST['guest_id'] ?? null;
            if (!$guestId) {
                $error = "No guest patient selected!";
            } else {
                try {
                    $stmtInsert = $pdo->prepare("
                        INSERT INTO appointments (guest_id, doctor_id, date, time, status)
                        VALUES (?, NULL, ?, ?, 'scheduled')
                    ");
                    $stmtInsert->execute([$guestId, $appointmentDate, $appointmentTime]);
                    $success = "Appointment scheduled for guest patient!";
                } catch (PDOException $e) {
                    $error = "Error scheduling appointment: " . $e->getMessage();
                }
            }
        } else {
            $error = "Please select a patient type for appointment.";
        }
    }
    // 3B. Admit / Modify Patient Status (registered only)
    elseif ($action === 'admit_patient') {
        $admitPatientId   = $_POST['admit_patient_id']   ?? null;
        $newPatientStatus = $_POST['new_patient_status'] ?? '';

        if (!$admitPatientId || !$newPatientStatus) {
            $error = "Select a patient and status for admission/update!";
        } else {
            try {
                $stmtUpdate = $pdo->prepare("
                    UPDATE patients
                    SET status = ?
                    WHERE id = ?
                ");
                $stmtUpdate->execute([$newPatientStatus, $admitPatientId]);
                $success = "Patient status updated to '{$newPatientStatus}'!";
            } catch (PDOException $e) {
                $error = "Error updating patient status: " . $e->getMessage();
            }
        }
    }
    // 3C. Payment creation
    elseif ($action === 'create_payment') {
        $patientType = $_POST['patient_type'] ?? '';
        $amount      = $_POST['amount']       ?? '0.00';

        if ($patientType === 'registered') {
            $payPatientId = $_POST['registered_patient_id'] ?? null;
            if (!$payPatientId) {
                $error = "No registered patient selected for payment!";
            } else {
                try {
                    $stmtPayment = $pdo->prepare("
                        INSERT INTO payments (patient_id, amount, status, payment_date)
                        VALUES (?, ?, 'pending', NOW())
                    ");
                    $stmtPayment->execute([$payPatientId, $amount]);
                    $success = "Payment record created for registered patient!";
                } catch (PDOException $e) {
                    $error = "Error creating payment: " . $e->getMessage();
                }
            }
        } elseif ($patientType === 'guest') {
            $payGuestId = $_POST['guest_id'] ?? null;
            if (!$payGuestId) {
                $error = "No guest patient selected for payment!";
            } else {
                try {
                    $stmtPayment = $pdo->prepare("
                        INSERT INTO payments (guest_id, amount, status, payment_date)
                        VALUES (?, ?, 'pending', NOW())
                    ");
                    $stmtPayment->execute([$payGuestId, $amount]);
                    $success = "Payment record created for guest patient!";
                } catch (PDOException $e) {
                    $error = "Error creating payment: " . $e->getMessage();
                }
            }
        } else {
            $error = "Please select a patient type for payment creation.";
        }
    }
    // 3D. Update Payment Status
    elseif ($action === 'update_payment_status') {
        $paymentId = $_POST['payment_id'] ?? null;
        $newStatus = $_POST['new_status']   ?? '';

        if (!$paymentId || !$newStatus) {
            $error = "Payment ID or new status is missing!";
        } else {
            try {
                $stmtUpdate = $pdo->prepare("
                    UPDATE payments
                    SET status = ?
                    WHERE id = ?
                ");
                $stmtUpdate->execute([$newStatus, $paymentId]);

                if ($stmtUpdate->rowCount() > 0) {
                    $success = "Payment status updated to '" . htmlspecialchars($newStatus) . "'!";
                } else {
                    $error = "No payment updated. Check the payment ID.";
                }
            } catch (PDOException $e) {
                $error = "Error updating payment status: " . $e->getMessage();
            }
        }
    }
}

// 4. Fetch Payment Records to View Payment Status
try {
    $stmtPayments = $pdo->query("
        SELECT 
            pay.id, 
            pay.amount, 
            pay.status, 
            pay.payment_date,
            p.id AS patient_id, 
            u.name AS patient_name,
            gp.id AS guest_id,
            gp.name AS guest_name
        FROM payments pay
        LEFT JOIN patients p ON pay.patient_id = p.id
        LEFT JOIN users u    ON p.user_id = u.id
        LEFT JOIN guest_patients gp ON pay.guest_id = gp.id
        ORDER BY pay.payment_date DESC
    ");
    $payments = $stmtPayments->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die('Error fetching payments: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receptionist Dashboard - Ramisi HMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-image: url('https://static.vecteezy.com/system/resources/previews/040/835/804/non_2x/ai-generated-interior-of-a-hospital-corridor-with-green-walls-and-blue-floor-photo.jpg');
            background-color: #f8f9fa;
        }
        .dashboard-container {
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
            color: #343a40;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }
        .section-title i {
            margin-right: 10px;
        }
        .btn i {
            margin-right: 8px;
        }
        .status-form {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .status-form select {
            width: auto;
        }
    </style>
</head>
<body>
    <div class="container dashboard-container">
        <h2 class="text-center mb-4">Receptionist Dashboard</h2>

        <!-- Success/Error Messages -->
        <?php if (!empty($success)): ?>
            <div class="alert alert-success text-center"><?= htmlspecialchars($success) ?></div>
        <?php elseif (!empty($error)): ?>
            <div class="alert alert-danger text-center"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- 1. Schedule an Appointment -->
        <div class="mb-4">
            <h3 class="section-title"><i class="fas fa-calendar-check"></i> Schedule Appointment</h3>
            <form method="POST" action="" class="row g-3">
                <input type="hidden" name="action" value="schedule_appointment">

                <div class="col-md-3">
                    <label class="form-label">Patient Type</label>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="patient_type" id="regSchedRadio" value="registered" checked>
                        <label class="form-check-label" for="regSchedRadio">Registered</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="patient_type" id="guestSchedRadio" value="guest">
                        <label class="form-check-label" for="guestSchedRadio">Guest</label>
                    </div>
                </div>

                <div class="col-md-4" id="registeredSchedFields">
                    <label for="registered_patient_id" class="form-label">Select Registered Patient</label>
                    <select name="registered_patient_id" id="registered_patient_id" class="form-select">
                        <option value="">-- Select Patient --</option>
                        <?php foreach ($registeredPatients as $rp): ?>
                            <option value="<?= htmlspecialchars($rp['patient_id']) ?>">
                                <?= htmlspecialchars($rp['patient_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-4" id="guestSchedFields" style="display: none;">
                    <label for="guest_id" class="form-label">Select Guest Patient</label>
                    <select name="guest_id" id="guest_id" class="form-select">
                        <option value="">-- Select Guest --</option>
                        <?php foreach ($guestPatients as $gp): ?>
                            <option value="<?= htmlspecialchars($gp['guest_id']) ?>">
                                <?= htmlspecialchars($gp['guest_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="appointment_date" class="form-label">Date</label>
                    <input type="date" name="appointment_date" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label for="appointment_time" class="form-label">Time</label>
                    <input type="time" name="appointment_time" class="form-control" required>
                </div>
                <div class="col-md-12 text-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Schedule
                    </button>
                </div>
            </form>
        </div>

        <!-- 2. Admit/Modify Patient Status -->
        <div class="mb-4">
            <h3 class="section-title"><i class="fas fa-hospital-user"></i> Admit/Update Patient Status</h3>
            <form method="POST" action="" class="row g-3">
                <input type="hidden" name="action" value="admit_patient">
                <div class="col-md-4">
                    <label for="admit_patient_id" class="form-label">Select Patient</label>
                    <select name="admit_patient_id" id="admit_patient_id" class="form-select" required>
                        <option value="">-- Select Patient --</option>
                        <?php foreach ($registeredPatients as $rp): ?>
                            <option value="<?= htmlspecialchars($rp['patient_id']) ?>">
                                <?= htmlspecialchars($rp['patient_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="new_patient_status" class="form-label">Patient Status</label>
                    <select name="new_patient_status" id="new_patient_status" class="form-select" required>
                        <option value="Admitted">Admitted</option>
                        <option value="Discharged">Discharged</option>
                        <option value="Under Observation">Under Observation</option>
                        <option value="Awaiting Tests">Awaiting Tests</option>
                    </select>
                </div>
                <div class="col-md-4 text-end">
                    <label class="form-label d-block">&nbsp;</label>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-user-edit"></i> Update Status
                    </button>
                </div>
            </form>
        </div>

        <!-- 3. Payment Creation -->
        <div class="mb-4">
            <h3 class="section-title"><i class="fas fa-money-bill"></i> Payment</h3>
            <form method="POST" action="" class="row g-3">
                <input type="hidden" name="action" value="create_payment">

                <div class="col-md-3">
                    <label class="form-label">Patient Type</label>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="patient_type" id="regPayRadio" value="registered" checked>
                        <label class="form-check-label" for="regPayRadio">Registered</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="patient_type" id="guestPayRadio" value="guest">
                        <label class="form-check-label" for="guestPayRadio">Guest</label>
                    </div>
                </div>

                <div class="col-md-4" id="registeredPayFields">
                    <label class="form-label">Select Registered Patient</label>
                    <select name="registered_patient_id" id="registered_patient_id_pay" class="form-select">
                        <option value="">-- Select Patient --</option>
                        <?php foreach ($registeredPatients as $rp): ?>
                            <option value="<?= htmlspecialchars($rp['patient_id']) ?>">
                                <?= htmlspecialchars($rp['patient_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-4" id="guestPayFields" style="display: none;">
                    <label class="form-label">Select Guest Patient</label>
                    <select name="guest_id" id="guest_id_pay" class="form-select">
                        <option value="">-- Select Guest --</option>
                        <?php foreach ($guestPatients as $gp): ?>
                            <option value="<?= htmlspecialchars($gp['guest_id']) ?>">
                                <?= htmlspecialchars($gp['guest_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="amount" class="form-label">Amount</label>
                    <input type="number" step="0.01" name="amount" class="form-control" required>
                </div>
                <div class="col-md-12 text-end">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-cash-register"></i> Record Payment
                    </button>
                </div>
            </form>
        </div>

        <!-- 4. View/Update Payment Status -->
        <div class="mb-4">
            <h3 class="section-title"><i class="fas fa-eye"></i> View Payment Status</h3>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Patient/Guest</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Update Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!empty($payments)): ?>
                        <?php foreach ($payments as $pay): ?>
                            <?php
                                // Determine if it's a registered patient or guest
                                $isRegistered = !empty($pay['patient_id']);
                                $displayName  = $isRegistered ? $pay['patient_name'] : $pay['guest_name'];
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($pay['id']) ?></td>
                                <td>
                                    <?= htmlspecialchars($displayName ?? 'Unknown') ?>
                                    <span class="badge bg-<?= $isRegistered ? 'primary' : 'info' ?>">
                                        <?= $isRegistered ? 'Registered' : 'Guest' ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($pay['amount']) ?></td>
                                <td><?= htmlspecialchars(ucfirst($pay['status'])) ?></td>
                                <td><?= htmlspecialchars($pay['payment_date']) ?></td>
                                <td>
                                    <!-- Only let receptionist update if payment is pending or certain statuses -->
                                    <?php if (in_array($pay['status'], ['pending','canceled','failed'])): ?>
                                        <form method="POST" class="status-form">
                                            <input type="hidden" name="action" value="update_payment_status">
                                            <input type="hidden" name="payment_id" value="<?= htmlspecialchars($pay['id']) ?>">
                                            <select name="new_status" class="form-select form-select-sm">
                                                <option value="paid">Paid</option>
                                                <option value="canceled">Canceled</option>
                                                <option value="failed">Failed</option>
                                                <option value="completed">Completed</option>
                                            </select>
                                            <button type="submit" class="btn btn-sm btn-primary">Update</button>
                                        </form>
                                    <?php else: ?>
                                        <em>No update needed</em>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6">No payments found.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="text-center">
            <a href="logout.php" class="btn btn-warning"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    <script>
        // Toggle for scheduling appointment
        const regSchedRadio   = document.getElementById('regSchedRadio');
        const guestSchedRadio = document.getElementById('guestSchedRadio');
        const registeredSchedFields = document.getElementById('registeredSchedFields');
        const guestSchedFields      = document.getElementById('guestSchedFields');

        function toggleSchedFields() {
            if (regSchedRadio.checked) {
                registeredSchedFields.style.display = 'block';
                guestSchedFields.style.display      = 'none';
            } else {
                registeredSchedFields.style.display = 'none';
                guestSchedFields.style.display      = 'block';
            }
        }
        regSchedRadio.addEventListener('change', toggleSchedFields);
        guestSchedRadio.addEventListener('change', toggleSchedFields);

        // Toggle for payments
        const regPayRadio   = document.getElementById('regPayRadio');
        const guestPayRadio = document.getElementById('guestPayRadio');
        const registeredPayFields = document.getElementById('registeredPayFields');
        const guestPayFields      = document.getElementById('guestPayFields');

        function togglePayFields() {
            if (regPayRadio.checked) {
                registeredPayFields.style.display = 'block';
                guestPayFields.style.display      = 'none';
            } else {
                registeredPayFields.style.display = 'none';
                guestPayFields.style.display      = 'block';
            }
        }
        regPayRadio.addEventListener('change', togglePayFields);
        guestPayRadio.addEventListener('change', togglePayFields);
    </script>
</body>
</html>
