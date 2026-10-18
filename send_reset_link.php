<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
     <title>Password Reset | Blood Donation System</title>
       <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='red'><path d='M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z'/><path d='M21 10h-2V8h-2v2h-2v2h2v2h2v-2h2z' fill='white'/></svg>">

</head>
<body>
    
</body>
</html><?php
include 'config.php'; // DB connection

// Start HTML output with blood-themed design
echo '<!DOCTYPE html>
<html lang="en">
<head>
    
 
    
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        body {
            font-family: "Roboto", sans-serif;
            background-color: #f8f8f8;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-image: linear-gradient(rgba(255,255,255,0.9), rgba(255,255,255,0.9)), url("https://www.transparenttextures.com/patterns/blood.png");
        }
        .container {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            padding: 30px;
            width: 90%;
            max-width: 500px;
            text-align: center;
            border-top: 5px solid #d32f2f;
        }
        h1 {
            color: #d32f2f;
            margin-bottom: 20px;
        }
        .reset-link {
            background-color: #ffebee;
            border: 1px dashed #d32f2f;
            padding: 15px;
            margin: 20px 0;
            word-break: break-all;
            border-radius: 5px;
            color: #333;
            text-decoration: none;
            display: block;
        }
        .reset-link:hover {
            background-color: #ffcdd2;
        }
        .btn {
            background-color: #d32f2f;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 10px;
            text-decoration: none;
            display: inline-block;
        }
        .btn:hover {
            background-color: #b71c1c;
        }
        .blood-drop {
            color: #d32f2f;
            font-size: 24px;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="container">';

if (isset($_GET['email'])) {
    $email = $_GET['email'];

    // Check if email exists in DB
    $stmt = $conn->prepare("SELECT * FROM admins WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Generate token
        $token = bin2hex(random_bytes(32));
        $expires = date("Y-m-d H:i:s", strtotime("+1 hour"));

        // Save token and expiry
        $update = $conn->prepare("UPDATE admins SET reset_token = ?, reset_expires = ? WHERE email = ?");
        $update->bind_param("sss", $token, $expires, $email);
        $update->execute();

        // Reset link
        $reset_link = "http://localhost:90/BLOOD-DONOR/reset_password.php?token=$token";

        // Display the reset link with professional design
        echo '<div class="blood-drop">💉</div>
              <h1>Password Reset Request</h1>
              <p>We\'ve generated a password reset link for your account. Please click the link below or copy it to your browser:</p>
              <a href="'.$reset_link.'" class="reset-link">'.$reset_link.'</a>
              <p>This link will expire in 1 hour for your security.</p>
              <a href="'.$reset_link.'" class="btn">Reset Password Now</a>
              <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
              <script>
                  Swal.fire({
                      title: "Reset Link Generated!",
                      text: "Your password reset link has been created successfully.",
                      icon: "success",
                      confirmButtonColor: "#d32f2f"
                  });
              </script>';
    } else {
        echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
              <script>
                  Swal.fire({
                      title: "Email Not Found!",
                      text: "The email address you entered was not found in our system.",
                      icon: "error",
                      confirmButtonColor: "#d32f2f"
                  }).then(() => {
                      window.location.href = "forgot_password.php";
                  });
              </script>';
    }
} else {
    echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
          <script>
              Swal.fire({
                  title: "Email Required!",
                  text: "Please provide your email address to reset your password.",
                  icon: "warning",
                  confirmButtonColor: "#d32f2f"
              }).then(() => {
                  window.location.href = "forgot_password.php";
              });
          </script>';
}

echo '</div>
</body>
</html>';
