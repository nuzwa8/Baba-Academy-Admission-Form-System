/**
 * assets/js/main.js
 * Baba Academy Admission Form System - Client-Side Interactivity and Validation
 * Phase 2 - JavaScript Logic
 */

// Define a global object for the Academy System to hold functions and variables
window.academySystem = (function() {
    // --- Constants and Elements ---
    const form = document.getElementById('admissionForm');
    const courseSelect = document.getElementById('course_id');
    const totalFeeInput = document.getElementById('total_fee');
    const amountPaidInput = document.getElementById('amount_paid');
    const remainingBalanceInput = document.getElementById('remaining_balance');
    const feeDisplayDiv = document.getElementById('courseFeeDisplay');
    const feeAmountSpan = document.getElementById('feeAmount');
    const uploadArea = document.getElementById('uploadArea');
    const fileInput = document.getElementById('payment_screenshot');
    const filePreview = document.getElementById('filePreview');
    const submitButton = document.getElementById('submitButton');
    
    // Max file size in bytes (must match PHP setting: 5MB)
    const MAX_FILE_SIZE = 5 * 1024 * 1024; 

    // --- Helper Functions ---

    /**
     * فارمیٹ کرنسی (PKR) کے لیے ہیلپر فنکشن
     * @param {number} amount
     * @returns {string} Formatted currency string
     */
    function formatCurrency(amount) {
        if (isNaN(amount)) return 'PKR 0';
        return 'PKR ' + amount.toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }

    /**
     * بقایا رقم کا حساب لگاتا ہے اور اسے ڈسپلے کرتا ہے
     */
    function calculateRemainingBalance() {
        const totalFee = parseFloat(totalFeeInput.value) || 0;
        const amountPaid = parseFloat(amountPaidInput.value) || 0;
        
        // حساب (Calculation)
        let remainingBalance = totalFee - amountPaid;
        remainingBalance = Math.max(0, remainingBalance); // Balance cannot be negative

        // ان پٹ فیلڈز کو اپ ڈیٹ کرنا
        remainingBalanceInput.value = remainingBalance.toFixed(2);

        // سمری کو اپ ڈیٹ کرنا
        updatePaymentSummary(totalFee, amountPaid, remainingBalance);
    }
    
    /**
     * کورس منتخب ہونے پر ٹوٹل فیس کو ان پٹ فیلڈز میں سیٹ کرتا ہے
     */
    function updateCourseFeeDisplay() {
        const selectedOption = courseSelect.options[courseSelect.selectedIndex];
        const fee = selectedOption.dataset.fee ? parseFloat(selectedOption.dataset.fee) : 0;
        
        if (fee > 0) {
            // ان پٹ فیلڈ اور ڈسپلے اپ ڈیٹ کریں
            totalFeeInput.value = fee.toFixed(2);
            feeAmountSpan.textContent = formatCurrency(fee);
            feeDisplayDiv.style.display = 'block';
        } else {
            totalFeeInput.value = '';
            feeDisplayDiv.style.display = 'none';
        }

        // بیلنس کا دوبارہ حساب لگائیں
        calculateRemainingBalance();
    }
    
    /**
     * ادائیگی کی سمری (Payment Summary) کو اپ ڈیٹ کرتا ہے
     */
    function updatePaymentSummary(total, paid, remaining) {
        const percentage = total > 0 ? (paid / total) * 100 : 0;
        const progressBar = document.getElementById('paymentProgress');
        const percentageText = document.getElementById('paymentPercentage');
        
        document.getElementById('summaryTotalFee').textContent = formatCurrency(total);
        document.getElementById('summaryAmountPaid').textContent = formatCurrency(paid);
        document.getElementById('summaryRemainingBalance').textContent = formatCurrency(remaining);
        
        // پروگریس بار (Progress Bar) کو اپ ڈیٹ کرنا
        progressBar.style.width = percentage.toFixed(1) + '%';
        progressBar.style.backgroundColor = percentage >= 100 ? '#4CAF50' : '#8A2BE2'; // مکمل ہونے پر سبز رنگ
        percentageText.textContent = percentage.toFixed(1) + '%';
    }

    // --- Validation Logic ---
    
    /**
     * ان پٹ فیلڈ کی توثیق کرتا ہے اور ایرر میسج ڈسپلے کرتا ہے
     * @param {HTMLElement} inputField
     * @param {string} errorMessage
     * @returns {boolean} True if valid, False otherwise
     */
    function validateField(inputField, customValidator = null) {
        const value = inputField.value.trim();
        const errorDiv = inputField.closest('.form-group').querySelector('.error-message');
        let isValid = true;
        let message = '';

        if (inputField.hasAttribute('required') && value === '') {
            isValid = false;
            message = 'This field is required.';
        } else if (inputField.id === 'phone_number') {
            // فون نمبر فارمیٹ کی توثیق (+92 3XX XXXXXXX)
            const phoneRegex = /^\+92\s\d{3}\s\d{7}$/;
            if (!phoneRegex.test(value)) {
                isValid = false;
                message = 'Format must be: +92 3XX XXXXXXX';
            }
        } else if (inputField.type === 'email' && !value.includes('@')) {
            isValid = false;
            message = 'Please enter a valid email address.';
        } else if (customValidator && !customValidator(value)) {
             isValid = false;
             message = 'Validation failed.'; // Custom error message from validator
        }
        
        if (!isValid) {
            inputField.classList.add('is-invalid');
            errorDiv.textContent = message;
        } else {
            inputField.classList.remove('is-invalid');
            errorDiv.textContent = '';
        }
        
        return isValid;
    }

    /**
     * مکمل فارم کی توثیق کرتا ہے (سبمٹ سے پہلے)
     * @returns {boolean} True if all fields are valid
     */
    function validateForm() {
        let allValid = true;
        
        // 1. فیلڈز کی توثیق
        const fieldsToValidate = [
            document.getElementById('student_name'),
            document.getElementById('parent_name'),
            document.getElementById('phone_number'),
            document.getElementById('email'),
            courseSelect,
            document.getElementById('next_payment_date')
        ];

        fieldsToValidate.forEach(field => {
            if (!validateField(field)) {
                allValid = false;
            }
        });

        // 2. فائل اور فیس کی توثیق
        if (!validateFile()) {
            allValid = false;
        }

        // فیس کی خصوصی توثیق
        const totalFee = parseFloat(totalFeeInput.value) || 0;
        const amountPaid = parseFloat(amountPaidInput.value) || 0;
        
        if (totalFee <= 0 && courseSelect.value !== "") {
            allValid = false;
            courseSelect.closest('.form-group').querySelector('.error-message').textContent = 'Course fee is missing or zero.';
        } else if (amountPaid > totalFee) {
            allValid = false;
            amountPaidInput.classList.add('is-invalid');
            amountPaidInput.closest('.form-group').querySelector('.error-message').textContent = 'Amount Paid cannot exceed Total Fee.';
        } else {
             amountPaidInput.classList.remove('is-invalid');
             amountPaidInput.closest('.form-group').querySelector('.error-message').textContent = '';
        }

        return allValid;
    }
    
    /**
     * فائل اپ لوڈ کی توثیق کرتا ہے
     */
    function validateFile() {
        const errorDiv = uploadArea.closest('.form-group').querySelector('.error-message');
        const file = fileInput.files[0];
        let isValid = true;
        let message = '';

        if (fileInput.hasAttribute('required') && !file) {
            isValid = false;
            message = 'Payment screenshot is required.';
        } else if (file && file.size > MAX_FILE_SIZE) {
            isValid = false;
            message = 'File size (Maximum 5MB) exceeded. Current size: ' + (file.size / 1024 / 1024).toFixed(2) + ' MB.';
        }
        // PHP will handle type check for security

        if (!isValid) {
            uploadArea.classList.add('is-invalid-upload');
            errorDiv.textContent = message;
        } else {
            uploadArea.classList.remove('is-invalid-upload');
            errorDiv.textContent = '';
        }
        return isValid;
    }
    
    // --- Event Handlers (DOM Ready) ---
    function initialize() {
        // 1. Course Selection / Fee Calculation
        courseSelect.addEventListener('change', updateCourseFeeDisplay);
        amountPaidInput.addEventListener('input', calculateRemainingBalance);
        
        // Initial call to set the total fee if course was selected on form error re-population
        updateCourseFeeDisplay(); 

        // 2. File Upload Drag & Drop Functionality
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            uploadArea.addEventListener(eventName, preventDefaults, false);
            document.body.addEventListener(eventName, preventDefaults, false);
        });
        ['dragenter', 'dragover'].forEach(eventName => {
            uploadArea.addEventListener(eventName, () => uploadArea.classList.add('highlight'), false);
        });
        ['dragleave', 'drop'].forEach(eventName => {
            uploadArea.addEventListener(eventName, () => uploadArea.classList.remove('highlight'), false);
        });

        uploadArea.addEventListener('drop', handleDrop, false);
        uploadArea.addEventListener('click', () => fileInput.click(), false);
        fileInput.addEventListener('change', handleFileSelect, false);
        
        // 3. Form Submission
        form.addEventListener('submit', function(e) {
            // سبمٹ بٹن کو غیر فعال کریں
            submitButton.disabled = true;
            submitButton.textContent = 'Submitting...';
            
            if (!validateForm()) {
                e.preventDefault(); // اگر توثیق کامیاب نہیں ہوتی تو سبمیشن کو روکیں
                alert('Please correct the highlighted errors before submitting the form.');
                submitButton.disabled = false;
                submitButton.textContent = 'Submit Application';
            }
            // اگر توثیق کامیاب ہو جاتی ہے، تو فارم کو PHP کی طرف جانے دیں
        });
        
        // 4. Real-time input validation (on blur)
        document.querySelectorAll('.form-control').forEach(input => {
             // Readonly fields ko skip karen
            if (!input.hasAttribute('readonly')) {
                 input.addEventListener('blur', () => {
                     // File input aur select fields ko skip karen, unka change event alag se handle hota hai
                    if (input.type !== 'file' && input.tagName !== 'SELECT') {
                        validateField(input);
                    }
                });
            }
        });
    }

    // Drag & Drop Helpers
    function preventDefaults (e) {
        e.preventDefault();
        e.stopPropagation();
    }
    
    function handleDrop(e) {
        let dt = e.dataTransfer;
        let files = dt.files;
        fileInput.files = files; // files کو ان پٹ فیلڈ میں سیٹ کریں
        handleFileSelect();
    }
    
    function handleFileSelect() {
        const file = fileInput.files[0];
        filePreview.innerHTML = ''; // پرانا پریویو صاف کریں

        if (file && validateFile()) {
            const fileName = document.createElement('p');
            fileName.className = 'file-name';
            fileName.innerHTML = `✅ **${file.name}** (${(file.size / 1024 / 1024).toFixed(2)} MB) ready to upload.`;
            
            // اگر تصویر ہے تو پریویو دکھائیں
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.style.maxWidth = '100px';
                    img.style.maxHeight = '100px';
                    img.style.borderRadius = '5px';
                    img.style.marginTop = '10px';
                    filePreview.appendChild(img);
                };
                reader.readAsDataURL(file);
            }
            filePreview.appendChild(fileName);
            uploadArea.classList.add('file-added');
        } else {
             filePreview.innerHTML = '';
             uploadArea.classList.remove('file-added');
        }
    }


    // جب DOM لوڈ ہو جائے تو انیشیئلائز کریں
    document.addEventListener('DOMContentLoaded', initialize);

    // وہ فنکشنز جو بیرونی طور پر استعمال ہو سکتے ہیں (PHP ان لائن سکرپٹ میں)
    return {
        formatCurrency: formatCurrency,
        calculateRemainingBalance: calculateRemainingBalance,
        validateForm: validateForm
    };

})(); // Immediate Invoked Function Expression (IIFE)
