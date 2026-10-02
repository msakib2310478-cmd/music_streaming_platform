<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>CampusBeatz | Admin Login</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link rel="stylesheet" href="auth-style.css" />
    <style>
      .flash-message {
        margin-bottom: 16px;
        padding: 10px 12px;
        border-radius: 10px;
        font-size: 0.9rem;
        border: 1px solid transparent;
      }
      .flash-message.error {
        background: rgba(220, 53, 69, 0.12);
        border-color: rgba(220, 53, 69, 0.4);
        color: #ffd4d9;
      }
      .flash-message.success {
        background: rgba(255, 107, 87, 0.12);
        border-color: rgba(255, 107, 87, 0.4);
        color: #d4f8e3;
      }
    </style>
  </head>
  <body>
    <div class="auth-shell">
      <div class="auth-visual">
        <div class="brand">
          <span class="brand-logo"><i class="fa-brands fa-music"></i></span>
          <span>CampusBeatz</span>
        </div>

        <div class="visual-copy">
          <small>Admin Portal</small>
          <h1>Manage the platform efficiently.</h1>
          <p>
            Monitor users, manage music content, and control premium features with secure access.
          </p>

          <div class="feature-badges">
            <span>Content tools</span>
            <span>User controls</span>
            <span>Reports</span>
          </div>
        </div>
      </div>

      <div class="auth-panel">
        <div class="auth-panel-inner">
          <div class="panel-top">
            <div class="eyebrow">Administrator</div>
            <h2>Admin login</h2>
            <p>Secure access for staff and moderators.</p>
          </div>

          <?php if (isset($_SESSION['error'])): ?>
            <div class="flash-message error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
          <?php endif; ?>

          <?php if (isset($_SESSION['success'])): ?>
            <div class="flash-message success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
          <?php endif; ?>

          <form action="../backend/admin_login.php" method="POST">
            <div class="input-group">
              <label for="admin-email">Admin email</label>
              <input id="admin-email" name="email" type="email" placeholder="admin@CampusBeatz.com" required />
            </div>

            <div class="input-group">
              <label for="admin-password">Password</label>
              <input id="admin-password" name="password" type="password" placeholder="Enter admin password" required />
            </div>

            <button class="primary-btn" type="submit">Access Dashboard</button>
          </form>

          <div class="form-foot">
            User account? <a href="user-login.php">Login here</a>
          </div>
          <div class="form-foot">
            Need an account? <a href="../backend/create_admin.php">Register admin</a>
          </div>
          <div class="form-foot">
            Need a user account? <a href="user-register.php">Register</a>
          </div>
        </div>
      </div>
    </div>
  </body>
</html>
