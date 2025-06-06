<?php
require_once 'config.php';

function getPosts($filter = 'all') {
    global $pdo;
    
    $query = "SELECT p.id, p.user_id, p.content, p.type, p.media_url, p.created_at, u.name AS studio, u.avatar 
              FROM posts p JOIN users u ON p.user_id = u.id";
    if ($filter !== 'all') {
        $query .= " WHERE p.type = ?";
    }
    $query .= " ORDER BY p.created_at DESC";
    
    $stmt = $pdo->prepare($query);
    if ($filter !== 'all') {
        $stmt->execute([$filter]);
    } else {
        $stmt->execute();
    }
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function toggleLike($user_id, $post_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT id FROM likes WHERE user_id = ? AND post_id = ?");
    $stmt->execute([$user_id, $post_id]);
    
    if ($stmt->fetch()) {
        $stmt = $pdo->prepare("DELETE FROM likes WHERE user_id = ? AND post_id = ?");
        $stmt->execute([$user_id, $post_id]);
        return false; // Unliked
    } else {
        $stmt = $pdo->prepare("INSERT INTO likes (user_id, post_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $post_id]);
        return true; // Liked
    }
}

function getLikeCount($post_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) AS count FROM likes WHERE post_id = ?");
    $stmt->execute([$post_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC)['count'];
}

function isLiked($user_id, $post_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT id FROM likes WHERE user_id = ? AND post_id = ?");
    $stmt->execute([$user_id, $post_id]);
    return $stmt->fetch() !== false;
}
?>