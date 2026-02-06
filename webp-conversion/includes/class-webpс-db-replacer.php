<?php

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists( 'WebpC_DB_Replacer')) {

    class WebpC_DB_Replacer
    {

        /**
         * Recursively replaces old URLs with new URLs in an array or string.
         *
         * @param mixed $value Value to process (array or string).
         * @param array $old_urls Array of old URLs to replace.
         * @param array $new_urls Array of new URLs to replace with.
         * @return mixed Processed value with old URLs replaced by new URLs.
         */
        public static function webpc_recursive_replace($value, array $old_urls, array $new_urls)
        {
            if (is_array($value)) {
                foreach ($value as $k => $v) {
                    $value[$k] = self::webpc_recursive_replace($v, $old_urls, $new_urls);
                }
            } elseif (is_string($value)) {
                foreach ($old_urls as $i => $old_url) {
                    if (!empty($new_urls[$i])) {
                        $value = str_replace($old_url, $new_urls[$i], $value);
                    }
                }
            }
            return $value;
        }

        /**
         * Updates ACF fields replacing old URLs with new URLs.
         *
         * @param int $id Attachment ID used for filtering ACF fields.
         * @param array $old_urls Array of old URLs to replace.
         * @param array $new_urls Array of new URLs to replace with.
         * @return void
         */
        public static function webpc_db_replace_handle_acf($id, array $old_urls, array $new_urls)
        {
            global $wpdb;

            $acf_fields = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT post_id, meta_key, meta_value FROM {$wpdb->postmeta} WHERE meta_value LIKE %s",
                    '%' . $wpdb->esc_like($id) . '%'
                )
            );

            foreach ($acf_fields as $field) {
                $value = maybe_unserialize($field->meta_value);
                $updated_value = self::webpc_recursive_replace($value, $old_urls, $new_urls);

                if ($updated_value !== $value) {
                    update_post_meta($field->post_id, $field->meta_key, maybe_serialize($updated_value));
                }
            }
        }

        /**
         * Updates option fields replacing old URLs with new URLs.
         *
         * @param array $old_urls Array of old URLs to replace.
         * @param array $new_urls Array of new URLs to replace with.
         * @return void
         */
        public static function webpc_db_replace_handle_acf_option_fields($old_urls, $new_urls)
        {
            global $wpdb;

            foreach ($old_urls as $old_url) {
                $options = $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT option_id, option_value FROM {$wpdb->options} WHERE option_value LIKE %s",
                        '%' . $wpdb->esc_like($old_url) . '%'
                    )
                );

                foreach ($options as $option) {
                    $value = maybe_unserialize($option->option_value);

                    $updated_value = self::webpc_recursive_replace($value, $old_urls, $new_urls);

                    if ($updated_value !== $value) {
                        $wpdb->update(
                            $wpdb->options,
                            ['option_value' => maybe_serialize($updated_value)],
                            ['option_id' => $option->option_id]
                        );
                    }
                }
            }
        }

        /**
         * Updates post content and excerpt replacing old URLs with new URLs.
         *
         * @param array $old_urls Array of old URLs to replace.
         * @param array $new_urls Array of new URLs to replace with.
         * @return void
         */
        public static function webpc_db_replace_handle_post_content_post_excerpt(array $old_urls, array $new_urls)
        {
            global $wpdb;

            foreach ($old_urls as $index => $old_url) {
                if (empty($new_urls[$index])) {
                    continue;
                }
                $new_url = $new_urls[$index];

                //Post content
                $wpdb->query(
                    $wpdb->prepare(
                        "UPDATE {$wpdb->posts} SET post_content = REPLACE(post_content, %s, %s) WHERE post_content LIKE %s",
                        $old_url,
                        $new_url,
                        '%' . $wpdb->esc_like($old_url) . '%'
                    )
                );

                //Post excerpt
                $wpdb->query(
                    $wpdb->prepare(
                        "UPDATE {$wpdb->posts} SET post_excerpt = REPLACE(post_excerpt, %s, %s) WHERE post_excerpt LIKE %s",
                        $old_url,
                        $new_url,
                        '%' . $wpdb->esc_like($old_url) . '%'
                    )
                );

            }
        }

        /**
         * Updates meta tables (postmeta, usermeta, termmeta) replacing old URL with new URL.
         *
         * @param string $old_url Old URL to replace.
         * @param string $new_url New URL to replace with.
         * @return void
         */
        public static function webpc_db_replace_handle_urls($old_url, $new_url)
        {

            global $wpdb;

            $wpdb_metas = [$wpdb->postmeta, $wpdb->usermeta, $wpdb->termmeta];

            foreach ($wpdb_metas as $meta) {
                $cache_key = "{$meta}_meta_value_" . md5($old_url);
                $cached_meta = wp_cache_get($cache_key);

                if ($cached_meta === false) {
                    $wpdb->query(
                        $wpdb->prepare(
                            "UPDATE $meta SET meta_value = REPLACE(meta_value, %s, %s) WHERE meta_value LIKE %s",
                            $old_url,
                            $new_url,
                            '%' . $wpdb->esc_like($old_url) . '%'
                        )
                    );
                    wp_cache_set($cache_key, $new_url);
                }
            }
        }

    }

}