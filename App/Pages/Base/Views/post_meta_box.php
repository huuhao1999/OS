<?php

namespace FSPoster\App\Pages\Base\Views;

use FSPoster\App\Providers\Pages;
use FSPoster\App\Providers\Helper;

defined( 'ABSPATH' ) or exit;
?>

<link rel="stylesheet" href="<?php echo Pages::asset( 'Base', 'css/fsp-metabox.css' ); ?>">

<div class="fsp-metabox <?php echo $fsp_params[ 'minified' ] === TRUE ? 'fsp-is-mini' : 'fsp-card'; ?>">
	<div class="fsp-card-body">
		<div class="fsp-form-toggle-group">
			<label><?php echo fsp__( 'Share' ); ?></label>
			<div class="fsp-toggle">
				<input type="hidden" name="share_checked" value="off">
				<input type="checkbox" name="share_checked" class="fsp-toggle-checkbox" id="fspMetaboxShare" <?php echo $fsp_params[ 'share_checkbox' ] ? 'checked' : ''; ?>>
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
				<div data-tab="fb" class="fsp-metabox-tab fsp-tooltip fsp-temp-tooltip" data-title="<?php echo fsp__( 'Show only Facebook accounts' ); ?>">
					<i class="fab fa-facebook-f"></i>
				</div>
				<div data-tab="twitter" class="fsp-metabox-tab fsp-tooltip fsp-temp-tooltip" data-title="<?php echo fsp__( 'Show only Twitter accounts' ); ?>">
					<i class="fab fa-twitter"></i>
				</div>
				<div data-tab="instagram" class="fsp-metabox-tab fsp-tooltip fsp-temp-tooltip" data-title="<?php echo fsp__( 'Show only Instagram accounts' ); ?>">
					<i class="fab fa-instagram"></i>
				</div>
				<div data-tab="linkedin" class="fsp-metabox-tab fsp-tooltip fsp-temp-tooltip" data-title="<?php echo fsp__( 'Show only Linkedin accounts' ); ?>">
					<i class="fab fa-linkedin-in"></i>
				</div>
				<div data-tab="vk" class="fsp-metabox-tab fsp-tooltip fsp-temp-tooltip" data-title="<?php echo fsp__( 'Show only VKontakte accounts' ); ?>">
					<i class="fab fa-vk"></i>
				</div>
				<div data-tab="pinterest" class="fsp-metabox-tab fsp-tooltip fsp-temp-tooltip" data-title="<?php echo fsp__( 'Show only Pinterest accounts' ); ?>">
					<i class="fab fa-pinterest-p"></i>
				</div>
				<div data-tab="reddit" class="fsp-metabox-tab fsp-tooltip fsp-temp-tooltip" data-title="<?php echo fsp__( 'Show only Reddit accounts' ); ?>">
					<i class="fab fa-reddit-alien"></i>
				</div>
				<div data-tab="tumblr" class="fsp-metabox-tab fsp-tooltip fsp-temp-tooltip" data-title="<?php echo fsp__( 'Show only Tumblr accounts' ); ?>">
					<i class="fab fa-tumblr"></i>
				</div>
				<div data-tab="ok" class="fsp-metabox-tab fsp-tooltip fsp-temp-tooltip" data-title="<?php echo fsp__( 'Show only Odnoklassniki accounts' ); ?>">
					<i class="fab fa-odnoklassniki"></i>
				</div>
				<div data-tab="plurk" class="fsp-metabox-tab fsp-tooltip fsp-temp-tooltip" data-title="<?php echo fsp__( 'Show only Plurk accounts' ); ?>">
					<i class="fas fa-parking"></i>
				</div>
				<div data-tab="google_b" class="fsp-metabox-tab fsp-tooltip fsp-temp-tooltip" data-title="<?php echo fsp__( 'Show only Google My Business accounts' ); ?>">
					<i class="fab fa-google"></i>
				</div>
				<div data-tab="blogger" class="fsp-metabox-tab fsp-tooltip fsp-temp-tooltip" data-title="<?php echo fsp__( 'Show only Blogger accounts' ); ?>">
					<i class="fab fa-blogger"></i>
				</div>
				<div data-tab="telegram" class="fsp-metabox-tab fsp-tooltip fsp-temp-tooltip" data-title="<?php echo fsp__( 'Show only Telegram accounts' ); ?>">
					<i class="fab fa-telegram-plane"></i>
				</div>
				<div data-tab="medium" class="fsp-metabox-tab fsp-tooltip fsp-temp-tooltip" data-title="<?php echo fsp__( 'Show only Medium accounts' ); ?>">
					<i class="fab fa-medium-m"></i>
				</div>
				<div data-tab="wordpress" class="fsp-metabox-tab fsp-tooltip fsp-temp-tooltip" data-title="<?php echo fsp__( 'Show only WordPress websites' ); ?>">
					<i class="fab fa-wordpress-simple"></i>
				</div>
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

					$sn_names = [
						'fb'        => fsp__( 'FB' ),
						'fsp'        => fsp__( 'FSP' ),
						'twitter'   => fsp__( 'Twitter' ),
						'instagram' => fsp__( 'Instagram' ),
						'linkedin'  => fsp__( 'Linkedin' ),
						'vk'        => fsp__( 'VK' ),
						'pinterest' => fsp__( 'Pinterest' ),
						'reddit'    => fsp__( 'Reddit' ),
						'tumblr'    => fsp__( 'Tumblr' ),
						'ok'        => fsp__( 'OK' ),
						'google_b'  => fsp__( 'GMB' ),
						'blogger'   => fsp__( 'Blogger' ),
						'telegram'  => fsp__( 'Telegram' ),
						'medium'    => fsp__( 'Medium' ),
						'wordpress' => fsp__( 'WordPress' ),
						'plurk'     => fsp__( 'Plurk' )
					];
					$driver   = $sn_names[ $node_info[ 'driver' ] ];
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
								<?php echo $driver; ?>&nbsp;>&nbsp;<?php echo esc_html( $node_info[ 'node_type' ] ); ?>&nbsp;<?php echo empty( $titleText ) ? '' : '<i class="fas fa-filter fsp-tooltip" data-title="' . $titleText . '" ></i>'; ?>
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
		<button type="button" class="fsp-button fsp-is-gray fsp-metabox-add"><?php echo fsp__( 'ADD' ); ?></button>
		<button type="button" class="fsp-button fsp-is-red fsp-metabox-clear"><?php echo fsp__( 'CLEAR' ); ?></button>
	</div>
</div>

<script>
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

				wp.data.subscribe( function () {
					let isChecked = $( '#fspMetaboxShare' ).is( ':checked' );
					let isSavingPost = wp.data.select( 'core/editor' ).isSavingPost();
					let isAutosavingPost = wp.data.select( 'core/editor' ).isAutosavingPost();
					let isPostUpdated = window.location.href.match( /post\.php\?post=([0-9]+)/ );

					if ( isSavingPost && ! isAutosavingPost && isChecked && isPostUpdated )
					{
						let postID = isPostUpdated[ 1 ];

						setTimeout( function () {
							if ( ! alreadyShared )
							{
								FSPoster.ajax( 'check_post_is_published', { 'id': postID }, function ( result ) {
									if ( result[ 'post_status' ] === '2' )
									{
										FSPoster.loadModal( 'share_feeds', {
											'post_id': postID, 'dont_reload': '1'
										}, true );

										alreadyShared = true;
									}
								}, true, null, false );
							}
						}, 2000 );
					}
				} );
			}
			<?php } ?>

			FSPoster.load_script( '<?php echo Pages::asset( 'Base', 'js/fsp-metabox.js' ); ?>' );
		} );
	} )( jQuery );
</script>
