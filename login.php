<?php
session_start();
include("config/db.php");

// If already logged in, redirect to dashboard
if (isset($_SESSION['id'])) {
    header('Location: dashboard.php');
    exit();
}

$error = '';

if (isset($_POST['login'])) {
    $username = mysqli_real_escape_string($conn, trim($_POST['username']));
    $password = trim($_POST['password']);

    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password';
    } else {
        // Query to check if user exists (by username or email)
        $query = "SELECT * FROM users WHERE username = '$username' OR email = '$username' LIMIT 1";
        $result = mysqli_query($conn, $query);

        if (mysqli_num_rows($result) == 1) {
            $user = mysqli_fetch_assoc($result);

            // Direct password comparison (no hashing)
            if ($password === $user['password']) {
                // Set session variables
                $_SESSION['id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];

                // Redirect to dashboard
                header('Location: dashboard.php');
                exit();
            } else {
                $error = 'Invalid username or password';
            }
        } else {
            $error = 'Invalid username or password';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .left-img{
            display:flex;
            width:70%;
        }

        .left-img img{
            width : 100%;
            height:600px;
        }

        .login-container {
            background: white;
            border-radius: 12px;
            width: 100%;
            max-width: 420px;
            padding: 20px 15px;
            animation: slideUp 0.5s ease-out;
        }

        .login-img{
            display:flex;
            justify-content: center;
            align-items: center;
            width:100%;
            margin-bottom: 10px;
        }

        .login-img img{
            width:50%;
        }

        .login-header {
            text-align: center;
            margin-bottom: 15px;
        }

        .login-header h1 {
            color: #1e3a8a;
            font-size: 28px;
            margin-bottom: 8px;
        }

        .login-header p {
            color: #64748b;
            font-size: 14px;
        }

        .admin-badge {
            display: inline-block;
            background: #fef3c7;
            color: #92400e;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            color: #334155;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 8px;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s;
            outline: none;
        }

        .form-control:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .error-message {
            background: #fee2e2;
            border: 1px solid #fecaca;
            color: #dc2626;
            padding: 12px 15px;
            border-radius: 8px;
            font-size: 14px;
            margin-bottom: 20px;
            animation: shake 0.5s;
        }

        @keyframes shake {
            0%, 100% {
                transform: translateX(0);
            }
            25% {
                transform: translateX(-10px);
            }
            75% {
                transform: translateX(10px);
            }
        }

        .btn-login {
            width: 100%;
            padding: 13px;
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
            border: none;
            border-radius: 8px;
            color: white;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(30, 58, 138, 0.4);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .back-link {
            text-align: center;
            margin-top: 20px;
        }

        .back-link a {
            color: #3b82f6;
            text-decoration: none;
            font-size: 14px;
        }

        .back-link a:hover {
            text-decoration: underline;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Mobile Responsiveness */
        @media (max-width: 480px) {
            body {
                padding: 15px;
            }

            .login-container {
                padding: 30px 25px;
            }

            .login-header h1 {
                font-size: 24px;
            }

            .form-control {
                padding: 11px 14px;
                font-size: 14px;
            }

            .btn-login {
                padding: 12px;
                font-size: 14px;
            }
        }

        

    </style>
</head>

<body>

    <div class="left-img">
        <img src="uploads/image.png" alt="">
    </div>
    <div class="login-container">
        <div class="login-img">
            <img src="uploads/FinalLogos.png" alt="">
        </div>

        <div class="login-header">
            <h1>Welcome Back</h1>
            <p>Login to your account</p>
        </div>

        <?php if ($error): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Username</label>
                <input
                    type="text"
                    id="username"
                    name="username"
                    class="form-control"
                    placeholder="Enter your username or email"
                    required
                    autocomplete="username"
                    value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    class="form-control"
                    placeholder="Enter your password"
                    required
                    autocomplete="current-password">
            </div>

            <button type="submit" name="login" class="btn-login">
                Log In 
            </button>
        </form>

        <div class="back-link">
            <a href="register.php">← New User? Register here</a>
        </div>
    </div>
</body>

</html>