<?php

defined('ABSPATH') or die('Plugin file cannot be accessed directly.');

/* get generel summery data */

/* get total media count */
$media_count = get_option('wmh_media_count');
if (isset($media_count) && $media_count != '' && $media_count != 0) {
	$media_count =  get_option('wmh_media_count');
} else {
	$media_count = 0;
}

/* get total media size. */
$media_size = get_option('wmh_total_media_size');
if (isset($media_size) && $media_size != '' && $media_size != 0) {
	$media_size =  size_format(get_option('wmh_total_media_size'));
} else {
	$media_size = 0;
}

/* get total unused media count */
$unused_media_count = get_option('wmh_total_unused_media_count');
if (isset($unused_media_count) && $unused_media_count != '' && $unused_media_count != 0) {
	$unused_media_count =  get_option('wmh_total_unused_media_count');
} else {
	$unused_media_count = 0;
}

/* unused media size */
$unused_media_size = get_option('wmh_unused_media_size');
if (isset($unused_media_size) && $unused_media_size != '' && $unused_media_size != 0) {
	$unused_media_size = size_format(get_option('wmh_unused_media_size'));
} else {
	$unused_media_size = 0;
}

/* used media count */
$use_media_count = get_option('wmh_use_media_count');
if (isset($use_media_count) && $use_media_count != '' && $use_media_count != 0) {
	$use_media_count =  get_option('wmh_use_media_count');
} else {
	$use_media_count = 0;
}

/* used media size */
$use_media_size = get_option('wmh_use_media_size');
if (isset($use_media_size) && $use_media_size != '' && $use_media_size != 0) {
	$use_media_size = size_format(get_option('wmh_use_media_size'));
} else {
	$use_media_size = 0;
}

/* get media break down */
$media_breakdown_data = get_option('wmh_media_breakdown');
if (isset($media_breakdown_data['image_count'])) {
	$media_breakdown_data = array();
}

/* get media type info */
$media_type_info = get_option('wmh_media_type_info');
if (isset($media_type_info[0]['media_type_name'])) {
	$media_type_info = array();
}

/* get scan status */
$wmh_scan_status = get_option('wmh_scan_status');

$cat_count = '-';
$attachment_cat = '-';
$cat_per = '-';

?>

<div class="wpm-height">
	<div class="row row-main" id="wmh-statistics">
		<div class="col-md-12 px-0">
			<div class="wmh-dashboard mb-0">
				<!-- General summary -->
				<div id="general-summary-accordion" class="admin-accord">
					<div class="card mt-2">
						<div class="card-header pt-1 pb-1" id="general-summary">
							<h5 class="mb-0">
								<button class="btn btn-link w-100 text-start" data-bs-toggle="collapse" data-bs-target="#general-summary-accordion-content" aria-expanded="true" aria-controls="general-summary-accordion-content">
									<?php _e('General summary', MEDIA_HYGIENE); ?>
								</button>
							</h5>
						</div>
						<div id="general-summary-accordion-content" class="collapse show" aria-labelledby="summary" data-parent="#general-summary-accordion">
							<div class="card-body px-0">
								<div class="row row-main">
									<div class="col-xl-4 col-sm-6">
										<div class="card text-white box mt-0" id="total-media">
											<div class="card-body text-center">
												<div class="wmh-ans"><?php echo esc_html($media_count); ?></div>
												<div class="wmh-title"><?php _e('Total Media', MEDIA_HYGIENE); ?></div>
											</div>
										</div>
									</div>
									<div class="col-xl-4 col-sm-6">
										<div class="card text-white box mt-0" id="media-in-use">
											<div class="card-body text-center">
												<div class="wmh-ans"><?php echo esc_html($use_media_count); ?></div>
												<div class="wmh-title"><?php _e('Media In Use', MEDIA_HYGIENE); ?></div>
											</div>
										</div>
									</div>
									<div class="col-xl-4 col-sm-6">
										<div class="card text-white box mt-0" id="media-over-left">
											<div class="card-body text-center">
												<div class="wmh-ans"><?php echo esc_html($unused_media_count); ?></div>
												<div class="wmh-title"><?php _e('Media Left Over', MEDIA_HYGIENE); ?></div>
											</div>
										</div>
									</div>
									<div class="col-xl-4 col-sm-6">
										<div class="card text-white box" id="total-media-size">
											<div class="card-body text-center">
												<div class="wmh-ans"><?php echo esc_html($media_size); ?></div>
												<div class="wmh-title"><?php _e('Total Media Space', MEDIA_HYGIENE); ?></div>
											</div>
										</div>
									</div>
									<div class="col-xl-4 col-sm-6">
										<div class="card text-white box" id="media-in-use-size">
											<div class="card-body text-center">
												<div class="wmh-ans"><?php echo esc_html($use_media_size); ?></div>
												<div class="wmh-title"><?php _e('In Use Media Space', MEDIA_HYGIENE); ?></div>
											</div>
										</div>
									</div>
									<div class="col-xl-4 col-sm-6">
										<div class="card text-white box" id="media-over-left-size">
											<div class="card-body text-center">
												<div class="wmh-ans"><?php echo esc_html($unused_media_size); ?></div>
												<div class="wmh-title"><?php _e('Media Left Over Space', MEDIA_HYGIENE); ?></div>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>

				<!--  Media left over -->
				<div id="media-break-down-accordion" class="admin-accord">
					<div class="card mt-2">
						<div class="card-header pt-1 pb-1" id="media-break-down">
							<h5 class="mb-0">
								<button class="btn btn-link w-100 text-start media-break" data-bs-toggle="collapse" data-bs-target="#media-break-down-content" aria-expanded="true" aria-controls="media-break-down-content">
									<?php _e('Media Left over', MEDIA_HYGIENE); ?>
								</button>
							</h5>
						</div>
						<div id="media-break-down-content" class="collapse show" aria-labelledby="media-reak-down" data-parent="#media-break-down-accordion">
							<div class="card-body p-0 pb-3">
								<div class="row row-main">

									<?php if (isset($media_breakdown_data) && !empty($media_breakdown_data)) foreach ($media_breakdown_data as $value) { {


											if (isset($value['cat_count']) && $value['cat_count'] != '') {
												$cat_count = $value['cat_count'];
											}

											if (isset($value['attachment_cat']) && $value['attachment_cat'] != '') {
												$attachment_cat = ucfirst($value['attachment_cat']);
											}

											if (isset($value['cat_per']) && $value['cat_per'] != '') {
												$cat_per = $value['cat_per'] . ' %';
											}


									?>
											<div class="col-xl-4 col-sm-6 pt-3">
												<div class="card text-white box mt-0" id="image">
													<div class="card-body text-center">
														<div class="type-count"><?php echo esc_html($cat_count); ?></div>
														<div class="wmh-media-percentage">
															<div class="type-name"><?php echo esc_html($attachment_cat); ?></div>
															<div class="type-percetage"><?php echo esc_html($cat_per); ?></div>
														</div>
													</div>
												</div>
											</div>
										<?php }
									}
									else { ?>
										<div class="col-xl-4 col-sm-6 pt-3">
											<div class="card text-white box mt-0" id="image">
												<div class="card-body text-center">
													<div class="type-count"><?php echo esc_html('0'); ?></div>
													<div class="wmh-media-percentage">
														<div class="type-name"><?php _e('Images', MEDIA_HYGIENE); ?></div>
														<div class="type-percetage"><?php echo esc_html('0 %'); ?></div>
													</div>
												</div>
											</div>
										</div>
										<div class="col-xl-4 col-sm-6 pt-3">
											<div class="card text-white box mt-0" id="documents">
												<div class="card-body text-center">
													<div class="type-count"><?php echo esc_html('0'); ?></div>
													<div class="wmh-media-percentage">
														<div class="type-name"><?php _e('Documents', MEDIA_HYGIENE); ?></div>
														<div class="type-percetage"><?php echo esc_html('0 %'); ?></div>
													</div>
												</div>
											</div>
										</div>
										<div class="col-xl-4 col-sm-6 pt-3">
											<div class="card text-white box mt-0" id="videos">
												<div class="card-body text-center">
													<div class="type-count"><?php echo esc_html('0'); ?></div>
													<div class="wmh-media-percentage">
														<div class="type-name"><?php _e('Video', MEDIA_HYGIENE); ?></div>
														<div class="type-percetage"><?php echo esc_html('0 %'); ?></div>
													</div>
												</div>
											</div>
										</div>
										<div class="col-xl-4 col-sm-6 pt-3">
											<div class="card text-white box mt-0" id="audios">
												<div class="card-body text-center">
													<div class="type-count"><?php echo esc_html('0'); ?></div>
													<div class="wmh-media-percentage">
														<div class="type-name"><?php _e('Audio', MEDIA_HYGIENE); ?></div>
														<div class="type-percetage"><?php echo esc_html('0 %'); ?></div>
													</div>
												</div>
											</div>
										</div>
										<div class="col-xl-4 col-sm-6 pt-3">
											<div class="card text-white box mt-0" id="other">
												<div class="card-body text-center">
													<div class="type-count"><?php echo esc_html('0'); ?></div>
													<div class="wmh-media-percentage">
														<div class="type-name"><?php _e('Other', MEDIA_HYGIENE); ?></div>
														<div class="type-percetage"><?php echo esc_html('0 %'); ?></div>
													</div>
												</div>
											</div>
										</div>
									<?php } ?>
								</div>
							</div>
						</div>
					</div>
				</div>

			</div>
		</div>

		<div class="col-md-12 px-0">
			<div class="wmh-dashboard mb-2">
				<!-- Media left over breakdown -->
				<div id="media-type-accordion" class="admin-accord">
					<div class="card mt-2">
						<div class="card-header pt-1 pb-1" id="media-type">
							<h5 class="mb-0">
								<button class="btn btn-link w-100 text-start collapsed" data-bs-toggle="collapse" data-bs-target="#media-type-content" aria-expanded="false" aria-controls="media-type-accordion-content">
									<?php _e('Media Left Over Breakdown', MEDIA_HYGIENE); ?>
								</button>
							</h5>
						</div>
						<div id="media-type-content" class="collapse" aria-expanded="true" aria-labelledby="summary" data-parent="#media-type-accordion">
							<div class="card-body p-0 pb-3">
								<div class="row row-main media-type-info">

									<?php
									if (isset($media_type_info) && !empty($media_type_info)) {
										foreach ($media_type_info as $info) {
											if (isset($info['ext']) && $info['ext'] != '') {
												$media_type_name = $info['ext'];
											} else {
												$media_type_name = '-';
											}
											if (isset($info['ext_count']) && $info['ext_count'] != '') {
												$media_type_count = $info['ext_count'];
											} else {
												$media_type_count = '-';
											}
											if (isset($info['ext_per']) && $info['ext_per'] != '') {
												$media_type_per = $info['ext_per'];
											} else {
												$media_type_per = '-';
											}
											if (isset($info['file_size']) && $info['file_size'] != '') {
												$media_type_size = $info['file_size'];
											} else {
												$media_type_size = '-';
											}

									?>
											<div class="col-xl-2 col-sm-6 pt-3">
												<div class="card text-white box mt-0">
													<div class="card-body text-center media-type-body">
														<div class="wmh-media-type-text">
															<p><?php echo esc_html($media_type_count); ?></p>
														</div>
														<div class="wmh-media-type-info">
															<p><?php echo ($media_type_size); ?></p>
															<span><?php echo esc_html($media_type_per); ?> %</span>
														</div>
														<div class="wmh-media-type-extantion">
															<p><?php echo esc_html($media_type_name); ?></p>
														</div>
													</div>
												</div>
											</div>
										<?php }
									} else { ?>
										<div>
											<p><?php _e('No Data', MEDIA_HYGIENE); ?></p>
										</div>
									<?php } ?>

								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>