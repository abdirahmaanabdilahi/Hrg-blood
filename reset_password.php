<?php
$token = $_GET['token'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Password Reset</title>
     <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='red'><path d='M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z'/><path d='M21 10h-2V8h-2v2h-2v2h2v2h2v-2h2z' fill='white'/></svg>">


  <!-- Tailwind CSS -->
  <script src="https://cdn.tailwindcss.com"></script>

  <!-- SweetAlert2 -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <!-- Custom Colors -->
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            blood: {
              50: '#fee2e2',
              100: '#fecaca',
              200: '#fca5a5',
              300: '#f87171',
              400: '#ef4444',
              500: '#dc2626',
              600: '#b91c1c',
              700: '#991b1b',
              800: '#7f1d1d',
              900: '#450a0a',
            }
          }
        }
      }
    }
  </script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">
  <div class="text-center">
    <!-- Reset Password Button -->
    <button 
      onclick="openResetModal('<?php echo $token; ?>')"
      class="bg-blood-600 hover:bg-blood-700 text-white font-medium py-3 px-6 rounded-lg shadow-md hover:shadow-lg transition-all duration-300 ease-in-out transform hover:-translate-y-1 flex items-center gap-2"
    >
      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
        <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
      </svg>
      Reset Password
    </button>
  </div>

  <script>
  function openResetModal(token) {
    Swal.fire({
      title: '<span class="text-blood-800 font-bold">Reset Your Password</span>',
      html:
        `<div class="space-y-4">
          <div>
            <label for="new_password" class="block text-sm font-medium text-blood-700 mb-1 text-left">New Password</label>
            <input 
              type="password" 
              id="new_password" 
              class="swal2-input w-full px-4 py-2 border border-blood-300 rounded-md focus:ring-blood-500 focus:border-blood-500" 
              placeholder="Enter new password"
            >
          </div>
          <input type="hidden" id="token" value="${token}">
        </div>`,
      background: '#fef2f2',
      showCancelButton: true,
      confirmButtonText: 'Reset Password',
      confirmButtonColor: '#b91c1c',
      cancelButtonColor: '#6b7280',
      focusConfirm: false,
      preConfirm: () => {
        const new_password = document.getElementById('new_password').value;
        const token = document.getElementById('token').value;

        if (!new_password) {
          Swal.showValidationMessage('Please enter a new password');
          return false;
        }

        return fetch('update_password.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
          },
          body: `new_password=${encodeURIComponent(new_password)}&token=${encodeURIComponent(token)}`
        })
        .then(response => response.text())
        .then(data => {
          Swal.fire({
            title: '<span class="text-blood-800 font-bold">Success!</span>',
            text: data,
            icon: 'success',
            confirmButtonColor: '#b91c1c',
            background: '#fef2f2'
          }).then(() => {
            // ✅ Redirect to admin login page after success
            window.location.href = 'admin_login.php';
          });
        })
        .catch(() => {
          Swal.fire({
            title: '<span class="text-blood-800 font-bold">Error</span>',
            text: 'Failed to reset password',
            icon: 'error',
            confirmButtonColor: '#b91c1c',
            background: '#fef2f2'
          });
        });
      },
      customClass: {
        popup: 'rounded-xl',
        validationMessage: 'text-blood-600'
      }
    });
  }
  </script>
</body>
</html>
