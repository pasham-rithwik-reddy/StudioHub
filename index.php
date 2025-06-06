<?php
session_start();
require_once 'includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'user') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch posts from followed studios
$stmt = $pdo->prepare("
    SELECT p.*, u.name as studio_name, u.avatar as studio_avatar, 
           (SELECT COUNT(*) FROM likes l WHERE l.post_id = p.id) as like_count,
           (SELECT 1 FROM likes l WHERE l.post_id = p.id AND l.user_id = ?) as user_liked
    FROM posts p
    JOIN users u ON p.user_id = u.id
    JOIN follows f ON p.user_id = f.studio_id
    WHERE f.user_id = ?
    ORDER BY p.created_at DESC
");
$stmt->execute([$user_id, $user_id]);
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle like/unlike
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['post_id'])) {
    $post_id = (int)$_POST['post_id'];
    $stmt = $pdo->prepare("SELECT * FROM likes WHERE user_id = ? AND post_id = ?");
    $stmt->execute([$user_id, $post_id]);
    if ($stmt->fetch()) {
        $stmt = $pdo->prepare("DELETE FROM likes WHERE user_id = ? AND post_id = ?");
        $stmt->execute([$user_id, $post_id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO likes (user_id, post_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $post_id]);
    }
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudioHub - Home</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="index-page">
    <?php include 'includes/navbar.php'; ?>
    <div class="container my-5" style="max-width: 600px;">
        <div class="text-center mb-4">
            <img src="uploads/images/logo.png.jpg" alt="StudioHub Logo" class="mb-2 logo-img-large">
            <h2 class="fs-4 fw-bold">StudioHub</h2>
            <p class="text-muted small">Explore creative studios</p>
        </div>
        <?php if (isset($_GET['booked'])): ?>
            <div class="alert alert-success">Booking request sent successfully!</div>
        <?php endif; ?>
        <?php if (empty($posts)): ?>
            <p class="text-muted text-center">Follow studios to see their posts!</p>
        <?php else: ?>
            <?php foreach ($posts as $post): ?>
                <div class="card mb-3 shadow">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-2">
                            <img src="<?php echo htmlspecialchars($post['studio_avatar'] ?: 'https://via.placeholder.com/40'); ?>" alt="Studio Avatar" class="rounded-circle me-2" style="width: 40px; height: 40px;">
                            <h6 class="mb-0"><a href="studio-profile.php?id=<?php echo $post['user_id']; ?>" class="text-decoration-none"><?php echo htmlspecialchars($post['studio_name']); ?></a></h6>
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
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                            <button type="submit" class="btn btn-link p-0">
                                <i class="bi bi-heart<?php echo $post['user_liked'] ? '-fill text-danger' : ''; ?>" style="font-size: 1.2rem;"></i>
                            </button>
                        </form>
                        <p class="text-muted small"><?php echo $post['created_at']; ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <?php include 'includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.js"></script>
</body>
</html>