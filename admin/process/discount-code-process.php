<?php
ob_start();
error_reporting(E_ALL ^ E_NOTICE);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include('../include/database.php');
include('../include/check_login.php');

$db = new Database();
$action = isset($_POST['action']) ? $_POST['action'] : '';

switch ($action) {

    // ── CREATE ──────────────────────────────────────────────
    case 'create':
        $code        = trim($_POST['code'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $percentage  = trim($_POST['percentage'] ?? '0');

        if ($code === '' || $description === '') {
            header('Location: ../discount-code.php?error=missing_fields');
            exit();
        }

        $existing = $db->getRow('SELECT id FROM discount_code WHERE code = ?', [$code]);
        if ($existing) {
            header('Location: ../discount-code.php?error=duplicate_code');
            exit();
        }

        $db->insertRow(
            'INSERT INTO discount_code (code, description, percentage) VALUES (?, ?, ?)',
            [$code, $description, $percentage]
        );
        header('Location: ../discount-code.php?success=created');
        exit();

    // ── UPDATE ──────────────────────────────────────────────
    case 'update':
        $id          = intval($_POST['id'] ?? 0);
        $code        = trim($_POST['code'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $percentage  = trim($_POST['percentage'] ?? '0');

        if ($id <= 0 || $code === '' || $description === '') {
            header('Location: ../discount-code.php?error=missing_fields');
            exit();
        }

        // Check duplicate code excluding self
        $existing = $db->getRow('SELECT id FROM discount_code WHERE code = ? AND id != ?', [$code, $id]);
        if ($existing) {
            header('Location: ../discount-code.php?error=duplicate_code');
            exit();
        }

        $db->updateRow(
            'UPDATE discount_code SET code = ?, description = ?, percentage = ? WHERE id = ?',
            [$code, $description, $percentage, $id]
        );
        header('Location: ../discount-code.php?success=updated');
        exit();

    // ── DELETE ──────────────────────────────────────────────
    case 'delete':
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) {
            header('Location: ../discount-code.php?error=invalid_id');
            exit();
        }
        $db->deleteRow('DELETE FROM discount_code WHERE id = ?', [$id]);
        header('Location: ../discount-code.php?success=deleted');
        exit();

    default:
        header('Location: ../discount-code.php');
        exit();
}
