-- Migration: rename password_hash to password (PlainText)
ALTER TABLE users CHANGE password_hash password VARCHAR(255);
