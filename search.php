<?php
session_start();
require_once 'includes/config.php';

$search_results = [];
$search_query = '';

if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['query'])) {
    $search_query = trim($_GET['query']);
    if (!empty($search_query)) {
        $stmt = $pdo->prepare("SELECT id, name, location, services, avatar, image 
                              FROM users 
                              WHERE user_type = 'studio' 
                              AND (name LIKE ? OR location LIKE ? OR services LIKE ?)
                              LIMIT 6");
        $like_query = '%' . $search_query . '%';
        $stmt->execute([$like_query, $like_query, $like_query]);
        $search_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $stmt = $pdo->prepare("SELECT id, name, location, services, avatar, image 
                              FROM users 
                              WHERE user_type = 'studio' 
                              LIMIT 6");
        $stmt->execute();
        $search_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudioHub - Search Studios</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="search-page">
    <?php include 'includes/navbar.php'; ?>
    <div class="search-container">
        <div class="search-grid p-4" id="search-grid-theme">
            <div class="text-center mb-4">
            <img src="uploads/images/logo.png.jpg" alt="StudioHub Logo" class="mb-2">
                <h2 class="fs-4 fw-bold">StudioHub</h2>
                <p class="text-muted small">Discover studios</p>
            </div>
            <form method="GET" class="mb-5">
                <div class="form-floating search-bar-container mx-auto">
                    <input type="text" class="form-control" id="query" name="query" placeholder="Search studios..." value="<?php echo htmlspecialchars($search_query); ?>">
                    <label for="query">Search by name, location, or services...</label>
                    <div class="invalid-feedback">Please enter a search term.</div>
                </div>
                <button type="submit" class="btn btn-primary w-100 mt-2">Search</button>
            </form>
            <div class="stories-teaser mb-5 d-flex gap-2 overflow-auto justify-content-center">
                <img src="https://randomuser.me/api/portraits/men/4.jpg" class="rounded-circle" alt="Studio 1">
                <img src="https://randomuser.me/api/portraits/women/5.jpg" class="rounded-circle" alt="Studio 2">
                <img src="https://randomuser.me/api/portraits/men/6.jpg" class="rounded-circle" alt="Studio 3">
            </div>
            <div class="search-results row g-4">
                <?php if (empty($search_results) && !empty($search_query)): ?>
                    <p class="text-center text-muted col-12">No studios found.</p>
                <?php else: ?>
                    <?php foreach ($search_results as $studio): ?>
                        <div class="col-md-4">
                            <div class="studio-card h-100 d-flex flex-column text-center p-3 rounded">
                                <img src="<?php echo htmlspecialchars($studio['image'] ?: 'https://via.placeholder.com/200'); ?>" alt="Studio Image" class="studio-image mb-2 rounded" style="width: 100%; height: 120px; object-fit: cover;">
                                <img src="<?php echo htmlspecialchars($studio['avatar'] ?: 'https://via.placeholder.com/80'); ?>" alt="Studio Avatar" class="rounded-circle mb-2" style="width: 80px; height: 80px;">
                                <h6 class="mb-1 fw-bold"><?php echo htmlspecialchars($studio['name']); ?></h6>
                                <p class="text-muted small mb-1"><?php echo htmlspecialchars($studio['location'] ?: 'No location'); ?></p>
                                <p class="small mb-2"><?php echo htmlspecialchars($studio['services'] ?: 'No services'); ?></p>
                                <a href="studio-profile.php?id=<?php echo $studio['id']; ?>" class="btn btn-primary btn-sm">View</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php for ($i = count($search_results); $i < 6; $i++): ?>
                        <div class="col-md-4">
                            <div class="studio-card h-100 d-flex flex-column text-center p-3 rounded invisible">
                                <img src="https://via.placeholder.com/200" alt="Placeholder" class="studio-image mb-2 rounded" style="width: 100%; height: 120px; object-fit: cover;">
                                <img src="https://via.placeholder.com/80" alt="Placeholder" class="rounded-circle mb-2" style="width: 80px; height: 80px;">
                                <h6 class="mb-1 fw-bold">Placeholder</h6>
                                <p class="text-muted small mb-1">No location</p>
                                <p class="small mb-2">No services</p>
                                <a href="#" class="btn btn-primary btn-sm disabled">View</a>
                            </div>
                        </div>
                    <?php endfor; ?>
                <?php endif; ?>
            </div>
            <div class="text-center mt-5">
                <button class="btn btn-outline-secondary btn-sm" id="theme-toggle">Toggle Dark Mode</button>
            </div>
        </div>
    </div>
    <?php include 'includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/script.js"></script>
    <script>
        const form = document.querySelector('form');
        form.addEventListener('submit', function(e) {
            if (!this.querySelector('input').value.trim()) {
                e.preventDefault();
                this.classList.add('was-validated');
            }
        });
        const themeToggle = document.getElementById('theme-toggle');
        const gridTheme = document.getElementById('search-grid-theme');
        themeToggle.addEventListener('click', () => {
            gridTheme.classList.toggle('bg-dark');
            gridTheme.classList.toggle('bg-white');
            gridTheme.classList.toggle('text-white');
            gridTheme.classList.toggle('text-dark');
        });
    </script>
</body>
</html>