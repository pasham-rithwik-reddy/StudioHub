SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS likes;
DROP TABLE IF EXISTS posts;
DROP TABLE IF EXISTS bookings;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    location VARCHAR(255),
    user_type ENUM('user', 'studio', 'admin') NOT NULL,
    services TEXT,
    avatar VARCHAR(255),
    image VARCHAR(255)
);

CREATE TABLE posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    content TEXT,
    type ENUM('image', 'video') NOT NULL,
    media_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE likes (
    user_id INT,
    post_id INT,
    PRIMARY KEY (user_id, post_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
);

CREATE TABLE bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    studio_id INT,
    event_date DATE NOT NULL,
    status ENUM('pending', 'accepted', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (studio_id) REFERENCES users(id) ON DELETE CASCADE
);

INSERT INTO users (id, name, email, password, location, user_type, services, avatar, image) VALUES
(1, 'Chei Studio', 'chei@studio.com', '$2y$10$5X8z8y2Z6Qz3v7m9k1w2Ae3Xb9f8g7h6i5j4k3l2m1n0p9q8r7s6t', 'Chei Beachfront', 'studio', 'Photography, Videography', 'https://randomuser.me/api/portraits/men/1.jpg', 'https://images.unsplash.com/photo-1519125323398-675f398f6978'),
(2, 'ArtVibe Studio', 'artvibe@studio.com', '$2y$10$5X8z8y2Z6Qz3v7m9k1w2Ae3Xb9f8g7h6i5j4k3l2m1n0p9q8r7s6t', 'Chei Downtown', 'studio', 'Event Planning, Video Production', 'https://randomuser.me/api/portraits/women/2.jpg', 'https://images.unsplash.com/photo-1511556820780-d912e42b4980'),
(3, 'Sky Creative', 'sky@studio.com', '$2y$10$5X8z8y2Z6Qz3v7m9k1w2Ae3Xb9f8g7h6i5j4k3l2m1n0p9q8r7s6t', 'Skyline District', 'studio', 'Graphic Design, Photography', 'https://randomuser.me/api/portraits/men/3.jpg', 'https://images.unsplash.com/photo-1507679799987-c73779587ccf'),
(4, 'Moonlight Studio', 'moonlight@studio.com', '$2y$10$5X8z8y2Z6Qz3v7m9k1w2Ae3Xb9f8g7h6i5j4k3l2m1n0p9q8r7s6t', 'Chei Hills', 'studio', 'Wedding Photography', 'https://randomuser.me/api/portraits/women/4.jpg', 'https://images.unsplash.com/photo-1521747116042-5a8107733f0e'),
(5, 'Urban Snap', 'urban@studio.com', '$2y$10$5X8z8y2Z6Qz3v7m9k1w2Ae3Xb9f8g7h6i5j4k3l2m1n0p9q8r7s6t', 'City Center', 'studio', 'Street Photography', 'https://randomuser.me/api/portraits/men/5.jpg', 'https://images.unsplash.com/photo-1517365830460-955ce3f6b1f7'),
(6, 'Star Studio', 'star@studio.com', '$2y$10$5X8z8y2Z6Qz3v7m9k1w2Ae3Xb9f8g7h6i5j4k3l2m1n0p9q8r7s6t', 'Starlight Avenue', 'studio', 'Video Editing', 'https://randomuser.me/api/portraits/women/6.jpg', 'https://images.unsplash.com/photo-1531297484009-0c37f01e7e84'),
(7, 'User1', 'user1@example.com', '$2y$10$5X8z8y2Z6Qz3v7m9k1w2Ae3Xb9f8g7h6i5j4k3l2m1n0p9q8r7s6t', 'Chei', 'user', NULL, NULL, NULL),
(8, 'Admin', 'admin@studiohub.com', '$2y$10$5X8z8y2Z6Qz3v7m9k1w2Ae3Xb9f8g7h6i5j4k3l2m1n0p9q8r7s6t', 'HQ', 'admin', NULL, NULL, NULL);

INSERT INTO posts (user_id, content, type, media_url, created_at) VALUES
(1, 'Captured a stunning sunset shoot! 🌅 #Photography', 'image', 'https://images.unsplash.com/photo-1506748686214-e9df14d4d9d0', NOW()),
(2, 'Behind-the-scenes of our music video! 🎬 #VideoProduction', 'video', 'https://www.w3schools.com/html/mov_bbb.mp4', NOW()),
(3, 'New track recorded today! 🎵 #MusicProduction', 'image', 'https://images.unsplash.com/photo-1511376777868-611b54f68947', NOW()),
(4, 'Designed a sleek logo! 🎨 #GraphicDesign', 'image', 'https://images.unsplash.com/photo-1600585154340-be6161a56a0c', NOW()),
(5, 'Edited a cinematic trailer! 🎥 #FilmEditing', 'video', 'https://www.w3schools.com/html/mov_bbb.mp4', NOW()),
(6, 'Perfect portrait session! 📸 #PortraitPhotography', 'image', 'https://images.unsplash.com/photo-1529626455594-4ff0802cfb7e', NOW());