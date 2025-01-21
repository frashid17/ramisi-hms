<?php
// File: nurse/patient_care.php
session_start();

// Check if user is logged in and has the 'nurse' role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'nurse') {
    header('Location: login.php');
    exit();
}

require_once '../config/database.php';

// 1. Fetch existing patients for the dropdown (registered users)
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

$success = '';
$error   = '';

// 2. Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Common vitals/fields
    $temperature   = $_POST['temperature']   ?? '';
    $sugarLevels   = $_POST['sugar_levels']  ?? '';
    $bloodPressure = $_POST['blood_pressure']?? '';
    $notes         = $_POST['notes']         ?? '';

    // Check if user selected 'Registered Patient' or 'Guest Patient'
    $patientType = $_POST['patient_type'] ?? '';

    if ($patientType === 'registered') {
        // 2A. Registered patient path
        $patientId = $_POST['registered_patient_id'] ?? '';

        if (!$patientId) {
            $error = "Please select a registered patient.";
        } else {
            // Insert a record into `patient_care` referencing the patient_id
            try {
                $stmtCare = $pdo->prepare("
                    INSERT INTO patient_care 
                    (patient_id, guest_id, temperature, sugar_levels, blood_pressure, notes, created_at)
                    VALUES (?, NULL, ?, ?, ?, ?, NOW())
                ");
                $stmtCare->execute([$patientId, $temperature, $sugarLevels, $bloodPressure, $notes]);
                $success = "Vitals recorded for registered patient successfully!";
            } catch (PDOException $e) {
                $error = "Error recording vitals: " . $e->getMessage();
            }
        }
    } elseif ($patientType === 'guest') {
        // 2B. Guest patient path
        $guestName          = $_POST['guest_name']          ?? '';
        $guestAge           = $_POST['guest_age']           ?? '';
        $guestSicknessSigns = $_POST['guest_sickness_signs']?? '';

        if (empty($guestName) || empty($guestAge)) {
            $error = "Please fill out guest patient's name and age.";
        } else {
            // 2B1. Create a record in `guest_patients`
            try {
                $stmtGuest = $pdo->prepare("
                    INSERT INTO guest_patients (name, age, signs_of_sickness, created_at)
                    VALUES (?, ?, ?, NOW())
                ");
                $stmtGuest->execute([$guestName, $guestAge, $guestSicknessSigns]);
                $guestId = $pdo->lastInsertId();

                // 2B2. Insert a record into `patient_care` referencing the guest_id
                $stmtCare = $pdo->prepare("
                    INSERT INTO patient_care 
                    (patient_id, guest_id, temperature, sugar_levels, blood_pressure, notes, created_at)
                    VALUES (NULL, ?, ?, ?, ?, ?, NOW())
                ");
                $stmtCare->execute([$guestId, $temperature, $sugarLevels, $bloodPressure, $notes]);
                $success = "Vitals recorded for guest patient successfully!";
            } catch (PDOException $e) {
                $error = "Error creating guest patient record: " . $e->getMessage();
            }
        }
    } else {
        $error = "Please select whether the patient is registered or a guest.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Care - Nurse</title>
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
        .toggle-section {
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2 class="text-center mb-4">Patient Care</h2>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success text-center"><?= htmlspecialchars($success) ?></div>
        <?php elseif (!empty($error)): ?>
            <div class="alert alert-danger text-center"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <!-- 1. Choose Patient Type -->
            <div class="mb-4">
                <h4 class="section-title"><i class="fas fa-users"></i> Patient Type</h4>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="patient_type" id="registeredOption" value="registered">
                    <label class="form-check-label" for="registeredOption">Registered Patient</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="patient_type" id="guestOption" value="guest">
                    <label class="form-check-label" for="guestOption">Guest Patient</label>
                </div>
            </div>

            <!-- 2A. Registered Patient Fields -->
            <div id="registeredPatientFields" style="display: none;">
                <h5><i class="fas fa-user"></i> Registered Patient Info</h5>
                <div class="mb-3">
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
            </div>

            <!-- 2B. Guest Patient Fields -->
            <div id="guestPatientFields" style="display: none;">
                <h5><i class="fas fa-user-plus"></i> Guest Patient Info</h5>
                <div class="mb-3">
                    <label for="guest_name" class="form-label">Name</label>
                    <input type="text" name="guest_name" id="guest_name" class="form-control">
                </div>
                <div class="mb-3">
                    <label for="guest_age" class="form-label">Age</label>
                    <input type="number" name="guest_age" id="guest_age" class="form-control">
                </div>
                <div class="mb-3">
                    <label for="guest_sickness_signs" class="form-label">Signs of Sickness</label>
                    <textarea name="guest_sickness_signs" id="guest_sickness_signs" rows="2" class="form-control"></textarea>
                </div>
            </div>

            <!-- 3. Vitals for Both Registered and Guest -->
            <h5 class="mt-4"><i class="fas fa-heartbeat"></i> Record Vitals</h5>
            <div class="mb-3">
                <label for="temperature" class="form-label">Temperature</label>
                <input type="text" name="temperature" id="temperature" class="form-control" placeholder="e.g. 37°C">
            </div>
            <div class="mb-3">
                <label for="sugar_levels" class="form-label">Sugar Levels</label>
                <input type="text" name="sugar_levels" id="sugar_levels" class="form-control" placeholder="e.g. 120 mg/dL">
            </div>
            <div class="mb-3">
                <label for="blood_pressure" class="form-label">Blood Pressure</label>
                <input type="text" name="blood_pressure" id="blood_pressure" class="form-control" placeholder="e.g. 120/80">
            </div>
            <div class="mb-3">
                <label for="notes" class="form-label">Additional Notes</label>
                <textarea name="notes" id="notes" rows="3" class="form-control"></textarea>
            </div>

            <button type="submit" class="btn btn-success w-100 mt-3">
                <i class="fas fa-save"></i> Save Patient Care
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
        // Toggle fields based on patient type
        const registeredOption   = document.getElementById('registeredOption');
        const guestOption        = document.getElementById('guestOption');
        const registeredFields   = document.getElementById('registeredPatientFields');
        const guestFields        = document.getElementById('guestPatientFields');

        function toggleFields() {
            if (registeredOption.checked) {
                registeredFields.style.display = 'block';
                guestFields.style.display      = 'none';
            } else if (guestOption.checked) {
                registeredFields.style.display = 'none';
                guestFields.style.display      = 'block';
            }
        }

        registeredOption?.addEventListener('change', toggleFields);
        guestOption?.addEventListener('change', toggleFields);
    </script>
</body>
</html>
