<?php

/* Get admin site_url */
$admin_url = admin_url();

$page = '';
if (isset($_GET['page'])) {
    $page = sanitize_text_field($_GET['page']);
}

/* get status pro subscription is active or not */
$wmh_licence_key_status = '';
$all_plugins = array();
$all_plugins = get_plugins();
/* media hygiene pro version plugin key */
$media_hygine_key = 'media-hygiene-pro/media-hygiene-pro.php';
if ((array_key_exists($media_hygine_key, $all_plugins))) {
    $wmh_licence_key_status = get_option('wmh_licence_key_status');
}

?>
<div class="wmh-hd-bar">

    <!-- Logo -->
    <div class="wmh-hd-logo">
        <a href="<?php echo esc_url($admin_url); ?>admin.php?page=wmh-media-hygiene">
            <img src="<?php echo esc_url(MH_FILE_URL . "media/wpmediahygiene_logo-horizontal-black.png"); ?>" alt="Media Hygiene" />
        </a>
    </div>

    <!-- Navigation -->
    <nav class="wmh-hd-nav">
        <a class="wmh-hd-link <?php echo esc_attr($page) == 'wmh-media-hygiene' ? 'active' : ''; ?>"
           href="<?php echo esc_url($admin_url); ?>admin.php?page=wmh-media-hygiene">
            <i class="fa-solid fa-gauge-high"></i>
            <?php _e('Dashboard', MEDIA_HYGIENE); ?>
        </a>
        <a class="wmh-hd-link <?php echo esc_attr($page) == 'wmh-settings' ? 'active' : ''; ?>"
           href="<?php echo esc_url($admin_url); ?>admin.php?page=wmh-settings">
            <i class="fa-solid fa-sliders"></i>
            <?php _e('Settings', MEDIA_HYGIENE); ?>
        </a>
        <a class="wmh-hd-link <?php echo esc_attr($page) == 'wmh-get-help' ? 'active' : ''; ?>"
           href="<?php echo esc_url($admin_url); ?>admin.php?page=wmh-get-help">
            <i class="fa-solid fa-life-ring"></i>
            <?php _e('Get Help', MEDIA_HYGIENE); ?>
        </a>
        <a class="wmh-hd-link wmh-hd-folderscan" href="https://www.mediahygiene.com/pricing/" target="_blank">
            <i class="fa-solid fa-folder-open"></i>
            <?php _e('Folder Scan', MEDIA_HYGIENE); ?>
            <span class="wmh-pro-badge"><?php _e('Pro', MEDIA_HYGIENE); ?></span>
        </a>
    </nav>

    <!-- Action Buttons -->
    <div class="wmh-hd-actions">
        <a class="wmh-hd-faq" href="https://www.mediahygiene.com/faq/" target="_blank">
            <i class="fa-solid fa-circle-question"></i>
            <?php _e('FAQ', MEDIA_HYGIENE); ?>
        </a>
        <a class="wmh-hd-upgrade" href="https://www.mediahygiene.com/pricing/" target="_blank">
            <i class="fa-solid fa-crown"></i>
            <?php _e('Upgrade to Pro', MEDIA_HYGIENE); ?>
        </a>
    </div>

</div>
