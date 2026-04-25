<?php

defined('ABSPATH') or die('Plugin file cannot be accessed directly.');

global $wpdb;

/* get total unused media count - query actual table to avoid drift from incremental updates */
$wmh_unused_table = $wpdb->prefix . MH_PREFIX . 'unused_media_post_id';
$unused_table_exists = $wpdb->get_var( "SHOW TABLES LIKE '$wmh_unused_table'" ) == $wmh_unused_table;
if ( $unused_table_exists ) {
	$unused_row = $wpdb->get_row( 'SELECT COUNT(post_id) as cnt FROM ' . $wmh_unused_table );
	$unused_media_count = $unused_row ? (int) $unused_row->cnt : 0;
} else {
	$unused_media_count = 0;
}

/* get total media count - query wp_posts directly to avoid drift */
$media_count = (int) $wpdb->get_var(
	"SELECT COUNT(ID) FROM {$wpdb->posts} WHERE post_type = 'attachment' AND post_status = 'inherit'"
);

/* used media count - derived so it never goes negative */
$use_media_count = max( 0, $media_count - $unused_media_count );

/* get total media size */
$media_size = get_option('wmh_total_media_size');
if ( isset( $media_size ) && $media_size != '' && $media_size != 0 ) {
	$media_size = size_format( get_option('wmh_total_media_size') );
} else {
	$media_size = 0;
}

/* unused media size */
$unused_media_size = get_option('wmh_unused_media_size');
if ( isset( $unused_media_size ) && $unused_media_size != '' && $unused_media_size != 0 ) {
	$unused_media_size = size_format( get_option('wmh_unused_media_size') );
} else {
	$unused_media_size = 0;
}

/* used media size */
$use_media_size = get_option('wmh_use_media_size');
if ( isset( $use_media_size ) && $use_media_size != '' && $use_media_size != 0 ) {
	$use_media_size = size_format( get_option('wmh_use_media_size') );
} else {
	$use_media_size = 0;
}

/* get media breakdown */
$media_breakdown_data = get_option('wmh_media_breakdown');
if ( isset( $media_breakdown_data['image_count'] ) ) {
	$media_breakdown_data = array();
}

/* get media type info */
$media_type_info = get_option('wmh_media_type_info');
if ( isset( $media_type_info[0]['media_type_name'] ) ) {
	$media_type_info = array();
}

?>

<div class="wmh-db-wrap" id="wmh-statistics" style="margin-top:12px;">

	<!-- Stats Strip -->
	<div class="wmh-stats-strip">

		<div class="wmh-st-tile wmh-st-total" id="total-media">
			<i class="fa-solid fa-photo-film wmh-st-icon"></i>
			<div class="wmh-st-num"><?php echo esc_html( $media_count ); ?></div>
			<div class="wmh-st-lbl"><?php _e( 'Total Media', MEDIA_HYGIENE ); ?></div>
		</div>

		<div class="wmh-st-tile wmh-st-used" id="media-in-use">
			<i class="fa-solid fa-link wmh-st-icon"></i>
			<div class="wmh-st-num"><?php echo esc_html( $use_media_count ); ?></div>
			<div class="wmh-st-lbl"><?php _e( 'In Use', MEDIA_HYGIENE ); ?></div>
		</div>

		<div class="wmh-st-tile wmh-st-left" id="media-over-left">
			<i class="fa-solid fa-circle-exclamation wmh-st-icon"></i>
			<div class="wmh-st-num"><?php echo esc_html( $unused_media_count ); ?></div>
			<div class="wmh-st-lbl"><?php _e( 'Left Over', MEDIA_HYGIENE ); ?></div>
		</div>

		<div class="wmh-st-divider"></div>

		<div class="wmh-st-tile wmh-st-total" id="total-media-size">
			<i class="fa-solid fa-database wmh-st-icon"></i>
			<div class="wmh-st-num"><?php echo esc_html( $media_size ); ?></div>
			<div class="wmh-st-lbl"><?php _e( 'Total Space', MEDIA_HYGIENE ); ?></div>
		</div>

		<div class="wmh-st-tile wmh-st-used" id="media-in-use-size">
			<i class="fa-solid fa-hard-drive wmh-st-icon"></i>
			<div class="wmh-st-num"><?php echo esc_html( $use_media_size ); ?></div>
			<div class="wmh-st-lbl"><?php _e( 'In Use Space', MEDIA_HYGIENE ); ?></div>
		</div>

		<div class="wmh-st-tile wmh-st-left" id="media-over-left-size">
			<i class="fa-solid fa-triangle-exclamation wmh-st-icon"></i>
			<div class="wmh-st-num"><?php echo esc_html( $unused_media_size ); ?></div>
			<div class="wmh-st-lbl"><?php _e( 'Left Over Space', MEDIA_HYGIENE ); ?></div>
		</div>

	</div><!-- .wmh-stats-strip -->

	<!-- Lower two-column section -->
	<div class="wmh-db-lower">

		<!-- Left: Media Left Over (category breakdown) -->
		<div class="wmh-db-panel">
			<div class="wmh-db-panel-hd">
				<i class="fa-solid fa-circle-exclamation"></i>
				<?php _e( 'Media Left Over', MEDIA_HYGIENE ); ?>
			</div>
			<div class="wmh-lo-list">
				<?php
				$wmh_cat_icon_map = [
					'images'    => 'fa-image',
					'video'     => 'fa-video',
					'audio'     => 'fa-music',
					'documents' => 'fa-file-lines',
					'others'    => 'fa-file',
				];
				if ( isset( $media_breakdown_data ) && ! empty( $media_breakdown_data ) ) {
					foreach ( $media_breakdown_data as $value ) {
						$cat_count      = isset( $value['cat_count'] ) && $value['cat_count'] != '' ? $value['cat_count'] : '0';
						$attachment_cat = isset( $value['attachment_cat'] ) && $value['attachment_cat'] != '' ? ucfirst( $value['attachment_cat'] ) : '-';
						$cat_per_num    = isset( $value['cat_per'] ) ? floatval( $value['cat_per'] ) : 0;
						$cat_per        = number_format( $cat_per_num, 2 ) . '%';
						$cat_slug       = strtolower( $value['attachment_cat'] ?? '' );
						$cat_icon       = $wmh_cat_icon_map[ $cat_slug ] ?? 'fa-file';
						?>
						<div class="wmh-lo-row">
							<i class="fa-solid <?php echo esc_attr( $cat_icon ); ?> wmh-lo-icon"></i>
							<span class="wmh-lo-cat"><?php echo esc_html( $attachment_cat ); ?></span>
							<span class="wmh-lo-cnt"><?php echo esc_html( $cat_count ); ?></span>
							<div class="wmh-lo-bar"><div class="wmh-lo-fill" style="width:<?php echo esc_attr( min( 100, $cat_per_num ) ); ?>%"></div></div>
							<span class="wmh-lo-pct"><?php echo esc_html( $cat_per ); ?></span>
						</div>
						<?php
					}
				} else {
					$wmh_defaults = [
						[ 'label' => 'Images',    'icon' => 'fa-image' ],
						[ 'label' => 'Documents', 'icon' => 'fa-file-lines' ],
						[ 'label' => 'Video',     'icon' => 'fa-video' ],
						[ 'label' => 'Audio',     'icon' => 'fa-music' ],
						[ 'label' => 'Other',     'icon' => 'fa-file' ],
					];
					foreach ( $wmh_defaults as $d ) {
						?>
						<div class="wmh-lo-row wmh-lo-empty">
							<i class="fa-solid <?php echo esc_attr( $d['icon'] ); ?> wmh-lo-icon"></i>
							<span class="wmh-lo-cat"><?php echo esc_html( $d['label'] ); ?></span>
							<span class="wmh-lo-cnt">—</span>
							<div class="wmh-lo-bar"><div class="wmh-lo-fill" style="width:0%"></div></div>
							<span class="wmh-lo-pct">—</span>
						</div>
						<?php
					}
				}
				?>
			</div>
		</div><!-- .wmh-db-panel (left) -->

		<!-- Right: Left Over Breakdown (by extension) -->
		<div class="wmh-db-panel">
			<div class="wmh-db-panel-hd">
				<i class="fa-solid fa-layer-group"></i>
				<?php _e( 'Left Over Breakdown', MEDIA_HYGIENE ); ?>
			</div>
			<div class="wmh-lo-list wmh-bd-list">
				<?php
				if ( isset( $media_type_info ) && ! empty( $media_type_info ) ) {
					foreach ( $media_type_info as $info ) {
						$media_type_name  = isset( $info['ext'] )        && $info['ext']        != '' ? $info['ext']        : '-';
						$media_type_count = isset( $info['ext_count'] )  && $info['ext_count']  != '' ? $info['ext_count']  : '-';
						$media_type_per   = isset( $info['ext_per'] )    && $info['ext_per']    != '' ? floatval( $info['ext_per'] ) : 0;
						$media_type_size  = isset( $info['file_size'] )  && $info['file_size']  != '' ? $info['file_size']  : '-';
						$media_type_per_display = number_format( $media_type_per, 2 ) . '%';

						$ext = strtolower( $media_type_name );
						if ( in_array( $ext, [ 'jpg','jpeg','png','gif','webp','svg','bmp','ico','tiff','avif' ] ) ) {
							$bd_icon = 'fa-image';
						} elseif ( in_array( $ext, [ 'mp4','avi','mov','wmv','mkv','flv','webm' ] ) ) {
							$bd_icon = 'fa-video';
						} elseif ( in_array( $ext, [ 'mp3','wav','ogg','aac','flac','m4a' ] ) ) {
							$bd_icon = 'fa-music';
						} elseif ( $ext === 'pdf' ) {
							$bd_icon = 'fa-file-pdf';
						} elseif ( in_array( $ext, [ 'doc','docx' ] ) ) {
							$bd_icon = 'fa-file-word';
						} elseif ( in_array( $ext, [ 'xls','xlsx','csv' ] ) ) {
							$bd_icon = 'fa-file-excel';
						} elseif ( in_array( $ext, [ 'zip','rar','7z','tar','gz' ] ) ) {
							$bd_icon = 'fa-file-zipper';
						} else {
							$bd_icon = 'fa-file';
						}
						?>
						<div class="wmh-lo-row">
							<i class="fa-solid <?php echo esc_attr( $bd_icon ); ?> wmh-lo-icon"></i>
							<span class="wmh-lo-cat wmh-bd-ext"><?php echo esc_html( strtoupper( $media_type_name ) ); ?></span>
							<span class="wmh-lo-cnt"><?php echo esc_html( $media_type_count ); ?></span>
							<div class="wmh-lo-bar"><div class="wmh-lo-fill" style="width:<?php echo esc_attr( min( 100, $media_type_per ) ); ?>%"></div></div>
							<span class="wmh-bd-size"><?php echo esc_html( $media_type_size ); ?></span>
							<span class="wmh-lo-pct"><?php echo esc_html( $media_type_per_display ); ?></span>
						</div>
						<?php
					}
				} else { ?>
					<p class="wmh-bd-empty"><?php _e( 'No data available. Run a scan to see the breakdown.', MEDIA_HYGIENE ); ?></p>
				<?php } ?>
			</div>
		</div><!-- .wmh-db-panel (right) -->

	</div><!-- .wmh-db-lower -->

</div><!-- .wmh-db-wrap -->
