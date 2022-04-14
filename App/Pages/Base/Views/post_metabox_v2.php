<?php

namespace FSPoster\App\Pages\Base\Views;

use FSPoster\App\Providers\Pages;
use FSPoster\App\Providers\Helper;

defined( 'ABSPATH' ) or exit;
?>

<link rel="stylesheet" href="<?php echo Pages::asset( 'Base', 'css/fsp-metabox.css' ); ?>">

<div class="fsp-metabox fsp-is-mini">
	<div class="fsp-card-body">
		<div class="fsp-form-toggle-group">
			<label><?php echo fsp__( 'Share' ); ?></label>
			<div class="fsp-toggle">
				<input type="hidden" name="share_checked" value="off">
				<input type="checkbox" name="share_checked" class="fsp-toggle-checkbox" id="fspMetaboxShare" <?php echo $fsp_params[ 'share_checkbox' ] === 'on' ? 'checked' : ''; ?>>
				<label class="fsp-toggle-label" for="fspMetaboxShare"></label>
			</div>
		</div>
		<div id="fspMetaboxShareContainer">
			<div class="fsp-metabox-tabs">
				<div data-tab="all" class="fsp-metabox-tab fsp-is-active fsp-tooltip fsp-temp-tooltip" data-title="<?php echo fsp__( 'Show all accounts' ); ?>">
					<i class="fas fa-grip-horizontal"></i>
				</div>
				<div data-tab="fsp" class="fsp-metabox-tab fsp-tooltip fsp-temp-tooltip" data-title="<?php echo fsp__( 'Show all groups' ); ?>">
					<i class="fas fa-object-group"></i>
				</div>
				<?php foreach ( $fsp_params[ 'tabs' ] as $tab_k => $tab ) { ?>
					<div data-tab="<?php echo $tab_k; ?>" class="fsp-metabox-tab fsp-tooltip fsp-temp-tooltip" data-title="<?php echo fsp__( 'Show only %s accounts', [ $tab[ 'long' ] ] ); ?>">
						<i class="<?php echo $tab[ 'icon' ]; ?>"></i>
					</div>
				<?php } ?>
			</div>
			<div class="fsp-metabox-accounts">
				<div class="fsp-metabox-accounts-empty">
					<?php echo fsp__( 'Please select an account' ); ?>
				</div>
				<?php foreach ( $fsp_params[ 'active_nodes' ] as $node_info )
				{
					$coverPhoto = Helper::profilePic( $node_info );

					if ( $node_info[ 'filter_type' ] === 'no' )
					{
						$titleText = '';
					}
					else
					{
						$titleText = ( $node_info[ 'filter_type' ] == 'in' ? 'Share only the posts of the selected categories:' : 'Do not share the posts of the selected categories:' ) . "\n";
						$titleText .= str_replace( ',', ', ', $node_info[ 'categories_name' ] );
					}

					$driver = $fsp_params[ 'tabs' ][ $node_info[ 'driver' ] ];
					?>

					<div data-driver="<?php echo $node_info[ 'driver' ]; ?>" class="fsp-metabox-account">
						<input type="hidden" name="share_on_nodes[]" value="<?php echo $node_info[ 'driver' ] . ':' . $node_info[ 'node_type' ] . ':' . $node_info[ 'id' ] . ':' . htmlspecialchars( $node_info[ 'filter_type' ] ) . ':' . htmlspecialchars( $node_info[ 'categories' ] ); ?>">
						<div class="fsp-metabox-account-image">
							<img src="<?php echo $coverPhoto; ?>" onerror="FSPoster.no_photo( this );">
						</div>
						<div class="fsp-metabox-account-label">
							<a target="_blank" href="<?php echo Helper::profileLink( $node_info ); ?>" class="fsp-metabox-account-text">
								<?php echo esc_html( $node_info[ 'name' ] ); ?>
							</a>
							<div class="fsp-metabox-account-subtext">
								<?php echo $driver[ 'long' ]; ?>&nbsp;>&nbsp;<?php echo esc_html( $node_info[ 'node_type' ] ); ?>&nbsp;<?php echo empty( $titleText ) ? '' : '<i class="fas fa-filter fsp-tooltip" data-title="' . $titleText . '" ></i>'; ?>
							</div>
						</div>
						<div class="fsp-metabox-account-remove">
							<i class="fas fa-times"></i>
						</div>
					</div>
				<?php } ?>
			</div>
			<div id="fspMetaboxCustomMessages" class="fsp-metabox-custom-messages">
				<input type="hidden" name="is_fsp_request" value="true">
				<div data-driver="fb">
					<div class="fsp-metabox-custom-message-label">
						<i class="fas fa-chevron-down"></i>&nbsp;<?php echo fsp__( 'Customize Facebook post message' ); ?>
					</div>
					<textarea class="fsp-form-textarea" rows="4" maxlength="3000" name="fs_post_text_message_fb"><?php echo htmlspecialchars( $fsp_params[ 'cm_fs_post_text_message_fb' ] ); ?></textarea>
					<div class="fsp-metabox-custom-message-label">
						<i class="fas fa-chevron-down"></i>&nbsp;<?php echo fsp__( 'Customize Facebook story message' ); ?>
					</div>
					<textarea class="fsp-form-textarea" rows="4" maxlength="3000" name="fs_post_text_message_fb_h"><?php echo htmlspecialchars( $fsp_params[ 'cm_fs_post_text_message_fb_h' ] ); ?></textarea>
				</div>
				<div data-driver="twitter">
					<div class="fsp-metabox-custom-message-label">
						<i class="fas fa-chevron-down"></i>&nbsp;<?php echo fsp__( 'Customize Twitter post message' ); ?>
					</div>
					<textarea class="fsp-form-textarea" rows="4" maxlength="3000" name="fs_post_text_message_twitter"><?php echo htmlspecialchars( $fsp_params[ 'cm_fs_post_text_message_twitter' ] ); ?></textarea>
				</div>
				<div data-driver="instagram">
					<div class="fsp-metabox-custom-message-label">
						<i class="fas fa-chevron-down"></i>&nbsp;<?php echo fsp__( 'Customize Instagram post message' ); ?>
					</div>
					<textarea class="fsp-form-textarea" rows="4" maxlength="3000" name="fs_post_text_message_instagram"><?php echo htmlspecialchars( $fsp_params[ 'cm_fs_post_text_message_instagram' ] ); ?></textarea>
					<div class="fsp-metabox-custom-message-label">
						<i class="fas fa-chevron-down"></i>&nbsp;<?php echo fsp__( 'Customize Instagram story message' ); ?>
					</div>
					<textarea class="fsp-form-textarea" rows="4" maxlength="3000" name="fs_post_text_message_instagram_h"><?php echo htmlspecialchars( $fsp_params[ 'cm_fs_post_text_message_instagram_h' ] ); ?></textarea>
				</div>
				<div data-driver="linkedin">
					<div class="fsp-metabox-custom-message-label">
						<i class="fas fa-chevron-down"></i>&nbsp;<?php echo fsp__( 'Customize LinkedIn post message' ); ?>
					</div>
					<textarea class="fsp-form-textarea" rows="4" maxlength="3000" name="fs_post_text_message_linkedin"><?php echo htmlspecialchars( $fsp_params[ 'cm_fs_post_text_message_linkedin' ] ); ?></textarea>
				</div>
				<div data-driver="vk">
					<div class="fsp-metabox-custom-message-label">
						<i class="fas fa-chevron-down"></i>&nbsp;<?php echo fsp__( 'Customize VKontakte post message' ); ?>
					</div>
					<textarea class="fsp-form-textarea" rows="4" maxlength="3000" name="fs_post_text_message_vk"><?php echo htmlspecialchars( $fsp_params[ 'cm_fs_post_text_message_vk' ] ); ?></textarea>
				</div>
				<div data-driver="pinterest">
					<div class="fsp-metabox-custom-message-label">
						<i class="fas fa-chevron-down"></i>&nbsp;<?php echo fsp__( 'Customize Pinterest post message' ); ?>
					</div>
					<textarea class="fsp-form-textarea" rows="4" maxlength="3000" name="fs_post_text_message_pinterest"><?php echo htmlspecialchars( $fsp_params[ 'cm_fs_post_text_message_pinterest' ] ); ?></textarea>
				</div>
				<div data-driver="reddit">
					<div class="fsp-metabox-custom-message-label">
						<i class="fas fa-chevron-down"></i>&nbsp;<?php echo fsp__( 'Customize Reddit post message' ); ?>
					</div>
					<textarea class="fsp-form-textarea" rows="4" maxlength="3000" name="fs_post_text_message_reddit"><?php echo htmlspecialchars( $fsp_params[ 'cm_fs_post_text_message_reddit' ] ); ?></textarea>
				</div>
				<div data-driver="tumblr">
					<div class="fsp-metabox-custom-message-label">
						<i class="fas fa-chevron-down"></i>&nbsp;<?php echo fsp__( 'Customize Tumblr post message' ); ?>
					</div>
					<textarea class="fsp-form-textarea" rows="4" maxlength="3000" name="fs_post_text_message_tumblr"><?php echo htmlspecialchars( $fsp_params[ 'cm_fs_post_text_message_tumblr' ] ); ?></textarea>
				</div>
				<div data-driver="ok">
					<div class="fsp-metabox-custom-message-label">
						<i class="fas fa-chevron-down"></i>&nbsp;<?php echo fsp__( 'Customize Odnoklassniki post message' ); ?>
					</div>
					<textarea class="fsp-form-textarea" rows="4" maxlength="3000" name="fs_post_text_message_ok"><?php echo htmlspecialchars( $fsp_params[ 'cm_fs_post_text_message_ok' ] ); ?></textarea>
				</div>
				<div data-driver="plurk">
					<div class="fsp-metabox-custom-message-label">
						<i class="fas fa-chevron-down"></i>&nbsp;<?php echo fsp__( 'Customize Plurk post message' ); ?>
					</div>
					<textarea class="fsp-form-textarea" rows="4" maxlength="3000" name="fs_post_text_message_ok"><?php echo htmlspecialchars( $fsp_params[ 'cm_fs_post_text_message_plurk' ] ); ?></textarea>
				</div>
				<div data-driver="google_b">
					<div class="fsp-metabox-custom-message-label">
						<i class="fas fa-chevron-down"></i>&nbsp;<?php echo fsp__( 'Customize Google My Business post message' ); ?>
					</div>
					<textarea class="fsp-form-textarea" rows="4" maxlength="3000" name="fs_post_text_message_google_b"><?php echo htmlspecialchars( $fsp_params[ 'cm_fs_post_text_message_google_b' ] ); ?></textarea>
				</div>
				<div data-driver="blogger">
					<div class="fsp-metabox-custom-message-label">
						<i class="fas fa-chevron-down"></i>&nbsp;<?php echo fsp__( 'Customize Blogger post message' ); ?>
					</div>
					<textarea class="fsp-form-textarea" rows="4" maxlength="3000" name="fs_post_text_message_blogger"><?php echo htmlspecialchars( $fsp_params[ 'cm_fs_post_text_message_blogger' ] ); ?></textarea>
				</div>
				<div data-driver="telegram">
					<div class="fsp-metabox-custom-message-label">
						<i class="fas fa-chevron-down"></i>&nbsp;<?php echo fsp__( 'Customize Telegram post message' ); ?>
					</div>
					<textarea class="fsp-form-textarea" rows="4" maxlength="3000" name="fs_post_text_message_telegram"><?php echo htmlspecialchars( $fsp_params[ 'cm_fs_post_text_message_telegram' ] ); ?></textarea>
				</div>
				<div data-driver="medium">
					<div class="fsp-metabox-custom-message-label">
						<i class="fas fa-chevron-down"></i>&nbsp;<?php echo fsp__( 'Customize Medium post message' ); ?>
					</div>
					<textarea class="fsp-form-textarea" rows="4" maxlength="3000" name="fs_post_text_message_medium"><?php echo htmlspecialchars( $fsp_params[ 'cm_fs_post_text_message_medium' ] ); ?></textarea>
				</div>
				<div data-driver="wordpress">
					<div class="fsp-metabox-custom-message-label">
						<i class="fas fa-chevron-down"></i>&nbsp;<?php echo fsp__( 'Customize WordPress post message' ); ?>
					</div>
					<textarea class="fsp-form-textarea" rows="4" maxlength="3000" name="fs_post_text_message_wordpress"><?php echo htmlspecialchars( $fsp_params[ 'cm_fs_post_text_message_wordpress' ] ); ?></textarea>
				</div>
				<div data-driver="plurk">
					<div class="fsp-metabox-custom-message-label">
						<i class="fas fa-chevron-down"></i>&nbsp;<?php echo fsp__( 'Customize Plurk post message' ); ?>
					</div>
					<textarea class="fsp-form-textarea" rows="4" maxlength="3000" name="fs_post_text_message_plurk"><?php echo htmlspecialchars( $fsp_params[ 'cm_fs_post_text_message_plurk' ] ); ?></textarea>
				</div>
			</div>
		</div>
	</div>
	<div class="fsp-card-footer fsp-is-right">
		<div id="fspSavingMetabox" class="fsp-metabox-loading-icon fsp-hide">
			<i class="fas fa-spin fa-spinner"></i>
		</div>
		<button type="button" class="fsp-button fsp-is-gray fsp-metabox-add"><?php echo fsp__( 'ADD' ); ?></button>
		<button type="button" class="fsp-button fsp-is-red fsp-metabox-clear"><?php echo fsp__( 'CLEAR' ); ?></button>
	</div>
</div>

<script>
	FSPObject.id = <?php echo (int) $fsp_params[ 'post_id' ]; ?>;

	( function ( $ ) {
		let doc = $( document );

		doc.ready( function () {
			<?php if ( ! defined( 'NOT_CHECK_SP' ) && isset( $fsp_params[ 'check_not_sended_feeds' ] ) && $fsp_params[ 'check_not_sended_feeds' ][ 'cc' ] > 0 ) { ?>
			FSPoster.loadModal( 'share_feeds', { 'post_id': '<?php echo (int) $fsp_params[ 'post_id' ]; ?>' }, true );
			<?php } ?>

			<?php if ( (int) Helper::getOption( 'share_on_background', '1' ) === 0 && get_post_status() != 'publish' ) { ?>

			if ( $( '.block-editor__container' ).length )
			{
				let alreadyShared = false;

				doc.on( 'click', '.editor-post-publish-button', function () {
					setTimeout( function () {
						let isChecked = $( '#fspMetaboxShare' ).is( ':checked' );
						let isSaved = window.location.href.match( /post\.php\?post=([0-9]+)/ );

						if ( ! alreadyShared && isChecked && isSaved && isSaved[ 1 ] )
						{
							FSPoster.ajax( 'check_post_is_published', { 'id': isSaved[ 1 ] }, function ( result ) {
								if ( result[ 'post_status' ] === '2' )
								{
									FSPoster.loadModal( 'share_feeds', {
										'post_id': isSaved[ 1 ],
										'dont_reload': '1'
									}, true );

									alreadyShared = true;
								}
							}, true, null );
						}
					}, 2000 );
				} );
			}
			<?php } ?>

			FSPoster.load_script( '<?php echo Pages::asset( 'Base', 'js/fsp-metabox.js' ); ?>' );
		} );
	} )( jQuery );
</script>
