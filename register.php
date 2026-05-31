<?php
// register.php
session_start();

// Include Composer autoloader for PHPMailer
require_once 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once 'databse/database.php';
require_once 'models/User.php';

$database = new Database();
$db = $database->getConnection(); 
$userModel = new User($db);

// Input sanitization function to prevent XSS and code injection
function sanitize_input($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

$show_modal = false;
$modal_type = '';
$modal_title = '';
$modal_message = '';

$show_verify_modal = false;
$verify_error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? 'register';

    if ($action === 'register') {
        // Sanitize inputs to prevent code injection
        $first_name = sanitize_input($_POST['first_name'] ?? '');
        $last_name = sanitize_input($_POST['last_name'] ?? '');
        $email = sanitize_input($_POST['email'] ?? '');
        $phone = sanitize_input($_POST['phone'] ?? '');
        // Passwords should NOT be sanitized (stripped of chars) as it messes up complex passwords, 
        // they are protected against SQLi by PDO and XSS by not being echoed.
        $password = $_POST['password'] ?? ''; 
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (empty($first_name) || empty($last_name) || empty($email) || empty($password)) {
            $show_modal = true;
            $modal_type = 'error';
            $modal_title = 'Registration Failed';
            $modal_message = 'Please fill in all required fields.';
        } elseif ($password !== $confirm_password) {
            $show_modal = true;
            $modal_type = 'error';
            $modal_title = 'Registration Failed';
            $modal_message = 'Passwords do not match.';
        } else {
            try {
                // Check if email already exists using User model
                if ($userModel->emailExists($email)) {
                    $show_modal = true;
                    $modal_type = 'error';
                    $modal_title = 'Registration Failed';
                    $modal_message = 'An account with this email already exists.';
                } else {
                    // Generate a 6-digit verification code
                    $verification_code = sprintf("%06d", mt_rand(1, 999999));

                    // Store pending user details in session
                    $_SESSION['pending_user'] = [
                        'first_name' => $first_name,
                        'last_name' => $last_name,
                        'email' => $email,
                        'phone' => $phone,
                        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                        'verification_code' => $verification_code
                    ];

                    // Setup PHPMailer
                    $mail = new PHPMailer(true);
                    try {
                        // Server settings (Use your own SMTP credentials here, e.g., Gmail App Passwords or Mailtrap)
                        $mail->isSMTP();
                        $mail->Host       = 'smtp.gmail.com'; 
                        $mail->SMTPAuth   = true;
                        $mail->Username   = 'bongbongmarcuz1@gmail.com'; // REPLACE THIS
                        $mail->Password   = 'rxne ucwq uraf fien'; // REPLACE THIS
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                        $mail->Port       = 587;
                        
                        // Recipients
                        $mail->setFrom('noreply@pearlz.com', 'Pearlz Exclusives');
                        $mail->addAddress($email, $first_name . ' ' . $last_name);

                        // Content
                        $mail->isHTML(true);
                        $mail->Subject = 'Pearlz Registration - Verification Code';
                        $mail->Body    = '<div style="font-family: sans-serif; padding: 20px;"><h2>Welcome to Pearlz!</h2><p>Your verification code is: <strong style="font-size: 24px; color: #db2777;">' . $verification_code . '</strong></p></div>';
                        $mail->AltBody = "Welcome to Pearlz!\n\nYour verification code is: " . $verification_code;

                        $mail->send();
                        $show_verify_modal = true;
                    } catch (Exception $e) {
                        $show_modal = true;
                        $modal_type = 'error';
                        $modal_title = 'Email Failed';
                        $modal_message = 'Verification email could not be sent. Mailer Error: ' . $mail->ErrorInfo;
                        // Clear pending user if email fails
                        unset($_SESSION['pending_user']);
                    }
                }
            } catch(PDOException $e) {
                $show_modal = true;
                $modal_type = 'error';
                $modal_title = 'Database Error';
                $modal_message = 'An error occurred while connecting to the database.';
            }
        }
    } elseif ($action === 'verify') {
        $entered_code = sanitize_input($_POST['verification_code'] ?? '');

        if (isset($_SESSION['pending_user']) && $entered_code === $_SESSION['pending_user']['verification_code']) {
            try {
                $user = $_SESSION['pending_user'];

                // Insert the new user into the database using User model
                if ($userModel->register($user['first_name'], $user['last_name'], $user['email'], $user['phone'], $user['password_hash'])) {
                    // Clear the session as it's no longer needed
                    unset($_SESSION['pending_user']);
                    
                    // Success Modal
                    $show_modal = true;
                    $modal_type = 'success';
                    $modal_title = 'Registration Successful!';
                    $modal_message = 'Your account has been created successfully. You can now log in.';
                } else {
                    $show_modal = true;
                    $modal_type = 'error';
                    $modal_title = 'Registration Failed';
                    $modal_message = 'Something went wrong while saving your account. Please try again.';
                }
            } catch(PDOException $e) {
                $show_modal = true;
                $modal_type = 'error';
                $modal_title = 'Database Error';
                $modal_message = 'An error occurred while saving your account.';
            }
        } else {
            // Code was incorrect
            $show_verify_modal = true;
            $verify_error = 'Invalid verification code. Please try again.';
        }
    } elseif ($action === 'resend') {
        // Handle "resend code" button
        if (isset($_SESSION['pending_user'])) {
            $verification_code = sprintf("%06d", mt_rand(1, 999999));
            $_SESSION['pending_user']['verification_code'] = $verification_code;
            
            $email = sanitize_input($_SESSION['pending_user']['email']);
            $first_name = sanitize_input($_SESSION['pending_user']['first_name']);
            $last_name = sanitize_input($_SESSION['pending_user']['last_name']);

            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com'; 
                $mail->SMTPAuth   = true;
                $mail->Username   = 'bongbongmarcuz1@gmail.com'; // REPLACE THIS
                $mail->Password   = 'rxne ucwq uraf fien'; // REPLACE THIS
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;
                
                $mail->setFrom('noreply@pearlz.com', 'Pearlz Exclusives');
                $mail->addAddress($email, $first_name . ' ' . $last_name);

                $mail->isHTML(true);
                $mail->Subject = 'Pearlz Registration - New Verification Code';
                $mail->Body    = '<div style="font-family: sans-serif; padding: 20px;"><p>Your new Pearlz verification code is: <strong style="font-size: 24px; color: #db2777;">' . $verification_code . '</strong></p></div>';
                $mail->AltBody = "Your new Pearlz verification code is: " . $verification_code;

                $mail->send();
                $show_verify_modal = true;
                $verify_error = 'A new code has been sent to your email.'; 
            } catch (Exception $e) {
                $show_verify_modal = true;
                $verify_error = 'Failed to resend email: ' . $mail->ErrorInfo;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | Pearlz</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&display=swap" rel="stylesheet">
    <style>
        @font-face {
            font-family: 'Amsterdam One';
            font-style: normal;
            font-weight: 400;
            src: local('Amsterdam One'), local('Amsterdam'), url('https://fonts.cdnfonts.com/s/17992/Amsterdam.woff') format('woff');
        }
        .font-logo { font-family: 'Amsterdam One', cursive; font-weight: normal; }
        body { font-family: 'DM Sans', sans-serif; }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-100 via-blue-50 to-pink-100 min-h-screen text-gray-800 antialiased flex flex-col">

    <!-- Header -->
    <header class="py-4 px-6 md:py-6 md:px-8 flex items-center justify-center bg-white/40 backdrop-blur-md sticky top-0 z-50 border-b border-white/50">
        <a href="index.php" class="block">
            <img src="images/logo.png" alt="Logo Placeholder" class="h-10 md:h-12 object-contain hover:opacity-80 transition-opacity">
        </a>
    </header>

    <main class="flex-grow flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-3xl w-full bg-white/80 backdrop-blur-xl p-8 sm:p-12 rounded-3xl shadow-2xl relative">
            
            <!-- Back Button -->
            <a href="index.php" class="absolute top-6 left-6 md:top-8 md:left-8 text-gray-500 hover:text-pink-600 transition-colors flex items-center space-x-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                <span class="text-sm font-semibold uppercase tracking-wider hidden sm:inline">Back</span>
            </a>
            <div class="text-left mb-10">
                <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mb-3 mt-6">Create an Account</h1>
                <p class="text-gray-600">Join Pearlz and discover exclusive handcrafted collections.</p>
            </div>

            <form action="register.php" method="POST" class="space-y-6">
                <input type="hidden" name="action" value="register">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-gray-700 text-sm font-semibold mb-2" for="first_name">First Name <span class="text-pink-600">*</span></label>
                        <input type="text" id="first_name" name="first_name" required value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>" class="w-full bg-white/50 border border-gray-300 rounded-full px-4 py-3 text-sm focus:outline-none focus:border-pink-400 focus:ring-1 focus:ring-pink-400 transition-all" placeholder="Jane">
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-semibold mb-2" for="last_name">Last Name <span class="text-pink-600">*</span></label>
                        <input type="text" id="last_name" name="last_name" required value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>" class="w-full bg-white/50 border border-gray-300 rounded-full px-4 py-3 text-sm focus:outline-none focus:border-pink-400 focus:ring-1 focus:ring-pink-400 transition-all" placeholder="Doe">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-gray-700 text-sm font-semibold mb-2" for="email">Email Address <span class="text-pink-600">*</span></label>
                        <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" class="w-full bg-white/50 border border-gray-300 rounded-full px-4 py-3 text-sm focus:outline-none focus:border-pink-400 focus:ring-1 focus:ring-pink-400 transition-all" placeholder="jane@example.com">
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-semibold mb-2" for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" class="w-full bg-white/50 border border-gray-300 rounded-full px-4 py-3 text-sm focus:outline-none focus:border-pink-400 focus:ring-1 focus:ring-pink-400 transition-all" placeholder="+1 (555) 000-0000">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-gray-700 text-sm font-semibold mb-2" for="password">Password <span class="text-pink-600">*</span></label>
                        <input type="password" id="password" name="password" required class="w-full bg-white/50 border border-gray-300 rounded-full px-4 py-3 text-sm focus:outline-none focus:border-pink-400 focus:ring-1 focus:ring-pink-400 transition-all" placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;">
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-semibold mb-2" for="confirm_password">Confirm Password <span class="text-pink-600">*</span></label>
                        <input type="password" id="confirm_password" name="confirm_password" required class="w-full bg-white/50 border border-gray-300 rounded-full px-4 py-3 text-sm focus:outline-none focus:border-pink-400 focus:ring-1 focus:ring-pink-400 transition-all" placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;">
                    </div>
                </div>

                <div class="pt-6">
                    <button type="submit" class="w-full bg-gradient-to-r from-blue-600 to-pink-500 text-white rounded-full py-4 font-bold uppercase tracking-widest text-sm hover:opacity-90 transition-opacity shadow-xl">
                        Create Account
                    </button>
                </div>
            </form>

            <div class="mt-8 text-center border-t border-gray-200/60 pt-6">
                <p class="text-sm text-gray-600">
                    Already have an account? 
                    <a href="index.php" class="font-semibold text-pink-600 hover:text-pink-800 transition-colors underline">Sign in here</a>
                </p>
            </div>
        </div>
    </main>

    <footer class="py-6 text-center text-sm text-gray-500 bg-white/30 backdrop-blur-md border-t border-white/50">
        &copy; <?php echo date("Y"); ?> Pearlz Exclusive. All rights reserved.
    </footer>

    <!-- Verification Modal -->
    <div id="verifyModal" class="fixed inset-0 z-[80] flex items-center justify-center bg-black/50 backdrop-blur-sm hidden opacity-0 transition-opacity duration-300">
        <div class="bg-white/95 backdrop-blur-md p-8 rounded-3xl shadow-2xl w-full max-w-sm transform scale-95 transition-transform duration-300 mx-4 text-center" id="verifyModalContent">
            
            <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-blue-100 mb-6">
                <svg class="h-8 w-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
            </div>

            <h3 class="text-2xl font-bold text-gray-900 mb-2">Verify Email</h3>
            <p class="text-gray-600 mb-6 text-sm">We've sent a 6-digit code to your email. Please enter it below.</p>
            
            <?php if ($verify_error): ?>
                <p class="text-red-500 text-sm mb-4 font-medium"><?php echo htmlspecialchars($verify_error); ?></p>
            <?php endif; ?>

            <form action="register.php" method="POST">
                <input type="hidden" name="action" value="verify">
                <input type="text" name="verification_code" required maxlength="6" class="w-full text-center tracking-[0.5em] text-2xl font-bold bg-white/50 border border-gray-300 rounded-xl px-4 py-3 focus:outline-none focus:border-pink-400 focus:ring-1 focus:ring-pink-400 transition-all mb-6" placeholder="------">
                
                <button type="submit" class="w-full bg-gradient-to-r from-blue-600 to-pink-500 text-white rounded-full py-3 font-semibold uppercase tracking-widest text-sm hover:opacity-90 transition-opacity shadow-lg mb-4">
                    Verify & Create Account
                </button>
            </form>
            
            <form action="register.php" method="POST">
                <input type="hidden" name="action" value="resend">
                <button type="submit" class="text-sm text-gray-500 hover:text-pink-600 transition-colors font-medium">
                    Didn't receive a code? Resend
                </button>
            </form>
        </div>
    </div>

    <!-- Status Modal -->
    <div id="statusModal" class="fixed inset-0 z-[70] flex items-center justify-center bg-black/50 backdrop-blur-sm hidden opacity-0 transition-opacity duration-300">
        <div class="bg-white/95 backdrop-blur-md p-8 rounded-3xl shadow-2xl w-full max-w-sm transform scale-95 transition-transform duration-300 mx-4 text-center" id="statusModalContent">
            
            <!-- Success Icon -->
            <div id="modalSuccessIcon" class="hidden mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 mb-6">
                <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            </div>

            <!-- Error Icon -->
            <div id="modalErrorIcon" class="hidden mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-red-100 mb-6">
                <svg class="h-8 w-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </div>

            <h3 class="text-2xl font-bold text-gray-900 mb-2" id="modalTitle">Status</h3>
            <p class="text-gray-600 mb-8" id="modalMessage">Message goes here.</p>
            
            <button type="button" id="closeStatusModalBtn" class="w-full bg-gray-900 text-white rounded-full py-3 font-semibold uppercase tracking-widest text-sm hover:bg-gray-800 transition-colors shadow-lg">
                Close
            </button>
        </div>
    </div>

    <script>
        // Status Modal Logic
        const statusModal = document.getElementById('statusModal');
        const statusModalContent = document.getElementById('statusModalContent');
        const closeStatusModalBtn = document.getElementById('closeStatusModalBtn');

        function openModal() {
            statusModal.classList.remove('hidden');
            setTimeout(() => {
                statusModal.classList.remove('opacity-0');
                statusModalContent.classList.remove('scale-95');
                statusModalContent.classList.add('scale-100');
            }, 10);
        }

        function closeModal() {
            statusModal.classList.add('opacity-0');
            statusModalContent.classList.remove('scale-100');
            statusModalContent.classList.add('scale-95');
            setTimeout(() => {
                statusModal.classList.add('hidden');
            }, 300);
        }

        if(closeStatusModalBtn) closeStatusModalBtn.addEventListener('click', closeModal);
        if(statusModal) statusModal.addEventListener('click', (e) => {
            if (e.target === statusModal) closeModal();
        });

        // Verification Modal Logic
        const verifyModal = document.getElementById('verifyModal');
        const verifyModalContent = document.getElementById('verifyModalContent');

        function openVerifyModal() {
            verifyModal.classList.remove('hidden');
            setTimeout(() => {
                verifyModal.classList.remove('opacity-0');
                verifyModalContent.classList.remove('scale-95');
                verifyModalContent.classList.add('scale-100');
            }, 10);
        }

        // PHP Triggers
        <?php if ($show_modal): ?>
            document.getElementById('modalTitle').textContent = <?php echo json_encode($modal_title); ?>;
            document.getElementById('modalMessage').textContent = <?php echo json_encode($modal_message); ?>;
            
            <?php if ($modal_type === 'success'): ?>
                document.getElementById('modalSuccessIcon').classList.remove('hidden');
            <?php else: ?>
                document.getElementById('modalErrorIcon').classList.remove('hidden');
            <?php endif; ?>
            
            openModal();
        <?php endif; ?>

        <?php if ($show_verify_modal): ?>
            openVerifyModal();
        <?php endif; ?>
    </script>
</body>
</html>
