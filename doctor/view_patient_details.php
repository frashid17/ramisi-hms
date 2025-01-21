<?php
// File: doctor/view_patient_details.php
session_start();

// Check role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header('Location: login.php');
    exit();
}

require_once '../config/database.php';

// (Optional) Identify the logged-in doctor
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

// 1. Gather form inputs: 'search' text and 'patient_type' selection
$searchTerm   = $_GET['search']       ?? '';
$patientType  = $_GET['patient_type'] ?? 'all';  // 'registered', 'guest', or 'all'

// Arrays to store matched records
$matchedRegistered = [];
$matchedGuests     = [];

if ($searchTerm !== '') {
    // 2A. If 'registered' or 'all', search in 'patients' + 'users'
    if ($patientType === 'registered' || $patientType === 'all') {
        try {
            $stmtSearch = $pdo->prepare("
                SELECT 
                    p.id AS patient_id,
                    p.dob,
                    p.address,
                    p.phone,
                    p.insurance_details,
                    p.status,
                    u.name AS patient_name
                FROM patients p
                JOIN users u ON p.user_id = u.id
                WHERE u.name LIKE :search
                ORDER BY u.name ASC
            ");
            $stmtSearch->execute(['search' => '%' . $searchTerm . '%']);
            $matchedRegistered = $stmtSearch->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            die('Error searching registered patients: ' . $e->getMessage());
        }
    }

    // 2B. If 'guest' or 'all', search in 'guest_patients'
    if ($patientType === 'guest' || $patientType === 'all') {
        try {
            $stmtGuests = $pdo->prepare("
                SELECT 
                    gp.id AS guest_id,
                    gp.name AS guest_name,
                    gp.age,
                    gp.signs_of_sickness,
                    '' AS status  -- guests do not have a status
                FROM guest_patients gp
                WHERE gp.name LIKE :search
                ORDER BY gp.name ASC
            ");
            $stmtGuests->execute(['search' => '%' . $searchTerm . '%']);
            $matchedGuests = $stmtGuests->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            die('Error searching guest patients: ' . $e->getMessage());
        }
    }
}

// 3. Combine data for final output
$resultsData = [];

// 3A. Build data for matched registered patients
foreach ($matchedRegistered as $patient) {
    $patientId = $patient['patient_id'];
    // Query 'patient_care' for patient_id
    try {
        $stmtCare = $pdo->prepare("
            SELECT 
                pc.id AS care_id,
                pc.temperature,
                pc.sugar_levels,
                pc.blood_pressure,
                pc.notes,
                pc.created_at
            FROM patient_care pc
            WHERE pc.patient_id = ?
            ORDER BY pc.created_at DESC
        ");
        $stmtCare->execute([$patientId]);
        $careRecords = $stmtCare->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die('Error fetching care records: ' . $e->getMessage());
    }

    $resultsData[] = [
        'type'             => 'registered',
        'name'             => $patient['patient_name'],
        'status'           => $patient['status'] ?: 'N/A',
        'dob'              => $patient['dob'] ?? 'N/A',
        'address'          => $patient['address'] ?? 'N/A',
        'phone'            => $patient['phone'] ?? 'N/A',
        'insurance_details'=> $patient['insurance_details'] ?? 'N/A',
        'care_records'     => $careRecords
    ];
}

// 3B. Build data for matched guest patients
foreach ($matchedGuests as $guest) {
    $guestId = $guest['guest_id'];
    // Query 'patient_care' for guest_id
    try {
        $stmtCare = $pdo->prepare("
            SELECT 
                pc.id AS care_id,
                pc.temperature,
                pc.sugar_levels,
                pc.blood_pressure,
                pc.notes,
                pc.created_at
            FROM patient_care pc
            WHERE pc.guest_id = ?
            ORDER BY pc.created_at DESC
        ");
        $stmtCare->execute([$guestId]);
        $careRecords = $stmtCare->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die('Error fetching care records for guest: ' . $e->getMessage());
    }

    $resultsData[] = [
        'type'             => 'guest',
        'name'             => $guest['guest_name'],
        'status'           => $guest['status'] ?: 'N/A',
        'age'              => $guest['age']  ?? 'N/A',
        'signs_of_sickness'=> $guest['signs_of_sickness'] ?? '',
        'care_records'     => $careRecords
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Patient/Guest Details - Doctor</title>
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
        .patient-section {
            background: #f8f9fa;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 10px;
        }
        .patient-section h5 {
            margin-bottom: 10px;
        }
        .records-table thead {
            background: #e9ecef;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2 class="text-center mb-4">View Patient/Guest Details</h2>

        <!-- Search Form -->
        <div class="mb-4">
            <h4 class="section-title"><i class="fas fa-search"></i> Search by Name & Type</h4>
            <form method="GET" action="" class="row g-3">
                <div class="col-auto">
                    <input 
                        type="text" 
                        name="search" 
                        class="form-control" 
                        placeholder="Enter name" 
                        value="<?= htmlspecialchars($searchTerm) ?>" 
                        required
                    >
                </div>
                <div class="col-auto">
                    <select name="patient_type" class="form-select">
                        <option value="all" <?= $patientType === 'all' ? 'selected' : '' ?>>All</option>
                        <option value="registered" <?= $patientType === 'registered' ? 'selected' : '' ?>>Registered</option>
                        <option value="guest" <?= $patientType === 'guest' ? 'selected' : '' ?>>Guest</option>
                    </select>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-eye"></i> Search
                    </button>
                </div>
            </form>
        </div>

        <!-- If no matches, show info -->
        <?php if ($searchTerm !== '' && empty($resultsData)): ?>
            <div class="alert alert-info">
                No <?= htmlspecialchars($patientType) ?> patient(s) found matching: 
                <strong><?= htmlspecialchars($searchTerm) ?></strong>
            </div>
        <?php endif; ?>

        <!-- Display matched patients/guests + their care records -->
        <?php if (!empty($resultsData)): ?>
            <?php foreach ($resultsData as $data): ?>
                <div class="patient-section">
                    <?php if ($data['type'] === 'registered'): ?>
                        <h5><i class="fas fa-user-injured"></i> Registered Patient Info</h5>
                        <p><strong>Name:</strong> <?= htmlspecialchars($data['name']) ?></p>
                        <p><strong>Date of Birth:</strong> <?= htmlspecialchars($data['dob']) ?></p>
                        <p><strong>Address:</strong> <?= htmlspecialchars($data['address']) ?></p>
                        <p><strong>Phone:</strong> <?= htmlspecialchars($data['phone']) ?></p>
                        <p><strong>Insurance Details:</strong> <?= htmlspecialchars($data['insurance_details']) ?></p>
                        <p><strong>Status:</strong> <?= htmlspecialchars($data['status']) ?></p>
                    <?php else: ?>
                        <h5><i class="fas fa-user-plus"></i> Guest Patient Info</h5>
                        <p><strong>Name:</strong> <?= htmlspecialchars($data['name']) ?></p>
                        <p><strong>Age:</strong> <?= htmlspecialchars($data['age'] ?? 'N/A') ?></p>
                        <p><strong>Signs of Sickness:</strong> <?= htmlspecialchars($data['signs_of_sickness'] ?? '') ?></p>
                        <p><strong>Status:</strong> <?= htmlspecialchars($data['status']) ?></p>
                    <?php endif; ?>

                    <h5 class="mt-3"><i class="fas fa-heartbeat"></i> Patient Care Records</h5>
                    <?php if (!empty($data['care_records'])): ?>
                        <div class="table-responsive">
                            <table class="table table-hover records-table">
                                <thead>
                                    <tr>
                                        <th>Care ID</th>
                                        <th>Temperature</th>
                                        <th>Sugar Levels</th>
                                        <th>Blood Pressure</th>
                                        <th>Notes</th>
                                        <th>Date Created</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($data['care_records'] as $care): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($care['care_id']) ?></td>
                                            <td><?= htmlspecialchars($care['temperature'] ?? '') ?></td>
                                            <td><?= htmlspecialchars($care['sugar_levels'] ?? '') ?></td>
                                            <td><?= htmlspecialchars($care['blood_pressure'] ?? '') ?></td>
                                            <td><?= htmlspecialchars($care['notes'] ?? '') ?></td>
                                            <td><?= htmlspecialchars($care['created_at']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p>No patient care records found.</p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- Back to Dashboard -->
        <div class="text-center mt-4">
            <a href="dashboard.php" class="btn btn-secondary btn-lg">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
</body>
</html>
