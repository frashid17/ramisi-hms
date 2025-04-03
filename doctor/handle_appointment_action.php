<?php

session_start();

// Ensure the doctor is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header('Location: login.php');
    exit();
}

require_once '../config/database.php';

// Process the POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $appointmentId = $_POST['appointment_id'] ?? null;
    $action = $_POST['action'] ?? '';

    // Validate appointment ID and action
    if (!$appointmentId || !in_array($action, ['accept', 'cancel'])) {
        die('Invalid request.');
    }

    try {
        // Start a database transaction to ensure atomicity
        $pdo->beginTransaction();

        if ($action === 'accept') {
            // Update the appointment to 'completed'
            $stmt = $pdo->prepare("UPDATE appointments SET status = 'completed' WHERE id = ?");
            $stmt->execute([$appointmentId]);
        } elseif ($action === 'cancel') {
            // Update the appointment to 'canceled' without a cancellation reason
            $stmt = $pdo->prepare("UPDATE appointments SET status = 'canceled' WHERE id = ?");
            $stmt->execute([$appointmentId]);
        }

        // Check if any row was affected (successful query)
        if ($stmt->rowCount() === 0) {
            throw new Exception('No changes were made. Appointment may already be in the desired state.');
        }

        // Commit the transaction if the update is successful
        $pdo->commit();

        // Redirect back to dashboard with a success message
        $_SESSION['success'] = 'Appointment status updated successfully.';
        header("Location: dashboard.php");
        exit();

    } catch (PDOException $e) {
        // Rollback the transaction in case of an error
        $pdo->rollBack();
        die("Database error: " . $e->getMessage());
    } catch (Exception $e) {
        // Handle other errors (like no rows affected)
        $pdo->rollBack();
        die($e->getMessage());
    }

} else {
    header("Location: dashboard.php");
    exit();
}
