<?php
session_start();
include('config/db.php');

if (!isset($_SESSION['id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['id'];
$success_message = '';
$error_message   = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username         = trim($_POST['username']);
    $email            = trim($_POST['email']);
    $current_password = $_POST['current_password'] ?? '';
    $new_password     = $_POST['new_password']     ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($username) || empty($email)) {
        $error_message = 'Username and email are required.';
    } else {
        // Check username uniqueness
        $check_username_stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $check_username_stmt->bind_param("si", $username, $user_id);
        $check_username_stmt->execute();
        if ($check_username_stmt->get_result()->num_rows > 0) {
            $error_message = 'Username is already taken by another account.';
        } else {
            // Check email uniqueness
            $check_email_stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $check_email_stmt->bind_param("si", $email, $user_id);
            $check_email_stmt->execute();
            if ($check_email_stmt->get_result()->num_rows > 0) {
                $error_message = 'Email is already in use by another account.';
            } else {
                // Update basic info
                $update_stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                $update_stmt->bind_param("ssi", $username, $email, $user_id);

                if ($update_stmt->execute()) {
                    $_SESSION['username'] = $username;
                    $_SESSION['email']    = $email;
                    $success_message      = 'Profile updated successfully!';

                    // Handle password change if fields are filled
                    if (!empty($current_password) && !empty($new_password)) {
                        $pass_stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
                        $pass_stmt->bind_param("i", $user_id);
                        $pass_stmt->execute();
                        $user_data = $pass_stmt->get_result()->fetch_assoc();

                        // BUG FIX: was $$user['password'] (double-dollar) — corrected to $user_data['password']
                        if ($current_password === $user_data['password']) {
                            if ($new_password !== $confirm_password) {
                                $error_message = 'Profile updated but new passwords do not match.';
                            } elseif (strlen($new_password) < 6) {
                                $error_message = 'Profile updated but new password must be at least 6 characters.';
                            } else {
                                $pass_update_stmt = $conn->prepare("UPDATE users SET password = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                                $pass_update_stmt->bind_param("si", $new_password, $user_id);
                                if ($pass_update_stmt->execute()) {
                                    $success_message = 'Profile and password updated successfully!';
                                } else {
                                    $error_message = 'Profile updated but password change failed.';
                                }
                            }
                        } else {
                            $error_message = 'Profile updated but current password is incorrect.';
                        }
                    }
                } else {
                    $error_message = 'Failed to update profile.';
                }
            }
        }
    }
}

// Fetch current user
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    header('Location: login.php');
    exit;
}

include 'includes/sidebar.php';
?>

<style>
    .profile-container {
        max-width: 800px;
        margin: 0 auto;
    }

    .profile-card {
        background: white;
        border-radius: 12px;
        padding: 32px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        margin-bottom: 24px;
        border: 1px solid #e2e8f0;
    }

    .profile-card h2 {
        font-size: 20px;
        font-weight: 600;
        color: #1e293b;
        margin-bottom: 24px;
        padding-bottom: 16px;
        border-bottom: 2px solid #e2e8f0;
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    .form-group {
        margin-bottom: 24px;
    }

    .form-group label {
        display: block;
        font-size: 14px;
        font-weight: 600;
        color: #1e293b;
        margin-bottom: 8px;
    }

    .form-group input {
        width: 100%;
        padding: 12px 16px;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        font-size: 14px;
        transition: all 0.2s ease;
        font-family: inherit;
        box-sizing: border-box;
    }

    .form-group input:focus {
        outline: none;
        border-color: #0ea5e9;
        box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.1);
    }

    /* Password wrapper with eye icon */
    .password-wrap {
        position: relative;
        display: flex;
        align-items: center;
    }

    .password-wrap input {
        padding-right: 46px; /* room for the eye button */
    }

    .toggle-pw {
        position: absolute;
        right: 12px;
        background: none;
        border: none;
        cursor: pointer;
        padding: 4px;
        color: #94a3b8;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: color .2s ease;
        border-radius: 4px;
    }

    .toggle-pw:hover { color: #0ea5e9; }
    .toggle-pw svg   { width: 18px; height: 18px; pointer-events: none; }

    /* Hide the "closed eye" icon by default */
    .toggle-pw .icon-eye-off { display: none; }

    /* When input is type="text" (visible), swap icons */
    .toggle-pw.visible .icon-eye     { display: none; }
    .toggle-pw.visible .icon-eye-off { display: block; }

    .form-group-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
    }

    .alert {
        padding: 16px;
        border-radius: 8px;
        margin-bottom: 24px;
        font-size: 14px;
        display: flex;
        align-items: center;
        gap: 12px;
        animation: slideIn 0.3s ease;
    }

    @keyframes slideIn {
        from { opacity: 0; transform: translateY(-10px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    .alert-success { background: #d1fae5; color: #065f46; border: 1px solid #10b981; }
    .alert-error   { background: #fee2e2; color: #991b1b; border: 1px solid #ef4444; }

    .btn {
        padding: 12px 24px;
        border: none;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-family: inherit;
    }

    .btn-primary { background: #0ea5e9; color: white; }
    .btn-primary:hover {
        background: #0284c7;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(14, 165, 233, 0.3);
    }

    .btn-secondary { background: #e2e8f0; color: #1e293b; }
    .btn-secondary:hover { background: #cbd5e1; }

    .form-actions {
        display: flex;
        gap: 12px;
        justify-content: flex-end;
        margin-top: 32px;
    }

    .password-hint {
        color: #64748b;
        font-size: 13px;
        margin-top: 6px;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .required-star { color: #ef4444; margin-left: 2px; }
    .info-label { font-weight: 600; color: #64748b; font-size: 14px; }
    .info-value { color: #1e293b; font-size: 14px; }

    @media (max-width: 768px) {
        .form-group-row    { grid-template-columns: 1fr; }
        .profile-card      { padding: 24px 16px; }
        .form-actions      { flex-direction: column; }
        .btn               { width: 100%; justify-content: center; }
    }
</style>

<main class="main-content">
    <div class="profile-container">

        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <i class="fa-solid fa-circle-check"></i>
                <span><?php echo htmlspecialchars($success_message); ?></span>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-error">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span><?php echo htmlspecialchars($error_message); ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="" id="profileForm">

            <!-- ── Personal Information ── -->
            <div class="profile-card">
                <h2>
                    <i class="fa-solid fa-user-pen"></i>
                    Personal Information
                    <span style="font-size:14px;font-weight:400;color:#64748b;margin-left:4px;">
                        (<i class="fa-solid fa-id-badge"></i>&nbsp;User ID&nbsp;
                        <span class="info-value">#<?php echo $user['id']; ?></span>)
                    </span>
                </h2>

                <div class="form-group-row">
                    <div class="form-group">
                        <label for="username">
                            Username<span class="required-star">*</span>
                        </label>
                        <input type="text" id="username" name="username"
                               value="<?php echo htmlspecialchars($user['username']); ?>"
                               required maxlength="50">
                    </div>

                    <div class="form-group">
                        <label for="email">
                            Email Address<span class="required-star">*</span>
                        </label>
                        <input type="email" id="email" name="email"
                               value="<?php echo htmlspecialchars($user['email']); ?>"
                               required maxlength="100">
                    </div>
                </div>
            </div>

            <!-- ── Change Password ── -->
            <div class="profile-card">
                <h2><i class="fa-solid fa-lock"></i> Change Password</h2>
                <p style="color:#64748b;font-size:14px;margin-bottom:24px;">
                    <i class="fa-solid fa-info-circle"></i>
                    Leave these fields blank if you don't want to change your password.
                </p>

                <!-- Current Password -->
                <div class="form-group">
                    <label for="current_password">Current Password</label>
                    <div class="password-wrap">

                        <input type="password" id="current_password" name="current_password"
                               value="<?php echo htmlspecialchars($user['password']) ?>" maxlength="100" autocomplete="current-password">
                        <button type="button" class="toggle-pw" data-target="current_password" title="Show / hide password">
                            <!-- Eye open -->
                            <svg class="icon-eye" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                            <!-- Eye closed -->
                            <svg class="icon-eye-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/>
                                <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>
                                <line x1="1" y1="1" x2="23" y2="23"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- New & Confirm Password -->
                <div class="form-group-row">
                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <div class="password-wrap">
                            <input type="password" id="new_password" name="new_password"
                                   placeholder="Enter new password" maxlength="100" autocomplete="new-password">
                            <button type="button" class="toggle-pw" data-target="new_password" title="Show / hide password">
                                <svg class="icon-eye" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                    <circle cx="12" cy="12" r="3"/>
                                </svg>
                                <svg class="icon-eye-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/>
                                    <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>
                                    <line x1="1" y1="1" x2="23" y2="23"/>
                                </svg>
                            </button>
                        </div>
                        <div class="password-hint">
                            <i class="fa-solid fa-shield-halved"></i>
                            Must be at least 6 characters
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <div class="password-wrap">
                            <input type="password" id="confirm_password" name="confirm_password"
                                   placeholder="Confirm new password" maxlength="100" autocomplete="new-password">
                            <button type="button" class="toggle-pw" data-target="confirm_password" title="Show / hide password">
                                <svg class="icon-eye" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                    <circle cx="12" cy="12" r="3"/>
                                </svg>
                                <svg class="icon-eye-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/>
                                    <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>
                                    <line x1="1" y1="1" x2="23" y2="23"/>
                                </svg>
                            </button>
                        </div>
                        <div class="password-hint">
                            <i class="fa-solid fa-check-double"></i>
                            Must match new password
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="window.location.href='dashboard.php'">
                        <i class="fa-solid fa-xmark"></i>
                        <span>Cancel</span>
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-floppy-disk"></i>
                        <span>Save Changes</span>
                    </button>
                </div>
            </div>

        </form>
    </div>
</main>

<script>
    // ── Password visibility toggle ──
    document.querySelectorAll('.toggle-pw').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const input = document.getElementById(this.dataset.target);
            const isHidden = input.type === 'password';
            input.type = isHidden ? 'text' : 'password';
            this.classList.toggle('visible', isHidden);
            this.title = isHidden ? 'Hide password' : 'Show password';
        });
    });

    // ── Form validation ──
    document.getElementById('profileForm').addEventListener('submit', function (e) {
        const username        = document.getElementById('username').value.trim();
        const email           = document.getElementById('email').value.trim();
        const currentPassword = document.getElementById('current_password').value;
        const newPassword     = document.getElementById('new_password').value;
        const confirmPassword = document.getElementById('confirm_password').value;

        if (!username || !email) {
            e.preventDefault();
            alert('Username and email are required.');
            return false;
        }

        // Only validate password fields if any one of them is filled
        if (currentPassword || newPassword || confirmPassword) {
            if (!currentPassword) {
                e.preventDefault();
                alert('Please enter your current password to change it.');
                return false;
            }
            if (!newPassword) {
                e.preventDefault();
                alert('Please enter a new password.');
                return false;
            }
            if (newPassword !== confirmPassword) {
                e.preventDefault();
                alert('New passwords do not match.');
                return false;
            }
            if (newPassword.length < 6) {
                e.preventDefault();
                alert('New password must be at least 6 characters long.');
                return false;
            }
            if (!confirm('Are you sure you want to change your password?')) {
                e.preventDefault();
                return false;
            }
        }

        return true;
    });

    // ── Auto-hide alerts after 5 s ──
    setTimeout(function () {
        document.querySelectorAll('.alert').forEach(function (el) {
            el.style.transition = 'opacity 0.3s ease';
            el.style.opacity    = '0';
            setTimeout(function () { el.remove(); }, 300);
        });
    }, 5000);
</script>