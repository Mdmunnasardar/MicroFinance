<?php
session_start();
include "config/db.php";

// If already logged in, go to dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter username and password';
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            if (password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['name'] = $user['full_name'];
                
                header("Location: dashboard.php");
                exit();
            } else {
                $error = 'Invalid password';
            }
        } else {
            $error = 'User not found';
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MicroFinance - Login</title>
    
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    
    <!-- Google Font Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        /* ========================================
           RESET & BASE
        ======================================== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #0f172a;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }

        /* ========================================
           ANIMATED BACKGROUND
        ======================================== */
        .bg-animation {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 0;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 30%, #312e81 60%, #4f46e5 100%);
            background-size: 300% 300%;
            animation: gradientShift 15s ease infinite;
        }

        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* Floating Orbs */
        .orb {
            position: fixed;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.3;
            z-index: 0;
            animation: floatOrb 20s ease-in-out infinite;
        }

        .orb-1 {
            width: 400px;
            height: 400px;
            background: #4f46e5;
            top: -100px;
            right: -100px;
            animation-delay: 0s;
        }

        .orb-2 {
            width: 300px;
            height: 300px;
            background: #7c3aed;
            bottom: -50px;
            left: -50px;
            animation-delay: -5s;
        }

        .orb-3 {
            width: 200px;
            height: 200px;
            background: #06b6d4;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            animation-delay: -10s;
        }

        @keyframes floatOrb {
            0%, 100% { transform: translate(0, 0) scale(1); }
            33% { transform: translate(30px, -30px) scale(1.1); }
            66% { transform: translate(-20px, 20px) scale(0.9); }
        }

        /* ========================================
           LOGIN CONTAINER
        ======================================== */
        .login-wrapper {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 440px;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border-radius: 28px;
            padding: 48px 40px 40px;
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 
                0 25px 60px rgba(0, 0, 0, 0.5),
                inset 0 1px 0 rgba(255, 255, 255, 0.05);
            transition: all 0.3s ease;
        }

        .login-card:hover {
            box-shadow: 
                0 35px 80px rgba(0, 0, 0, 0.6),
                inset 0 1px 0 rgba(255, 255, 255, 0.08);
        }

        /* ========================================
           LOGO SECTION
        ======================================== */
        .login-header {
            text-align: center;
            margin-bottom: 36px;
        }

        .logo-wrapper {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 72px;
            height: 72px;
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            border-radius: 20px;
            margin-bottom: 16px;
            box-shadow: 0 8px 32px rgba(79, 70, 229, 0.3);
            transition: transform 0.3s ease;
        }

        .logo-wrapper:hover {
            transform: scale(1.05) rotate(-3deg);
        }

        .logo-wrapper i {
            font-size: 32px;
            color: white;
        }

        .login-header h1 {
            font-size: 26px;
            font-weight: 800;
            color: white;
            letter-spacing: -0.5px;
            margin: 0;
        }

        .login-header p {
            color: rgba(255, 255, 255, 0.4);
            font-size: 14px;
            margin: 6px 0 0;
            font-weight: 400;
        }

        /* ========================================
           ERROR MESSAGE
        ======================================== */
        .error-alert {
            background: rgba(239, 68, 68, 0.12);
            border: 1px solid rgba(239, 68, 68, 0.2);
            border-radius: 12px;
            padding: 12px 16px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: shake 0.5s ease;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            20% { transform: translateX(-8px); }
            40% { transform: translateX(8px); }
            60% { transform: translateX(-4px); }
            80% { transform: translateX(4px); }
        }

        .error-alert i {
            color: #ef4444;
            font-size: 18px;
            flex-shrink: 0;
        }

        .error-alert span {
            color: #fca5a5;
            font-size: 14px;
            font-weight: 500;
        }

        /* ========================================
           FORM
        ======================================== */
        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            color: rgba(255, 255, 255, 0.6);
            font-size: 13px;
            font-weight: 500;
            margin-bottom: 6px;
            letter-spacing: 0.3px;
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-wrapper .input-icon {
            position: absolute;
            left: 16px;
            color: rgba(255, 255, 255, 0.2);
            font-size: 16px;
            transition: color 0.3s ease;
            pointer-events: none;
        }

        .input-wrapper input {
            width: 100%;
            padding: 14px 16px 14px 48px;
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 14px;
            color: white;
            font-size: 15px;
            font-family: 'Inter', sans-serif;
            transition: all 0.3s ease;
            outline: none;
        }

        .input-wrapper input::placeholder {
            color: rgba(255, 255, 255, 0.2);
            font-weight: 400;
        }

        .input-wrapper input:focus {
            background: rgba(255, 255, 255, 0.08);
            border-color: #4f46e5;
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.15);
        }

        .input-wrapper input:focus + .input-icon,
        .input-wrapper input:focus ~ .input-icon {
            color: #4f46e5;
        }

        .input-wrapper input:-webkit-autofill {
            -webkit-box-shadow: 0 0 0 30px rgba(15, 23, 42, 0.9) inset !important;
            -webkit-text-fill-color: white !important;
        }

        /* Password Toggle */
        .toggle-password {
            position: absolute;
            right: 16px;
            background: none;
            border: none;
            color: rgba(255, 255, 255, 0.2);
            cursor: pointer;
            font-size: 16px;
            transition: color 0.3s ease;
            padding: 4px;
        }

        .toggle-password:hover {
            color: rgba(255, 255, 255, 0.5);
        }

        /* ========================================
           OPTIONS ROW
        ======================================== */
        .options-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            color: rgba(255, 255, 255, 0.4);
            font-size: 13px;
            transition: color 0.3s ease;
        }

        .remember-me:hover {
            color: rgba(255, 255, 255, 0.6);
        }

        .remember-me input[type="checkbox"] {
            width: 16px;
            height: 16px;
            accent-color: #4f46e5;
            border-radius: 4px;
            cursor: pointer;
        }

        .forgot-link {
            color: rgba(255, 255, 255, 0.3);
            font-size: 13px;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .forgot-link:hover {
            color: #4f46e5;
        }

        /* ========================================
           LOGIN BUTTON
        ======================================== */
        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            border: none;
            border-radius: 14px;
            color: white;
            font-size: 16px;
            font-weight: 600;
            font-family: 'Inter', sans-serif;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            position: relative;
            overflow: hidden;
        }

        .btn-login::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            transition: left 0.5s ease;
        }

        .btn-login:hover::before {
            left: 100%;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 40px rgba(79, 70, 229, 0.4);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .btn-login i {
            font-size: 18px;
        }

        /* ========================================
           FOOTER
        ======================================== */
        .login-footer {
            text-align: center;
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
        }

        .login-footer p {
            color: rgba(255, 255, 255, 0.2);
            font-size: 12px;
            font-weight: 400;
            letter-spacing: 0.5px;
        }

        .login-footer p span {
            color: rgba(255, 255, 255, 0.4);
        }

        /* ========================================
           RESPONSIVE
        ======================================== */
        @media (max-width: 480px) {
            .login-card {
                padding: 32px 24px 28px;
                border-radius: 20px;
            }

            .login-header h1 {
                font-size: 22px;
            }

            .logo-wrapper {
                width: 60px;
                height: 60px;
            }

            .logo-wrapper i {
                font-size: 26px;
            }

            .options-row {
                flex-direction: column;
                gap: 12px;
                align-items: flex-start;
            }

            .input-wrapper input {
                padding: 12px 16px 12px 44px;
                font-size: 14px;
            }
        }

        @media (max-width: 380px) {
            .login-card {
                padding: 24px 16px 20px;
            }
        }

        /* ========================================
           SCROLLBAR
        ======================================== */
        ::-webkit-scrollbar {
            width: 4px;
        }
        ::-webkit-scrollbar-track {
            background: transparent;
        }
        ::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
        }
    </style>
</head>
<body>

    <!-- Animated Background -->
    <div class="bg-animation"></div>
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="orb orb-3"></div>

    <!-- Login Wrapper -->
    <div class="login-wrapper">

        <div class="login-card">

            <!-- Header -->
            <div class="login-header">
                <div class="logo-wrapper">
                    <i class="fa-solid fa-building-columns"></i>
                </div>
                <h1>Welcome Back</h1>
                <p>Secure access to your microfinance dashboard</p>
            </div>

            <!-- Error Message -->
            <?php if($error): ?>
            <div class="error-alert">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
            <?php endif; ?>

            <!-- Login Form -->
            <form method="POST" action="">

                <div class="form-group">
                    <label>Username</label>
                    <div class="input-wrapper">
                        <input 
                            type="text" 
                            name="username" 
                            placeholder="Enter your username" 
                            value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                            required
                            autofocus
                        >
                        <i class="fa-regular fa-user input-icon"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <div class="input-wrapper">
                        <input 
                            type="password" 
                            name="password" 
                            id="password"
                            placeholder="Enter your password" 
                            required
                        >
                        <i class="fa-regular fa-lock input-icon"></i>
                        <button type="button" class="toggle-password" id="togglePassword">
                            <i class="fa-regular fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="options-row">
                    <label class="remember-me">
                        <input type="checkbox" name="remember">
                        Remember me
                    </label>
                    <a href="#" class="forgot-link">Forgot password?</a>
                </div>

                <button type="submit" class="btn-login">
                    <i class="fa-regular fa-right-to-bracket"></i>
                    Sign In
                </button>

            </form>

            <!-- Footer -->
            <div class="login-footer">
                <p>Powered by <span>MicroFinance System</span></p>
            </div>

        </div>

    </div>

    <!-- ========================================
       SCRIPTS
    ======================================== -->
    <script>
        // Toggle Password Visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const icon = this.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });

        // Auto-dismiss error after 5 seconds
        <?php if($error): ?>
        setTimeout(function() {
            const alert = document.querySelector('.error-alert');
            if (alert) {
                alert.style.transition = 'opacity 0.5s ease';
                alert.style.opacity = '0';
                setTimeout(function() {
                    alert.style.display = 'none';
                }, 500);
            }
        }, 5000);
        <?php endif; ?>

        // Smooth entrance animation
        document.addEventListener('DOMContentLoaded', function() {
            const card = document.querySelector('.login-card');
            card.style.opacity = '0';
            card.style.transform = 'translateY(30px)';
            
            setTimeout(function() {
                card.style.transition = 'all 0.8s cubic-bezier(0.4, 0, 0.2, 1)';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, 100);
        });
    </script>

</body>
</html>