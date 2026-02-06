<?php
/**
 * Plugin Name: WebP Conversion
 * Description: Convert your media images into .webp extension with no limits!
 * Version: 2.2
 * Author: SheepFish
 * Author URI: https://sheep.fish/
 * Requires at least: 6.4
 * Requires PHP: 8.1
 * Text Domain: webp-conversion
 * License: GPL2
 */

if (!defined('ABSPATH')) {
    exit;
}

require 'vendor/autoload.php';

use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

if (!class_exists('WEBPCbySheepFish')) {

    class WEBPCbySheepFish
    {

        private static $instance = null;

        private $plugin_page;
        private $plugin_path;
        private $plugin_url;
        private $plugin_basename;

        private $webpc_auto;
        private $webpc_svg;
        private $webpc_ico;
        private $webpc_remove;
        private $webpc_conversion_library;
        private $webpc_200kb;
        private $webpc_1000kb;
        private $webpc_2500kb;
        private $webpc_more_2500kb;

        public function __construct()
        {

            $this->plugin_page = admin_url('options-general.php?page=webp-conversion');
            $this->plugin_path = plugin_dir_path(__FILE__);
            $this->plugin_url = plugin_dir_url(__FILE__);
            $this->plugin_basename = plugin_basename(__FILE__);

            $this->webpc_auto = get_option('webpc_auto');
            $this->webpc_svg = get_option('webpc_svg');
            $this->webpc_ico = get_option('webpc_ico');
            $this->webpc_remove = get_option('webpc_remove');
            $this->webpc_conversion_library = get_option('webpc_conversion_library');
            $this->webpc_200kb = intval(get_option('webpc_200kb', 75));
            $this->webpc_1000kb = intval(get_option('webpc_1000kb', 70));
            $this->webpc_2500kb = intval(get_option('webpc_2500kb', 50));
            $this->webpc_more_2500kb = intval(get_option('webpc_more_2500kb', 45));

            $this->include_files();

            register_activation_hook(__FILE__, [$this, 'webp_conversion_activate']);
            add_action('admin_init', [$this, 'webp_conversion_redirect']);
            register_deactivation_hook(__FILE__, [$this, 'webp_conversion_deactivate']);

            add_action('admin_menu', [$this, 'register_submenu_page']);
            add_filter('plugin_action_links_' . $this->plugin_basename, [$this, 'webpc_plugin_action_links']);
            add_action('admin_enqueue_scripts', [$this, 'webpc_enqueue_scripts_and_styles']);
            add_action('admin_init', [$this, 'webpc_register_settings']);
            add_filter('attachment_fields_to_edit', [$this, 'add_custom_media_button'], 10, 2);

            add_action('wp_handle_upload', [$this, 'webpc_auto_convert']);

            add_action('wp_ajax_webpc_convert_single', [$this, 'webpc_convert_certain']);
            add_action('wp_ajax_nopriv_webpc_convert_single', [$this, 'webpc_convert_certain']);
            add_action('wp_ajax_webpc_convert_selected', [$this, 'webpc_convert_certain']);
            add_action('wp_ajax_nopriv_webpc_convert_selected', [$this, 'webpc_convert_certain']);
            add_action('wp_ajax_webpc_restore_selected', [$this, 'webpc_restore_selected']);
            add_action('wp_ajax_nopriv_webpc_restore_selected', [$this, 'webpc_restore_selected']);
            add_action('wp_ajax_webpc_remove_originals_selected', [$this, 'webpc_remove_originals_selected']);
            add_action('wp_ajax_nopriv_webpc_remove_originals_selected', [$this, 'webpc_remove_originals_selected']);

            add_action('wp_ajax_webpc_restore_single', [$this, 'webpc_restore_single']);
            add_action('wp_ajax_nopriv_webpc_restore_single', [$this, 'webpc_restore_single']);
            add_action('wp_ajax_webpc_remove_single', [$this, 'webpc_remove_single']);
            add_action('wp_ajax_nopriv_webpc_remove_single', [$this, 'webpc_remove_single']);

            add_action('delete_attachment', ['WebpC_Remover', 'webpc_remove_original']);

            add_action('post-upload-ui', [$this, 'add_select_button']);

            add_action('wp_ajax_update', [$this, 'webpc_update_settings']);
            add_action('wp_ajax_nopriv_update', [$this, 'webpc_update_settings']);

            add_action('wp_ajax_webpc_remove_all_originals', [$this, 'webpc_remove_all_originals']);
            add_action('wp_ajax_nopriv_webpc_remove_all_originals', [$this, 'webpc_remove_all_originals']);

            add_filter('bulk_actions-upload', [$this, 'register_webpc_media_action']);
            add_filter('handle_bulk_actions-upload', [$this, 'webpc_bulk_actions_handler'], 10, 3);
            add_action('admin_notices', [$this, 'convert_selected_admin_notice']);

            add_filter('upload_mimes', [$this, 'allow_svg_ico_mimes']);
            add_filter('wp_check_filetype_and_ext', [$this, 'return_mime_types'], 10, 4);

        }


        /**
         * Includes files from /includes folder (Classes WebpC_Remover and WebpC_DB_Replacer).
         */
        public function include_files()
        {
            require_once $this->plugin_path . 'includes/class-webpc-remover.php';
            require_once $this->plugin_path . 'includes/class-webpс-db-replacer.php';
        }

        /**
         * Gets Plugin Instance.
         *
         * @return WEBPCbySheepFish Plugin's class instance for accessing methods in this class.
         */
        public static function get_instance()
        {
            if (self::$instance == null) {
                self::$instance = new WEBPCbySheepFish();
            }
            return self::$instance;
        }

        /**
         * Sets transient for redirection to plugin's page, updates options on settings page to default value.
         *
         * @return void.
         */
        public function webp_conversion_activate(): void
        {
            set_transient('webpc_redirect', true, 30);
            if ($this->webpc_auto === false) {
                update_option('webpc_auto', 1);
            }
            if ($this->webpc_svg === false) {
                update_option('webpc_svg', 1);
            }
            if ($this->webpc_ico === false) {
                update_option('webpc_ico', 1);
            }
            if ($this->webpc_remove === false) {
                update_option('webpc_remove', 0);
            }
            if ($this->webpc_conversion_library === false) {
                update_option('webpc_conversion_library', 'auto');
            }
            flush_rewrite_rules();
        }

        /**
         * Redirects to plugin page after activation.
         *
         * @return void.
         */
        public function webp_conversion_redirect(): void
        {
            if (get_transient('webpc_redirect')) {

                delete_transient('webpc_redirect');
                wp_redirect($this->plugin_page);
                exit;

            }
        }

        /**
         * Flushes rewrite rules on plugin deactivation.
         *
         * @return void.
         */
        public function webp_conversion_deactivate(): void
        {
            flush_rewrite_rules();
        }

        /**
         * Includes uninstall.php on plugin uninstall.
         *
         * @return void.
         */
        public function webp_conversion_uninstall(): void
        {
            include_once($this->plugin_path . 'uninstall.php');
        }

        /**
         * Registers plugin tab menu.
         *
         * @return void.
         */
        public function register_submenu_page(): void
        {

            add_submenu_page(
                'options-general.php',
                __('WebP Conversion', 'webp-conversion'),
                __('WebP Conversion', 'webp-conversion'),
                'manage_options',
                'webp-conversion',
                [$this, 'webpc_plugin_page_content']
            );

        }

        /**
         * Requires webpc-settings-page.php template for displaying plugin's settings page content.
         *
         * @return void.
         */
        public function webpc_plugin_page_content(): void
        {
            require 'templates/webpc-settings-page.php';
        }

        /**
         * Adds a custom "Settings" link to the plugin action links on the Plugins page.
         *
         * This method hooks into the plugin action links and appends a link
         * to the plugin's settings page, making it easier for users to access
         * the plugin configuration directly from the WordPress Plugins list.
         *
         * @param array $links Existing action links for the plugin.
         * @return array Modified array of action links including the custom Settings link.
         */
        public function webpc_plugin_action_links($links): array
        {
            $custom_link = '<a href="' . $this->plugin_page . '">' . __('Settings', 'webp-conversion') . '</a>';
            array_push($links, $custom_link);
            return $links;
        }

        /**
         * Enqueues plugins scripts and styles.
         *
         * @return void.
         */
        public function webpc_enqueue_scripts_and_styles(): void
        {
            wp_enqueue_style(
                'webpc_style',
                $this->plugin_url . 'assets/css/style.css',
                [],
                '1.0',
                'all'
            );

            wp_enqueue_script(
                'webpc_ajax_script',
                $this->plugin_url . 'assets/js/ajax.js',
                ['jquery'],
                '1.0',
                true
            );

            wp_localize_script(
                'webpc_ajax_script',
                'webp_conversion',
                [
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('webpc_nonce'),
                    'message' => [
                        'conversion_failed' => __('Failed to convert image.', 'webp-conversion'),
                        'server_conversion_error' => __(
                            'Error occurred while converting image. The server cannot process the image. This can happen if the server is busy or does not have enough resources to complete the task. Uploading a smaller image may help.',
                            'webp-conversion'
                        ),
                        'restoring_failed' => __('Failed to restore image.', 'webp-conversion'),
                        'server_restoring_error' => __(
                            'Error occurred while restoring image. The server cannot process the image. This can happen if the server is busy or does not have enough resources to complete the task. Uploading a smaller image may help.',
                            'webp-conversion'
                        ),
                        'removing_failed' => __('Failed to remove image.', 'webp-conversion'),
                        'server_removing_error' => __(
                            'Error occurred while removing image.',
                            'webp-conversion'
                        ),
                        'batch_process_error' => __('Failed to process selected images.', 'webp-conversion'),
                        'server_batch_process_error' => __(
                            'Error occurred while processing images.',
                            'webp-conversion'
                        ),
                        'server_batch_complex_task_error' => __(
                            'Error occurred while restoring images. The server cannot process the image. This can happen if the server is busy or does not have enough resources to complete the task. Uploading a smaller image may help. Please check attachments with ids: ',
                            'webp-conversion'
                        ),
                        'no_images_selected' => __('No images selected.', 'webp-conversion'),
                        'continue_processing' => __('Continue processing images?', 'webp-conversion'),
                        'are_you_sure_to_remove_originals' => __('Are you sure? It will be only applied for images that were converted to .webp', 'webp-conversion')
                    ]
                ]
            );

            wp_enqueue_script(
                'webpc_main_script',
                $this->plugin_url . 'assets/js/main.js',
                ['jquery'],
                '1.0',
                true
            );
        }

        /**
         * Registers plugins settings.
         *
         * @return void.
         */
        public function webpc_register_settings(): void
        {
            register_setting('webpc-settings-group', 'webpc_auto', 'intval');
            register_setting('webpc-settings-group', 'webpc_svg', 'intval');
            register_setting('webpc-settings-group', 'webpc_ico', 'intval');
            register_setting('webpc-settings-group', 'webpc_remove', 'intval');
            register_setting('webpc-settings-group', 'webpc_conversion_library', 'sanitize_text_field');
            register_setting('webpc-settings-group', 'webpc_200kb', 'intval');
            register_setting('webpc-settings-group', 'webpc_1000kb', 'intval');
            register_setting('webpc-settings-group', 'webpc_2500kb', 'intval');
            register_setting('webpc-settings-group', 'webpc_more_2500kb', 'intval');
        }

        /**
         * Gets available library for conversion based on user's settings.
         *
         * @return string.
         */
        public function webpc_get_available_library(): string{
            $gd_available = extension_loaded('gd');
            $imagick_available = extension_loaded('imagick');

            $saved = get_option('webpc_conversion_library');

            if ($saved === 'imagick' && !$imagick_available && $gd_available) {
                update_option('webpc_conversion_library', 'gd');
                return 'gd';
            }

            if ($saved === 'gd' && !$gd_available && $imagick_available) {
                update_option('webpc_conversion_library', 'imagick');
                return 'imagick';
            }

            if (!$gd_available && !$imagick_available) {
                update_option('webpc_conversion_library', 'none');
                return 'none';
            }

            if (in_array($saved, ['gd', 'imagick']) && extension_loaded($saved)) {
                return $saved;
            }

            if ($imagick_available) {
                update_option('webpc_conversion_library', 'imagick');
                return 'imagick';
            } elseif ($gd_available) {
                update_option('webpc_conversion_library', 'gd');
                return 'gd';
            }

            update_option('webpc_conversion_library', 'none');
            return 'none';
        }

        /**
         * Adds custom action buttons (Convert, Restore, Remove) to media attachment fields.
         *
         * This method is used to enhance WordPress media attachments by adding
         * WebP conversion functionality directly in the Media Library.
         *
         * - For PNG, JPEG, JPG images: adds a "Convert to WebP" button.
         * - For WebP images with a backup file: adds "Restore Original" and "Remove Original" buttons.
         * - Images larger than 10MB are not converted and show a description instead.
         *
         * @param array $form_fields Existing attachment form fields.
         * @param WP_Post $post The attachment post object.
         * @return array Modified form fields including WebP conversion, restore, and remove buttons.
         */
        public function add_custom_media_button($form_fields, $post): array
        {

            $image_path = wp_get_original_image_path($post->ID);
            $file_extension = pathinfo($image_path, PATHINFO_EXTENSION);
            $backup_file = get_post_meta($post->ID, '_webpc_backup_file', true);

            $image_size = filesize($image_path);
            $image_weight = intval(round($image_size / 1000));

            if ($image_weight >= 10000) {
                $text = '<p class="description">' . __('Image is too big for conversion', 'webp-conversion') . '</p>';
            } else {
                $text = '
                        <button type="button" class="button button-primary webpc_convert_single" data-id="' . esc_html($post->ID) . '">' .
                    __('Convert to WebP', 'webp-conversion')
                    . '</button>
                        <div class="webpc-single-attach-spinner" style="display:none;"></div>
                    ';
            }

            if ($file_extension == 'png' || $file_extension == 'jpeg' || $file_extension == 'jpg') {
                $form_fields['webpc_onvert_selected'] = [
                    'label' => __('Convert', 'webp-conversion'),
                    'input' => 'html',
                    'html' => $text,
                ];
            }

            if ($file_extension == 'webp' && !empty($backup_file)) {

                $restore_original_text = '
                        <button type="button" class="button button-primary webpc_restore_single" data-id="' . esc_html($post->ID) . '">' .
                    __('Restore', 'webp-conversion')
                    . '</button>
                        <div class="webpc-single-attach-spinner" style="display:none;"></div>
                    ';

                $form_fields['webpc_restore_original'] = [
                    'label' => __('Restore Original File', 'webp-conversion'),
                    'input' => 'html',
                    'html' => $restore_original_text,
                ];

                $remove_original_text = '
                        <button type="button" class="button webpc_remove_single" data-id="' . esc_html($post->ID) . '">' .
                    __('Remove', 'webp-conversion')
                    . '</button>
                        <div class="webpc-single-attach-spinner" style="display:none;"></div>
                    ';

                $form_fields['webpc_remove_original'] = [
                    'label' => __('Remove Original File', 'webp-conversion'),
                    'input' => 'html',
                    'html' => $remove_original_text,
                ];
            }

            return $form_fields;
        }

        /**
         * Automatically converts uploaded images to WebP format.
         *
         * @param array $file Uploaded file array from WordPress.
         * @return array Modified file array including WebP version information.
         */
        public function webpc_auto_convert($file): array
        {

            if (!$this->webpc_auto || $file['type'] !== 'image/png' && $file['type'] !== 'image/jpeg') {
                return $file;
            }

            $conversion_library = $this->webpc_get_available_library();

            if ($conversion_library == 'none') {
                return $file;
            }

            $image_path = $file['file'];

            $original_size = filesize($image_path);

            $original_weight = intval(round($original_size / 1000));

            if ($original_weight >= 10000) {
                return $file;
            }

            $manager = new ImageManager(new Driver());

            if ($this->weights_a_lot($image_path)) {
                if ($conversion_library == 'imagick') {
                    $image = ImageManager::imagick()->read($image_path);
                } else {
                    $image = ImageManager::gd()->read($image_path);
                }
            } else {
                $image = $manager->read($image_path);
            }

            if (!is_object($image)) {
                return $file;
            }

            $file_name = basename($image_path, '.' . pathinfo($image_path, PATHINFO_EXTENSION)) . '.webp';

            $unique_filename = wp_unique_filename(wp_upload_dir()['path'], $file_name);
            $path = trailingslashit(wp_upload_dir()['path']) . $unique_filename;
            $url = trailingslashit(wp_upload_dir()['url']) . $unique_filename;

            $this->convert_from_settings($original_weight, $image, $path);

            $img_array = [
                'file' => $path,
                'url' => $url,
                'type' => 'image/webp'
            ];

            if ($this->webpc_remove) {
                WebpC_Remover::webpc_delete_file_directly($image_path);
            }

            return $img_array;
        }

        /**
         * Converts selected images to WebP format.
         *
         * This method handles image conversion triggered by:
         * - Single image conversion via AJAX (`image_id`)
         * - Multiple image conversion via AJAX (`image_ids`)
         * - Direct method call with an array of attachment IDs ($post_ids)
         *
         * It checks the AJAX nonce for security, sanitizes input IDs, and
         * loops through each image to convert it using `webpc_convert_single()`.
         * For single-image AJAX requests, it returns a JSON response immediately.
         * For multiple images, it returns the total number of successfully converted images.
         *
         * @param array|null $post_ids Optional array of attachment IDs to convert.
         *                              If null, IDs are taken from AJAX request.
         * @return int|null Returns the number of successfully converted images,
         *                  or null if an AJAX response has been sent.
         * @throws \Exception Throws exception if conversion fails for a single image with an error message.
         */
        public function webpc_convert_certain($post_ids): ?int
        {

            if (!$post_ids) {

                check_ajax_referer('webpc_nonce', 'nonce');

                if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'webpc_nonce')) {
                    wp_send_json_error('Invalid nonce');
                    return null;
                }

                if (isset($_POST['image_id'])) {
                    $image_ids = [];
                    $image_ids[] = sanitize_text_field(wp_unslash($_POST['image_id']));
                    $single_image = true;
                } elseif (isset($_POST['image_ids'])) {
                    $image_ids = array_map('sanitize_text_field', wp_unslash($_POST['image_ids']));
                    $single_image = false;
                } else {
                    wp_send_json_error(esc_html__('No image ID(s) provided', 'webp-conversion'));
                    return null;
                }

            } else {
                $image_ids = $post_ids;
                $single_image = false;
            }

            $count = 0;

            foreach ($image_ids as $attachment_id) {

                $response = $this->webpc_convert_single($attachment_id);

                if (!$response) {
                    continue;
                } else {
                    if (is_array($response) && !$response['success'] && !empty($response['message'])) {
                        throw new \Exception(esc_html($response['message']));
                    }
                }

                $count++;

                if ($single_image) {
                    wp_send_json_success([
                        'url' => 'reload',
                        'converted' => $count,
                        'library' => $response
                    ]);
                    exit;
                }

            }

            if (!$post_ids) {

                wp_send_json_success([
                    'url' => admin_url('upload.php?conversion_done='),
                    'converted' => $count
                ]);

            }

            return $count;
        }

        /**
         * AJAX handler for media button "Restore Selected".
         *
         * @return void.
         */
        public function webpc_restore_selected(): void
        {

            check_ajax_referer('webpc_nonce', 'nonce');

            if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'webpc_nonce')) {
                wp_send_json_error(esc_html__('Invalid nonce', 'webp-conversion'));
            }

            if (!isset($_POST['image_ids'])) {
                wp_send_json_error(esc_html__('No image IDs provided', 'webp-conversion'));
            }

            $image_ids = array_map('sanitize_text_field', wp_unslash($_POST['image_ids']));

            $count = 0;

            foreach ($image_ids as $attachment_id) {

                $return = $this->webpc_restore_single($attachment_id);

                if ($return) {
                    $count++;
                }
            }

            wp_send_json_success([
                'url' => admin_url('upload.php?restoring_done='),
                'converted' => $count
            ]);

        }

        /**
         * AJAX handler for media button "Remove Originals for Selected".
         *
         * @return void.
         */
        public function webpc_remove_originals_selected(): void
        {

            check_ajax_referer('webpc_nonce', 'nonce');

            if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'webpc_nonce')) {
                wp_send_json_error(esc_html__('Invalid nonce', 'webp-conversion'));
            }

            if (!isset($_POST['image_ids'])) {
                wp_send_json_error(esc_html__('No image IDs provided', 'webp-conversion'));
            }

            $image_ids = array_map('sanitize_text_field', wp_unslash($_POST['image_ids']));

            $count = 0;

            foreach ($image_ids as $attachment_id) {

                $return = WebpC_Remover::webpc_remove_original($attachment_id);

                if ($return) {
                    $count++;
                }
            }

            wp_send_json_success([
                'url' => admin_url('upload.php?removing_done='),
                'converted' => $count
            ]);

        }

        /**
         * Converts a single attachment image to WebP format.
         *
         * This method handles the full conversion process for a single image attachment.
         * It supports PNG and JPEG images, checks available conversion libraries (GD or Imagick),
         * creates a unique WebP file, updates attachment metadata, optionally removes the original image,
         * and updates references in the database to the new WebP URLs.
         *
         *
         * @param int $attachment_id Attachment ID of the image to convert.
         * @return string|false|array Returns the conversion library used ('gd' or 'imagick') on success,
         *                      or false if the image could not be converted (unsupported type, too large, or other failure).
         */
        public function webpc_convert_single($attachment_id)
        {

            $mime_type = get_post_mime_type($attachment_id);
            if ($mime_type !== 'image/png' && $mime_type !== 'image/jpeg') {
                return false;
            }

            $conversion_library = $this->webpc_get_available_library();

            if ($conversion_library == 'none') {
                return [
                    'success' => false,
                    'message' => 'No available extension found on your server'
                ];
            }

            $manager = new ImageManager(new Driver());
            $original_path = wp_get_original_image_path($attachment_id);

            $image_size = filesize($original_path);
            $image_weight = intval(round($image_size / 1000));

            if ($image_weight >= 10000) {
                return false;
            }

            $this->weights_a_lot($original_path);

            if ($this->weights_a_lot($original_path)) {
                if ($conversion_library == 'imagick') {
                    $image = ImageManager::imagick()->read($original_path);
                } else {
                    $image = ImageManager::gd()->read($original_path);
                }
            } else {
                $image = $manager->read($original_path);
            }

            if (!is_object($image)) {
                return false;
            }

            $file_name = basename($original_path, '.' . pathinfo($original_path, PATHINFO_EXTENSION)) . '.webp';
            $unique_filename = wp_unique_filename(wp_upload_dir()['path'], $file_name);
            $new_path = trailingslashit(wp_upload_dir()['path']) . $unique_filename;
            $url = trailingslashit(wp_upload_dir()['url']) . $unique_filename;

            $this->convert_from_settings($image_weight, $image, $new_path);

            $old_urls = $this->webpc_get_all_sizes($attachment_id, 'url');
            $old_paths = $this->webpc_get_all_sizes($attachment_id, 'path');

            if (!$this->webpc_remove) {
                $this->webpc_save_original_image($attachment_id);
            }

            require_once(ABSPATH . 'wp-admin/includes/image.php');
            $attach_data = wp_generate_attachment_metadata($attachment_id, $new_path);
            $info = pathinfo($new_path);
            $scaled_path = $info['dirname'] . '/' . $info['filename'] . '-scaled.' . $info['extension'];

            if (file_exists($scaled_path)) {
                $relative_scaled = str_replace(wp_get_upload_dir()['basedir'] . '/', '', $scaled_path);
                $attach_data['original_image'] = $info['basename'];
                $attach_data['file'] = $relative_scaled;

                update_attached_file($attachment_id, $scaled_path);
            } else {
                update_attached_file($attachment_id, $new_path);
            }

            wp_update_attachment_metadata($attachment_id, $attach_data);
            wp_update_post([
                'ID' => $attachment_id,
                'post_mime_type' => 'image/webp',
            ]);

            WebpC_Remover::webpc_delete_images_by_paths($old_paths);

            $new_urls = $this->webpc_get_all_sizes($attachment_id, 'url');

            $this->webpc_db_replace($attachment_id, $old_urls, $new_urls);

            return $conversion_library;
        }

        /**
         * AJAX handler for restoring single original image (button "Restore").
         *
         * This method handles the restoration of a WebP-converted image back to the original file.
         *
         * @param int|null $attachment_id Optional. Attachment ID of the image to restore. If null, taken from AJAX request.
         * @return bool Returns true if restoration was successful, false otherwise (for direct method calls).
         */
        public function webpc_restore_single($attachment_id = NULL)
        {

            if (empty($attachment_id)) {

                check_ajax_referer('webpc_nonce', 'nonce');

                if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'webpc_nonce')) {
                    wp_send_json_error(esc_html__('Invalid nonce', 'webp-conversion'));
                }

                if (!isset($_POST['image_id'])) {
                    wp_send_json_error(esc_html__('No image ID provided', 'webp-conversion'));
                }

                $attachment_id = sanitize_text_field(wp_unslash($_POST['image_id']));
                $ajax = true;

            }

            $old_urls = $this->webpc_get_all_sizes($attachment_id, 'url');
            $return = $this->webpc_restore_original_image($attachment_id);
            $new_urls = $this->webpc_get_all_sizes($attachment_id, 'url');

            if ($return) {
                $this->webpc_db_replace($attachment_id, $old_urls, $new_urls);
            }

            if (!empty($ajax)) {
                wp_send_json_success([
                    'url' => 'reload'
                ]);
            } else {
                return $return;
            }
        }

        /**
         * AJAX handler for removing single original image (button "Remove").
         *
         * This method handles the removing of an original file for WebP-converted image.
         *
         * @return void.
         */
        public function webpc_remove_single(): void{
            check_ajax_referer('webpc_nonce', 'nonce');

            if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'webpc_nonce')) {
                wp_send_json_error(esc_html__('Invalid nonce', 'webp-conversion'));
            }

            if (!isset($_POST['image_id'])) {
                wp_send_json_error(esc_html__('No image ID provided', 'webp-conversion'));
            }

            $attachment_id = sanitize_text_field(wp_unslash($_POST['image_id']));

            WebpC_Remover::webpc_remove_original($attachment_id);
            wp_send_json_success([
                'url' => 'reload'
            ]);
        }

        /**
         * Handler for "Restore Original for Selected" option in upload.php?mode=list.
         *
         * @param array $attachment_ids Attachment ids for images to convert
         * @return int Amount of images restored.
         */
        public function webpc_restore_certain($attachment_ids): int
        {

            $count = 0;

            foreach ($attachment_ids as $attachment_id) {

                $response = $this->webpc_restore_single($attachment_id);

                if ($response) {
                    $count++;
                }
            }

            return $count;
        }

        /**
         * Handler for "Remove Original for Selected" option in upload.php?mode=list.
         *
         * @param array $attachment_ids Attachment ids for images to remove
         * @return int Amount of images removed.
         */
        public function webpc_remove_certain($attachment_ids): int
        {

            $count = 0;

            foreach ($attachment_ids as $attachment_id) {
                $return = WebpC_Remover::webpc_remove_original($attachment_id);
                if ($return) {
                    $count++;
                }
            }

            return $count;
        }

        /**
         * Saves original images in separate folder.
         *
         * @param array $attachment_id Attachment ID of the image to save as an original.
         * @return bool Whether the image was successfully saved in a separate folder.
         */
        public function webpc_save_original_image($attachment_id): bool{

            $all_sizes = $this->webpc_get_all_sizes($attachment_id, 'path');

            if (empty($all_sizes)) {
                return false;
            }

            $upload_dir = wp_upload_dir();
            $backup_dir = trailingslashit($upload_dir['basedir']) . 'webpc-backup/';

            wp_mkdir_p($backup_dir);

            $main_file = end($all_sizes);

            if (!file_exists($main_file)) {
                return false;
            }

            $backup_file = $backup_dir . basename($main_file);

            if (@copy($main_file, $backup_file)) {
                update_post_meta($attachment_id, '_webpc_backup_file', $backup_file);
            }

            return true;
        }

        /**
         * Restores .webp image to its original for single attachment.
         *
         * @param array $attachment_id Attachment ID of the image to be restored.
         * @return bool Whether the .webp image was successfully restored to original png/jpeg image.
         */
        public function webpc_restore_original_image($attachment_id): bool
        {
            $backup_file = get_post_meta($attachment_id, '_webpc_backup_file', true);

            if (empty($backup_file)) {
                return false;
            }

            if (!file_exists($backup_file)) {
                delete_post_meta($attachment_id, '_webpc_backup_file');
                return false;
            }

            $upload_dir = wp_upload_dir();
            $base_path = trailingslashit($upload_dir['path']);
            $webp_images_path = $this->webpc_get_all_sizes($attachment_id, 'path');

            // Move main original back to current upload folder
            $filename = basename($backup_file);
            $target_path = $base_path . $filename;
            global $wp_filesystem;
            if ( ! function_exists( 'WP_Filesystem' ) ) {
                require_once ABSPATH . 'wp-admin/includes/file.php';
            }
            WP_Filesystem();
            wp_mkdir_p( dirname( $target_path ) );
            $wp_filesystem->move( $backup_file, $target_path, true );
            WebpC_Remover::webpc_maybe_remove_backup_folder();

            // Regenerate attachment metadata from the main original
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            $attach_data = wp_generate_attachment_metadata($attachment_id, $target_path);
            $info = pathinfo($target_path);
            $scaled_path = $info['dirname'] . '/' . $info['filename'] . '-scaled.' . $info['extension'];

            if (file_exists($scaled_path)) {
                $relative_scaled = str_replace(wp_get_upload_dir()['basedir'] . '/', '', $scaled_path);
                $attach_data['original_image'] = $info['basename'];
                $attach_data['file'] = $relative_scaled;

                update_attached_file($attachment_id, $scaled_path);
            } else {
                update_attached_file($attachment_id, $target_path);
            }

            wp_update_attachment_metadata($attachment_id, $attach_data);

            // Update MIME type
            $mime_type = wp_check_filetype($target_path)['type'] ?? 'image/jpeg';
            wp_update_post([
                'ID' => $attachment_id,
                'post_mime_type' => $mime_type
            ]);

            delete_post_meta($attachment_id, '_webpc_backup_file');
            WebpC_Remover::webpc_delete_images_by_paths($webp_images_path);
            WebpC_Remover::webpc_maybe_remove_backup_folder();

            return true;
        }

        /**
         * Removes all original images from "webpc-backup" and after, removes a back-up folder.
         *
         * @return void.
         */
        public function webpc_remove_all_originals(): void
        {

            $response = true;

            $args = [
                'post_type' => 'attachment',
                'post_status' => 'any',
                'posts_per_page' => -1,
                'meta_query' => [
                    [
                        'key' => '_webpc_backup_file',
                        'value' => '',
                        'compare' => '!=',
                    ]
                ],
                'fields' => 'ids'
            ];

            $query = new WP_Query($args);
            $upload_dir = wp_get_upload_dir();
            $backup_dir = trailingslashit($upload_dir['basedir']) . 'webpc-backup';

            if (Webpc_Remover::webpc_is_dir_empty($backup_dir)) {
                $response = false;
            }

            if ($query->have_posts()) {
                foreach ($query->posts as $attachment_id) {
                    $backup_file = get_post_meta($attachment_id, '_webpc_backup_file', true);
                    if (file_exists($backup_file)) {
                        wp_delete_file($backup_file);
                    }
                    delete_post_meta($attachment_id, '_webpc_backup_file');
                }
            }

            WebpC_Remover::webpc_remove_backup_folder();

            if ($response) {
                wp_send_json_success([
                    'message' => esc_html__('All original images were removed, only .webp versions are remained', 'webp-conversion'),
                    'removed' => true
                ]);
            } else {
                wp_send_json_error([
                    'message' => esc_html__('No original images to remove', 'webp-conversion'),
                    'removed' => false
                ]);
            }

        }

        /**
         * Adds "Convert Selected", "Restore Selected", "Remove Originals for Selected", "Select All/Unselect All" buttons and a loading spinner to Media Library page.
         *
         * @return void.
         */
        public function add_select_button(): void
        {
            $script = "
        jQuery(document).ready(function ($) {
            //Buttons
            const convertSelected = $('<button type=\"button\" class=\"button media-button button-secondary button-large delete-selected-button webpc_convert_selected\" style=\"display: none;\">" . __('Convert Selected', 'webp-conversion') . "</button>');
            const restoreSelected = $('<button type=\"button\" class=\"button media-button button-secondary button-large delete-selected-button webpc_restore_selected\" style=\"display: none;\">" . __('Restore Selected', 'webp-conversion') . "</button>');
            const removeOriginalsSelected = $('<button type=\"button\" class=\"button media-button button-secondary button-large delete-selected-button webpc_remove_originals_selected\" style=\"display: none;\">" . __('Remove Originals for Selected', 'webp-conversion') . "</button>');

            $('.delete-selected-button').after(convertSelected);
            $('.webpc_convert_selected').after(restoreSelected);
            $('.webpc_restore_selected').after(removeOriginalsSelected);

            //Counter and spinner
            const convertedCounterAndSpinner = $('<div class=\"webpc-counter-and-spinner media-button\" style=\"display: none;\"><div class=\"webpc-counter-and-spinner-inner\"><div class=\"webpc-converted-count-container\"><p>" . __('Images processed: ', 'webp-conversion') . "<span id=\"webpc-converted-count\">0</span></p></div><div class=\"webpc-convert-selected-spinner-container\"><div class=\"webpc-convert-selected-spinner\"></div></div></div></div>');
            $('.webpc_remove_originals_selected').after(convertedCounterAndSpinner);

            //Button Select All
            const toggleSelect = $('<button type=\"button\" class=\"button media-button button-primary button-large delete-selected-button webpc_toggle_select\" style=\"display: none;\">" . __('Select All', 'webp-conversion') . "</button>');
            $('.webpc-counter-and-spinner').after(toggleSelect);

            $('.select-mode-toggle-button').one('click', function () {
                convertSelected.css('display', '');
                restoreSelected.css('display', '');
                removeOriginalsSelected.css('display', '');
                toggleSelect.css('display', '');
            });

            function selectAllImages() {
                $('.attachments-browser .attachments .attachment').each(function () {
                    if (!$(this).hasClass('selected')) {
                        $(this).find('.attachment-preview').click();
                    }
                });
            }

            function deselectAllImages() {
                $('.attachments-browser .attachments .attachment').each(function () {
                    if ($(this).hasClass('selected')) {
                        $(this).find('.attachment-preview').click();
                    }
                });
            }

            $('.webpc_toggle_select').on('click', function () {
                if ($(this).text() === '" . __('Select All', 'webp-conversion') . "') {
                    selectAllImages();
                    $(this).text('" . __('Unselect All', 'webp-conversion') . "');
                } else {
                    deselectAllImages();
                    $(this).text('" . __('Select All', 'webp-conversion') . "');
                }
            });            

        });
    ";

            wp_add_inline_script('webpc_main_script', $script);
        }

        /**
         * Replaces old (.png/.jpeg) images for a new (.webp) ones in wpdb
         *
         * @return void.
         */
        public function webpc_db_replace($id, $old_urls = NULL, $new_urls = NULL): void
        {

            $id = esc_sql($id);

            Webpc_DB_Replacer::webpc_db_replace_handle_acf($id, $old_urls, $new_urls);
            Webpc_DB_Replacer::webpc_db_replace_handle_acf_option_fields($old_urls, $new_urls);
            Webpc_DB_Replacer::webpc_db_replace_handle_post_content_post_excerpt($old_urls, $new_urls);

            foreach ($old_urls as $index => $old_url) {
                if (!empty($new_urls[$index])) {
                    WebpC_DB_Replacer::webpc_db_replace_handle_urls($old_url, $new_urls[$index]);
                }
            }

        }

        /**
         * Retrieves all image sizes (including original) for a given attachment.
         *
         * This method returns an array of file names, URLs, or file paths for all
         * registered image sizes of an attachment, including the original image.
         *
         * @param int $id Attachment ID of the image.
         * @param string|null $return Optional. Determines the return type:
         *                            - 'url'  => returns full URLs
         *                            - 'path' => returns full file paths
         *                            - null   => returns just the filenames
         * @return array Array of image filenames, URLs, or file paths.
         */
        public function webpc_get_all_sizes($id, $return = null): array
        {
            $attachment_metadata = wp_get_attachment_metadata($id);
            $upload_dir = wp_get_upload_dir();
            $base_url = trailingslashit($upload_dir['baseurl']);
            $base_dir = trailingslashit($upload_dir['basedir']);
            $file_path = $attachment_metadata['file'];
            $image_uploads_folder = trailingslashit(dirname($file_path));

            $filename = basename($attachment_metadata['file']);
            $original_filename = $attachment_metadata['original_image'];

            $sizes = [];
            if (!empty($attachment_metadata['sizes'])) {
                foreach ($attachment_metadata['sizes'] as $size) {
                    $sizes[] = $size['file'];
                }
            }

            $sizes[] = $filename;
            if ($original_filename) {
                $sizes[] = $original_filename;
            }

            $sizes = array_unique($sizes);

            if ($return === 'url') {
                $sizes = array_map(function ($size) use ($base_url, $image_uploads_folder) {
                    return $base_url . $image_uploads_folder . $size;
                }, $sizes);
            }

            if ($return == 'path') {
                $sizes = array_map(function ($size) use ($base_dir, $image_uploads_folder) {
                    return $base_dir . $image_uploads_folder . $size;
                }, $sizes);
            }

            return $sizes;
        }

        /**
         * Updates user settings (options).
         *
         * @return void.
         */
        public function webpc_update_settings(): void
        {
            check_ajax_referer('webpc-settings-group-options');

            update_option('webpc_auto', isset($_POST['webpc_auto']) ? 1 : 0);
            update_option('webpc_svg', isset($_POST['webpc_svg']) ? 1 : 0);
            update_option('webpc_ico', isset($_POST['webpc_ico']) ? 1 : 0);
            update_option('webpc_remove', isset($_POST['webpc_remove']) ? 1 : 0);
            update_option('webpc_conversion_library', isset($_POST['webpc_conversion_library']) ? sanitize_text_field(wp_unslash($_POST['webpc_conversion_library'])) : 'auto');

            $quality_settings = [
                '200kb' => 75,
                '1000kb' => 70,
                '2500kb' => 50,
                'more_2500kb' => 45
            ];

            foreach ($quality_settings as $key => $default_value) {
                if (isset($_POST['webpc_' . $key])) {
                    update_option('webpc_' . $key, intval(sanitize_text_field(wp_unslash($_POST['webpc_' . $key]))));
                }
            }


            wp_send_json_success([
                'message' => esc_html__('Changes have been saved!', 'webp-conversion')
            ]);
        }

        /**
         * Adds "Convert Selected", "Restore Original for Selected" and "Remove Original for Selected" actions in upload.php?mode=list.
         *
         * @param array $bulk_actions Array of bulk actions.
         * @return array Modified array of bulk actions.
         */
        public function register_webpc_media_action($bulk_actions): array
        {
            $bulk_actions['webpc_convert_selected'] = __('Convert Selected', 'webp-conversion');
            $bulk_actions['webpc_restore_selected'] = __('Restore Original for Selected', 'webp-conversion');
            $bulk_actions['webpc_remove_selected'] = __('Remove Original for Selected', 'webp-conversion');
            return $bulk_actions;
        }

        /**
         * Handles bulk actions for WebP Conversion in the Media Library list view.
         *
         * This method processes bulk actions triggered from `upload.php?mode=list`:
         * - Convert selected images to WebP (`webpc_convert_selected`)
         * - Restore original images (`webpc_restore_selected`)
         * - Remove original images (`webpc_remove_selected`)
         *
         * After processing, it appends the number of affected images to the redirect URL.
         *
         * @param string $redirect_to The URL to redirect to after action is performed.
         * @param string $doaction The action being performed.
         * @param array $post_ids Array of attachment IDs affected by the bulk action.
         * @return string Updated redirect URL with query parameters indicating the number of processed images.
         */
        public function webpc_bulk_actions_handler($redirect_to, $doaction, $post_ids): string
        {

            if ($doaction === 'webpc_convert_selected') {

                $num = $this->webpc_convert_certain($post_ids);
                $redirect_to = add_query_arg('conversion_done', $num, $redirect_to);
            }

            if ($doaction === 'webpc_restore_selected') {

                $num = $this->webpc_restore_certain($post_ids);
                $redirect_to = add_query_arg('restoring_done', $num, $redirect_to);
            }

            if ($doaction === 'webpc_remove_selected') {

                $num = $this->webpc_remove_certain($post_ids);
                $redirect_to = add_query_arg('removing_done', $num, $redirect_to);
            }

            return $redirect_to;

        }

        /**
         * Adds Notices after conversion, restoring or removal of multiple images.
         *
         * @return void.
         */
        public function convert_selected_admin_notice(): void
        {
            if (!empty($_REQUEST['conversion_done'])) {

                $count = intval(sanitize_text_field(wp_unslash($_REQUEST['conversion_done'])));
                printf(
                    /* translators: %s: Number of media items converted */
                '<div id="message" class="updated notice is-dismissible webpc-notice"><p>' . esc_html__('Conversion applied to %s media items.', 'webp-conversion') . '</p></div>',
                    esc_html($count)
                );
            }

            if (!empty($_REQUEST['restoring_done'])) {

                $count = intval(sanitize_text_field(wp_unslash($_REQUEST['restoring_done'])));
                printf(
                    /* translators: %s: Number of media items restored to original */
                '<div id="message" class="updated notice is-dismissible webpc-notice"><p>' . esc_html__('%s media items were restored to original.', 'webp-conversion') . '</p></div>',
                    esc_html($count)
                );
            }

            if (!empty($_REQUEST['removing_done'])) {

                $count = intval(sanitize_text_field(wp_unslash($_REQUEST['removing_done'])));
                printf(
                    /* translators: %s: Number of media items that had their original images deleted */
                '<div id="message" class="updated notice is-dismissible webpc-notice"><p>' . esc_html__('Original images were deleted for %s media items.', 'webp-conversion') . '</p></div>',
                    esc_html($count)
                );
            }
        }

        /**
         * Converts a single image to WebP using user-defined quality settings.
         *
         * This method converts the given image object to WebP format based on its size in kilobytes,
         * applying different quality settings defined in the plugin options:
         * - <= 200 KB → `$this->webpc_200kb`
         * - <= 1000 KB → `$this->webpc_1000kb`
         * - <= 2500 KB → `$this->webpc_2500kb`
         * - > 2500 KB → `$this->webpc_more_2500kb`
         *
         * @param int $image_weight Weight of the image in kilobytes.
         * @param ImageManager $image Image object (from Intervention Image library).
         * @param string $path Full file path where the WebP image will be saved.
         * @return void
         */
        public function convert_from_settings($image_weight, $image, $path): void
        {

            if ($image_weight <= 200) {
                $image->toWebp($this->webpc_200kb)->save($path);
            } else if ($image_weight <= 1000) {
                $image->toWebp($this->webpc_1000kb)->save($path);
            } else if ($image_weight <= 2500) {
                $image->toWebp($this->webpc_2500kb)->save($path);
            } else {
                $image->toWebp($this->webpc_more_2500kb)->save($path);
            }

        }

        /**
         * Checks if image weight is more than 1.6mb.
         *
         * @param string $path Path to the image
         * @return bool Whether image weights more than 1.6mb.
         */
        public function weights_a_lot($path): bool
        {

            $image_dimensions = getimagesize($path);
            $image_size = filesize($path);
            $image_weight = intval(round($image_size / 1000));

            if ($image_weight >= 1600) {
                return true;
            }

            if ($image_dimensions[0] > '2000' || $image_dimensions[1] > '2000') {
                return true;
            }

            return false;
        }

        /**
         * Allows svg and ico uploads if it set to "true" in plugin's settings.
         *
         * @param array $mimes Array of allowed mime types for uploading.
         * @return array Modified Array of allowed mime types for uploading.
         */
        public function allow_svg_ico_mimes($mimes): array
        {
            if ($this->webpc_svg == 1) {
                $mimes['svg'] = 'image/svg+xml';
            }
            if ($this->webpc_ico == 1) {
                $mimes['ico'] = 'image/x-icon';
            }

            return $mimes;
        }

        /**
         * Return correct MIME types for .svg and .ico files.
         *
         * This method is typically used as a filter to allow uploading of SVG and ICO files.
         * It overrides the MIME type and file extension if the plugin settings enable them.
         *
         * @param array  $data     Array of current MIME type data ['type' => string, 'ext' => string].
         * @param string $file     Full path to the file being uploaded.
         * @param string $filename Name of the file being uploaded.
         * @param array  $mimes    Current allowed MIME types.
         * @return array Modified MIME type data array.
         */
        public function return_mime_types($data, $file, $filename, $mimes): array
        {

            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

            if ($this->webpc_svg == 1 && $ext === 'svg') {
                $data['type'] = 'image/svg+xml';
                $data['ext'] = 'svg';
            }

            if ($this->webpc_ico == 1 && $ext === 'ico') {
                $data['type'] = 'image/x-icon';
                $data['ext'] = 'ico';
            }

            return $data;
        }

    }

    $webpcsf = new WEBPCbySheepFish;

}