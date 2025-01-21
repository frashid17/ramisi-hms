<?php
// File: doctor/diagnose_prescribe.php
session_start();

// Check if user is logged in and has the 'doctor' role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header('Location: login.php');
    exit();
}

require_once '../config/database.php';

// 1. Identify the logged-in doctor
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
    $registeredPatients = $stmtPatients->fetchAll(PDO::FETCH_ASSOC);
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
    $guestPatients = $stmtGuests->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die('Error fetching guest patients: ' . $e->getMessage());
}

$success = '';
$error   = '';

// 3. Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patientType   = $_POST['patient_type']   ?? ''; // 'registered' or 'guest'
    $diagnosis     = $_POST['diagnosis']      ?? '';
    $prescription  = $_POST['prescription']   ?? '';
    $notes         = $_POST['notes']          ?? '';

    if (empty($patientType) || empty($diagnosis) || empty($prescription)) {
        $error = "Please fill in all required fields (patient type, diagnosis, prescription).";
    } else {
        // Insert new medical record
        try {
            if ($patientType === 'registered') {
                $regPatientId = $_POST['registered_patient_id'] ?? '';
                if (!$regPatientId) {
                    $error = "Please select a registered patient.";
                } else {
                    $stmtInsert = $pdo->prepare("
                        INSERT INTO medical_records
                        (patient_id, guest_id, doctor_id, diagnosis, prescription, notes, created_at)
                        VALUES (?, NULL, ?, ?, ?, ?, NOW())
                    ");
                    $stmtInsert->execute([$regPatientId, $doctorId, $diagnosis, $prescription, $notes]);
                    $success = "Diagnosis and prescription saved for registered patient!";
                }
            } elseif ($patientType === 'guest') {
                $guestId = $_POST['guest_id'] ?? '';
                if (!$guestId) {
                    $error = "Please select a guest patient.";
                } else {
                    $stmtInsert = $pdo->prepare("
                        INSERT INTO medical_records
                        (patient_id, guest_id, doctor_id, diagnosis, prescription, notes, created_at)
                        VALUES (NULL, ?, ?, ?, ?, ?, NOW())
                    ");
                    $stmtInsert->execute([$guestId, $doctorId, $diagnosis, $prescription, $notes]);
                    $success = "Diagnosis and prescription saved for guest patient!";
                }
            } else {
                $error = "Invalid patient type selected.";
            }
        } catch (PDOException $e) {
            $error = "Error saving medical record: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnose & Prescribe - Doctor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-image: url('https://static.vecteezy.com/system/resources/previews/040/835/804/non_2x/ai-generated-interior-of-a-hospital-corridor-with-green-walls-and-blue-floor-photo.jpg');
            background-color: #f8f9fa;
        }
        .container {
            margin: 30px auto;
            max-width: 900px;
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
        <h2 class="text-center mb-4">Diagnose & Prescribe (Patients & Guests)</h2>

        <!-- Success/Error Messages -->
        <?php if (!empty($success)): ?>
            <div class="alert alert-success text-center"><?= htmlspecialchars($success) ?></div>
        <?php elseif (!empty($error)): ?>
            <div class="alert alert-danger text-center"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Form to create a new medical record for Registered or Guest -->
        <form method="POST" action="">
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
                <label for="registered_patient_id" class="form-label">Select Registered Patient</label>
                <select name="registered_patient_id" id="registered_patient_id" class="form-select">
                    <option value="">-- Select Patient --</option>
                    <?php foreach ($registeredPatients as $r): ?>
                        <option value="<?= htmlspecialchars($r['patient_id']) ?>">
                            <?= htmlspecialchars($r['patient_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Guest Patient Dropdown -->
            <div id="guestFields" class="mb-3" style="display: none;">
                <label for="guest_id" class="form-label">Select Guest Patient</label>
                <select name="guest_id" id="guest_id" class="form-select">
                    <option value="">-- Select Guest --</option>
                    <?php foreach ($guestPatients as $g): ?>
                        <option value="<?= htmlspecialchars($g['guest_id']) ?>">
                            <?= htmlspecialchars($g['guest_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Diagnosis & Prescription -->
            <div class="mb-3">
                <label for="diagnosis" class="form-label">Diagnosis</label>
                <textarea name="diagnosis" id="diagnosis" rows="2" class="form-control" required></textarea>
            </div>
            <div class="mb-3">
                <label for="prescription" class="form-label">Prescription</label>
                <textarea name="prescription" id="prescription" rows="2" class="form-control" required></textarea>
            </div>
            <div class="mb-3">
                <label for="notes" class="form-label">Additional Notes (optional)</label>
                <textarea name="notes" id="notes" rows="3" class="form-control"></textarea>
            </div>
            <button type="submit" class="btn btn-primary w-100">
                <i class="fas fa-file-medical"></i> Save
            </button>
        </form>

        <div class="text-center mt-4">
            <a href="dashboard.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    <script>
        // Toggle fields for Registered vs. Guest
        const registeredRadio  = document.getElementById('registeredRadio');
        const guestRadio       = document.getElementById('guestRadio');
        const registeredFields = document.getElementById('registeredFields');
        const guestFields      = document.getElementById('guestFields');

        function togglePatientFields() {
            if (registeredRadio.checked) {
                registeredFields.style.display = 'block';
                guestFields.style.display      = 'none';
            } else {
                registeredFields.style.display = 'none';
                guestFields.style.display      = 'block';
            }
        }

        registeredRadio.addEventListener('change', togglePatientFields);
        guestRadio.addEventListener('change', togglePatientFields);
    </script>
</body>
</html>
