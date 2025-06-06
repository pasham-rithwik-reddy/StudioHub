<?php
session_start();
require_once 'config.php';

function registerUser($name, $email, $password, $password_confirm, $location, $user_type, $services = '') {
    global $pdo;
    
    if (empty($name) || empty($email) || empty($password) || empty($user_type)) {
        return ['success' => false, 'message' => 'All required fields must be filled.'];
    }
    
    if ($password !== $password_confirm) {
        return ['success' => false, 'message' => 'Passwords do not match.'];
    }
    
    if (strlen($password) < 6) {
        return ['success' => false, 'message' => 'Password must be at least 6 characters.'];
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Invalid email format.'];
    }
    
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        return ['success' => false, 'message' => 'Email already registered.'];
    }
    
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, location, user_type, services) VALUES (?, ?, ?, ?, ?, ?)");
    
    try {
        $stmt->execute([$name, $email, $hashed_password, $location, $user_type, $user_type === 'studio' ? $services : null]);
        return ['success' => true, 'message' => 'Registration successful! Please log in.'];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()];
    }
}

function loginUser($email, $password) {
    global $pdo;
    
    if (empty($email) || empty($password)) {
        return ['success' => false, 'message' => 'Email and password are required.'];
    }
    
    $stmt = $pdo->prepare("SELECT id, name, email, password, user_type FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_type'] = $user['user_type'];
        return ['success' => true, 'message' => 'Login successful!'];
    }
    
    return ['success' => false, 'message' => 'Invalid email or password.'];
}