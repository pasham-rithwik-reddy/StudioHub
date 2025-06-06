<?php
session_start();
require_once 'includes/config.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: search.php");
    exit;
}

$studio_id = (int)$_GET['id'];
$stmt = $pdo->prepare("SELECT name, location, services, avatar FROM users WHERE id = ? AND user_type = 'studio'");
$stmt->execute([$studio_id]);
$studio = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$studio) {
    header("Location: search.php");
    exit;
}

// Handle like/unlike
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['post_id']) && isset($_SESSION['user_id']) && $_SESSION['user_type'] == 'user') {
    $post_id = (int)$_POST['post_id'];
    $user_id = $_SESSION['user_id'];
    $stmt = $pdo->prepare("SELECT * FROM likes WHERE user_id = ? AND post_id = ?");
    $stmt->execute([$user_id, $post_id]);
    if ($stmt->fetch()) {
        $stmt = $pdo->prepare("DELETE FROM likes WHERE user_id = ? AND post_id = ?");
        $stmt->execute([$user_id, $post_id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO likes (user_id, post_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $post_id]);
    }
    header("Location: studio-profile.php?id=$studio_id");
    exit;
}

// Handle follow/unfollow
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['follow']) && isset($_SESSION['user_id']) && $_SESSION['user_type'] == 'user') {
    $user_id = $_SESSION['user_id'];
    $stmt = $pdo->prepare("SELECT * FROM follows WHERE user_id = ? AND studio_id = ?");
    $stmt->execute([$user_id, $studio_id]);
    if ($stmt->fetch()) {
        $stmt = $pdo->prepare("DELETE FROM follows WHERE user_id = ? AND studio_id = ?");
        $stmt->execute([$user_id, $studio_id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO follows (user_id, studio_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $studio_id]);
    }
    header("Location: studio-profile.php?id=$studio_id");
    exit;
}

// Fetch posts with like counts
$stmt = $pdo->prepare("SELECT p.*, (SELECT COUNT(*) FROM likes l WHERE l.post_id = p.id) as like_count, 
                      (SELECT 1 FROM likes l WHERE l.post_id = p.id AND l.user_id = ?) as user_liked 
                      FROM posts p WHERE p.user_id = ? ORDER BY p.created_at DESC");
$stmt->execute([isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0, $studio_id]);
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch stories (not expired)
$stmt = $pdo->prepare("SELECT * FROM stories WHERE user_id = ? AND expires_at > NOW() ORDER BY created_at DESC");
$stmt->execute([$studio_id]);
$stories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch follower count and follow status
$stmt = $pdo->prepare("SELECT COUNT(*) as follower_count FROM follows WHERE studio_id = ?");
$stmt->execute([$studio_id]);
$follower_count = $stmt->fetch(PDO::FETCH_ASSOC)['follower_count'];

$is_following = false;
if (isset($_SESSION['user_id']) && $_SESSION['user_type'] == 'user') {
    $stmt = $pdo->prepare("SELECT * FROM follows WHERE user_id = ? AND studio_id = ?");
    $stmt->execute([$_SESSION['user_id'], $studio_id]);
    $is_following = $stmt->fetch() ? true : false;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudioHub - <?php echo htmlspecialchars($studio['name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="index-page">
    <?php include 'includes/navbar.php'; ?>
    <div class="container my-5" style="max-width: 600px;">
        <div class="profile-header mb-4">
            <div class="story-container position-relative">
                <img src="<?php echo htmlspecialchars($studio['avatar'] ?: 'https://via.placeholder.com/120'); ?>" alt="Studio Avatar" class="profile-avatar rounded-circle">
                <?php if (!empty($stories)): ?>
                    <div class="story-ring"></div>
                <?php endif; ?>
            </div>
            <div class="profile-bio">
                <h2 class="fs-4 fw-bold"><?php echo htmlspecialchars($studio['name']); ?></h2>
                <p class="text-muted small mb-1"><?php echo htmlspecialchars($studio['services'] ?: 'No services'); ?></p>
                <p class="text-muted small mb-1"><?php echo htmlspecialchars($studio['location'] ?: 'No location'); ?></p>
                <p class="small"><strong><?php echo $follower_count; ?></strong> followers</p>
                <?php if (isset($_SESSION['user_id']) && $_SESSION['user_type'] == 'user'): ?>
                    <form method="POST" class="d-inline">
                        <button type="submit" name="follow" class="btn btn-<?php echo $is_following ? 'secondary' : 'primary'; ?> btn-sm">
                            <?php echo $is_following ? 'Unfollow' : 'Follow'; ?>
                        </button>
                    </form>
                    <a href="event-booking.php?studio_id=<?php echo $studio_id; ?>" class="btn btn-primary btn-sm ms-2">Book Event</a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Stories -->
        <?php if (!empty($stories)): ?>
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

        <hr>
        <h5 class="mb-3 text-center">Posts</h5>
        <?php if (empty($posts)): ?>
            <p class="text-muted text-center">No posts yet.</p>
        <?php else: ?>
            <div class="post-grid">
                <?php foreach ($posts as $post): ?>
                    <div class="position-relative">
                        <?php if ($post['type'] == 'image'): ?>
                            <img src="<?php echo htmlspecialchars($post['media_url']); ?>" alt="Post" class="w-100" style="aspect-ratio: 1; object-fit: cover;">
                        <?php else: ?>
                            <video controls class="w-100" style="aspect-ratio: 1; object-fit: cover;">
                                <source src="<?php echo htmlspecialchars($post['media_url']); ?>" type="video/mp4">
                            </video>
                        <?php endif; ?>
                        <?php if (isset($_SESSION['user_id']) && $_SESSION['user_type'] == 'user'): ?>
                            <form method="POST" class="position-absolute" style="bottom: 10px; right: 10px;">
                                <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                <button type="submit" class="btn btn-link p-0">
                                    <i class="bi bi-heart<?php echo $post['user_liked'] ? '-fill text-danger' : ''; ?>" style="font-size: 1.2rem;"></i>
                                </button>
                            </form>
                        <?php endif; ?>
                        <span class="position-absolute" style="bottom: 10px; left: 10px; color: white; text-shadow: 0 0 3px rgba(0,0,0,0.5);">
                            <?php echo $post['like_count']; ?> likes
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php include 'includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.js"></script>
</body>
</html>