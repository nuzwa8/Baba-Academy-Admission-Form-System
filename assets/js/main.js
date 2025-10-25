/**
 * assets/js/main.js
 * Baba Academy Admission Form System - Client-Side Logic
 * Phase 2 & 5 - Fee Calculation, Validation, and File Upload
 */

document.addEventListener('DOMContentLoaded', () => {
    // -------------------------------------------
    // 1. Element References
    // -------------------------------------------
    const admissionForm = document.getElementById('admission_form');
    const courseSelect = document.getElementById('course_id');
    const totalFeeDisplay = document.getElementById('total_fee');
    const amountPaidInput = document.getElementById('amount_paid');
    const remainingBalanceDisplay = document.getElementById('remaining_balance');
    const nextPaymentDateInput = document.getElementById('next_payment_date');
    const submitButton = document.getElementById('submit_application');
    
    // File Upload Elements
    const uploadArea = document.getElementById('upload_area');
    const screenshotInput = document.getElementById('screenshot_path'); // Hidden File Input
    const filePreview = document.getElementById('file_preview');

    // Fee Data (Embedded from PHP/config.php)
    const feesDataElement = document.getElementById('fees_data');
    const FEES_DATA = feesDataElement ? JSON.parse(feesDataElement.textContent) : {};

    const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5MB

    // -------------------------------------------
    // 2. Fee Calculation Logic (Phase 2)
    // -------------------------------------------

    /**
     * Calculates the remaining balance and sets the next payment date.
     */
    function updateFeeSummary() {
        const selectedCourseId = courseSelect.value;
        const courseFee = FEES_DATA[selectedCourseId] || 0;
        const amountPaid = parseFloat(amountPaidInput.value) || 0;
        
        // Update Total Fee Display (Readonly field)
        totalFeeDisplay.value = courseFee.toFixed(2);
        
        // Calculate Remaining Balance
        let remainingBalance = courseFee - amountPaid;
        remainingBalance = Math.max(0, remainingBalance); // Balance cannot be negative
        
        remainingBalanceDisplay.value = remainingBalance.toFixed(2);
        
        // Update the static summary below the inputs (for visual clarity)
        document.getElementById('summary_total_fee').textContent = 'PKR ' + courseFee.toLocaleString('en-US');
        document.getElementById('summary_amount_paid').textContent = 'PKR ' + amountPaid.toLocaleString('en-US');
        document.getElementById('summary_remaining_balance').textContent = 'PKR ' + remainingBalance.toLocaleString('en-US');


        // Update Next Payment Date Logic
        if (remainingBalance > 0) {
            // If balance is remaining, set default date 1 month from now (or keep current value if already set)
            if (!nextPaymentDateInput.value) {
                const today = new Date();
                today.setMonth(today.getMonth() + 1);
                // Format date as YYYY-MM-DD
                const formattedDate = today.toISOString().split('T')[0];
                nextPaymentDateInput.value = formattedDate;
            }
        } else {
            // If paid in full, clear the next payment date
            nextPaymentDateInput.value = '';
        }
        
        validateForm(); // Re-validate on every calculation change
    }

    // Attach listeners for dynamic updates
    courseSelect.addEventListener('change', updateFeeSummary);
    amountPaidInput.addEventListener('input', updateFeeSummary);

    // Initial load calculation
    updateFeeSummary();

    // -------------------------------------------
    // 3. Form Validation (Phase 2)
    // -------------------------------------------

    /**
     * Performs basic client-side validation for required fields.
     */
    function validateField(input) {
        let isValid = true;
        const errorElement = input.closest('.form-group').querySelector('.error-message');
        
        if (input.hasAttribute('required') && input.value.trim() === '') {
            isValid = false;
        }

        if (input.id === 'amount_paid' && (parseFloat(input.value) > parseFloat(totalFeeDisplay.value))) {
            isValid = false;
        }
        
        if (isValid) {
            input.classList.remove('is-invalid');
            if (errorElement) errorElement.textContent = '';
        } else {
            input.classList.add('is-invalid');
            if (errorElement) errorElement.textContent = input.dataset.errorMsg || 'This field is required.';
        }
        return isValid;
    }

    /**
     * Runs validation on all inputs and controls the submit button state.
     */
    function validateForm() {
        let formIsValid = true;
        
        // 1. Validate Basic Info Fields
        const requiredInputs = admissionForm.querySelectorAll('[required]');
        requiredInputs.forEach(input => {
            if (!validateField(input)) {
                formIsValid = false;
            }
        });

        // 2. Validate Payment Screenshot (Only if file is required)
        if (screenshotInput.hasAttribute('required') && !screenshotInput.files.length) {
            uploadArea.classList.add('is-invalid-upload');
            formIsValid = false;
        } else if (screenshotInput.files.length) {
            uploadArea.classList.remove('is-invalid-upload');
        }

        // 3. Final Submit Button State
        submitButton.disabled = !formIsValid;
        return formIsValid;
    }

    // Attach listeners for real-time validation
    admissionForm.addEventListener('input', (e) => {
        if (e.target.hasAttribute('required')) {
            validateField(e.target);
        }
        // Also validate file upload status
        validateForm();
    });

    // Prevent submission if final validation fails (though handled by disabled button)
    admissionForm.addEventListener('submit', (e) => {
        if (!validateForm()) {
            e.preventDefault();
            alert('Please fix the errors in the form before submitting.');
        }
    });

    // -------------------------------------------
    // 4. File Upload (Drag & Drop) Logic (Phase 5)
    // -------------------------------------------

    if (uploadArea && screenshotInput) {
        
        // Function to process the file and assign it to the hidden input
        function handleFile(file) {
            if (!file) return;

            // Check Size
            if (file.size > MAX_FILE_SIZE) {
                filePreview.innerHTML = `<p class="error-message">Error: File size exceeds 5MB limit.</p>`;
                screenshotInput.value = '';
                submitButton.disabled = true;
                return;
            }

            // Check Type
            const validTypes = ['image/jpeg', 'image/png', 'application/pdf', 'image/gif'];
            if (!validTypes.includes(file.type)) {
                filePreview.innerHTML = `<p class="error-message">Error: Invalid file type. Use JPG, PNG, GIF, or PDF.</p>`;
                screenshotInput.value = '';
                submitButton.disabled = true;
                return;
            }

            // Display file name for preview
            filePreview.innerHTML = `<p>Selected File: <strong>${file.name}</strong> (${(file.size / 1024 / 1024).toFixed(2)} MB)</p>`;
            
            // Assign the file object to the input's files property (Crucial for form submission)
            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(file);
            screenshotInput.files = dataTransfer.files;
            
            // Re-validate to enable/disable button
            validateForm(); 
            uploadArea.classList.remove('is-invalid-upload');
        }

        // A. Click to Browse functionality
        uploadArea.addEventListener('click', () => {
            screenshotInput.click();
        });

        // B. File Input Change Listener (when selected via click/browse)
        screenshotInput.addEventListener('change', (e) => {
            if (e.target.files.length > 0) {
                handleFile(e.target.files[0]);
            }
        });

        // C. Drag & Drop events (Prevent default behavior for all drag events)
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            uploadArea.addEventListener(eventName, (e) => {
                e.preventDefault();
                e.stopPropagation();
            }, false);
        });

        // Highlight effect on drag enter/over
        ['dragenter', 'dragover'].forEach(eventName => {
            uploadArea.addEventListener(eventName, () => {
                uploadArea.classList.add('highlight');
            }, false);
        });

        // Un-highlight effect on drag leave/drop
        ['dragleave', 'drop'].forEach(eventName => {
            uploadArea.addEventListener(eventName, () => {
                uploadArea.classList.remove('highlight');
            }, false);
        });

        // Handle dropped files
        uploadArea.addEventListener('drop', (e) => {
            const droppedFiles = e.dataTransfer.files;
            if (droppedFiles.length > 0) {
                handleFile(droppedFiles[0]);
            }
        }, false);
    }
    
    // Run validation on initial load to set button state
    validateForm();
});
