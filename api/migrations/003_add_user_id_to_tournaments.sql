-- Migration: add user_id to tournaments
ALTER TABLE `tournaments`
  ADD COLUMN `user_id` INT UNSIGNED NULL AFTER `id`,
  ADD INDEX (`user_id`);

-- Optional foreign key (commented out for users who don't want FK constraints):
-- ALTER TABLE `tournaments` ADD CONSTRAINT fk_tournaments_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL;
