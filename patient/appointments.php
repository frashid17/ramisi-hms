<?php
// File: patient/appointments.php
session_start();

// Check if user is logged in and is a patient
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header('Location: login.php');
    exit();
}

require_once '../config/database.php';

// Handle form submission for booking an appointment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $doctorId = $_POST['doctor_id'];
    $appointmentDate = $_POST['appointment_date'];
    $appointmentTime = $_POST['appointment_time'];

    // Validate doctor's availability
    $availabilityStmt = $pdo->prepare("SELECT availability FROM doctors WHERE id = ?");
    $availabilityStmt->execute([$doctorId]);
    $availability = $availabilityStmt->fetchColumn();

    // Check if the doctor is available
    if ($availability === 'Available') {
        try {
            // Book the appointment
            $stmt = $pdo->prepare("INSERT INTO appointments (patient_id, doctor_id, date, time, status) VALUES (?, ?, ?, ?, 'scheduled')");
            $stmt->execute([$_SESSION['user_id'], $doctorId, $appointmentDate, $appointmentTime]);
            $success = "Appointment booked successfully!";
        } catch (PDOException $e) {
            $error = "An error occurred: " . $e->getMessage();
        }
    } else {
        // Doctor is not available
        $error = "The selected doctor is not available at this time.";
    }
}


// Fetch doctors
try {
    $stmtDoctors = $pdo->query("SELECT d.id, u.name, d.specialization, d.experience, d.availability FROM doctors d JOIN users u ON d.user_id = u.id");
    $doctors = $stmtDoctors->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching doctors: " . $e->getMessage());
}

// Fetch appointments for the logged-in patient
try {
    $stmtAppointments = $pdo->prepare("SELECT a.id, a.date, a.time, a.status, u.name AS doctor_name, d.specialization FROM appointments a JOIN doctors d ON a.doctor_id = d.id JOIN users u ON d.user_id = u.id WHERE a.patient_id = ? ORDER BY a.date DESC");
    $stmtAppointments->execute([$_SESSION['user_id']]);
    $appointments = $stmtAppointments->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching appointments: " . $e->getMessage());
}

// Function to check doctor availability
function isDoctorAvailable($availability, $date, $time) {
    // Example: Check if the doctor's availability includes the day of the week
    $dayOfWeek = date('l', strtotime($date));
    return strpos($availability, $dayOfWeek) !== false;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointments - Ramisi HMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .appointments-container {
            margin: 30px auto;
            max-width: 900px;
            padding: 20px;
            background: #ffffff;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .section-title {
            font-size: 1.25rem;
            font-weight: bold;
            margin-bottom: 20px;
            color: #343a40;
        }
    </style>
</head>
<body>
    <div class="container appointments-container">
        <h2 class="text-center mb-4">Appointments</h2>

        <!-- Book Appointment Section -->
        <div class="mb-4">
            <h3 class="section-title">Book an Appointment</h3>
            <?php if (isset($success)): ?>
                <div class="alert alert-success"> <?= htmlspecialchars($success) ?> </div>
            <?php elseif (isset($error)): ?>
                <div class="alert alert-danger"> <?= htmlspecialchars($error) ?> </div>
            <?php endif; ?>
            <form method="POST" action="">
                <div class="mb-3">
                    <label for="doctor_id" class="form-label">Select Doctor</label>
                    <select name="doctor_id" id="doctor_id" class="form-select" required>
                        <option value="">-- Select Doctor --</option>
                        <?php foreach ($doctors as $doctor): ?>
                            <option value="<?= htmlspecialchars($doctor['id']) ?>">
                                <?= htmlspecialchars($doctor['name'] . " - " . $doctor['specialization'] . " (Experience: " . $doctor['experience'] . " years, Available: " . $doctor['availability'] . ")") ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="appointment_date" class="form-label">Appointment Date</label>
                    <input type="date" name="appointment_date" id="appointment_date" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="appointment_time" class="form-label">Appointment Time</label>
                    <input type="time" name="appointment_time" id="appointment_time" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary">Book Appointment</button>
            </form>
        </div>

        <!-- View Appointments Section -->
        <div class="mb-4">
            <h3 class="section-title">Your Appointments</h3>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
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

        <div class="text-center">
            <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
