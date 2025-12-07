<?php
session_start();
$last_action = $_SESSION['last_action'] ?? null;
$error = $_SESSION['error'] ?? null;
$success = $_SESSION['success'] ?? null;
// Clear session messages so they don't persist
unset($_SESSION['error'], $_SESSION['success'], $_SESSION['last_action']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register & Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="../style.css">
  <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Courier New', monospace;
    }

    :root {
        --primary-color: #00ff88;
        --bg-dark: #0f0f1e;
        --bg-darker: #1e1e2e;
        --text-color: #00ff88;
        --secondary-color: #2d2d44;
    }

    body {
        background: linear-gradient(135deg, #1e1e2e 0%, #2d2d44 100%);
        color: var(--text-color);
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        
    }

    .container {
        background: rgba(15, 15, 30, 0.8);
        width: 450px;
        padding: 2.5rem;
        border-radius: 8px;
        border: 2px solid var(--primary-color);
        box-shadow: 0 0 20px rgba(0, 255, 136, 0.2), 0 0 40px rgba(0, 255, 136, 0.1);
        backdrop-filter: blur(10px);
    }

    .form-title {
        font-size: 1.8rem;
        font-weight: bold;
        text-align: center;
        padding: 1rem 0;
        margin-bottom: 1.5rem;
        color: var(--primary-color);
        text-transform: uppercase;
        letter-spacing: 2px;
    }

    form {
        margin: 0;
    }

    .input-group {
        padding: 1.2rem 0;
        position: relative;
    }

    .input-group i {
        position: absolute;
        color: var(--primary-color);
        left: 0;
        top: 1.4rem;
    }

    input {
        color: var(--text-color);
        width: 100%;
        background-color: transparent;
        border: none;
        border-bottom: 2px solid rgba(0, 255, 136, 0.3);
        padding-left: 1.8rem;
        font-size: 15px;
        transition: border-color 0.3s ease;
    }

    input::placeholder {
        color: transparent;
    }

    input:focus {
        background-color: transparent;
        outline: none;
        border-bottom: 2px solid var(--primary-color);
        box-shadow: 0 2px 10px rgba(0, 255, 136, 0.2);
    }

    label {
        color: rgba(0, 255, 136, 0.6);
        position: absolute;
        left: 1.8rem;
        top: 1.4rem;
        cursor: auto;
        transition: 0.3s ease all;
    }

    input:focus ~ label,
    input:not(:placeholder-shown) ~ label {
        top: 0;
        color: var(--primary-color);
        font-size: 13px;
    }

    .btn {
        font-size: 1rem;
        padding: 12px 0;
        border-radius: 5px;
        outline: none;
        border: 2px solid var(--primary-color);
        width: 100%;
        background: transparent;
        color: var(--primary-color);
        cursor: pointer;
        transition: all 0.3s ease;
        text-transform: uppercase;
        font-weight: bold;
        margin-top: 1rem;
    }

    .btn:hover {
        background: rgba(0, 255, 136, 0.1);
        box-shadow: 0 0 15px rgba(0, 255, 136, 0.3);
    }

    .or {
        font-size: 0.9rem;
        margin-top: 1.5rem;
        text-align: center;
        color: rgba(0, 255, 136, 0.5);
    }

    .icons {
        text-align: center;
        margin-top: 1.5rem;
    }

    .google-login {
        display: inline-flex;
        align-items: center;
        padding: 10px 20px;
        background: transparent;
        border: 2px solid var(--primary-color);
        border-radius: 5px;
        color: var(--primary-color);
        text-decoration: none;
        transition: all 0.3s ease;
        margin: 0 10px;
        font-weight: bold;
    }

    .google-login i {
        margin-right: 8px;
        color: var(--primary-color);
    }

    .google-login:hover {
        background: rgba(0, 255, 136, 0.1);
        box-shadow: 0 0 15px rgba(0, 255, 136, 0.3);
    }

    .links {
        display: flex;
        justify-content: center;
        gap: 1rem;
        padding: 1rem 0;
        margin-top: 1.5rem;
        flex-wrap: wrap;
    }

    .links p {
        color: rgba(0, 255, 136, 0.6);
    }

    button {
        color: var(--primary-color);
        border: none;
        background-color: transparent;
        font-size: 1rem;
        font-weight: bold;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    button:hover {
        color: var(--primary-color);
        text-decoration: underline;
        text-shadow: 0 0 10px rgba(0, 255, 136, 0.3);
    }

    .alert {
        padding: 12px 15px;
        margin: 10px 0;
        border-radius: 5px;
        text-align: center;
        font-size: 0.9rem;
        border: 2px solid;
    }

    .alert-error {
        background-color: rgba(255, 0, 0, 0.1);
        color: #ff6b6b;
        border-color: #ff6b6b;
    }

    .alert-success {
        background-color: rgba(0, 255, 136, 0.1);
        color: var(--primary-color);
        border-color: var(--primary-color);
    }
  </style>
  <script>
    // Prevent back button from going back after logout
    history.pushState(null, null, location.href);
    window.onpopstate = function() {
      history.pushState(null, null, location.href);
    };
  </script>
</head>
<body>

  <div class="container" id="signup" style="display: <?php echo ($last_action === 'signUp') ? 'block' : 'none'; ?>;">
    <?php if ($last_action === 'signUp' && $error): ?>
      <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php elseif ($last_action === 'signUp' && $success): ?>
      <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
      <h1 class="form-title">Sign Up</h1>
      <form method="post" action="../auth/register.php">
        <div class="input-group">
           <i class="fas fa-user"></i>
           <input type="text" name="fName" id="fName-signup" placeholder="First Name" required>
           <label for="fName-signup">First Name</label>
        </div>
        <div class="input-group">
            <i class="fas fa-user"></i>
            <input type="text" name="lName" id="lName-signup" placeholder="Last Name" required>
            <label for="lName-signup">Last Name</label>
        </div>
        <div class="input-group">
            <i class="fas fa-envelope"></i>
            <input type="email" name="email" id="email-signup" placeholder="Email" required>
            <label for="email-signup">Email</label>
        </div>
        <div class="input-group">
            <i class="fas fa-lock"></i>
          <input type="password" name="password" id="password-signup" placeholder="Password" required>
            <label for="password-signup">Password</label>
        </div>
       <input type="submit" class="btn" value="Sign Up" name="signUp">
      </form>
      <p class="or">
        -------or-------
      </p>
      <div class="icons">
        <a href="../auth/google-login.php" class="google-login">
          <i class="fab fa-google"></i>
          <span>Sign in with Google</span>
        </a>
      </div>
      <div class="links">
        <p>Already have account?</p>
        <button id="signInButton">Sign In</button>
      </div>
    </div>

    <div class="container" id="signIn" style="display: <?php echo ($last_action === 'signUp') ? 'none' : 'block'; ?>;">
        <?php if ($last_action === 'signIn' && $error): ?>
          <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php elseif ($last_action === 'signIn' && $success): ?>
          <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <h1 class="form-title">Sign In</h1>
        <form method="post" action="../auth/register.php">
          <div class="input-group">
              <i class="fas fa-envelope"></i>
              <input type="email" name="email" id="email-signin" placeholder="Email" required>
              <label for="email-signin">Email</label>
          </div>
            <div class="input-group">
              <i class="fas fa-lock"></i>
              <input type="password" name="password" id="password-signin" placeholder="Password" required>
              <label for="password-signin">Password</label>
            </div>
         <input type="submit" class="btn" value="Sign In" name="signIn">
        </form>
        <p class="or">
          -------or-------
        </p>
        <div class="icons">
          <a href="../auth/google-login.php" class="google-login">
            <i class="fab fa-google"></i>
            <span>Sign in with Google</span>
          </a>
        </div>
        <div class="links">
          <p>Don't have account yet?</p>
          <button id="signUpButton">Sign Up</button>
        </div>
      </div>
      <script src="script.js"></script>
</body>
</html>