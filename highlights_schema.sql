-- highlights_schema.sql
-- Database schema for highlights functionality

-- Create highlights table
CREATE TABLE IF NOT EXISTS highlights (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    thumbnail VARCHAR(500) NOT NULL,
    video_url VARCHAR(500) NOT NULL,
    duration VARCHAR(10) NOT NULL,
    categories VARCHAR(255) NOT NULL, -- Comma-separated categories
    tags VARCHAR(500), -- Comma-separated tags
    featured BOOLEAN DEFAULT FALSE,
    views INT DEFAULT 0,
    likes INT DEFAULT 0,
    posted_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create user_saved_highlights table for user's saved highlights
CREATE TABLE IF NOT EXISTS user_saved_highlights (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    highlight_id INT NOT NULL,
    saved_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (highlight_id) REFERENCES highlights(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_highlight (user_id, highlight_id)
);

-- Create newsletter_subscriptions table
CREATE TABLE IF NOT EXISTS newsletter_subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    active BOOLEAN DEFAULT TRUE,
    subscribed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    unsubscribed_at TIMESTAMP NULL
);

-- Insert sample highlights data
INSERT INTO highlights (title, description, thumbnail, video_url, duration, categories, tags, featured, views, likes) VALUES
('Incredible Team Try - National Championship', 'The national team scores an amazing try after 15 phases of play in the championship final.', 'images/try-highlight.jpg', 'videos/try-highlight.mp4', '2:45', 'try,recent', 'championship,national,team', TRUE, 24500, 1200),
('Spectacular Solo Try - Youth League', 'A young player beats five defenders to score a memorable solo try in the Youth League finals.', 'images/rugby7.jpg', 'videos/solo-try.mp4', '2:10', 'try,recent', 'youth,solo,finals', FALSE, 12300, 850),
('Game-Saving Tackle - Kenya Cup Semifinal', 'An incredible try-saving tackle that secured victory for KCB in the dying minutes.', 'images/tackle-highlight.jpg', 'videos/tackle-save.mp4', '1:15', 'tackle,recent', 'kenya cup,kcb,semifinal', TRUE, 18200, 950),
('Match-Winning Penalty From 50m', 'A clutch penalty kick from the halfway line to win the match after the siren.', 'images/kick-highlight.jpg', 'videos/penalty-kick.mp4', '1:30', 'kick,recent', 'penalty,50m,clutch', FALSE, 22700, 1100),
('Classic: The Try That Won The 2019 Championship', 'Relive the iconic try that secured the championship in the final seconds of extra time.', 'images/classic-highlight.jpg', 'videos/classic-try.mp4', '3:20', 'try,classic', 'championship,2019,iconic', TRUE, 156000, 8500),
('Defensive Masterclass - 2018 Final', 'An incredible 20-phase defensive stand that secured the championship.', 'images/classic-tackle.jpg', 'videos/defensive-stand.mp4', '2:15', 'tackle,classic', 'defense,2018,championship', FALSE, 98300, 4200),
('Historic Drop Goal - 2015 World Cup', 'The iconic drop goal that secured victory against all odds in the World Cup quarterfinal.', 'images/classic-kick.jpg', 'videos/drop-goal.mp4', '1:45', 'kick,classic', 'world cup,2015,drop goal', TRUE, 210000, 12000);

-- Create indexes for better performance
CREATE INDEX idx_highlights_categories ON highlights(categories);
CREATE INDEX idx_highlights_featured ON highlights(featured);
CREATE INDEX idx_highlights_posted_date ON highlights(posted_date);
CREATE INDEX idx_highlights_views ON highlights(views);
CREATE INDEX idx_highlights_likes ON highlights(likes);
CREATE INDEX idx_user_saved_highlights_user_id ON user_saved_highlights(user_id);
CREATE INDEX idx_newsletter_subscriptions_email ON newsletter_subscriptions(email);
CREATE INDEX idx_newsletter_subscriptions_active ON newsletter_subscriptions(active);