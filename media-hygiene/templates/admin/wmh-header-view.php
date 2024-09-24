<?php

/* Get admin site_url */
$admin_url = admin_url();

$page = '';
if (isset($_GET['page'])) {
    $page = sanitize_text_field($_GET['page']);
}

/* get status pro subscription is active or not */
/* get active plugin list */
$wmh_licence_key_status = '';
$all_plugins = array();
$all_plugins = get_plugins();
/* media hygiene pro version plugin key */
$media_hygine_key = 'media-hygiene-pro/media-hygiene-pro.php';
if ((array_key_exists($media_hygine_key, $all_plugins))) {
    $wmh_licence_key_status = get_option('wmh_licence_key_status');
}

?>
<div class="row">
    <div class="media-hygiene-logo mt-2 col-md-1">
        <a href="<?php echo esc_url($admin_url); ?>admin.php?page=wmh-media-hygiene"><img src="<?php echo esc_url(MH_FILE_URL . "media/wpmediahygiene_logo-horizontal-black.png"); ?>" /></a>
    </div>

    <div class="wmh-header col-md-11 mt-2">
        <div class="bg-light bg-light d-flex justify-content-between align-items-center">
            <nav class="navbar navbar-expand-md navbar-light p-lg-0 p-md-0">
                <button class="navbar-toggler collapsed ms-sm-2" type="button" data-bs-toggle="collapse" data-bs-target="#collapsibleNavbar" aria-expanded="false">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="navbar-collapse collapse wmh-menu-top" id="collapsibleNavbar">
                    <ul class="navbar-nav wp-mh">
                        <li class="nav-item">
                            <a class="nav-link <?php echo esc_attr($page) == 'wmh-media-hygiene' ? 'active' : ''; ?>" href="<?php echo esc_url($admin_url); ?>admin.php?page=wmh-media-hygiene"><?php _e("Dashboard", MEDIA_HYGIENE); ?></a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo esc_attr($page) == 'wmh-settings' ? 'active' : ''; ?>" href="<?php echo esc_url($admin_url); ?>admin.php?page=wmh-settings"><?php _e("Settings", MEDIA_HYGIENE); ?></a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo esc_attr($page) == 'wmh-get-help' ? 'active' : ''; ?>" href="<?php echo esc_url($admin_url); ?>admin.php?page=wmh-get-help"><?php _e("Get Help", MEDIA_HYGIENE); ?></a>
                        </li>
                        <!--<li class="nav-item">
                            <a class="nav-link <?php /*echo esc_attr($page) == 'wmh-deleted-media' ? 'active' : '';*/ ?>" href="<?php /*echo esc_url($admin_url); */?>admin.php?page=wmh-deleted-media"><?php /*_e("Deleted Media", MEDIA_HYGIENE); */?></a>
                        </li>-->
                        <li class="nav-item">
                            <a class="nav-link" href="https://www.mediahygiene.com/pricing/" target="_blank">
                                <span><?php _e('Pro', MEDIA_HYGIENE); ?></span>
                                <?php _e("Folder Scan", MEDIA_HYGIENE); ?></a>
                        </li>
                    </ul>
                </div>
            </nav>
            <div>
                <a class="btn btn-primary wmh-button ml-lg-2 ml-md-2 rounded-0" href="https://www.mediahygiene.com/faq/" target="_blank"><?php _e('FAQ', MEDIA_HYGIENE); ?></a>
                <a class="btn btn-primary wmh-button ml-lg-2 ml-md-2 rounded-0" href="https://www.mediahygiene.com/pricing/" target="_blank"><?php _e('Upgrade to Pro', MEDIA_HYGIENE); ?></a>            
            </div>
        </div>
    </div>
</div>