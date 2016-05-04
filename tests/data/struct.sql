-- Creator:       MySQL Workbench 6.1.7/ExportSQLite plugin 2009.12.02
-- Author:        Kamen
-- Caption:       New Model
-- Project:       Name of the project
-- Changed:       2016-04-03 01:21
-- Created:       2016-03-13 21:06
PRAGMA foreign_keys = OFF;

-- Schema: minicloud
BEGIN;
CREATE TABLE "users"(
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL CHECK("id">=0),
  "name" VARCHAR(64) NOT NULL,
  "password" VARCHAR(64) NOT NULL,
  "email" VARCHAR(128) DEFAULT NULL,
  "key" TEXT DEFAULT NULL,
  "key_encryption" VARCHAR(255),
  "admin" INTEGER NOT NULL DEFAULT '0',
  CONSTRAINT "name"
    UNIQUE("name")
);
CREATE TABLE "paths"(
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL CHECK("id">=0),
  "user_id" INTEGER NOT NULL CHECK("user_id">=0),
  "parent_id" INTEGER CHECK("parent_id">=0),
  "path" VARCHAR(256) NOT NULL,
  "mktime" INTEGER NOT NULL,
  "mdtime" INTEGER NOT NULL,
  "checksum" VARCHAR(64),
  CONSTRAINT "fk_paths_user"
    FOREIGN KEY("user_id")
    REFERENCES "users"("id")
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT "fk_paths_parent"
    FOREIGN KEY("parent_id")
    REFERENCES "paths"("id")
    ON DELETE CASCADE
    ON UPDATE CASCADE
);
CREATE INDEX "paths.fk_paths_user_idx" ON "paths"("user_id");
CREATE INDEX "paths.fk_paths_parent_idx" ON "paths"("parent_id");
CREATE TABLE "files"(
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL CHECK("id">=0),
  "user_id" INTEGER CHECK("user_id">=0),
  "path_id" INTEGER CHECK("path_id">=0),
  "filename" VARCHAR(256) NOT NULL,
  "size" INTEGER NOT NULL,
  "mktime" INTEGER NOT NULL,
  "mdtime" INTEGER NOT NULL,
  "encryption" VARCHAR(256),
  "checksum" VARCHAR(64),
  "public" BOOL DEFAULT 0,
  CONSTRAINT "fk_files_path"
    FOREIGN KEY("path_id")
    REFERENCES "paths"("id")
    ON DELETE SET NULL
    ON UPDATE SET NULL,
  CONSTRAINT "fk_files_user"
    FOREIGN KEY("user_id")
    REFERENCES "users"("id")
    ON DELETE SET NULL
    ON UPDATE SET NULL
);
CREATE INDEX "files.fk_files_path_idx" ON "files"("path_id");
CREATE INDEX "files.fk_files_user_idx" ON "files"("user_id");
CREATE TABLE "versions"(
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL CHECK("id">=0),
  "file_id" INTEGER NOT NULL CHECK("file_id">=0),
  "created" INTEGER,
  CONSTRAINT "fk_versions_file"
    FOREIGN KEY("file_id")
    REFERENCES "files"("id")
    ON DELETE CASCADE
    ON UPDATE CASCADE
);
CREATE INDEX "versions.fk_versions_file_idx" ON "versions"("file_id");
COMMIT;
