<?php
session_start();
require_once 'includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'studio') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Handle avatar upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['avatar'])) {
    $avatar = $_FILES['avatar'];
    $allowed_types = ['image/jpeg', 'image/png'];
    $max_size = 2 * 1024 * 1024; // 2MB

    if ($avatar['size'] > $max_size) {
        $avatar_error = "Avatar size exceeds 2MB.";
    } elseif ($avatar['error'] != UPLOAD_ERR_OK) {
        $avatar_error = "Avatar upload error.";
    } elseif (!in_array(mime_content_type($avatar['tmp_name']), $allowed_types)) {
        $avatar_error = "Only JPG or PNG avatars are allowed.";
    } else {
        $ext = pathinfo($avatar['name'], PATHINFO_EXTENSION);
        $filename = 'avatar_' . $user_id . '_' . uniqid() . '.' . $ext;
        $upload_dir = 'uploads/avatars/';
        $upload_path = $upload_dir . $filename;

        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        if (move_uploaded_file($avatar['tmp_name'], $upload_path)) {
            $stmt = $pdo->prepare("UPDATE users SET avatar = ? WHERE id = ?");
            $stmt->execute([$upload_path, $user_id]);
            header("Location: studio-home.php?avatar_updated=1");
            exit;
        } else {
            $avatar_error = "Failed to upload avatar.";
        }
    }
}

// Handle story upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['story'])) {
    $story = $_FILES['story'];
    $allowed_types = ['image/jpeg', 'image/png', 'video/mp4'];
    $max_size = 5 * 1024 * 1024; // 5MB

    if ($story['size'] > $max_size) {
        $story_error = "Story size exceeds 5MB.";
    } elseif ($story['error'] != UPLOAD_ERR_OK) {
        $story_error = "Story upload error.";
    } else {
        $mime_type = mime_content_type($story['tmp_name']);
        $type = in_array($mime_type, ['image/jpeg', 'image/png']) ? 'image' : ($mime_type == 'video/mp4' ? 'video' : null);
        if (!$type) {
            $story_error = "Only JPG, PNG, or MP4 stories are allowed.";
        } else {
            $ext = pathinfo($story['name'], PATHINFO_EXTENSION);
            $filename = 'story_' . $user_id . '_' . uniqid() . '.' . $ext;
            $upload_dir = 'uploads/stories/';
            $upload_path = $upload_dir . $filename;

            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            if (move_uploaded_file($story['tmp_name'], $upload_path)) {
                $expires_at = date('Y-m-d H:i:s', strtotime('+24 hours'));
                $stmt = $pdo->prepare("INSERT INTO stories (user_id, type, media_url, expires_at) VALUES (?, ?, ?, ?)");
                $stmt->execute([$user_id, $type, $upload_path, $expires_at]);
                header("Location: studio-home.php?story_posted=1");
                exit;
            } else {
                $story_error = "Failed to upload story.";
            }
        }
    }
}

// Handle media upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['media'])) {
    $content = trim($_POST['content']);
    $media = $_FILES['media'];
    $allowed_image_types = ['image/jpeg', 'image/png'];
    $allowed_video_types = ['video/mp4'];
    $max_size = 10 * 1024 * 1024; // 10MB

    if ($media['size'] > $max_size) {
        $media_error = "File size exceeds 10MB.";
    } elseif ($media['error'] != UPLOAD_ERR_OK) {
        $media_error = "Upload error occurred.";
    } else {
        $mime_type = mime_content_type($media['tmp_name']);
        $type = in_array($mime_type, $allowed_image_types) ? 'image' : (in_array($mime_type, $allowed_video_types) ? 'video' : null);
        if (!$type) {
            $media_error = "Only JPG, PNG, or MP4 files are allowed.";
        } else {
            $ext = pathinfo($media['name'], PATHINFO_EXTENSION);
            $filename = uniqid() . '.' . $ext;
            $upload_dir = $type == 'image' ? 'uploads/images/' : 'uploads/videos/';
            $upload_path = $upload_dir . $filename;

            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            if (move_uploaded_file($media['tmp_name'], $upload_path)) {
                $stmt = $pdo->prepare("INSERT INTO posts (user_id, content, type, media_url) VALUES (?, ?, ?, ?)");
                $stmt->execute([$user_id, $content, $type, $upload_path]);
                header("Location: studio-home.php?posted=1");
                exit;
            } else {
                $media_error = "Failed to upload file.";
            }
        }
    }
}

// Handle booking actions
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['booking_id']) && isset($_POST['action'])) {
    $booking_id = (int)$_POST['booking_id'];
    $action = $_POST['action'];
    $status = $action == 'accept' ? 'accepted' : 'rejected';
    $stmt = $pdo->prepare("UPDATE bookings SET status = ? WHERE id = ? AND studio_id = ?");
    $stmt->execute([$status, $booking_id, $user_id]);
}

// Fetch studio info
$stmt = $pdo->prepare("SELECT name, avatar, services FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$studio = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$studio) {
    session_destroy();
    header("Location: login.php?error=Studio not found");
    exit;
}

// Fetch follower count
$stmt = $pdo->prepare("SELECT COUNT(*) as follower_count FROM follows WHERE studio_id = ?");
$stmt->execute([$user_id]);
$follower_count = $stmt->fetch(PDO::FETCH_ASSOC)['follower_count'];

// Fetch posts with like counts
$stmt = $pdo->prepare("SELECT p.*, (SELECT COUNT(*) FROM likes l WHERE l.post_id = p.id) as like_count 
                      FROM posts p WHERE p.user_id = ? ORDER BY p.created_at DESC");
$stmt->execute([$user_id]);
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch bookings
$stmt = $pdo->prepare("SELECT b.*, u.name as user_name FROM bookings b JOIN users u ON b.user_id = u.id WHERE b.studio_id = ? ORDER BY b.created_at DESC");
$stmt->execute([$user_id]);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch stories
$stmt = $pdo->prepare("SELECT * FROM stories WHERE user_id = ? AND expires_at > NOW() ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$stories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudioHub - Studio Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="index-page">
    <?php include 'includes/navbar.php'; ?>
    <div class="container my-5" style="max-width: 600px;">
        <div class="text-center mb-4">
            <img src="<?php echo htmlspecialchars($studio['avatar'] ?: 'https://via.placeholder.com/100'); ?>" alt="Studio Avatar" class="img-fluid mb-2 rounded-circle" style="width: 100px; height: 100px;">
            <h2 class="fs-4 fw-bold"><?php echo htmlspecialchars($studio['name']); ?></h2>
            <p class="text-muted small"><?php echo htmlspecialchars($studio['services'] ?: 'No services'); ?></p>
            <p class="small"><strong><?php echo $follower_count; ?></strong> followers</p>
        </div>

        <!-- Avatar Upload Form -->
        <h5 class="mb-3">Update Profile Picture</h5>
        <?php if (isset($avatar_error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($avatar_error); ?></div>
        <?php endif; ?>
        <?php if (isset($_GET['avatar_updated'])): ?>
            <div class="alert alert-success">Profile picture updated successfully!</div>
        <?php endif; ?>
        <form method="POST" enctype="multipart/form-data" class="card mb-4 shadow">
            <div class="card-body">
                <div class="mb-3">
                    <label for="avatar" class="form-label">Profile Picture (JPG, PNG, max 2MB)</label>
                    <input type="file" class="form-control" id="avatar" name="avatar" accept="image/jpeg,image/png" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Upload</button>
            </div>
        </form>

        <!-- Story Upload Form -->
        <h5 class="mb-3">Create Story</h5>
        <?php if (isset($story_error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($story_error); ?></div>
        <?php endif; ?>
        <?php if (isset($_GET['story_posted'])): ?>
            <div class="alert alert-success">Story posted successfully!</div>
        <?php endif; ?>
        <form method="POST" enctype="multipart/form-data" class="card mb-4 shadow">
            <div class="card-body">
                <div class="mb-3">
                    <label for="story" class="form-label">Story (JPG, PNG, MP4, max 5MB)</label>
                    <input type="file" class="form-control" id="story" name="story" accept="image/jpeg,image/png,video/mp4" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Post Story</button>
            </div>
        </form>

        <!-- Post Upload Form -->
        <h5 class="mb-3">Create Post</h5>
        <?php if (isset($media_error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($media_error); ?></div>
        <?php endif; ?>
        <?php if (isset($_GET['posted'])): ?>
            <div class="alert alert-success">Post created successfully!</div>
        <?php endif; ?>
        <form method="POST" enctype="multipart/form-data" class="card mb-4 shadow">
            <div class="card-body">
                <div class="mb-3">
                    <label for="content" class="form-label">Caption</label>
                    <textarea class="form-control" id="content" name="content" rows="3"></textarea>
                </div>
                <div class="mb-3">
                    <label for="media" class="form-label">Photo or Video (JPG, PNG, MP4)</label>
                    <input type="file" class="form-control" id="media" name="media" accept="image/jpeg,image/png,video/mp4" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Post</button>
            </div>
        </form>

        <!-- Bookings -->
        <h5 class="mb-3">Bookings</h5>
        <?php if (empty($bookings)): ?>
            <p class="text-muted">No bookings yet.</p>
        <?php else: ?>
            <?php foreach ($bookings as $booking): ?>
                <div class="card mb-3 shadow">
                    <div class="card-body">
                        <p><strong>User:</strong> <?php echo htmlspecialchars($booking['user_name']); ?></p>
                        <p><strong>Event Date:</strong> <?php echo $booking['event_date']; ?></p>
                        <p><strong>Event Type:</strong> <?php echo htmlspecialchars($booking['event_type']); ?></p>
                        <p><strong>Location:</strong> <?php echo htmlspecialchars($booking['location']); ?></p>
                        <p><strong>Status:</strong> <?php echo ucfirst($booking['status']); ?></p>
                        <?php if ($booking['status'] == 'pending'): ?>
                            <form method="POST" class="d-flex gap-2">
                                <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                <button type="submit" name="action" value="accept" class="btn btn-success btn-sm">Accept</button>
                                <button type="submit" name="action" value="reject" class="btn btn-danger btn-sm">Reject</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- Stories -->
        <h5 class="mb-3 mt-5">Your Stories</h5>
        <?php if (empty($stories)): ?>
            <p class="text-muted">No active stories.</p>
        <?php else: ?>
            <div class="story-carousel mb-4">
                <?php foreach ($stories as $story): ?>
                    <div class="story-item d-inline-block mx-1">
                        <?php if ($story['type'] == 'image'): ?>
                            <img src="<?php echo htmlspecialchars($story['media_url']); ?>" alt="Story" class="rounded" style="width: 60px; height: 60px; object-fit: cover;">
                        <?php else: ?>
                            <video muted class="rounded" style="width: 60px; height: 60px; object-fit: cover;">
                                <source src="<?php echo htmlspecialchars($story['media_url']); ?>" type="video/mp4">
                            </video>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Posts -->
        <h5 class="mb-3 mt-5">Your Posts</h5>
        <?php if (empty($posts)): ?>
            <p class="text-muted">No posts yet.</p>
        <?php else: ?>
            <?php foreach ($posts as $post): ?>
                <div class="card mb-3 shadow">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-2">
                            <img src="<?php echo htmlspecialchars($studio['avatar'] ?: 'https://via.placeholder.com/40'); ?>" alt="Avatar" class="rounded-circle me-2" style="width: 40px; height: 40px;">
                            <h6 class="mb-0"><?php echo htmlspecialchars($studio['name']); ?></h6>
                        </div>
                        <?php if ($post['type'] == 'image'): ?>
                            <img src="<?php echo htmlspecialchars($post['media_url']); ?>" class="card-img-top" alt="Post">
                        <?php else: ?>
                            <video controls class="card-img-top">
                                <source src="<?php echo htmlspecialchars($post['media_url']); ?>" type="video/mp4">
                            </video>
                        <?php endif; ?>
                        <p class="card-text mt-2"><?php echo htmlspecialchars($post['content']); ?></p>
                        <p class="small"><strong><?php echo $post['like_count']; ?></strong> likes</p>
                        <p class="text-muted small"><?php echo $post['created_at']; ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <?php include 'includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>