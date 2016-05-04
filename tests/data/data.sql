INSERT INTO `users` (`id`, `name`, `password`, `email`, `key`, `key_encryption`, `admin`) VALUES
(1, 'admin', '8c6976e5b5410415bde908bd4dee15dfb167a9c873fc4bb8a81f6f2ab448a918', 'admin@admin.cz', 'FOBxxyBhM2HiFRFqrePdFPjsyLo4f/GQGELY3bCya+RSNL7FXixhIg==', 'Blowfish/CBC/PKCS5Padding', 1);

INSERT INTO `paths` (`id`, `user_id`, `parent_id`, `path`, `mktime`, `mdtime`, `checksum`) VALUES
(1, 1, NULL, '', 1460374617, 1460374617, NULL);

INSERT INTO `files` (`id`, `user_id`, `path_id`, `filename`, `size`, `mktime`, `mdtime`, `encryption`, `checksum`, `public`) VALUES
(60, 1, 1, 'settings.json', 336, 1460276768, 1460276768, 'AES/CBC/PKCS5Padding', 'f5070966c01ac63631b9cf3cdde5445a', 0);

INSERT INTO `versions` (`id`, `file_id`, `created`) VALUES
(1, 1, 1460276768);