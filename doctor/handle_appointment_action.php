<?php

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header('Location: login.php');
    exit();
}

require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $appointmentId = $_POST['appointment_id'] ?? null;
    $action = $_POST['action'] ?? '';

    if (!$appointmentId || !in_array($action, ['accept', 'cancel'])) {
        die('Invalid request.');
    }

    try {
        if ($action === 'accept') {
            $stmt = $pdo->prepare("UPDATE appointments SET status = 'completed', cancellation_reason = NULL WHERE id = ?");
            $stmt->execute([$appointmentId]);
        } elseif ($action === 'cancel') {
            $reason = trim($_POST['cancellation_reason'] ?? '');
            if (empty($reason)) {
                die('Cancellation reason is required.');
            }
            $stmt = $pdo->prepare("UPDATE appointments SET status = 'canceled', cancellation_reason = ? WHERE id = ?");
            $stmt->execute([$reason, $appointmentId]);
        }

        // Redirect back to doctor dashboard
        header("Location: dashboard.php");
        exit();

    } catch (PDOException $e) {
        die("Database error: " . $e->getMessage());
    }
} else {
    header("Location: dashboard.php");
    exit();
}
