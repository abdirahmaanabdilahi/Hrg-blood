<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password | BloodConnect</title>
       <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='red'><path d='M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z'/><path d='M21 10h-2V8h-2v2h-2v2h2v2h2v-2h2z' fill='white'/></svg>">

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #d10000;
            --primary-hover: #b00000;
            --secondary-color: #ff4d4d;
            --text-color: #2d3748;
            --light-gray: #fff5f5;
            --border-color: #ffcccc;
            --error-color: #ff3333;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--light-gray);
            color: var(--text-color);
            line-height: 1.6;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(209, 0, 0, 0.1);
            width: 100%;
            max-width: 420px;
            padding: 40px 32px;
            border-top: 4px solid var(--primary-color);
        }
        
        .logo {
            text-align: center;
            margin-bottom: 24px;
            color: var(--primary-color);
        }
        
        .logo-icon {
            font-size: 48px;
            margin-bottom: 10px;
        }
        
        .logo-text {
            font-size: 24px;
            font-weight: 700;
            color: var(--primary-color);
            letter-spacing: 0.5px;
        }
        
        h2 {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 8px;
            text-align: center;
            color: var(--text-color);
        }
        
        .subtitle {
            color: #666;
            text-align: center;
            margin-bottom: 24px;
            font-size: 14px;
            line-height: 1.5;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            font-size: 14px;
            color: var(--text-color);
        }
        
        input {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
            background-color: #fff9f9;
        }
        
        input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(209, 0, 0, 0.1);
            background-color: white;
        }
        
        button {
            width: 100%;
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 14px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        button:hover {
            background-color: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(209, 0, 0, 0.2);
        }
        
        button:active {
            transform: translateY(0);
        }
        
        .back-to-login {
            text-align: center;
            margin-top: 24px;
            font-size: 14px;
            color: #666;
        }
        
        .back-to-login a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.2s;
        }
        
        .back-to-login a:hover {
            text-decoration: underline;
            color: var(--primary-hover);
        }
        
        .form-footer {
            margin-top: 20px;
            text-align: center;
            font-size: 12px;
            color: #999;
        }
        
        @media (max-width: 480px) {
            .container {
                padding: 30px 20px;
                border-radius: 10px;
            }
            
            .logo-icon {
                font-size: 42px;
            }
            
            .logo-text {
                font-size: 22px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <div class="logo-icon">
                <i class="fas fa-tint"></i>
            </div>
            <div class="logo-text">Blood Donation</div>
        </div>
        
        <h2>Forgot Password</h2>
        <p class="subtitle">Enter your username and email address associated with your account to receive password reset instructions</p>
        
        <form method="POST" action="check_user_email.php">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" placeholder="Enter your username" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" placeholder="Enter your registered email" required>
            </div>
            
            <button type="submit">
                <i class="fas fa-paper-plane" style="margin-right: 8px;"></i> Send Reset Link
            </button>
        </form>
        
        <div class="back-to-login">
            Remember your password? <a href="admin_login.php">Sign in here</a>
        </div>
        
        
    </div>
</body>
</html>