<?php
// File: doctor/update_medical_records.php
session_start();

// Check if user is logged in as doctor
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header('Location: login.php');
    exit();
}

require_once '../config/database.php';

// 1. Identify the logged-in doctor's ID from the 'doctors' table
try {
    $stmtDoctor = $pdo->prepare("SELECT id FROM doctors WHERE user_id = ?");
    $stmtDoctor->execute([$_SESSION['user_id']]);
    $doctorRecord = $stmtDoctor->fetch(PDO::FETCH_ASSOC);

    if (!$doctorRecord) {
        die('Error: Doctor record not found.');
    }
    $doctorId = $doctorRecord['id'];
} catch (PDOException $e) {
    die('Error fetching doctor info: ' . $e->getMessage());
}

// 2A. Fetch Registered Patients
try {
    $stmtPatients = $pdo->query("
        SELECT p.id AS patient_id, u.name AS patient_name
        FROM patients p
        JOIN users u ON p.user_id = u.id
        ORDER BY u.name ASC
    ");
    $patients = $stmtPatients->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die('Error fetching registered patients: ' . $e->getMessage());
}

// 2B. Fetch Guest Patients
try {
    $stmtGuests = $pdo->query("
        SELECT id AS guest_id, name AS guest_name
        FROM guest_patients
        ORDER BY name ASC
    ");
    $guests = $stmtGuests->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die('Error fetching guest patients: ' . $e->getMessage());
}

$success = '';
$error   = '';

// 3. Handle form submission for create, update, or delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action      = $_POST['action'];
    $recordId    = $_POST['record_id']    ?? 0;

    // Common fields for create/update
    $diagnosis   = $_POST['diagnosis']    ?? '';
    $prescription= $_POST['prescription'] ?? '';
    $notes       = $_POST['notes']        ?? '';

    if ($action === 'create') {
        // Check if we're creating for a registered or a guest
        $patientType = $_POST['patient_type'] ?? '';
        if ($patientType === 'registered') {
            $patientId = $_POST['registered_patient_id'] ?? '';
            if (!$patientId) {
                $error = "Please select a registered patient.";
            } else {
                // Insert record for a registered patient
                try {
                    $stmtCreate = $pdo->prepare("
                        INSERT INTO medical_records 
                        (patient_id, guest_id, doctor_id, diagnosis, prescription, notes, created_at)
                        VALUES (?, NULL, ?, ?, ?, ?, NOW())
                    ");
                    $stmtCreate->execute([$patientId, $doctorId, $diagnosis, $prescription, $notes]);
                    $success = "Medical record created successfully (registered patient)!";
                } catch (PDOException $e) {
                    $error = "Error creating medical record: " . $e->getMessage();
                }
            }
        } elseif ($patientType === 'guest') {
            $guestId = $_POST['guest_id'] ?? '';
            if (!$guestId) {
                $error = "Please select a guest patient.";
            } else {
                // Insert record for a guest patient
                try {
                    $stmtCreate = $pdo->prepare("
                        INSERT INTO medical_records
                        (patient_id, guest_id, doctor_id, diagnosis, prescription, notes, created_at)
                        VALUES (NULL, ?, ?, ?, ?, ?, NOW())
                    ");
                    $stmtCreate->execute([$guestId, $doctorId, $diagnosis, $prescription, $notes]);
                    $success = "Medical record created successfully (guest patient)!";
                } catch (PDOException $e) {
                    $error = "Error creating medical record: " . $e->getMessage();
                }
            }
        } else {
            $error = "Please select either Registered or Guest patient type.";
        }
    }
    elseif ($action === 'update') {
        // Update an existing record
        try {
            $stmtUpdate = $pdo->prepare("
                UPDATE medical_records
                SET diagnosis = ?, prescription = ?, notes = ?
                WHERE id = ? AND doctor_id = ?
            ");
            $stmtUpdate->execute([$diagnosis, $prescription, $notes, $recordId, $doctorId]);

            if ($stmtUpdate->rowCount() > 0) {
                $success = "Medical record updated successfully!";
            } else {
                $error = "No record updated. Check permissions or record ID.";
            }
        } catch (PDOException $e) {
            $error = "Error updating record: " . $e->getMessage();
        }
    }
    elseif ($action === 'delete') {
        // Delete an existing record
        try {
            $stmtDelete = $pdo->prepare("
                DELETE FROM medical_records
                WHERE id = ? AND doctor_id = ?
            ");
            $stmtDelete->execute([$recordId, $doctorId]);

            if ($stmtDelete->rowCount() > 0) {
                $success = "Medical record deleted successfully!";
            } else {
                $error = "No record deleted. Check permissions or record ID.";
            }
        } catch (PDOException $e) {
            $error = "Error deleting record: " . $e->getMessage();
        }
    }
}

// 4. Fetch existing medical records for this doctor (join both patients & guests)
try {
    $stmtRecords = $pdo->prepare("
        SELECT 
            mr.id, 
            mr.diagnosis, 
            mr.prescription, 
            mr.notes, 
            mr.created_at,
            p.id AS patient_id,
            u.name AS patient_name,
            gp.id AS guest_id,
            gp.name AS guest_name
        FROM medical_records mr
        LEFT JOIN patients p ON mr.patient_id = p.id
        LEFT JOIN users u    ON p.user_id = u.id
        LEFT JOIN guest_patients gp ON mr.guest_id = gp.id
        WHERE mr.doctor_id = ?
        ORDER BY mr.created_at DESC
    ");
    $stmtRecords->execute([$doctorId]);
    $medicalRecords = $stmtRecords->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die('Error fetching medical records: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Medical Records - Doctor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-image: url('https://static.vecteezy.com/system/resources/previews/040/835/804/non_2x/ai-generated-interior-of-a-hospital-corridor-with-green-walls-and-blue-floor-photo.jpg');
            background-color: #f8f9fa;
        }
        .container {
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
            color: #343a40;
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        .section-title i {
            margin-right: 10px;
        }
        .btn i {
            margin-right: 8px;
        }
        .toggle-section {
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2 class="text-center mb-4">Update Medical Records (Patients & Guests)</h2>

        <!-- Success/Error Messages -->
        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php elseif (!empty($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Create New Medical Record -->
        <div class="mb-4">
            <h3 class="section-title"><i class="fas fa-file-medical-alt"></i> Create New Record</h3>
            <form method="POST" action="">
                <input type="hidden" name="action" value="create">

                <!-- Patient Type (Registered or Guest) -->
                <div class="mb-3">
                    <label class="form-label">Patient Type</label>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="patient_type" id="registeredRadio" value="registered" checked>
                        <label class="form-check-label" for="registeredRadio">Registered Patient</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="patient_type" id="guestRadio" value="guest">
                        <label class="form-check-label" for="guestRadio">Guest Patient</label>
                    </div>
                </div>

                <!-- Registered Patient Dropdown -->
                <div id="registeredFields" class="mb-3">
                    <label class="form-label">Select Registered Patient</label>
                    <select name="registered_patient_id" id="registered_patient_id" class="form-select">
                        <option value="">-- Select Patient --</option>
                        <?php foreach ($patients as $p): ?>
                            <option value="<?= htmlspecialchars($p['patient_id']) ?>">
                                <?= htmlspecialchars($p['patient_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Guest Patient Dropdown -->
                <div id="guestFields" class="mb-3" style="display: none;">
                    <label class="form-label">Select Guest Patient</label>
                    <select name="guest_id" id="guest_id" class="form-select">
                        <option value="">-- Select Guest --</option>
                        <?php foreach ($guests as $g): ?>
                            <option value="<?= htmlspecialchars($g['guest_id']) ?>">
                                <?= htmlspecialchars($g['guest_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="diagnosis" class="form-label">Diagnosis</label>
                    <textarea name="diagnosis" id="diagnosis" class="form-control" rows="2" required></textarea>
                </div>
                <div class="mb-3">
                    <label for="prescription" class="form-label">Prescription</label>
                    <textarea name="prescription" id="prescription" class="form-control" rows="2" required></textarea>
                </div>
                <div class="mb-3">
                    <label for="notes" class="form-label">Notes</label>
                    <textarea name="notes" id="notes" class="form-control" rows="3"></textarea>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Create Record
                </button>
            </form>
        </div>

        <!-- Existing Medical Records -->
        <div class="mb-4">
            <h3 class="section-title"><i class="fas fa-edit"></i> Existing Records</h3>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Patient/Guest</th>
                            <th>Diagnosis</th>
                            <th>Prescription</th>
                            <th>Notes</th>
                            <th>Date Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($medicalRecords)): ?>
                            <?php foreach ($medicalRecords as $record): ?>
                                <?php
                                    // If 'patient_id' is set, show registered patient name; otherwise show guest
                                    $isRegistered = !empty($record['patient_id']);
                                    $displayName  = $isRegistered ? $record['patient_name'] : $record['guest_name'];
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($record['id']) ?></td>
                                    <td>
                                        <?= htmlspecialchars($displayName ?? 'Unknown') ?>
                                        <span class="badge bg-<?= $isRegistered ? 'primary' : 'info' ?>">
                                            <?= $isRegistered ? 'Registered' : 'Guest' ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($record['diagnosis']) ?></td>
                                    <td><?= htmlspecialchars($record['prescription']) ?></td>
                                    <td><?= htmlspecialchars($record['notes']) ?></td>
                                    <td><?= htmlspecialchars($record['created_at']) ?></td>
                                    <td>
                                        <!-- Update Form -->
                                        <form method="POST" action="" class="mb-2">
                                            <input type="hidden" name="action" value="update">
                                            <input type="hidden" name="record_id" value="<?= htmlspecialchars($record['id']) ?>">

                                            <div class="mb-2">
                                                <label class="form-label">Diagnosis</label>
                                                <textarea name="diagnosis" class="form-control" rows="1" required><?= htmlspecialchars($record['diagnosis']) ?></textarea>
                                            </div>
                                            <div class="mb-2">
                                                <label class="form-label">Prescription</label>
                                                <textarea name="prescription" class="form-control" rows="1" required><?= htmlspecialchars($record['prescription']) ?></textarea>
                                            </div>
                                            <div class="mb-2">
                                                <label class="form-label">Notes</label>
                                                <textarea name="notes" class="form-control" rows="1"><?= htmlspecialchars($record['notes']) ?></textarea>
                                            </div>
                                            <button type="submit" class="btn btn-success btn-sm">
                                                <i class="fas fa-sync-alt"></i> Save
                                            </button>
                                        </form>

                                        <!-- Delete Form -->
                                        <form method="POST" action="" class="d-inline">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="record_id" value="<?= htmlspecialchars($record['id']) ?>">
                                            <button type="submit" class="btn btn-danger btn-sm">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7">No medical records found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="text-center mt-4">
            <a href="/ramisi-hms/doctor/dashboard.php" class="btn btn-secondary btn-lg">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <script src=\"https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js\"></script>
    <script src=\"https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js\"></script>
    <script>
        // Toggle Registered vs. Guest fields in the create form
        const registeredRadio = document.getElementById('registeredRadio');
        const guestRadio      = document.getElementById('guestRadio');
        const registeredFields= document.getElementById('registeredFields');
        const guestFields     = document.getElementById('guestFields');

        function togglePatientType() {
            if (registeredRadio.checked) {
                registeredFields.style.display = 'block';
                guestFields.style.display      = 'none';
            } else if (guestRadio.checked) {
                registeredFields.style.display = 'none';
                guestFields.style.display      = 'block';
            }
        }
        registeredRadio.addEventListener('change', togglePatientType);
        guestRadio.addEventListener('change', togglePatientType);
    </script>
</body>
</html>