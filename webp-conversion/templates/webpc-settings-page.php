<?php

if (!defined('ABSPATH')) {
    exit;
}

?>
<div class="webpc-plugin-main-container">
    <div class="top-container">
        <h1><?php echo esc_html__('WebP Conversion', 'webp-conversion'); ?></h1>
        <p><?php echo esc_html__('Manage conversion settings', 'webp-conversion'); ?></p>
    </div>
    <div id="webpc-notice" class="notice notice-success" style="display:none;">
        <p><?php echo esc_html__('Changes have been saved!', 'webp-conversion'); ?></p>
    </div>
    <form method="post" action="options.php" id="webpc-settings-form">
        <?php settings_fields('webpc-settings-group'); ?>
        <div class="settings-container">
            <h2><?php echo esc_html__('Settings', 'webp-conversion'); ?></h2>
            <div class="input-field settings-block">
                <div class="settings-row">
                    <input type="checkbox" id="webpc_auto" name="webpc_auto"
                           value="1" <?php checked(1, get_option('webpc_auto', 1), true); ?> >
                    <?php echo esc_html__('Automatically convert images while uploading', 'webp-conversion'); ?>
                </div>
                <div class="settings-row">
                    <input type="checkbox" id="webpc_svg" name="webpc_svg"
                           value="1" <?php checked(1, get_option('webpc_svg', 1), true); ?> >
                    <?php echo esc_html__('Enable svg uploads', 'webp-conversion'); ?>
                </div>
                <div class="settings-row">
                    <input type="checkbox" id="webpc_ico" name="webpc_ico"
                           value="1" <?php checked(1, get_option('webpc_ico', 1), true); ?> >
                    <?php echo esc_html__('Enable ico uploads', 'webp-conversion'); ?>
                </div>
                <div class="settings-row">
                    <input type="checkbox" id="webpc_remove" name="webpc_remove"
                           value="1" <?php checked(1, get_option('webpc_remove', 1), true); ?> >
                    <?php echo esc_html__('Remove original media files after conversion', 'webp-conversion'); ?>
                </div>
            </div>

            <div class="input-field settings-block radio-button-block">
                <div class="settings-row">
                    <h4><?php echo esc_html__('Conversion Library', 'webp-conversion'); ?></h4>
                    <?php
                    $gd_available = extension_loaded('gd');
                    $imagick_available = extension_loaded('imagick');

                    $active_library = WEBPCbySheepFish::get_instance()->webpc_get_available_library();

                    ?>
                    <label style="margin-right: 20px;">
                        <input type="radio"
                               name="webpc_conversion_library"
                               value="gd"
                                <?php echo !$gd_available ? 'disabled' : ''; ?>
                                <?php checked($active_library, 'gd'); ?>>
                        <?php echo esc_html__('GD', 'webp-conversion'); ?>
                        <?php if (!$gd_available): ?>
                            <span style="color: #a00; font-style: italic;">(<?php echo esc_html__('Unavailable on your server', 'webp-conversion'); ?>)</span>
                        <?php endif; ?>
                    </label>

                    <label style="margin-right: 20px;">
                        <input type="radio"
                               name="webpc_conversion_library"
                               value="imagick"
                                <?php echo !$imagick_available ? 'disabled' : ''; ?>
                                <?php checked($active_library, 'imagick'); ?>>
                        <?php echo esc_html__('Imagick', 'webp-conversion'); ?>
                        <?php if (!$imagick_available): ?>
                            <span style="color: #a00; font-style: italic;">(<?php echo esc_html__('Unavailable on your server', 'webp-conversion'); ?>)</span>
                        <?php endif; ?>
                    </label>

                    <label style="margin-right: 20px;">
                        <input type="radio"
                               name="webpc_conversion_library"
                               value="none"
                                <?php echo ($gd_available || $imagick_available) ? 'disabled' : ''; ?>
                                <?php checked($active_library, 'none'); ?>>
                        <?php echo esc_html__('None', 'webp-conversion'); ?>
                        <?php if (!$gd_available && !$imagick_available): ?>
                            <span style="color: #a00; font-style: italic;">(<?php echo esc_html__('No compatible libraries available', 'webp-conversion'); ?>)</span>
                        <?php endif; ?>
                    </label>
                </div>
            </div>

            <h4><?php echo esc_html__('Conversion Quality', 'webp-conversion'); ?></h4>
            <table class="input-table">
                <?php

                $less_then_label = esc_html__('Less then', 'webp-conversion');
                $more_then_label = esc_html__('More then', 'webp-conversion');

                $quality_settings = [
                        '200kb' => ['label' => $less_then_label . ' 200kb', 'default' => 75],
                        '1000kb' => ['label' => $less_then_label . ' 1mb', 'default' => 70],
                        '2500kb' => ['label' => $less_then_label . ' 2.5mb', 'default' => 50],
                        'more_2500kb' => ['label' => $more_then_label . ' 2.5mb', 'default' => 45]
                ];

                foreach ($quality_settings as $key => $settings) {
                    $label = $settings['label'];
                    $default_value = $settings['default'];
                    $value = esc_attr(get_option('webpc_' . $key, $default_value));
                    ?>
                    <tr>
                        <td><label for="<?php echo 'webpc_' . esc_html($key) ?>"><?php echo esc_html($label) ?></label></td>
                        <td><input type="range" id="<?php echo 'webpc_' . esc_html($key) ?>"
                                   name="<?php echo 'webpc_' . esc_html($key) ?>" min="0" max="100"
                                   value="<?php echo esc_html($value) ?>"></td>
                        <td><input type="number" id="<?php echo 'webpc_' . esc_html($key) . '_value' ?>"
                                   name="<?php echo 'webpc_' . esc_html($key) . '_value' ?>" min="0" max="100"
                                   value="<?php echo esc_html($value) ?>"></td>
                    </tr>
                <?php } ?>
            </table>
        </div>
        <div class="buttons-container">
            <input type="submit" value="<?php echo esc_html__('Save', 'webp-conversion'); ?>"
                   class="button button-primary" id="webpc_submit_settings_button">
            <button id="webpc_remove_originals_button"
                    class="button"><?php echo esc_html__('Remove all originals (from .webp images)', 'webp-conversion'); ?></button>
        </div>
    </form>
</div>
