<?php
session_start();
require_once 'includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header("Location: login.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM users ORDER BY user_type, id");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT b.*, u1.name as user_name, u2.name as studio_name 
                      FROM bookings b 
                      JOIN users u1 ON b.user_id = u1.id 
                      JOIN users u2 ON b.studio_id = u2.id 
                      ORDER BY b.created_at DESC");
$stmt->execute();
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudioHub - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="index-page">
    <?php include 'includes/navbar.php'; ?>
    <div class="container my-5" style="max-width: 600px;">
        <div class="text-center mb-4">
            <img src="uploads/images/logo.png.jpg" alt="StudioHub Logo" class="mb-2 logo-img-large">
            <h2 class="fs-4 fw-bold">Admin Panel</h2>
            <p class="text-muted small">Manage users and bookings</p>
        </div>
        <h5 class="mb-3">Users</h5>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Type</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo $user['id']; ?></td>
                        <td><?php echo htmlspecialchars($user['name']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><?php echo ucfirst($user['user_type']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <h5 class="mb-3 mt-5">Bookings</h5>
        <?php if (empty($bookings)): ?>
            <p class="text-muted">No bookings yet.</p>
        <?php else: ?>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Studio</th>
                        <th>Event Date</th>
                        <th>Event Type</th>
                        <th>Location</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bookings as $booking): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($booking['user_name']); ?></td>
                            <td><?php echo htmlspecialchars($booking['studio_name']); ?></td>
                            <td><?php echo $booking['event_date']; ?></td>
                            <td><?php echo htmlspecialchars($booking['event_type']); ?></td>
                            <td><?php echo htmlspecialchars($booking['location']); ?></td>
                            <td><?php echo ucfirst($booking['status']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    <?php include 'includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>