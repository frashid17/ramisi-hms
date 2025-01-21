<?php
// File: nurse/dashboard.php
session_start();

// Check if user is logged in and has the 'nurse' role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'nurse') {
    header('Location: login.php');
    exit();
}

require_once '../config/database.php';

// 1. Fetch patients to allow the nurse to update their status, etc.
try {
    // Example: if you have a `patients` table with a status column
    $stmt = $pdo->query("
        SELECT p.id AS patient_id, u.name AS patient_name, p.status
        FROM patients p
        JOIN users u ON p.user_id = u.id
        ORDER BY u.name ASC
    ");
    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die('Error fetching patients: ' . $e->getMessage());
}

// 2. Handle form submission to update patient status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['patient_id']) && isset($_POST['new_status'])) {
    $patientId = $_POST['patient_id'];
    $newStatus = $_POST['new_status'];

    try {
        // Update the `status` in `patients` table
        $updateStmt = $pdo->prepare("UPDATE patients SET status = ? WHERE id = ?");
        $updateStmt->execute([$newStatus, $patientId]);
        $success = "Patient status updated successfully!";
    } catch (PDOException $e) {
        $error = "Error updating patient status: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nurse Dashboard - Ramisi HMS</title>
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
        <h2 class="text-center mb-4">Nurse Dashboard</h2>
        <p class="text-center">Welcome, Nurse!</p>

        <!-- Success/Error Messages -->
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php elseif (isset($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Update Patient Status Section -->
        <div class="mb-4">
            <h3 class="section-title"><i class="fas fa-notes-medical"></i> Update Patient Status</h3>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Patient ID</th>
                            <th>Patient Name</th>
                            <th>Current Status</th>
                            <th>Update Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($patients)): ?>
                            <?php foreach ($patients as $patient): ?>
                                <tr>
                                    <td><?= htmlspecialchars($patient['patient_id']) ?></td>
                                    <td><?= htmlspecialchars($patient['patient_name']) ?></td>
                                    <td><?= htmlspecialchars($patient['status'] ?? 'N/A') ?></td>
                                    <td>
                                        <form method="POST" action="">
                                            <input type="hidden" name="patient_id" value="<?= htmlspecialchars($patient['patient_id']) ?>">
                                            <select name="new_status" class="form-select form-select-sm d-inline-block w-auto">
                                                <option value="Admitted">Admitted</option>
                                                <option value="Discharged">Discharged</option>
                                                <option value="Under Observation">Under Observation</option>
                                                <option value="Awaiting Tests">Awaiting Tests</option>
                                            </select>
                                            <button type="submit" class="btn btn-primary btn-sm">
                                                <i class="fas fa-sync-alt"></i> Update
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="4">No patients found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Assist in Patient Care Section -->
        <div class="mb-4">
            <h3 class="section-title"><i class="fas fa-user-nurse"></i> Assist in Patient Care</h3>
            <p>
            Nurses can record vital signs, administer medication, and maintain patient comfort. 
            </p>
            <a href="patient_care.php" class="btn btn-success">
            <i class="fas fa-notes-medical"></i> Record Vitals
            </a>
        </div>

        <!-- Logout Section -->
        <div class="text-center">
            <a href="logout.php" class="btn btn-warning">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
</body>
</html>
