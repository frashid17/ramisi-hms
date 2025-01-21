<?php
// File: admin/manage_schedule.php
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

require_once '../config/database.php';

// Handle Update Schedule Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $doctorId = $_POST['doctor_id'];
    $availability = trim($_POST['availability']); // Trim extra spaces

    // Basic validation to prevent unexpected values
    $validStatuses = ['Available', 'Not Available'];
    if (!in_array($availability, $validStatuses)) {
        $error = "Invalid availability status!";
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE doctors SET availability = ? WHERE id = ?");
            $stmt->execute([$availability, $doctorId]);
            $success = "Doctor's schedule updated successfully!";
        } catch (PDOException $e) {
            $error = "An error occurred: " . $e->getMessage();
        }
    }
}


// Fetch Doctors and Their Schedules
try {
    $stmtDoctors = $pdo->query("SELECT d.id, u.name, d.specialization, d.availability FROM doctors d JOIN users u ON d.user_id = u.id");
    $doctors = $stmtDoctors->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching doctors: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Doctor Schedule - Ramisi HMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-image: url('https://static.vecteezy.com/system/resources/previews/040/835/804/non_2x/ai-generated-interior-of-a-hospital-corridor-with-green-walls-and-blue-floor-photo.jpg');
        }
        .dashboard-container {
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
        }
    </style>
</head>
<body>
    <div class="container dashboard-container">
        <h2 class="text-center mb-4">Manage Doctor Schedule</h2>

        <!-- Success/Error Messages -->
        <?php if (isset($success)): ?>
            <div class="alert alert-success"> <?= htmlspecialchars($success) ?> </div>
        <?php elseif (isset($error)): ?>
            <div class="alert alert-danger"> <?= htmlspecialchars($error) ?> </div>
        <?php endif; ?>

        <!-- Doctors List and Update Schedule -->
        <div class="mb-4">
            <h3 class="section-title">Doctors</h3>
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Specialization</th>
                        <th>Availability</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($doctors as $doctor): ?>
                        <tr>
                            <td><?= htmlspecialchars($doctor['id']) ?></td>
                            <td><?= htmlspecialchars($doctor['name']) ?></td>
                            <td><?= htmlspecialchars($doctor['specialization']) ?></td>
                            <td><?= htmlspecialchars($doctor['availability']) ?></td>
                            <td>
                                <!-- Update Availability Form -->
                                <form method="POST" action="" class="d-inline">
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="doctor_id" value="<?= htmlspecialchars($doctor['id']) ?>">
                                    <select name="availability" class="form-select form-select-sm d-inline-block w-auto" required>
                                        <option value="Available" <?= $doctor['availability'] === 'Available' ? 'selected' : '' ?>>Available</option>
                                        <option value="Not Available" <?= $doctor['availability'] === 'Not Available' ? 'selected' : '' ?>>Not Available</option>
                                    </select>
                                    <button type="submit" class="btn btn-primary btn-sm">Update</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="text-center">
            <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
