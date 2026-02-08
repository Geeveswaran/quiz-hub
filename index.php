<?php
session_start();
require_once 'config/mongodb.php';
require_once 'config/colleges.php';

$login_error = '';
$register_error = '';
$register_success = '';
$active_tab = isset($_GET['tab']) && $_GET['tab'] === 'register' ? 'register' : 'login';

// Handle Login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $login_error = "Please fill in all fields.";
    } else {
        $db = getDatabase();
        $user = $db->users->findOne(['username' => $username]);

        if ($user && isset($user['password']) && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['_id'] ?? uniqid();
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['college'] = $user['college'] ?? '';
            $_SESSION['college_name'] = $user['college_name'] ?? '';
            $_SESSION['batch'] = $user['batch'] ?? '';

            if ($user['role'] === 'teacher') {
                header("Location: teacher_dashboard.php");
            } else {
                header("Location: student_dashboard.php");
            }
            exit;
        } else {
            $login_error = "Invalid username or password.";
        }
    }
    $active_tab = 'login';
}

// Handle Registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'register') {
    $username = trim($_POST['reg_username']);
    $password = $_POST['reg_password'];
    $confirm_password = $_POST['reg_confirm_password'];
    $role = $_POST['reg_role'];
    $college = $_POST['reg_college'];
    $batch = $_POST['reg_batch'];

    if (empty($username) || empty($password) || empty($confirm_password) || empty($role) || empty($college) || empty($batch)) {
        $register_error = "Please fill in all fields.";
    } elseif ($password !== $confirm_password) {
        $register_error = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $register_error = "Password must be at least 6 characters long.";
    } else {
        $db = getDatabase();
        $existingUser = $db->users->findOne(['username' => $username]);
        
        if ($existingUser) {
            $register_error = "Username already taken.";
        } else {
            $result = $db->users->insertOne([
                'username' => $username,
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'role' => $role,
                'college' => $college,
                'college_name' => getCollegeName($college),
                'batch' => $batch,
                'created_at' => date('Y-m-d H:i:s')
            ]);

            if ($result->getInsertedCount() === 1) {
                $register_success = "Registration successful! Please login with your credentials.";
                // Auto-switch to login tab after delay
                echo '<script>setTimeout(function(){ document.getElementById("login-tab").click(); }, 2000);</script>';
            } else {
                $register_error = "Registration failed. Please try again.";
            }
        }
    }
    $active_tab = 'register';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Master Hub - Home</title>
    <link rel="stylesheet" href="style.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .navbar {
            background: rgba(255, 255, 255, 0.95);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .navbar-brand {
            font-size: 1.8rem;
            font-weight: bold;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .navbar-nav {
            display: flex;
            gap: 1rem;
        }
        .hero-section {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 3rem 2rem;
            text-align: center;
            color: white;
        }
        .hero-content {
            max-width: 600px;
        }
        .hero-content h1 {
            font-size: 3.5rem;
            margin-bottom: 1rem;
            font-weight: 700;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
        }
        .hero-content p {
            font-size: 1.3rem;
            margin-bottom: 2rem;
            opacity: 0.95;
            line-height: 1.6;
        }
        .features {
            display: flex;
            gap: 2rem;
            justify-content: center;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }
        .feature-item {
            background: rgba(255, 255, 255, 0.15);
            padding: 1.5rem;
            border-radius: 8px;
            flex: 1;
            min-width: 200px;
            backdrop-filter: blur(10px);
        }
        .feature-item h3 {
            margin-bottom: 0.5rem;
            font-size: 1.2rem;
        }
        .feature-item p {
            font-size: 0.95rem;
            opacity: 0.85;
        }
        .main-container {
            display: flex;
            height: 100vh;
        }
        .left-section {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 3rem 2rem;
        }
        .right-section {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        .auth-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            width: 100%;
            max-width: 450px;
        }
        .auth-tabs {
            display: flex;
            background: #f5f5f5;
            border-bottom: 2px solid #e0e0e0;
        }
        .auth-tab {
            flex: 1;
            padding: 1rem;
            text-align: center;
            cursor: pointer;
            background: #f5f5f5;
            border: none;
            font-size: 1rem;
            font-weight: 600;
            color: #666;
            transition: all 0.3s;
        }
        .auth-tab.active {
            background: white;
            color: #667eea;
            border-bottom: 3px solid #667eea;
        }
        .auth-tab:hover {
            background: white;
        }
        .auth-content {
            padding: 2rem;
            display: none;
        }
        .auth-content.active {
            display: block;
            animation: fadeIn 0.3s ease-in;
        }
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .form-group {
            margin-bottom: 1rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
            font-weight: 500;
            font-size: 0.95rem;
        }
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            transition: border 0.3s;
        }
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        .auth-btn {
            width: 100%;
            padding: 0.85rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 0.5rem;
        }
        .auth-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }
        .auth-message {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            font-size: 0.95rem;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #f5c6cb;
        }
        .text-center {
            text-align: center;
            font-size: 0.9rem;
            color: #666;
            margin-top: 1rem;
        }
        @media (max-width: 1024px) {
            .main-container {
                flex-direction: column;
                height: auto;
            }
            .left-section {
                padding: 2rem 1rem;
            }
            .hero-content h1 {
                font-size: 2.5rem;
            }
            .hero-content p {
                font-size: 1.1rem;
            }
        }
        @media (max-width: 768px) {
            .auth-container {
                max-width: 100%;
            }
            .hero-content h1 {
                font-size: 2rem;
            }
            .features {
                flex-direction: column;
                gap: 1rem;
            }
            .feature-item {
                min-width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="navbar">
        <div class="navbar-brand">🎯 Quiz Master Hub</div>
        <div class="navbar-nav"></div>
    </div>

    <div class="main-container">
        <!-- Left Section - Hero -->
        <div class="left-section">
            <div class="hero-content">
                <h1>Welcome to Quiz Master Hub</h1>
                <p>The ultimate platform for creating, managing, and taking quizzes with your college community.</p>
                
                <div class="features">
                    <div class="feature-item">
                        <h3>📝 Create</h3>
                        <p>Build engaging quizzes in minutes</p>
                    </div>
                    <div class="feature-item">
                        <h3>📊 Analyze</h3>
                        <p>Track student progress in real-time</p>
                    </div>
                    <div class="feature-item">
                        <h3>🎓 Learn</h3>
                        <p>Study materials & instant feedback</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Section - Auth Forms -->
        <div class="right-section">
            <div class="auth-container">
                <div class="auth-tabs">
                    <button class="auth-tab <?php echo $active_tab === 'login' ? 'active' : ''; ?>" 
                            id="login-tab" onclick="switchTab('login')">🔑 Login</button>
                    <button class="auth-tab <?php echo $active_tab === 'register' ? 'active' : ''; ?>" 
                            id="register-tab" onclick="switchTab('register')">✍️ Register</button>
                </div>

                <!-- Login Form -->
                <div class="auth-content <?php echo $active_tab === 'login' ? 'active' : ''; ?>" id="login-content">
                    <?php if ($login_error): ?>
                        <div class="auth-message alert-error"><?php echo htmlspecialchars($login_error); ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="login">
                        
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" id="username" name="username" required placeholder="Enter your username">
                        </div>
                        
                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" id="password" name="password" required placeholder="Enter your password">
                        </div>

                        <button type="submit" class="auth-btn">Login to Account</button>
                    </form>
                    
                    <div class="text-center">
                        Don't have an account? <a href="#" onclick="switchTab('register'); return false;" style="color: #667eea; text-decoration: none; font-weight: 600;">Register here</a>
                    </div>
                </div>

                <!-- Register Form -->
                <div class="auth-content <?php echo $active_tab === 'register' ? 'active' : ''; ?>" id="register-content">
                    <?php if ($register_error): ?>
                        <div class="auth-message alert-error"><?php echo htmlspecialchars($register_error); ?></div>
                    <?php endif; ?>
                    <?php if ($register_success): ?>
                        <div class="auth-message alert-success"><?php echo htmlspecialchars($register_success); ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="register">
                        
                        <div class="form-group">
                            <label for="reg_username">Username</label>
                            <input type="text" id="reg_username" name="reg_username" required placeholder="Choose a username">
                        </div>

                        <div class="form-group">
                            <label for="reg_password">Password</label>
                            <input type="password" id="reg_password" name="reg_password" required placeholder="At least 6 characters">
                        </div>

                        <div class="form-group">
                            <label for="reg_confirm_password">Confirm Password</label>
                            <input type="password" id="reg_confirm_password" name="reg_confirm_password" required placeholder="Confirm your password">
                        </div>

                        <div class="form-group">
                            <label for="reg_role">I am a</label>
                            <select id="reg_role" name="reg_role" required>
                                <option value="">-- Select Role --</option>
                                <option value="student">Student</option>
                                <option value="teacher">Teacher</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="reg_college">College</label>
                            <select id="reg_college" name="reg_college" required>
                                <option value="">-- Select Your College --</option>
                                <?php foreach (getCollegeList() as $id => $name): ?>
                                    <option value="<?php echo htmlspecialchars($id); ?>">
                                        <?php echo htmlspecialchars($name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="reg_batch">Batch</label>
                            <select id="reg_batch" name="reg_batch" required>
                                <option value="">-- Select Your Batch --</option>
                                <?php for ($i = 1; $i <= 10; $i++): ?>
                                    <option value="batch<?php echo $i; ?>">Batch <?php echo $i; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <button type="submit" class="auth-btn">Create Account</button>
                    </form>
                    
                    <div class="text-center">
                        Already have an account? <a href="#" onclick="switchTab('login'); return false;" style="color: #667eea; text-decoration: none; font-weight: 600;">Login here</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function switchTab(tab) {
            // Hide all content
            document.getElementById('login-content').classList.remove('active');
            document.getElementById('register-content').classList.remove('active');
            
            // Remove active class from tabs
            document.getElementById('login-tab').classList.remove('active');
            document.getElementById('register-tab').classList.remove('active');
            
            // Show selected content
            if (tab === 'login') {
                document.getElementById('login-content').classList.add('active');
                document.getElementById('login-tab').classList.add('active');
            } else {
                document.getElementById('register-content').classList.add('active');
                document.getElementById('register-tab').classList.add('active');
            }
        }
    </script>
</body>
</html>
