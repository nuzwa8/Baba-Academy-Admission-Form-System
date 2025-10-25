<?php
/**
 * admin.php
 * Baba Academy Admission System - Admin Dashboard
 * Phase 4 - PHP Logic and Data Presentation
 */

// Include necessary configurations and helpers
require_once 'includes/config.php';

// --- Simple Access Control (Recommended: Implement a proper session/login system later) ---
/*
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // header('Location: login.php'); // Redirect to a login page
    // exit;
}
*/

$admissions = [];
$total_admissions = 0;
$total_fee_collected = 0.0;
$total_balance_due = 0.0;
$message = '';

// --- Core Data Loading and Processing ---

try {
    // 1. Load Data from JSON file
    if (file_exists(DATA_FILE)) {
        $json_data = file_get_contents(DATA_FILE);
        $admissions = json_decode($json_data, true);
        
        if (!is_array($admissions)) {
            $admissions = []; // If decoding failed or file corrupted
        }
    }
    
    // Reverse the array to show the newest admissions first
    $admissions = array_reverse($admissions);

    // 2. Calculate Statistics and Days Remaining
    $total_admissions = count($admissions);

    foreach ($admissions as $key => $record) {
        
        // Calculate Statistics
        $total_fee_collected += (float)($record['amount_paid'] ?? 0);
        $total_balance_due += (float)($record['remaining_balance'] ?? 0);
        
        // Calculate Days Remaining (Countdown)
        $remaining_days = 'N/A';
        $payment_date = $record['next_payment_date'] ?? null;
        
        if ($payment_date) {
            try {
                $today = new DateTime();
                $next_payment = new DateTime($payment_date);
                
                if ($next_payment > $today) {
                    $diff = $today->diff($next_payment);
                    $remaining_days = $diff->days;
                } else if ($next_payment < $today) {
                    $remaining_days = 'Overdue';
                } else {
                    $remaining_days = 'Today';
                }
                
            } catch (Exception $e) {
                // Date format error
                $remaining_days = 'Error';
            }
        }
        
        // Add calculated value back to the array for display
        $admissions[$key]['days_remaining'] = $remaining_days;
    }
    
} catch (Exception $e) {
    $message = ['type' => 'error', 'text' => 'An error occurred while loading admission data: ' . $e->getMessage()];
    $admissions = [];
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Baba Academy</title>
    <link rel="stylesheet" href="assets/css/admin.css"> 
    <link rel="stylesheet" href="assets/css/style.css"> 
</head>
<body class="admin-body">
    <header class="admin-header">
        <div class="container">
            <h1>🎓 Baba Academy Admin Dashboard</h1>
            <nav class="admin-nav">
                <a href="index.php">Admission Form</a>
                </nav>
        </div>
    </header>

    <main class="container admin-main">
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?= $message['type'] ?>" role="alert">
                <?= $message['text'] ?>
            </div>
        <?php endif; ?>

        <h2>📊 System Overview</h2>
        <div class="stats-cards-container">
            <div class="stat-card total-admissions">
                <h3>Total Admissions</h3>
                <p><?= formatCurrency($total_admissions) ?></p>
            </div>
            <div class="stat-card total-collected">
                <h3>Total Paid</h3>
                <p><?= formatCurrency($total_fee_collected) ?> PKR</p>
            </div>
            <div class="stat-card total-due">
                <h3>Remaining Balance</h3>
                <p><?= formatCurrency($total_balance_due) ?> PKR</p>
            </div>
        </div>

        <h2 style="margin-top: 30px;">📋 Student Admission List (<?= $total_admissions ?> Records)</h2>

        <?php if (empty($admissions)): ?>
            <div class="alert alert-info">
                No admission records found yet.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="admission-table" id="adminTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Student Name</th>
                            <th>Course</th>
                            <th>Fixed Fee</th>
                            <th>Paid Amount</th>
                            <th>Remaining Balance</th>
                            <th>Next Payment Date</th>
                            <th>Days Remaining</th>
                            <th>Screenshot</th>
                            <th>Date Submitted</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $counter = 1;
                        foreach ($admissions as $record): 
                            $is_overdue = ($record['days_remaining'] === 'Overdue');
                            $is_paid_off = ($record['remaining_balance'] <= 0.01); // Handle float comparison
                        ?>
                        <tr class="<?= $is_overdue ? 'overdue' : '' ?> <?= $is_paid_off ? 'paid-off' : '' ?>">
                            <td><?= $counter++ ?></td>
                            <td data-label="Student Name"><?= htmlspecialchars($record['student_name'] ?? 'N/A') ?></td>
                            <td data-label="Course"><?= htmlspecialchars($record['course_name_en'] ?? 'N/A') ?></td>
                            <td data-label="Fixed Fee" data-sort-value="<?= $record['fixed_fee'] ?? 0 ?>"><?= formatCurrency($record['fixed_fee'] ?? 0) ?> PKR</td>
                            <td data-label="Paid Amount" data-sort-value="<?= $record['amount_paid'] ?? 0 ?>"><?= formatCurrency($record['amount_paid'] ?? 0) ?> PKR</td>
                            <td data-label="Remaining Balance" data-sort-value="<?= $record['remaining_balance'] ?? 0 ?>">
                                <?= formatCurrency($record['remaining_balance'] ?? 0) ?> PKR
                            </td>
                            <td data-label="Next Payment Date" data-sort-value="<?= strtotime($record['next_payment_date'] ?? 0) ?>">
                                <?= htmlspecialchars($record['next_payment_date'] ?? 'N/A') ?>
                            </td>
                            <td data-label="Days Remaining" class="days-remaining">
                                <?= htmlspecialchars($record['days_remaining']) ?>
                            </td>
                            <td data-label="Screenshot">
                                <?php if (!empty($record['screenshot_path'])): ?>
                                    <a href="<?= htmlspecialchars($record['screenshot_path']) ?>" target="_blank" class="screenshot-link">View File</a>
                                <?php else: ?>
                                    N/A
                                <?php endif; ?>
                            </td>
                            <td data-label="Date Submitted"><?= date('Y-m-d', strtotime($record['timestamp'] ?? 'now')) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </main>

    <script src="assets/js/admin.js"></script>

</body>
</html>
