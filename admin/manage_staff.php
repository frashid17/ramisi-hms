<?php
// File: admin/manage_staff.php
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

require_once '../config/database.php';

// Handle Add Staff Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];

    try {
        // Insert into users table
        $stmtUser = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
        $stmtUser->execute([$name, $email, $password, $role]);
        $userId = $pdo->lastInsertId();

        // Insert into specific staff table based on role
        if ($role === 'doctor') {
            $specialization = $_POST['specialization'];
            $experience = $_POST['experience'];
            $stmtDoctor = $pdo->prepare("INSERT INTO doctors (user_id, specialization, experience) VALUES (?, ?, ?)");
            $stmtDoctor->execute([$userId, $specialization, $experience]);
        } elseif ($role === 'nurse') {
            $department = $_POST['department'];
            $stmtNurse = $pdo->prepare("INSERT INTO nurses (user_id, department) VALUES (?, ?)");
            $stmtNurse->execute([$userId, $department]);
        } elseif ($role === 'receptionist') {
            $shift = $_POST['shift'];
            $stmtReceptionist = $pdo->prepare("INSERT INTO receptionists (user_id, shift) VALUES (?, ?)");
            $stmtReceptionist->execute([$userId, $shift]);
        }

        $success = "Staff member added successfully!";
    } catch (PDOException $e) {
        $error = "An error occurred: " . $e->getMessage();
    }
}

// Handle Delete Staff
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $userId = $_POST['user_id'];

    try {
        $stmtDelete = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmtDelete->execute([$userId]);
        $success = "Staff member deleted successfully!";
    } catch (PDOException $e) {
        $error = "An error occurred: " . $e->getMessage();
    }
}

// Fetch Staff Members
try {
    $stmtStaff = $pdo->query("SELECT u.id, u.name, u.email, u.role FROM users u WHERE u.role IN ('doctor', 'nurse', 'receptionist')");
    $staffMembers = $stmtStaff->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching staff members: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Staff - Ramisi HMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body{
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
        <h2 class="text-center mb-4">Manage Staff</h2>

        <!-- Add Staff Section -->
        <div class="mb-4">
            <h3 class="section-title">Add Staff Member</h3>
            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php elseif (isset($error)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <form method="POST" action="">
                <input type="hidden" name="action" value="add">
                <div class="mb-3">
                    <label for="name" class="form-label">Name</label>
                    <input type="text" name="name" id="name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" name="email" id="email" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" name="password" id="password" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="role" class="form-label">Role</label>
                    <select name="role" id="role" class="form-select" required>
                        <option value="">-- Select Role --</option>
                        <option value="doctor">Doctor</option>
                        <option value="nurse">Nurse</option>
                        <option value="receptionist">Receptionist</option>
                    </select>
                </div>
                <div class="mb-3" id="additionalFields"></div>
                <button type="submit" class="btn btn-primary">Add Staff</button>
            </form>
        </div>

        <!-- View Staff Section -->
        <div class="mb-4">
            <h3 class="section-title">Existing Staff Members</h3>
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($staffMembers as $staff): ?>
                        <tr>
                            <td><?= htmlspecialchars($staff['id']) ?></td>
                            <td><?= htmlspecialchars($staff['name']) ?></td>
                            <td><?= htmlspecialchars($staff['email']) ?></td>
                            <td><?= htmlspecialchars(ucfirst($staff['role'])) ?></td>
                            <td>
                                <form method="POST" action="" class="d-inline">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="user_id" value="<?= htmlspecialchars($staff['id']) ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
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

    <script>
        const roleSelect = document.getElementById('role');
        const additionalFields = document.getElementById('additionalFields');

        roleSelect.addEventListener('change', () => {
            additionalFields.innerHTML = '';
            if (roleSelect.value === 'doctor') {
                additionalFields.innerHTML = `
                    <label for="specialization" class="form-label">Specialization</label>
                    <input type="text" name="specialization" id="specialization" class="form-control" required>
                    <label for="experience" class="form-label">Experience (Years)</label>
                    <input type="number" name="experience" id="experience" class="form-control" required>
                `;
            } else if (roleSelect.value === 'nurse') {
                additionalFields.innerHTML = `
                    <label for="department" class="form-label">Department</label>
                    <input type="text" name="department" id="department" class="form-control" required>
                `;
            } else if (roleSelect.value === 'receptionist') {
                additionalFields.innerHTML = `
                    <label for="shift" class="form-label">Shift</label>
                    <input type="text" name="shift" id="shift" class="form-control" required>
                `;
            }
        });
    </script>
</body>
</html>
