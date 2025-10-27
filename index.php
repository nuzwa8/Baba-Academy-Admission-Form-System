<?php
/**
 * Academy Admission System - Student Portal
 * Created: 2025-10-25
 * Note: This file combines all PHP logic and HTML structure for Phase 1.
 */

// Include necessary configurations and helpers
require_once 'includes/config.php';

// Get available courses for the dropdown
$courses = CourseManager::getAllCourses();

$message = []; // Array to store success or error messages
$form_data = []; // Array to store submitted data for repopulating the form

// --- Form Submission Logic (PHP Backend Processing) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];

    // 1. Basic Info Validation and Sanitization
    $form_data['student_name'] = filter_input(INPUT_POST, 'student_name', FILTER_SANITIZE_STRING);
    $form_data['parent_name'] = filter_input(INPUT_POST, 'parent_name', FILTER_SANITIZE_STRING);
    $form_data['phone_number'] = filter_input(INPUT_POST, 'phone_number', FILTER_SANITIZE_STRING);
    $form_data['email'] = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $form_data['course_id'] = filter_input(INPUT_POST, 'course_id', FILTER_SANITIZE_STRING);
    $form_data['total_fee'] = filter_input(INPUT_POST, 'total_fee', FILTER_VALIDATE_FLOAT);
    $form_data['amount_paid'] = filter_input(INPUT_POST, 'amount_paid', FILTER_VALIDATE_FLOAT);
    $form_data['remaining_balance'] = filter_input(INPUT_POST, 'remaining_balance', FILTER_VALIDATE_FLOAT);
    $form_data['next_payment_date'] = filter_input(INPUT_POST, 'next_payment_date', FILTER_SANITIZE_STRING);

    // Mandatory Field Checks
    if (empty($form_data['student_name'])) { $errors[] = 'Student Name is required.'; }
    if (empty($form_data['parent_name'])) { $errors[] = 'Parent/Guardian Name is required.'; }
    if (empty($form_data['course_id'])) { $errors[] = 'Course Selection is required.'; }
    if (empty($form_data['next_payment_date']) && $form_data['remaining_balance'] > 0) { $errors[] = 'Next Payment Date is required.'; }
    
    // Format Checks
    if (!preg_match('/^\+92\s\d{3}\s\d{7}$/', $form_data['phone_number'])) { $errors[] = 'Phone Number is not in the correct format (+92 3XX XXXXXXX).'; }
    if (!filter_var($form_data['email'], FILTER_VALIDATE_EMAIL)) { $errors[] = 'A valid Email Address is required.'; }

    $selected_course = CourseManager::getCourseById($form_data['course_id']);
    
    // Fee Consistency Checks (Ensuring student hasn't manipulated the fixed fee)
    if ($selected_course && $form_data['total_fee'] != $selected_course['fee_amount']) {
          $errors[] = 'Total Fee mismatch. The fixed fee for the selected course is ' . formatCurrency($selected_course['fee_amount']) . ' PKR.';
    }
    if ($form_data['amount_paid'] < 0 || $form_data['amount_paid'] > $form_data['total_fee']) {
          $errors[] = 'Amount Paid must be a non-negative value and less than or equal to the Total Fee.';
    }
    if (abs(($form_data['total_fee'] - $form_data['amount_paid']) - $form_data['remaining_balance']) > 1) { // Floating point comparison tolerance
          $errors[] = 'The Remaining Balance calculation is incorrect.';
    }
    
    // 2. File Upload Handling
    $file_path = null;
    if (isset($_FILES['payment_screenshot']) && $_FILES['payment_screenshot']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['payment_screenshot'];
        $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $allowed_types = ['jpg', 'jpeg', 'png', 'pdf'];
        
        if (!in_array(strtolower($file_extension), $allowed_types)) {
            $errors[] = 'Invalid file type. Only JPG, PNG, and PDF are allowed.';
        }
        if ($file['size'] > MAX_FILE_SIZE) {
            $errors[] = 'File size exceeds the maximum limit of 5MB.';
        }
        
        // Move file if no errors occurred yet
        if (empty($errors)) {
            $unique_filename = uniqid('screenshot_') . '.' . $file_extension;
            $destination = UPLOAD_DIR . $unique_filename;
            
            // Move file if no errors occurred yet
        if (empty($errors)) {
            $unique_filename = uniqid('screenshot_') . '.' . $file_extension;
            $destination = UPLOAD_DIR . $unique_filename;
            
            // 1. Ensure UPLOAD_DIR exists. Use 0755 for creation (Hostinger approved).
            if (!is_dir(UPLOAD_DIR)) {
                // We use @ to suppress warnings in case the directory creation fails due to security
                @mkdir(UPLOAD_DIR, 0755, true); 
            }

            // 2. Attempt to move the uploaded file.
            if (!move_uploaded_file($file['tmp_name'], $destination)) {
                $errors[] = 'File upload failed. Please verify that the uploads folder has 755 permissions.';
            } else {
                // 3. Optional: Set file permissions to 0644 for security (Hostinger recommended)
                @chmod($destination, 0644); 
                $file_path = 'uploads/' . $unique_filename; // Relative path for storage
            }
        }
        }
    } else {
          $errors[] = 'Payment Screenshot is required.';
    }

    // 3. Save Data to Database/File
    if (empty($errors)) {
        $record = [
            'timestamp' => date('Y-m-d H:i:s'),
            'id' => uniqid('adm_'),
            'student_name' => $form_data['student_name'],
            'parent_name' => $form_data['parent_name'],
            'phone_number' => $form_data['phone_number'],
            'email' => $form_data['email'],
            'course_id' => $form_data['course_id'],
            'course_name_en' => $selected_course['name_en'],
            'fixed_fee' => $form_data['total_fee'],
            'amount_paid' => $form_data['amount_paid'],
            'remaining_balance' => $form_data['remaining_balance'],
            'next_payment_date' => $form_data['next_payment_date'],
            'screenshot_path' => $file_path,
        ];

        // Ensure data directory exists
        if (!is_dir(dirname(DATA_FILE))) {
            mkdir(dirname(DATA_FILE), 0777, true);
        }

        // Save to JSON file (simple database simulation)
        $json_data = file_exists(DATA_FILE) ? json_decode(file_get_contents(DATA_FILE), true) : [];
        $json_data[] = $record;
        
        if (file_put_contents(DATA_FILE, json_encode($json_data, JSON_PRETTY_PRINT))) {
            $message = ['type' => 'success', 'text' => 'Your admission form has been submitted successfully! We will contact you shortly.'];
            $form_data = []; // Clear form on success
        } else {
            $message = ['type' => 'error', 'text' => 'Could not save the admission record. Please try again or contact support.'];
            // Cleanup: attempt to delete the uploaded file if saving data fails
            if ($file_path && file_exists(BASE_PATH . '/' . $file_path)) {
                unlink(BASE_PATH . '/' . $file_path);
            }
        }
    } else {
        $message = ['type' => 'error', 'text' => 'The form contains errors. Please correct the following issues: <ul><li>' . implode('</li><li>', $errors) . '</li></ul>'];
    }
}
// --- End Form Submission Logic ---
?>

<!DOCTYPE html>
<html lang="en" data-lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Baba Academy - Admission Form</title>
    <link rel="stylesheet" href="assets/css/style.css"> 
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Nastaliq+Urdu:wght@400;700&display=swap" rel="stylesheet">
</head>
<body>
    <header class="header">
        <div class="container">
            <a href="#" class="logo">Baba Academy</a>
            <div class="header-right">
                <div class="language-switcher">
                    <span class="lang-switcher-text" data-lang-code="ur">اردو</span>
                </div>
            </div>
        </div>
    </header>

    <section class="hero">
        <div class="container">
            <h1 data-translate="welcome.title">Welcome to Baba Academy</h1>
            <p data-translate="welcome.subtitle">Fill your admission form and secure your future</p>
        </div>
    </section>

    <main class="container">
        <div id="alert-container">
            <?php if (!empty($message)): ?>
                <div class="alert alert-<?= $message['type'] ?>" role="alert">
                    <?= $message['text'] ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="card slide-up">
            <h2 class="card-title" data-translate="form.basic_info">Basic Information</h2>
            
            <form class="admission-form" id="admissionForm" method="POST" enctype="multipart/form-data"> 
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="student_name" class="form-label required" data-translate="form.student_name">Student Name</label>
                        <input type="text" id="student_name" name="student_name" class="form-control" 
                               value="<?= htmlspecialchars($form_data['student_name'] ?? '') ?>" required>
                        <div class="error-message"></div>
                    </div>
                    <div class="form-group">
                        <label for="parent_name" class="form-label required" data-translate="form.parent_name">Parent/Guardian Name</label>
                        <input type="text" id="parent_name" name="parent_name" class="form-control" 
                               value="<?= htmlspecialchars($form_data['parent_name'] ?? '') ?>" required>
                        <div class="error-message"></div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="phone_number" class="form-label required" data-translate="form.phone">Phone Number</label>
                        <input type="tel" id="phone_number" name="phone_number" class="form-control" 
                               placeholder="+92 300 1234567" 
                               value="<?= htmlspecialchars($form_data['phone_number'] ?? '') ?>" required>
                        <div class="error-message"></div>
                    </div>
                    <div class="form-group">
                        <label for="email" class="form-label required" data-translate="form.email">Email Address</label>
                        <input type="email" id="email" name="email" class="form-control" 
                               value="<?= htmlspecialchars($form_data['email'] ?? '') ?>" required>
                        <div class="error-message"></div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="course_id" class="form-label required" data-translate="form.select_course">Select Course</label>
                    <select id="course_id" name="course_id" class="form-control" required>
                        <option value="">-- Choose a Course --</option>
                        <?php 
                        $selected_course_id = $form_data['course_id'] ?? '';
                        foreach ($courses as $course): ?>
                            <option value="<?= $course['id'] ?>" data-fee="<?= $course['fee_amount'] ?>"
                                <?= ($course['id'] === $selected_course_id) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($course['name_en']) ?> (<?= htmlspecialchars($course['name_ur']) ?>) - <?= formatCurrency($course['fee_amount']) ?> PKR
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="course-fee-display" id="courseFeeDisplay" style="display: none; margin-top: 8px;">
                        Total Fee: <span id="feeAmount"></span>
                    </div>
                    <div class="error-message"></div>
                </div>

                <div class="card payment-section">
                    <h3 class="card-title" data-translate="form.payment_info">Payment Information</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="total_fee" class="form-label required" data-translate="form.total_fee">Total Fee (PKR)</label>
                            <input type="number" id="total_fee" name="total_fee" class="form-control" 
                                   step="0.01" min="0" readonly 
                                   value="<?= htmlspecialchars($form_data['total_fee'] ?? '') ?>">
                            <div class="error-message"></div>
                        </div>
                        <div class="form-group">
                            <label for="amount_paid" class="form-label" data-translate="form.amount_paid">Amount Paid (PKR)</label>
                            <input type="number" id="amount_paid" name="amount_paid" class="form-control" 
                                   step="0.01" min="0" 
                                   value="<?= htmlspecialchars($form_data['amount_paid'] ?? 0) ?>">
                            <div class="error-message"></div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="remaining_balance" class="form-label" data-translate="form.remaining_balance">Remaining Balance (PKR)</label>
                            <input type="number" id="remaining_balance" name="remaining_balance" class="form-control" 
                                   step="0.01" min="0" readonly 
                                   value="<?= htmlspecialchars($form_data['remaining_balance'] ?? '') ?>">
                            <div class="error-message"></div>
                        </div>
                        <div class="form-group">
                            <label for="next_payment_date" class="form-label required" data-translate="form.next_payment">Next Payment Date</label>
                            <input type="date" id="next_payment_date" name="next_payment_date" class="form-control" 
                                   min="<?= date('Y-m-d') ?>" 
                                   value="<?= htmlspecialchars($form_data['next_payment_date'] ?? '') ?>" required>
                            <div class="error-message"></div>
                        </div>
                    </div>

                    <div class="payment-summary">
                        <div class="payment-item"><span>Total Fee:</span> <span id="summary_total_fee">PKR 0</span></div>
                        <div class="payment-item"><span>Amount Paid:</span> <span id="summary_amount_paid">PKR 0</span></div>
                        <div class="payment-item total"><span>Remaining Balance:</span> <span id="summary_remaining_balance">PKR 0</span></div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label required" data-translate="form.upload_screenshot">Upload Screenshot</label>
                    <div class="upload-area" id="upload_area"> <input type="file" id="screenshot_path" name="screenshot" accept=".jpg,.jpeg,.png,.pdf" style="display: none;" required>
                        <div class="upload-icon">📄</div>
                        <p>Drag & drop your  screenshot here, or click to browse</p>
                        <small>Maximum file size: 5MB. Accepted formats: JPG, PNG, PDF</small>
                    </div>
                    <div class="file-preview" id="file_preview"></div> <div class="error-message"></div>
                </div>

                <div class="form-group submit-group">
                    <button type="submit" class="btn btn-primary" id="submit_application"> <span data-translate="form.submit">Submit Application</span>
                    </button>
                </div>
            </form>
        </div>
    </main>

    <footer>
        <div class="container text-center">
            <p>&copy; 2025 Baba Academy. All rights reserved.</p>
        </div>
    </footer>

    <script src="assets/js/main.js"></script> 
    
    <script>
        // Data embedded for main.js to use
        const feesDataElement = document.createElement('script');
        feesDataElement.id = 'fees_data';
        feesDataElement.type = 'application/json';
        
        // Transform the course data array into a fee mapping object {id: fee_amount}
        const feesMap = {};
        <?php foreach ($courses as $course): ?>
            feesMap['<?= $course['id'] ?>'] = <?= $course['fee_amount'] ?>;
        <?php endforeach; ?>

        feesDataElement.textContent = JSON.stringify(feesMap);
        document.body.appendChild(feesDataElement);
    </script>

    <script>
        // Note: The logic below is largely redundant since main.js handles the calculation now, 
        // but kept for reference consistency.
        const courseSelectLegacy = document.getElementById('course_id');
        const courseFeeDisplayLegacy = document.getElementById('courseFeeDisplay');
        const feeAmountLegacy = document.getElementById('feeAmount');

        function updateLegacyFeeDisplay() {
            const selectedOption = courseSelectLegacy.options[courseSelectLegacy.selectedIndex];
            const fee = selectedOption.getAttribute('data-fee');
            if (fee) {
                feeAmountLegacy.textContent = 'PKR ' + parseFloat(fee).toLocaleString('en-US');
                courseFeeDisplayLegacy.style.display = 'block';
            } else {
                courseFeeDisplayLegacy.style.display = 'none';
            }
        }
        courseSelectLegacy.addEventListener('change', updateLegacyFeeDisplay);
        updateLegacyFeeDisplay(); // Initial display update
    </script>
</body>
</html>
