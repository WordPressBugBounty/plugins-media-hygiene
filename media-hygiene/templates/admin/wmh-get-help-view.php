<?php
/* Hedaer view */
$wmh_general = new wmh_general();
$wmh_general->fn_wmh_get_template('wmh-header-view.php');
?>

<div class="row">
    <div class="col-md-6 col-lg-6 col-xl-4">
        <div class="card text-center mw-100 pt-5 rounded-0">
            <i class="fa-solid fa-headphones display-3"></i>
            <div class="card-body pl-0 pr-0">
                <h4 class="card-title"><?php _e('Need Expert Support?', MEDIA_HYGIENE); ?></h4>
                <p class="card-text"><?php _e('Our EXPERTS would like to assist you for your query and any customization.', MEDIA_HYGIENE); ?></p>
                <a href="https://www.mediahygiene.com/pricing/" class="button button-primary wmh-btn"><?php _e('Contact Support', MEDIA_HYGIENE); ?></a>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-6 col-xl-4">
        <div class="card text-center mw-100 pt-5 rounded-0">
            <i class="fa-solid fa-users display-3"></i>
            <div class="card-body pl-0 pr-0">
                <h4 class="card-title"><?php _e("Got An Idea?", MEDIA_HYGIENE); ?></h4>
                <p class="card-text"><?php _e("Submit your idea to our feedback portal to have it considered.", MEDIA_HYGIENE); ?></p>
                <a href="https://mediahygiene.ideas.userback.io/" target="_blank" class="button button-primary wmh-btn"><?php _e("Submit Idea", MEDIA_HYGIENE); ?></a>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-6 col-xl-4">
        <div class="card text-center mw-100 pt-4 rounded-0">
            <i class="fa-solid fa-bug display-3"></i>
            <div class="card-body pl-0 pr-0">
                <h4 class="card-title"><?php _e("Found A bug?", MEDIA_HYGIENE); ?></h4>
                <p class="card-text"><?php _e("Please report abnormal bug behaviour (e.g. error code) and we promise we will fix that as soon as humanly possible", MEDIA_HYGIENE); ?></p>
                <a href="https://wordpress.org/support/plugin/media-hygiene" target="_blank" class="button button-primary wmh-btn"><?php _e("Submit Bug", MEDIA_HYGIENE); ?></a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xl-8 col-md-12 col-sm-12">
        <div class="card text-center mw-100 rounded-0">
            <div class="card-body px-0">
                <h4 class="card-title pb-3"><?php _e("Tool Overview", MEDIA_HYGIENE); ?></h4>
                <div class="embed-responsive embed-responsive-16by9">
                    <iframe width="560" height="315" src="https://www.youtube.com/embed/laQX855BStM" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-md-12 col-sm-12">
        <div class="card text-center mw-100 pt-3 rounded-0">
            <i class="fa-solid fa-thumbs-up display-3"></i>
            <div class="card-body pl-0 pr-0">
                <h4 class="card-title"><?php _e("Write Review", MEDIA_HYGIENE); ?></h4>
                <p class="card-text"><?php _e("Please write a review in wp.org plugin repository. Your feedback inspires us to do more!", MEDIA_HYGIENE); ?></p>
                <a href="https://wordpress.org/support/plugin/media-hygiene/reviews/#new-post" class="button button-primary wmh-btn" target="_blank"><?php _e("Submit Review", MEDIA_HYGIENE); ?></a>
            </div>
        </div>

        <div class="card text-center mw-100 pt-5 pb-5 rounded-0">
            <i class="fa-solid fa-question display-3"></i>
            <div class="card-body pl-0 pr-0">
                <h4 class="card-title"><?php _e("FAQ", MEDIA_HYGIENE); ?></h4>
                <a href="https://mediahygiene.com/faq/" class="button mt-3 button-primary wmh-btn" target="_blank"><?php _e("Check", MEDIA_HYGIENE); ?></a>
            </div>
        </div>
    </div>
</div>
