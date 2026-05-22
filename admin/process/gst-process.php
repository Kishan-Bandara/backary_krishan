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
        $code            = trim($_POST['Code'] ?? '');
        $description     = trim($_POST['CodeDescription'] ?? '');
        $gstPercentage   = trim($_POST['GSTPercentage'] ?? '0');

        if ($code === '' || $description === '') {
            header('Location: ../gst-maintenance.php?error=missing_fields');
            exit();
        }

        // Check duplicate code
        $existing = $db->getRow('SELECT id FROM DST_Code WHERE Code = ?', [$code]);
        if ($existing) {
            header('Location: ../gst-maintenance.php?error=duplicate_code');
            exit();
        }

        $db->insertRow(
            'INSERT INTO DST_Code (Code, CodeDescription, GSTPercentage) VALUES (?, ?, ?)',
            [$code, $description, $gstPercentage]
        );
        header('Location: ../gst-maintenance.php?success=created');
        exit();

    // ── UPDATE ──────────────────────────────────────────────
    case 'update':
        $id              = intval($_POST['id'] ?? 0);
        $code            = trim($_POST['Code'] ?? '');
        $description     = trim($_POST['CodeDescription'] ?? '');
        $gstPercentage   = trim($_POST['GSTPercentage'] ?? '0');

        if ($id <= 0 || $code === '' || $description === '') {
            header('Location: ../gst-maintenance.php?error=missing_fields');
            exit();
        }

        // Check duplicate code (exclude self)
        $existing = $db->getRow('SELECT id FROM DST_Code WHERE Code = ? AND id != ?', [$code, $id]);
        if ($existing) {
            header('Location: ../gst-maintenance.php?error=duplicate_code');
            exit();
        }

        $db->updateRow(
            'UPDATE DST_Code SET Code = ?, CodeDescription = ?, GSTPercentage = ? WHERE id = ?',
            [$code, $description, $gstPercentage, $id]
        );
        header('Location: ../gst-maintenance.php?success=updated');
        exit();

    // ── DELETE ──────────────────────────────────────────────
    case 'delete':
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) {
            header('Location: ../gst-maintenance.php?error=invalid_id');
            exit();
        }
        $db->deleteRow('DELETE FROM DST_Code WHERE id = ?', [$id]);
        header('Location: ../gst-maintenance.php?success=deleted');
        exit();

    default:
        header('Location: ../gst-maintenance.php');
        exit();
}
