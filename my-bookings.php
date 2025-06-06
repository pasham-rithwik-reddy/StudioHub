<?php
session_start();
require_once 'includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'user') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT b.*, u.name as studio_name 
                      FROM bookings b 
                      JOIN users u ON b.studio_id = u.id 
                      WHERE b.user_id = ? 
                      ORDER BY b.created_at DESC");
$stmt->execute([$user_id]);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudioHub - My Bookings</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="index-page">
    <?php include 'includes/navbar.php'; ?>
    <div class="container my-5" style="max-width: 600px;">
        <div class="text-center mb-4">
            <img src="uploads/images/logo.png.jpg" alt="StudioHub Logo" class="mb-2 logo-img-large">
            <h2 class="fs-4 fw-bold">My Bookings</h2>
            <p class="text-muted small">View your booking status</p>
        </div>
        <?php if (empty($bookings)): ?>
            <p class="text-muted">No bookings yet.</p>
        <?php else: ?>
            <?php foreach ($bookings as $booking): ?>
                <div class="card mb-3 shadow">
                    <div class="card-body">
                        <h6 class="fw-bold"><?php echo htmlspecialchars($booking['studio_name']); ?></h6>
                        <p><strong>Event Date:</strong> <?php echo $booking['event_date']; ?></p>
                        <p><strong>Event Type:</strong> <?php echo htmlspecialchars($booking['event_type']); ?></p>
                        <p><strong>Location:</strong> <?php echo htmlspecialchars($booking['location']); ?></p>
                        <p><strong>Status:</strong> 
                            <span class="badge <?php echo $booking['status'] == 'accepted' ? 'bg-success' : ($booking['status'] == 'rejected' ? 'bg-danger' : 'bg-warning'); ?>">
                                <?php echo ucfirst($booking['status']); ?>
                            </span>
                        </p>
                        <p class="text-muted small">Booked on: <?php echo $booking['created_at']; ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <?php include 'includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>