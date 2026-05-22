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
        $uom_name = trim($_POST['uom_name'] ?? '');

        if ($uom_name === '') {
            header('Location: ../uom-maintenance.php?error=missing_fields');
            exit();
        }

        // Check duplicate name
        $existing = $db->getRow('SELECT uom_id FROM item_uom WHERE uom_name = ?', [$uom_name]);
        if ($existing) {
            header('Location: ../uom-maintenance.php?error=duplicate_name');
            exit();
        }

        $db->insertRow('INSERT INTO item_uom (uom_name) VALUES (?)', [$uom_name]);
        header('Location: ../uom-maintenance.php?success=created');
        exit();

    // ── UPDATE ──────────────────────────────────────────────
    case 'update':
        $uom_id   = intval($_POST['uom_id'] ?? 0);
        $uom_name = trim($_POST['uom_name'] ?? '');

        if ($uom_id <= 0 || $uom_name === '') {
            header('Location: ../uom-maintenance.php?error=missing_fields');
            exit();
        }

        // Check duplicate name (exclude self)
        $existing = $db->getRow('SELECT uom_id FROM item_uom WHERE uom_name = ? AND uom_id != ?', [$uom_name, $uom_id]);
        if ($existing) {
            header('Location: ../uom-maintenance.php?error=duplicate_name');
            exit();
        }

        $db->updateRow('UPDATE item_uom SET uom_name = ? WHERE uom_id = ?', [$uom_name, $uom_id]);
        header('Location: ../uom-maintenance.php?success=updated');
        exit();

    // ── DELETE ──────────────────────────────────────────────
    case 'delete':
        $uom_id = intval($_POST['uom_id'] ?? 0);
        if ($uom_id <= 0) {
            header('Location: ../uom-maintenance.php?error=invalid_id');
            exit();
        }

        // Prevent deletion if in use by any product UOM assignment
        $inUse = $db->getRow(
            'SELECT id FROM item_unit_of_measure WHERE uom_id = ? LIMIT 1',
            [$uom_id]
        );
        if ($inUse) {
            header('Location: ../uom-maintenance.php?error=in_use');
            exit();
        }

        $db->deleteRow('DELETE FROM item_uom WHERE uom_id = ?', [$uom_id]);
        header('Location: ../uom-maintenance.php?success=deleted');
        exit();

    default:
        header('Location: ../uom-maintenance.php');
        exit();
}
