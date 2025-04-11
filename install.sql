CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    username VARCHAR(15) NOT NULL UNIQUE,
    password CHAR(60) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS invite_codes (
    id SERIAL PRIMARY KEY,
    code CHAR(29) NOT NULL UNIQUE,
    is_used BOOLEAN NOT NULL DEFAULT FALSE
);

CREATE TABLE IF NOT EXISTS auth_tokens (
    user_id INTEGER PRIMARY KEY REFERENCES users(id) ON DELETE CASCADE,
    token CHAR(32) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expired_at TIMESTAMP NOT NULL
);

CREATE TABLE IF NOT EXISTS course_progress (
    id SERIAL PRIMARY KEY,
    course_name TEXT NOT NULL,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    progress INTEGER DEFAULT 0,
    UNIQUE (course_name, user_id)
);

CREATE TABLE IF NOT EXISTS course_comments (
    id SERIAL PRIMARY KEY,
    course_name TEXT NOT NULL,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_course_comments_course_name ON course_comments(course_name);
CREATE INDEX IF NOT EXISTS idx_course_comments_created_at ON course_comments(created_at);

CREATE TABLE IF NOT EXISTS course_comments_likes (
    id SERIAL PRIMARY KEY,
    comment_id INTEGER NOT NULL REFERENCES course_comments(id) ON DELETE CASCADE,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    rate INT DEFAULT 0 CHECK (rate IN (1, -1)),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (comment_id, user_id)
);
CREATE INDEX IF NOT EXISTS idx_ccl_comment_id ON course_comments_likes(comment_id);
CREATE INDEX IF NOT EXISTS idx_ccl_comment_id_rate ON course_comments_likes(comment_id, rate);

CREATE OR REPLACE FUNCTION course_comments_with_likes(
    p_user_id INTEGER, 
    p_course_name TEXT
)
RETURNS TABLE (
    id INTEGER,
    course_name TEXT,
    user_id INTEGER,
    content TEXT,
    created_at TIMESTAMP,
    total_likes INTEGER,
    own_rate INTEGER
) AS $$
SELECT 
    cc.id,
    cc.course_name,
    cc.user_id,
    cc.content,
    cc.created_at,
    COALESCE(ccl.total_likes, 0) AS total_likes,
    COALESCE(ccl_own.rate, 0) AS own_rate
FROM course_comments cc
LEFT JOIN LATERAL (
    SELECT SUM(rate) AS total_likes
    FROM course_comments_likes
    WHERE comment_id = cc.id
) ccl ON true
LEFT JOIN course_comments_likes ccl_own 
    ON ccl_own.comment_id = cc.id 
    AND ccl_own.user_id = p_user_id
WHERE cc.course_name = p_course_name;
$$ LANGUAGE SQL STABLE;