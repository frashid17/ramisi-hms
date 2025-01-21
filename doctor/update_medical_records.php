<?php
// File: doctor/update_medical_records.php
session_start();

// Check if user is logged in and has the 'doctor' role
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

// 2. Fetch patients for dropdown
try {
    $stmtPatients = $pdo->query("
        SELECT p.id AS patient_id, u.name AS patient_name
        FROM patients p
        JOIN users u ON p.user_id = u.id
        ORDER BY u.name ASC
    ");
    $patients = $stmtPatients->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die('Error fetching patients: ' . $e->getMessage());
}

// 3. Handle form submission for creating/updating a medical record
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $patientId = $_POST['patient_id'];
    $diagnosis = $_POST['diagnosis'];
    $prescription = $_POST['prescription'];
    $notes = $_POST['notes'];

    if ($_POST['action'] === 'create') {
        // Insert a new medical record
        try {
            $stmtCreate = $pdo->prepare("
                INSERT INTO medical_records (patient_id, doctor_id, diagnosis, prescription, notes, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmtCreate->execute([$patientId, $doctorId, $diagnosis, $prescription, $notes]);
            $success = "Medical record created successfully!";
        } catch (PDOException $e) {
            $error = "Error creating medical record: " . $e->getMessage();
        }
    } elseif ($_POST['action'] === 'update') {
        // Update an existing medical record
        $recordId = $_POST['record_id'] ?? 0;
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
                $error = "No record updated. Please check permissions or record ID.";
            }
        } catch (PDOException $e) {
            $error = "Error updating medical record: " . $e->getMessage();
        }
    }
}

// 4. Fetch existing medical records for this doctor
try {
    $stmtRecords = $pdo->prepare("
        SELECT mr.id, mr.diagnosis, mr.prescription, mr.notes, mr.created_at,
               p.id AS patient_id, u.name AS patient_name
        FROM medical_records mr
        JOIN patients p ON mr.patient_id = p.id
        JOIN users u ON p.user_id = u.id
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
    </style>
</head>
<body>
    <div class="container">
        <h2 class="text-center mb-4">Update Medical Records</h2>

        <!-- Success/Error Messages -->
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php elseif (isset($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Create New Medical Record -->
        <div class="mb-4">
            <h3 class="section-title"><i class="fas fa-file-medical-alt"></i> Create New Record</h3>
            <form method="POST" action="">
                <input type="hidden" name="action" value="create">
                <div class="mb-3">
                    <label for="patient_id" class="form-label">Patient</label>
                    <select name="patient_id" id="patient_id" class="form-select" required>
                        <option value="">-- Select Patient --</option>
                        <?php foreach ($patients as $patient): ?>
                            <option value="<?= htmlspecialchars($patient['patient_id']) ?>">
                                <?= htmlspecialchars($patient['patient_name']) ?>
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
                            <th>Patient</th>
                            <th>Diagnosis</th>
                            <th>Prescription</th>
                            <th>Notes</th>
                            <th>Date Created</th>
                            <th>Update</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($medicalRecords)): ?>
                            <?php foreach ($medicalRecords as $record): ?>
                                <tr>
                                    <td><?= htmlspecialchars($record['id']) ?></td>
                                    <td><?= htmlspecialchars($record['patient_name']) ?></td>
                                    <td><?= htmlspecialchars($record['diagnosis']) ?></td>
                                    <td><?= htmlspecialchars($record['prescription']) ?></td>
                                    <td><?= htmlspecialchars($record['notes']) ?></td>
                                    <td><?= htmlspecialchars($record['created_at']) ?></td>
                                    <td>
                                        <!-- Update Form -->
                                        <form method="POST" action="">
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


    <script src=\"https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js\"></script>
    <script src=\"https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js\"></script>
</body>
</html>
