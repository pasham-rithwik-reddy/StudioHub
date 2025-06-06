<?php
session_start();
require_once 'includes/config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $location = trim($_POST['location']);
    $user_type = $_POST['user_type'];
    $services = $user_type === 'studio' ? trim($_POST['services']) : null;
    $avatar = $_FILES['avatar']['name'] ? 'uploads/avatars/' . basename($_FILES['avatar']['name']) : null;
    $image = $_FILES['image']['name'] ? 'uploads/images/' . basename($_FILES['image']['name']) : null;

    if (empty($name) || empty($email) || empty($password) || empty($location) || ($user_type === 'studio' && empty($services))) {
        $error = "All required fields must be filled.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = "Email already registered.";
        } else {
            if ($avatar && move_uploaded_file($_FILES['avatar']['tmp_name'], $avatar)) {
                // Avatar uploaded
            } else {
                $avatar = null;
            }
            if ($image && move_uploaded_file($_FILES['image']['tmp_name'], $image)) {
                // Image uploaded
            } else {
                $image = null;
            }

            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, location, user_type, services, avatar, image) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([$name, $email, $hashed_password, $location, $user_type, $services, $avatar, $image])) {
                header("Location: login.php?registered=1");
                exit;
            } else {
                $error = "Registration failed.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudioHub - Register</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="login-page">
    <div class="container d-flex justify-content-center align-items-center min-vh-100">
        <div class="card p-4 shadow" style="max-width: 400px; width: 100%;">
            <div class="text-center mb-4">
                <img src="https://via.placeholder.com/50?text=Logo" alt="StudioHub Logo" class="mb-2">
                <h2 class="fs-4 fw-bold">StudioHub</h2>
                <p class="text-muted small">Create your account</p>
            </div>
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if (isset($_GET['registered'])): ?>
                <div class="alert alert-success">Registration successful! Please log in.</div>
            <?php endif; ?>
            <form method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="name" class="form-label">Name</label>
                    <input type="text" class="form-control" id="name" name="name" required>
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <div class="mb-3">
                    <label for="location" class="form-label">Location</label>
                    <input type="text" class="form-control" id="location" name="location" required>
                </div>
                <div class="mb-3">
                    <label for="user_type" class="form-label">Account Type</label>
                    <select class="form-select" id="user_type" name="user_type" required>
                        <option value="user">User</option>
                        <option value="studio">Studio</option>
                    </select>
                </div>
                <div class="mb-3" id="services-field" style="display: none;">
                    <label for="services" class="form-label">Services</label>
                    <input type="text" class="form-control" id="services" name="services">
                </div>
                <div class="mb-3">
                    <label for="avatar" class="form-label">Avatar</label>
                    <input type="file" class="form-control" id="avatar" name="avatar" accept="image/*">
                </div>
                <div class="mb-3">
                    <label for="image" class="form-label">Studio Image</label>
                    <input type="file" class="form-control" id="image" name="image" accept="image/*">
                </div>
                <button type="submit" class="btn btn-primary w-100">Register</button>
            </form>
            <p class="text-center mt-3 small">Already have an account? <a href="login.php">Login</a></p>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/script.js"></script>
</body>
</html>