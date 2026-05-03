CREATE DATABASE IF NOT EXISTS voterflow;
USE voterflow;

CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255),
    oauth_provider ENUM('local','google') DEFAULT 'local',
    oauth_id VARCHAR(255),
    is_admin TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS user_preferences (
    user_id INT PRIMARY KEY,
    state_province VARCHAR(100),
    registration_status ENUM('registered','unregistered','unknown') DEFAULT 'unknown',
    dark_mode TINYINT(1) DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS election_events (
    event_id INT AUTO_INCREMENT PRIMARY KEY,
    region VARCHAR(100),
    event_name VARCHAR(255),
    event_date DATE
);

CREATE TABLE IF NOT EXISTS user_milestones (
    user_id INT PRIMARY KEY,
    is_registered BOOLEAN DEFAULT FALSE,
    ballot_requested BOOLEAN DEFAULT FALSE,
    voted BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS chat_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    role ENUM('user','assistant') NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    message VARCHAR(500) NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS quiz_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    score INT NOT NULL,
    total INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS chat_reactions (
    user_id INT NOT NULL,
    msg_index INT NOT NULL,
    reaction ENUM('up','down') NOT NULL,
    PRIMARY KEY (user_id, msg_index),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS user_badges (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    badge_key VARCHAR(50) NOT NULL,
    badge_name VARCHAR(100) NOT NULL,
    badge_icon VARCHAR(10) NOT NULL,
    earned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_badge (user_id, badge_key),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Sample election events
INSERT INTO election_events (region, event_name, event_date) VALUES
('California', 'Voter Registration Deadline', '2025-10-20'),
('California', 'Mail-in Ballot Request Deadline', '2025-10-28'),
('California', 'Election Day', '2025-11-04'),
('Texas', 'Voter Registration Deadline', '2025-10-07'),
('Texas', 'Early Voting Starts', '2025-10-20'),
('Texas', 'Election Day', '2025-11-04'),
('New York', 'Voter Registration Deadline', '2025-10-25'),
('New York', 'Early Voting Starts', '2025-10-26'),
('New York', 'Election Day', '2025-11-04');
