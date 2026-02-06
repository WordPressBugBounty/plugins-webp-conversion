<?php

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists( 'WebpC_Remover')) {

    class WebpC_Remover
    {

        /**
         * Deletes file by its path.
         *
         * @param string $file_path Path of the file to remove
         * @return bool True if file exists and was deleted. False if file does not exist.
         */
        public static function webpc_delete_file_directly($file_path): bool
        {
            if (file_exists($file_path)) {
                wp_delete_file($file_path);
                return true;
            } else {
                return false;
            }
        }

        /**
         * Deletes multiple files by its path.
         *
         * @param array $paths Path of the files to remove
         * @return array Array of deleted files.
         */
        public static function webpc_delete_images_by_paths($paths): array
        {
            $deleted = [];

            foreach ($paths as $file_path) {
                if (file_exists($file_path)) {
                    if (wp_delete_file($file_path)) {
                        $deleted[] = $file_path;
                    }
                }
            }

            return $deleted;
        }

        /**
         * Removes original image if image was converted to .webp and original was saved in a separate folder.
         *
         * @param int $attachment_id Attachment ID of image that has an original to be removed
         * @return bool Whether image was deleted.
         */
        public static function webpc_remove_original($attachment_id): bool
        {
            $backup_file = get_post_meta($attachment_id, '_webpc_backup_file', true);

            $return = false;

            if (!empty($backup_file)) {
                if (file_exists($backup_file)) {
                    wp_delete_file($backup_file);
                    $return = true;
                    self::webpc_maybe_remove_backup_folder();
                }
                delete_post_meta($attachment_id, '_webpc_backup_file');
            }

            return $return;
        }

        /**
         * Removes "webpc-backup" folder and its contents.
         *
         * @return bool Whether folder was deleted.
         */
        public static function webpc_remove_backup_folder(): bool
        {
            $upload_dir = wp_get_upload_dir();
            $backup_dir = trailingslashit($upload_dir['basedir']) . 'webpc-backup';

            if (!is_dir($backup_dir)) {
                return false;
            }

            self::webpc_rrmdir($backup_dir);

            return true;
        }

        /**
         * Removes "webpc-backup" folder only if its empty.
         *
         * @return bool Whether folder was deleted.
         */
        public static function webpc_maybe_remove_backup_folder(): bool
        {
            $upload_dir = wp_get_upload_dir();
            $backup_dir = trailingslashit($upload_dir['basedir']) . 'webpc-backup';

            if (!is_dir($backup_dir)) {
                return false;
            }

            if (self::webpc_is_dir_empty($backup_dir)) {
                return self::webpc_remove_backup_folder();
            }

            return false;
        }

        /**
         * Checks if a directory is empty or not readable.
         *
         * @param string $dir Path to the directory to check.
         * @return bool True if directory is empty or not readable, false otherwise.
         */
        public static function webpc_is_dir_empty($dir): bool
        {
            if (!is_readable($dir)) {
                return true;
            }
            return count(scandir($dir)) === 2;
        }

        /**
         * Recursively deletes a folder and all its contents.
         *
         * @param string $dir Path to the directory.
         * @return bool True if directory was removed, false otherwise.
         */
        public static function webpc_rrmdir(string $dir): bool
        {
            if (!is_dir($dir)) {
                return false;
            }
            if (!function_exists('WP_Filesystem')) {
                require_once ABSPATH . 'wp-admin/includes/file.php';
            }
            global $wp_filesystem;
            WP_Filesystem();

            $items = array_diff(scandir($dir), ['.', '..']);
            foreach ($items as $item) {
                $path = $dir . DIRECTORY_SEPARATOR . $item;
                if (is_dir($path)) {
                    self::webpc_rrmdir($path);
                } else {
                    $wp_filesystem->delete($path, true);
                }
            }

            return $wp_filesystem->rmdir($dir, true);
        }

    }

}