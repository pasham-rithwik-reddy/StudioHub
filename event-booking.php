<?php
session_start();
require_once 'includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'user') {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['studio_id']) || !is_numeric($_GET['studio_id'])) {
    header("Location: search.php");
    exit;
}

$studio_id = (int)$_GET['studio_id'];
$stmt = $pdo->prepare("SELECT name FROM users WHERE id = ? AND user_type = 'studio'");
$stmt->execute([$studio_id]);
$studio = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$studio) {
    header("Location: search.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $event_date = $_POST['event_date'] ?? '';
    $event_type = $_POST['event_type'] ?? '';
    $location = $_POST['location'] ?? '';

    if (empty($event_date) || !preg_match("/^\d{4}-\d{2}-\d{2}$/", $event_date)) {
        $error = "Please select a valid event date.";
    } elseif (empty($event_type)) {
        $error = "Please select an event type.";
    } elseif (empty($location)) {
        $error = "Please provide a location.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO bookings (user_id, studio_id, event_date, event_type, location) VALUES (?, ?, ?, ?, ?)");
        if ($stmt->execute([$_SESSION['user_id'], $studio_id, $event_date, $event_type, $location])) {
            header("Location: index.php?booked=1");
            exit;
        } else {
            $error = "Booking failed.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudioHub - Book Event</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="index-page">
    <?php include 'includes/navbar.php'; ?>
    <div class="container my-5" style="max-width: 600px;">
        <div class="text-center mb-4">
            <img src="assets/images/logo.png" alt="StudioHub Logo" class="mb-2 logo-img-large">
            <h2 class="fs-4 fw-bold">Book Event with <?php echo htmlspecialchars($studio['name']); ?></h2>
            <p class="text-muted small">Provide event details</p>
        </div>
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form method="POST" action="event-booking.php?studio_id=<?php echo $studio_id; ?>">
            <div class="mb-3">
                <label for="event_date" class="form-label">Event Date</label>
                <input type="date" class="form-control" id="event_date" name="event_date" required>
            </div>
            <div class="mb-3">
                <label for="event_type" class="form-label">Event Type</label>
                <select class="form-select" id="event_type" name="event_type" required>
                    <option value="" disabled selected>Select event type</option>
                    <option value="Wedding Photography">Wedding Photography</option>
                    <option value="Portrait Session">Portrait Session</option>
                    <option value="Corporate Event">Corporate Event</option>
                    <option value="Music Video">Music Video</option>
                    <option value="Event Planning">Event Planning</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="location" class="form-label">Location</label>
                <input type="text" class="form-control" id="location" name="location" placeholder="e.g., Chei Beachfront" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Book</button>
        </form>
    </div>
    <?php include 'includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>