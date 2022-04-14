<?php

namespace FSPoster\App\Providers;

use Exception;
use FSPoster\App\Libraries\twitter\TwitterPrivateAPI;
use FSPoster\App\Libraries\vk\Vk;
use FSPoster\App\Libraries\fb\Facebook;
use FSPoster\App\Libraries\plurk\Plurk;
use FSPoster\App\Libraries\medium\Medium;
use FSPoster\App\Libraries\reddit\Reddit;
use FSPoster\App\Libraries\tumblr\Tumblr;
use FSPoster\App\Libraries\twitter\Twitter;
use FSPoster\App\Libraries\blogger\Blogger;
use FSPoster\App\Libraries\ok\OdnoKlassniki;
use FSPoster\App\Libraries\linkedin\Linkedin;
use FSPoster\App\Libraries\telegram\Telegram;
use FSPoster\App\Libraries\pinterest\Pinterest;
use FSPoster\App\Libraries\wordpress\Wordpress;
use FSPoster\App\Libraries\fb\FacebookCookieApi;
use FSPoster\App\Libraries\instagram\InstagramApi;
use FSPoster\App\Libraries\google\GoogleMyBusiness;
use FSPoster\App\Libraries\google\GoogleMyBusinessAPI;
use FSPoster\App\Libraries\pinterest\PinterestCookieApi;
use FSPoster\App\Libraries\tumblr\TumblrLoginPassMethod;

class ShareService
{
	public static function insertFeeds ( $wpPostId, $userId, $nodes_list, $custom_messages, $categoryFilter = TRUE, $schedule_date = NULL, $sharedFrom = NULL, $shareOnBackground = NULL, $scheduleId = NULL, $disableStartInterval = FALSE )
	{
		/**
		 * Accounts, communications list array
		 */
		$nodes_list = is_array( $nodes_list ) ? $nodes_list : [];

		/**
		 * Instagram, share on:
		 *  - 1: Profile only
		 *  - 2: Story only
		 *  - 3: Profile and Story
		 */
		$igPostType = Helper::getOption( 'instagram_post_in_type', '1' );
		$fbPostType = Helper::getOption( 'fb_post_in_type', '1' );

		/**
		 * Interval for each publication (sec.)
		 */
		$postInterval        = (int) Helper::getOption( 'post_interval', '0' );
		$postIntervalType    = (int) Helper::getOption( 'post_interval_type', '1' );
		$sendDateTime        = Date::dateTimeSQL( is_null( $schedule_date ) ? 'now' : $schedule_date );
		$intervalForNetworks = [];

		/**
		 * Time interval before start
		 */
		if ( ! $disableStartInterval )
		{
			$timer = (int) Helper::getOption( 'share_timer', '0' );

			if ( $timer > 0 )
			{
				$sendDateTime = Date::dateTimeSQL( $sendDateTime, '+' . $timer . ' minutes' );
			}
		}

		$feedsCount = 0;

		if ( is_null( $shareOnBackground ) )
		{
			$shareOnBackground = (int) Helper::getOption( 'share_on_background', '1' );
		}

		foreach ( $nodes_list as $nodeId )
		{
			if ( is_string( $nodeId ) && strpos( $nodeId, ':' ) !== FALSE )
			{
				$parse         = explode( ':', $nodeId );
				$driver        = $parse[ 0 ];
				$nodeType      = $parse[ 1 ];
				$nodeId        = $parse[ 2 ];
				$filterType    = isset( $parse[ 3 ] ) ? $parse[ 3 ] : 'no';
				$categoriesStr = isset( $parse[ 4 ] ) ? $parse[ 4 ] : '';

				if ( $categoryFilter && ! empty( $categoriesStr ) && $filterType != 'no' )
				{
					$categoriesFilter = [];

					foreach ( explode( ',', $categoriesStr ) as $termId )
					{
						if ( is_numeric( $termId ) && $termId > 0 )
						{
							$categoriesFilter[] = (int) $termId;
						}
					}

					$result = DB::DB()->get_row( "SELECT count(0) AS r_count FROM `" . DB::WPtable( 'term_relationships', TRUE ) . "` WHERE object_id='" . (int) $wpPostId . "' AND `term_taxonomy_id` IN (SELECT `term_taxonomy_id` FROM `" . DB::WPtable( 'term_taxonomy', TRUE ) . "` WHERE `term_id` IN ('" . implode( "' , '", $categoriesFilter ) . "'))", ARRAY_A );

					if ( ( $filterType == 'in' && $result[ 'r_count' ] == 0 ) || ( $filterType == 'ex' && $result[ 'r_count' ] > 0 ) )
					{
						continue;
					}
				}

				if ( $nodeType == 'account' && in_array( $driver, [ 'tumblr', 'google_b', 'telegram' ] ) )
				{
					continue;
				}

				if ( ! ( in_array( $nodeType, [
						'account',
						'ownpage',
						'page',
						'group',
						'event',
						'blog',
						'company',
						'community',
						'subreddit',
						'location',
						'chat',
						'board',
						'publication'
					] ) && is_numeric( $nodeId ) && $nodeId > 0 ) )
				{
					continue;
				}

				if ( $postInterval > 0 )
				{
					$driver2ForArr = $postIntervalType == 1 ? $driver : 'all';
					$dataSendTime  = isset( $intervalForNetworks[ $driver2ForArr ] ) ? $intervalForNetworks[ $driver2ForArr ] : $sendDateTime;
				}
				else
				{
					$dataSendTime = $sendDateTime;
				}

                $feedSQL = [
                    'blog_id'             => Helper::getBlogId(),
                    'user_id'             => $userId,
                    'driver'              => $driver,
                    'post_id'             => $wpPostId,
                    'node_type'           => $nodeType,
                    'node_id'             => (int) $nodeId,
                    'interval'            => $postInterval,
                    'send_time'           => $dataSendTime,
                    'share_on_background' => $shareOnBackground ? 1 : 0,
                    'schedule_id'         => $scheduleId,
                    'is_seen'             => 0,
                    'shared_from'         => $sharedFrom
                ];

				if ( ! ( $driver == 'instagram' && $igPostType == '2' ) && ! ( $driver == 'fb' && $nodeType === 'account' && $fbPostType == '2' ) )
				{
					$customMessage = isset( $custom_messages[ $driver ] ) ? $custom_messages[ $driver ] : NULL;

					if ( $customMessage == Helper::getOption( 'post_text_message_' . $driver, "{title}" ) )
					{
						$customMessage = NULL;
					}

                    $feedSQL[ 'custom_post_message' ] = $customMessage;

					DB::DB()->insert( DB::table( 'feeds' ), $feedSQL );

					$feedsCount++;
				}

				if ( ( $driver == 'instagram' && ( $igPostType == '2' || $igPostType == '3' ) ) || ( $driver == 'fb' && $nodeType === 'account' && ( $fbPostType == '2' || $fbPostType == '3' ) ) )
				{
					$customMessage = isset( $custom_messages[ $driver . '_h' ] ) ? $custom_messages[ $driver . '_h' ] : NULL;

					if ( $customMessage == Helper::getOption( 'post_text_message_' . $driver . '_h', "{title}" ) )
					{
						$customMessage = NULL;
					}

                    $feedSQL[ 'custom_post_message' ] = $customMessage;
                    $feedSQL[ 'feed_type' ] = 'story';

					DB::DB()->insert( DB::table( 'feeds' ), $feedSQL );

					$feedsCount++;
				}

				if ( $postInterval > 0 )
				{
					$intervalForNetworks[ $driver2ForArr ] = Date::dateTimeSQL( $dataSendTime, '+' . $postInterval . ' second' );
				}
			}
		}

		return $feedsCount;
	}

	public static function shareQueuedFeeds ()
	{
		$all_blogs = Helper::getBlogs();

		foreach ( $all_blogs as $blog_id )
		{
			Helper::setBlogId( $blog_id );

			$now = Date::dateTimeSQL();

			$feeds    = DB::DB()->get_results( DB::DB()->prepare( 'SELECT id FROM `' . DB::table( 'feeds' ) . '` tb1 WHERE `blog_id`=%d AND `share_on_background`=1 and `is_sended`=0 and `send_time`<=%s AND (SELECT count(0) FROM `' . DB::WPtable( 'posts', TRUE ) . '` WHERE `id`=tb1.`post_id` AND (`post_status`=\'publish\' OR `post_type`=\'attachment\'))>0 LIMIT 5', [
				$blog_id,
				$now
			] ), ARRAY_A );
			$feed_ids = [];

			foreach ( $feeds as $feed )
			{
				$feed_ids[] = intval( $feed[ 'id' ] );
			}

			if ( ! empty( $feed_ids ) )
			{
				DB::DB()->query( 'UPDATE `' . DB::table( 'feeds' ) . '` SET `is_sended`=2 WHERE id IN (\'' . implode( "','", $feed_ids ) . '\')' );

				foreach ( $feeds as $feed )
				{
					if ( ! empty( $feed[ 'schedule_id' ] ) )
					{
						$schedule_info = DB::DB()->get_row( DB::DB()->prepare( 'SELECT * FROM `' . DB::table( 'schedules' ) . '` WHERE `id` = %d', [ $feed[ 'schedule_id' ] ] ), ARRAY_A );

						if ( ! empty( $schedule_info ) && self::is_sleep_time( $schedule_info ) )
						{
							continue;
						}
					}

					ShareService::post( $feed[ 'id' ], TRUE );
				}
			}

            $pendingPosts = DB::DB()->get_row( DB::DB()->prepare( 'SELECT COUNT(0) AS `count` FROM `' . DB::table( 'feeds' ) . '` tb1 WHERE `blog_id`=%d AND `share_on_background`=1 and `is_sended`=0 and `send_time`<=%s AND (SELECT count(0) FROM `' . DB::WPtable( 'posts', TRUE ) . '` WHERE `id`=tb1.`post_id` AND (`post_status`=\'publish\' OR `post_type`=\'attachment\'))>0', [
                $blog_id,
                $now
            ] ), ARRAY_A );

            if ( ! empty( $pendingPosts[ 'count' ] ) && $pendingPosts[ 'count' ] > 1 )
            {
                wp_remote_get( site_url() .'/wp-cron.php?doing_wp_cron', [ 'blocking' => false ] );
            }

			Helper::resetBlogId();
		}
	}

	public static function postSaveEvent ( $new_status, $old_status, $post )
	{
		global $wp_version;

		$post_id = $post->ID;
		$userId  = $post->post_author;

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
		{
			return;
		}

		if ( ! in_array( $new_status, [ 'publish', 'future', 'draft', 'pending' ] ) )
		{
			return;
		}

		/**
		 * Gutenberg bug...
		 * https://github.com/WordPress/gutenberg/issues/15094
		 */
		if ( version_compare( $wp_version, '5.0', '>=' ) && isset( $_GET[ '_locale' ] ) && $_GET[ '_locale' ] == 'user' && empty( $_POST ) )
		{
			delete_post_meta( $post_id, '_fs_poster_post_old_status_saved' );
			add_post_meta( $post_id, '_fs_poster_post_old_status_saved', $old_status, TRUE );

			return;
		}

		if ( ! in_array( $post->post_type, explode( '|', Helper::getOption( 'allowed_post_types', 'post|page|attachment|product' ) ) ) )
		{
			return;
		}

		$metaBoxLoader            = (int) Request::get( 'meta-box-loader', 0, 'num', [ '1' ] );
		$original_post_old_status = Request::post( 'original_post_status', '', 'string' );

		if ( $metaBoxLoader === 1 && ! empty( $original_post_old_status ) )
		{
			// Gutenberg bug!
			$old_status = get_post_meta( $post_id, '_fs_poster_post_old_status_saved', TRUE );
			delete_post_meta( $post_id, '_fs_poster_post_old_status_saved' );
		}

		if ( $old_status === 'publish' )
		{
			return;
		}

		if ( $old_status === 'future' && ( $new_status === 'future' || $new_status === 'publish' ) )
		{
			$oldScheduleDate = Date::epoch( get_post_meta( $post_id, '_fs_poster_schedule_datetime', TRUE ) );
			$newDateTime     = $new_status == 'publish' ? Date::epoch() : Date::epoch( $post->post_date );
			$diff            = (int) ( ( $newDateTime - $oldScheduleDate ) / 60 );

			if ( $diff != 0 && abs( $diff ) < 60 * 24 * 90 )
			{
				DB::DB()->query( 'UPDATE `' . DB::table( 'feeds' ) . '` SET `send_time`=ADDDATE(`send_time`,INTERVAL ' . $diff . ' MINUTE) WHERE blog_id=\'' . Helper::getBlogId() . '\' AND is_sended=0 and post_id=\'' . (int) $post_id . '\'' );
			}

			delete_post_meta( $post_id, '_fs_poster_schedule_datetime' );

			if ( $new_status == 'future' )
			{
				add_post_meta( $post_id, '_fs_poster_schedule_datetime', $post->post_date, TRUE );
			}

			return;
		}

		if ( $old_status === 'future' )
		{
			$nodes_list        = [];
			$post_text_message = [
				'fb'          => Helper::getOption( 'post_text_message_fb', "{title}" ),
				'fb_h'        => Helper::getOption( 'post_text_message_fb_h', "{title}" ),
				'instagram'   => Helper::getOption( 'post_text_message_instagram', "{title}" ),
				'instagram_h' => Helper::getOption( 'post_text_message_instagram_h', "{title}" ),
				'twitter'     => Helper::getOption( 'post_text_message_twitter', "{title}" ),
				'linkedin'    => Helper::getOption( 'post_text_message_linkedin', "{title}" ),
				'tumblr'      => Helper::getOption( 'post_text_message_tumblr', "<img src='{featured_image_url}'>\n\n{content_full}" ),
				'reddit'      => Helper::getOption( 'post_text_message_reddit', "{title}" ),
				'vk'          => Helper::getOption( 'post_text_message_vk', "{title}" ),
				'ok'          => Helper::getOption( 'post_text_message_ok', "{title}" ),
				'pinterest'   => Helper::getOption( 'post_text_message_pinterest', "{content_short_500}" ),
				'google_b'    => Helper::getOption( 'post_text_message_google_b', "{title}" ),
				'blogger'     => Helper::getOption( 'post_text_message_blogger', "<img src='{featured_image_url}'>\n\n{content_full} \n\n<a href='{link}'>{link}</a>" ),
				'telegram'    => Helper::getOption( 'post_text_message_telegram', "{title}\n\n<img src='{featured_image_url}'>\n\n{content_full}{link}" ),
				'medium'      => Helper::getOption( 'post_text_message_medium', "<img src='{featured_image_url}'>\n\n{content_full}\n\n<a href='{link}'>{link}</a>" ),
				'wordpress'   => Helper::getOption( 'post_text_message_wordpress', "{content_full}" ),
				'plurk'       => Helper::getOption( 'post_text_message_plurk', "{title}\n\n{featured_image_url}\n\n{content_short_200}" )
			];

			$getScheduledFeeds = DB::DB()->get_results( DB::DB()->prepare( "
					SELECT tb1.node_id AS id, tb1.driver, tb1.node_type, tb2.filter_type, tb2.categories, tb1.custom_post_message FROM `" . DB::table( 'feeds' ) . "` tb1 LEFT JOIN `" . DB::table( 'account_status' ) . "` tb2 ON tb2.account_id=tb1.node_id AND tb2.user_id=%d WHERE tb1.post_id=%d AND node_type='account'
					UNION 
					SELECT tb1.node_id AS id, tb1.driver, tb1.node_type, tb2.filter_type, tb2.categories, tb1.custom_post_message FROM `" . DB::table( 'feeds' ) . "` tb1 LEFT JOIN `" . DB::table( 'account_node_status' ) . "` tb2 ON tb2.node_id=tb1.node_id AND tb2.user_id=%d WHERE tb1.post_id=%d AND node_type<>'account'
					", [ $userId, $post_id, $userId, $post_id ] ), ARRAY_A );

			foreach ( $getScheduledFeeds as $nodeInf )
			{
				$nodes_list[] = $nodeInf[ 'driver' ] . ':' . $nodeInf[ 'node_type' ] . ':' . $nodeInf[ 'id' ] . ':' . htmlspecialchars( $nodeInf[ 'filter_type' ] ) . ':' . htmlspecialchars( $nodeInf[ 'categories' ] );

				$post_text_message[ $nodeInf[ 'driver' ] ] = $nodeInf[ 'custom_post_message' ];
			}

			add_post_meta( $post_id, '_fs_poster_share', ( empty( $nodes_list ) ? Helper::getOption( 'auto_share_new_posts', '1' ) : 1 ), TRUE );
			add_post_meta( $post_id, '_fs_poster_node_list', $nodes_list, TRUE );

			foreach ( $post_text_message as $dr => $cmtxt )
			{
				add_post_meta( $post_id, '_fs_poster_cm_' . $dr, $cmtxt, TRUE );
			}

			DB::DB()->delete( DB::table( 'feeds' ), [
				'blog_id'   => Helper::getBlogId(),
				'post_id'   => $post_id,
				'is_sended' => '0'
			] );

			return;
		}

		// if the request is from real user
		if ( metadata_exists( 'post', $post_id, '_fs_is_manual_action' ) )
		{
			$post_text_message[ 'fb' ]          = get_post_meta( $post_id, '_fs_poster_cm_fb', TRUE );
			$post_text_message[ 'fb_h' ]        = get_post_meta( $post_id, '_fs_poster_cm_fb_h', TRUE );
			$post_text_message[ 'twitter' ]     = get_post_meta( $post_id, '_fs_poster_cm_twitter', TRUE );
			$post_text_message[ 'instagram' ]   = get_post_meta( $post_id, '_fs_poster_cm_instagram', TRUE );
			$post_text_message[ 'instagram_h' ] = get_post_meta( $post_id, '_fs_poster_cm_instagram_h', TRUE );
			$post_text_message[ 'linkedin' ]    = get_post_meta( $post_id, '_fs_poster_cm_linkedin', TRUE );
			$post_text_message[ 'vk' ]          = get_post_meta( $post_id, '_fs_poster_cm_vk', TRUE );
			$post_text_message[ 'pinterest' ]   = get_post_meta( $post_id, '_fs_poster_cm_pinterest', TRUE );
			$post_text_message[ 'reddit' ]      = get_post_meta( $post_id, '_fs_poster_cm_reddit', TRUE );
			$post_text_message[ 'tumblr' ]      = get_post_meta( $post_id, '_fs_poster_cm_tumblr', TRUE );
			$post_text_message[ 'ok' ]          = get_post_meta( $post_id, '_fs_poster_cm_ok', TRUE );
			$post_text_message[ 'google_b' ]    = get_post_meta( $post_id, '_fs_poster_cm_google_b', TRUE );
			$post_text_message[ 'blogger' ]     = get_post_meta( $post_id, '_fs_poster_cm_blogger', TRUE );
			$post_text_message[ 'telegram' ]    = get_post_meta( $post_id, '_fs_poster_cm_telegram', TRUE );
			$post_text_message[ 'medium' ]      = get_post_meta( $post_id, '_fs_poster_cm_medium', TRUE );
			$post_text_message[ 'wordpress' ]   = get_post_meta( $post_id, '_fs_poster_cm_wordpress', TRUE );
			$post_text_message[ 'plurk' ]       = get_post_meta( $post_id, '_fs_poster_cm_plurk', TRUE );
		}
		else
		{
			$post_text_message[ 'fb' ]          = Helper::getOption( 'post_text_message_fb', '{title}' );
			$post_text_message[ 'fb_h' ]        = Helper::getOption( 'post_text_message_fb_h', '{title}' );
			$post_text_message[ 'twitter' ]     = Helper::getOption( 'post_text_message_twitter', '{title}' );
			$post_text_message[ 'instagram' ]   = Helper::getOption( 'post_text_message_instagram', '{title}' );
			$post_text_message[ 'instagram_h' ] = Helper::getOption( 'post_text_message_instagram_h', '{title}' );
			$post_text_message[ 'linkedin' ]    = Helper::getOption( 'post_text_message_linkedin', '{title}' );
			$post_text_message[ 'vk' ]          = Helper::getOption( 'post_text_message_vk', '{title}' );
			$post_text_message[ 'pinterest' ]   = Helper::getOption( 'post_text_message_pinterest', "{content_short_500}" );
			$post_text_message[ 'reddit' ]      = Helper::getOption( 'post_text_message_reddit', '{title}' );
			$post_text_message[ 'tumblr' ]      = Helper::getOption( 'post_text_message_tumblr', "<img src='{featured_image_url}'>\n\n{content_full}" );
			$post_text_message[ 'ok' ]          = Helper::getOption( 'post_text_message_ok', '{title}' );
			$post_text_message[ 'google_b' ]    = Helper::getOption( 'post_text_message_google_b', '{title}' );
			$post_text_message[ 'blogger' ]     = Helper::getOption( 'post_text_message_blogger', "<img src='{featured_image_url}'>\n\n{content_full} \n\n<a href='{link}'>{link}</a>" );
			$post_text_message[ 'telegram' ]    = Helper::getOption( 'post_text_message_telegram', '{title}' );
			$post_text_message[ 'medium' ]      = Helper::getOption( 'post_text_message_medium', "<img src='{featured_image_url}'>\n\n{content_full}\n\n<a href='{link}'>{link}</a>" );
			$post_text_message[ 'wordpress' ]   = Helper::getOption( 'post_text_message_wordpress', '{content_full}' );
			$post_text_message[ 'plurk' ]       = Helper::getOption( 'post_text_message_plurk', "{title}\n\n{featured_image_url}\n\n{content_short_200}" );
		}

		// if the request is from real user
		if ( metadata_exists( 'post', $post_id, '_fs_is_manual_action' ) )
		{
			$share_checked_input = get_post_meta( $post_id, '_fs_poster_share', TRUE );
		}
		else
		{
			$share_checked_input = Helper::getOption( 'auto_share_new_posts', '1' ) ? 'on' : 'off';
		}

		if ( $new_status == 'future' )
		{
			$backgroundShare = 1;

			add_post_meta( $post_id, '_fs_poster_schedule_datetime', $post->post_date, TRUE );
		}
		else
		{
			$backgroundShare = (int) Helper::getOption( 'share_on_background', '1' );
		}

		if ( $share_checked_input !== 'on' )
		{
			DB::DB()->delete( DB::table( 'feeds' ), [
				'blog_id'   => Helper::getBlogId(),
				'post_id'   => $post_id,
				'is_sended' => '0'
			] );

			return;
		}

		// if the request is from real user
		if ( metadata_exists( 'post', $post_id, '_fs_is_manual_action' ) )
		{
			$nodes_list = get_post_meta( $post_id, '_fs_poster_node_list', TRUE );
			$nodes_list = Pages::action( 'Base', 'groups_to_nodes', [ 'node_list' => $nodes_list ] );
		}
		else
		{
			$nodes_list = [];

			$accounts = DB::DB()->get_results( DB::DB()->prepare( "
					SELECT tb2.id, tb2.driver, tb1.filter_type, tb1.categories, 'account' AS node_type 
					FROM " . DB::table( 'account_status' ) . " tb1
					INNER JOIN " . DB::table( 'accounts' ) . " tb2 ON tb2.id=tb1.account_id
					WHERE tb1.user_id=%d AND (tb2.user_id=%d OR tb2.is_public=1) AND tb2.blog_id=%d", [ $userId, $userId, Helper::getBlogId() ] ), ARRAY_A );

			$active_nodes = DB::DB()->get_results( DB::DB()->prepare( "
					SELECT tb2.id, tb2.driver, tb2.node_type, tb1.filter_type, tb1.categories FROM " . DB::table( 'account_node_status' ) . " tb1
					LEFT JOIN " . DB::table( 'account_nodes' ) . " tb2 ON tb2.id=tb1.node_id
					WHERE tb1.user_id=%d AND (tb2.user_id=%d OR tb2.is_public=1) AND tb2.blog_id=%d", [ $userId, $userId, Helper::getBlogId() ] ), ARRAY_A );

			$active_nodes = array_merge( $accounts, $active_nodes );

			foreach ( $active_nodes as $nodeInf )
			{
				$nodes_list[] = $nodeInf[ 'driver' ] . ':' . $nodeInf[ 'node_type' ] . ':' . $nodeInf[ 'id' ] . ':' . htmlspecialchars( $nodeInf[ 'filter_type' ] ) . ':' . htmlspecialchars( $nodeInf[ 'categories' ] );
			}
		}

		if ( $new_status === 'draft' || $new_status === 'pending' )
		{
			add_post_meta( $post_id, '_fs_poster_share', 1, TRUE );
			add_post_meta( $post_id, '_fs_poster_node_list', $nodes_list, TRUE );

			foreach ( $post_text_message as $dr => $custom_message )
			{
				add_post_meta( $post_id, '_fs_poster_cm_' . $dr, $custom_message, TRUE );
			}

			return;
		}

		$schedule_date = NULL;

		if ( $new_status == 'future' )
		{
			$schedule_date = Date::dateTimeSQL( $post->post_date, '+1 minute' );
		}

		self::insertFeeds( $post_id, $userId, $nodes_list, $post_text_message, TRUE, $schedule_date, 'auto_post', $backgroundShare );

		if ( $new_status == 'publish' )
		{
			add_filter( 'redirect_post_location', function ( $location ) use ( $backgroundShare ) {
				return $location . '&share=1&background=' . $backgroundShare;
			} );
		}
	}

	public static function deletePostFeeds ( $post_id )
	{
		DB::DB()->delete( DB::table( 'feeds' ), [
			'blog_id'   => Helper::getBlogId(),
			'post_id'   => $post_id,
			'is_sended' => 0
		] );
	}

	public static function shareSchedules ()
	{
		$nowDateTime = Date::dateTimeSQL();

		$getSchedules = DB::DB()->prepare( 'SELECT * FROM `' . DB::table( 'schedules' ) . '` WHERE `status`=\'active\' and `next_execute_time`<=%s', [ $nowDateTime ] );
		$getSchedules = DB::DB()->get_results( $getSchedules, ARRAY_A );

		$preventDublicates = DB::DB()->prepare( 'UPDATE `' . DB::table( 'schedules' ) . '` SET `next_execute_time`=DATE_ADD(\'%s\', INTERVAL `interval` MINUTE) WHERE `status`=\'active\' and `next_execute_time`<=%s', [
			$nowDateTime,
			$nowDateTime
		] );
		DB::DB()->query( $preventDublicates );

		$result = FALSE;

		foreach ( $getSchedules as $schedule_info )
		{
			if ( self::scheduledPost( $schedule_info ) === TRUE )
			{
				$result = TRUE;
			}
		}

		if ( $result )
		{
			self::shareQueuedFeeds();
		}
	}

	public static function scheduledPost ( $schedule_info )
	{
		$scheduleId = $schedule_info[ 'id' ];
		$userId     = $schedule_info[ 'user_id' ];
		$blogId     = $schedule_info[ 'blog_id' ];

		Helper::setBlogId( $blogId );

		if ( self::is_sleep_time( $schedule_info ) )
		{
			Helper::resetBlogId();

			return FALSE;
		}

		$filterQuery = Helper::scheduleFilters( $schedule_info );

		/* End post_sort */
		$getRandomPost = DB::DB()->get_row( "SELECT * FROM `" . DB::WPtable( 'posts', TRUE ) . "` tb1 WHERE (post_status='publish' OR post_type='attachment') {$filterQuery} LIMIT 1", ARRAY_A );

		$post_id = ! empty( $getRandomPost[ 'ID' ] ) ? $getRandomPost[ 'ID' ] : 0;

		if ( ! ( $post_id > 0 ) && ( $schedule_info[ 'post_sort' ] !== 'old_first' || ! empty( $schedule_info[ 'save_post_ids' ] ) )  )
		{
			DB::DB()->update( DB::table( 'schedules' ), [ 'status' => 'finished' ], [ 'id' => $scheduleId ] );

			Helper::resetBlogId();

			return FALSE;
		}
		else if ( empty( $getRandomPost ) )
		{
			Helper::resetBlogId();

			return FALSE;
		}

		if ( $schedule_info[ 'post_freq' ] === 'once' && ! empty( $schedule_info[ 'post_ids' ] ) )
		{
			DB::DB()->query( DB::DB()->prepare( "UPDATE `" . DB::table( 'schedules' ) . "` SET `post_ids`=TRIM(BOTH ',' FROM replace(concat(',',`post_ids`,','), ',%d,',',')), status=IF( `post_ids`='' , 'finished', `status`) WHERE `id`=%d", [
				$post_id,
				$scheduleId
			] ) );
		}

		$accountsList = explode( ',', $schedule_info[ 'share_on_accounts' ] );

		if ( ! empty( $schedule_info[ 'share_on_accounts' ] ) && is_array( $accountsList ) && ! empty( $accountsList ) && count( $accountsList ) > 0 )
		{
			$_accountsList = [];
			$_node_list    = [];

			foreach ( $accountsList as $accountN )
			{
				$accountN = explode( ':', $accountN );

				if ( ! isset( $accountN[ 1 ] ) )
				{
					continue;
				}

				if ( $accountN[ 0 ] == 'account' )
				{
					$_accountsList[] = (int) $accountN[ 1 ];
				}
				else
				{
					$_node_list[] = (int) $accountN[ 1 ];
				}
			}

			$get_activeAccounts = [];
			$get_activeNodes    = [];

			if ( ! empty( $_accountsList ) )
			{
				$get_activeAccounts = DB::DB()->get_results( DB::DB()->prepare( "
						SELECT tb1.*, IFNULL(filter_type,'no') AS filter_type, categories
						FROM " . DB::table( 'accounts' ) . " tb1
						LEFT JOIN " . DB::table( 'account_status' ) . " tb2 ON tb1.id=tb2.account_id AND tb2.user_id=%d
						WHERE (tb1.is_public=1 OR tb1.user_id=%d) AND tb1.blog_id=%d AND tb1.id in (" . implode( ',', $_accountsList ) . ")", [
					$userId,
					$userId,
					Helper::getBlogId()
				] ), ARRAY_A );
			}

			if ( ! empty( $_node_list ) )
			{
				$get_activeNodes = DB::DB()->get_results( DB::DB()->prepare( "
						SELECT tb1.*, IFNULL(filter_type,'no') AS filter_type, categories
						FROM " . DB::table( 'account_nodes' ) . " tb1
						LEFT JOIN " . DB::table( 'account_node_status' ) . " tb2 ON tb1.id=tb2.node_id AND tb2.user_id=%d
						WHERE (tb1.is_public=1 OR tb1.user_id=%d) AND tb1.blog_id=%d AND tb1.id in (" . implode( ',', $_node_list ) . ")", [
					$userId,
					$userId,
					Helper::getBlogId()
				] ), ARRAY_A );
			}
		}

		$customPostMessages = json_decode( $schedule_info[ 'custom_post_message' ], TRUE );
		$customPostMessages = is_array( $customPostMessages ) ? $customPostMessages : [];
		$nodes_list         = [];

		foreach ( $get_activeAccounts as $accountInf )
		{
			$nodes_list[] = $accountInf[ 'driver' ] . ':account:' . (int) $accountInf[ 'id' ] . ':' . $accountInf[ 'filter_type' ] . ':' . $accountInf[ 'categories' ];
		}

		foreach ( $get_activeNodes as $nodeInf )
		{
			$nodes_list[] = $nodeInf[ 'driver' ] . ':' . $nodeInf[ 'node_type' ] . ':' . (int) $nodeInf[ 'id' ] . ':' . $nodeInf[ 'filter_type' ] . ':' . $nodeInf[ 'categories' ];
		}

		if ( ! empty( $nodes_list ) )
		{
			self::insertFeeds( $post_id, $userId, $nodes_list, $customPostMessages, FALSE, NULL, 'schedule', 1, $scheduleId, TRUE );

			Helper::resetBlogId();

			return TRUE;
		}

		Helper::resetBlogId();

		return FALSE;
	}

	private static function is_sleep_time ( $schedule_info )
	{
		if ( ! empty( $schedule_info[ 'sleep_time_start' ] ) && ! empty( $schedule_info[ 'sleep_time_end' ] ) )
		{
			$currentTimestamp = Date::epoch();
			$sleepTimeStart   = Date::epoch( Date::dateSQL() . ' ' . $schedule_info[ 'sleep_time_start' ] );
			$sleepTimeEnd     = Date::epoch( Date::dateSQL() . ' ' . $schedule_info[ 'sleep_time_end' ] );

			return Helper::isBetweenDates( $currentTimestamp, $sleepTimeStart, $sleepTimeEnd );
		}

		return FALSE;
	}

	public static function post ( $feedId, $secureShare = FALSE )
	{
		$feedInf = DB::fetch( 'feeds', $feedId );

		if ( ! $feedInf || ( $secureShare && $feedInf[ 'is_sended' ] != 2 ) )
		{
			return;
		}

		$post_id             = $feedInf[ 'post_id' ];
		$custom_post_message = $feedInf[ 'custom_post_message' ];

		$node_info = Helper::getAccessToken( $feedInf[ 'node_type' ], $feedInf[ 'node_id' ] );

		$nodeProfileId     = $node_info[ 'node_id' ];
		$appId             = $node_info[ 'app_id' ];
		$driver            = $node_info[ 'driver' ];
		$accessToken       = $node_info[ 'access_token' ];
		$accessTokenSecret = $node_info[ 'access_token_secret' ];
		$proxy             = $node_info[ 'info' ][ 'proxy' ];
		$options           = $node_info[ 'options' ];
		$accoundId         = $node_info[ 'account_id' ];
		$poster_id         = $node_info[ 'poster_id' ];

		$link           = '';
		$message        = '';
		$sendType       = 'link';
		$images         = NULL;
		$imagesLocale   = NULL;
		$videoURL       = NULL;
		$videoURLLocale = NULL;

		$postInf   = get_post( $post_id, ARRAY_A );
		$postType  = $postInf[ 'post_type' ];
		$postTitle = $postInf[ 'post_title' ];

		if ( $postType == 'attachment' && strpos( $postInf[ 'post_mime_type' ], 'image' ) !== FALSE )
		{
			$sendType       = 'image';
			$images[]       = $postInf[ 'guid' ];
			$imagesLocale[] = get_attached_file( $post_id );
		}
		else if ( $postType == 'attachment' && strpos( $postInf[ 'post_mime_type' ], 'video' ) !== FALSE )
		{
			$sendType       = 'video';
			$videoURL       = $postInf[ 'guid' ];
			$videoURLLocale = get_attached_file( $post_id );
		}

		$shortLink = '';
		$longLink  = '';

		if ( $postType == 'fs_post' || $postType == 'fs_post_tmp' )
		{
			$message = json_decode( $postInf[ 'post_content' ], TRUE );

			if ( ! is_null( $message ) )
			{
				$message = ! empty( $message[ $driver ] ) ? $message[ $driver ] : $message[ 'default' ];
			}
			else
			{
				$message = $postInf[ 'post_content' ];
			}

			$message = Helper::spintax( $message );

			$link1    = get_post_meta( $post_id, '_fs_link', TRUE );
			$link1    = Helper::spintax( $link1 );
			$longLink = $link1;

			$mediaId = get_post_thumbnail_id( $post_id );

			if ( $mediaId > 0 && empty( $link1 ) )
			{
				$sendType = 'image';
				$url1     = wp_get_attachment_url( $mediaId );
				$url2     = get_attached_file( $mediaId );

				$images       = [ $url1 ];
				$imagesLocale = [ $url2 ];
			}
			else if ( empty( $link1 ) )
			{
				$sendType = 'custom_message';
			}

			if ( ! empty( $link1 ) )
			{
				if ( Helper::getCustomSetting( 'unique_link', '1', $feedInf[ 'node_type' ], $feedInf[ 'node_id' ] ) == 1 )
				{
					$link1 .= ( strpos( $link1, '?' ) === FALSE ? '?' : '&' ) . '_unique_id=' . uniqid();
				}

				$link      = $link1;
				$shortLink = Helper::shortenerURL( $link1, $feedInf[ 'node_type' ], $feedInf[ 'node_id' ] );
			}
		}
		else
		{
			$link = Helper::getPostLink( $postInf, $feedId, $node_info[ 'info' ] );

			if ( empty( $custom_post_message ) )
			{
				$default_value       = $driver == 'wordpress' ? '{content_full}' : '{title}';
				$custom_post_message = Helper::getOption( 'post_text_message_' . $driver . ( ( $driver == 'instagram' || $driver == 'fb' ) && $feedInf[ 'feed_type' ] == 'story' ? '_h' : '' ), $default_value );
			}

			$longLink  = $link;
			$shortLink = Helper::shortenerURL( $link, $feedInf[ 'node_type' ], $feedInf[ 'node_id' ] );

			$message = Helper::replaceTags( $custom_post_message, $postInf, $longLink, $shortLink );
			$message = Helper::spintax( $message );

			if ( Helper::getOption( 'replace_wp_shortcodes', 'off' ) === 'on' )
			{
				$message = do_shortcode( $message );
			}
			else if ( Helper::getOption( 'replace_wp_shortcodes', 'off' ) === 'del' )
			{
				$message = strip_shortcodes( $message );
			}

			$message = htmlspecialchars_decode( $message );
			$link    = $shortLink;
		}

		if ( $driver != 'medium' && $driver != 'wordpress' && $driver != 'tumblr' && $driver != 'blogger' )
		{
			if ( $driver === 'telegram' )
			{
				$message = strip_tags( $message, '<b><u><i><a>' );
			}
			else
			{
				$message = strip_tags( $message );
			}

			$message = str_replace( [ '&nbsp;', "\r\n" ], [ '', "\n" ], $message );
		}

		if ( Helper::getOption( 'multiple_newlines_to_single', '0' ) == '1' && ! ( $postType == 'fs_post' || $postType == 'fs_post_tmp' ) )
		{
			$message = preg_replace( "/\n\s*\n\s*/", "\n\n", $message );
			//$message = preg_replace( "/(\n\s*){2,}/", "\n\n", $message );
		}

		if ( $driver == 'fb' )
		{
			if ( $sendType != 'image' && $sendType != 'video' )
			{
				$pMethod   = (int) Helper::getCustomSetting( 'posting_type', Helper::getOption( 'facebook_posting_type', '1' ), $feedInf[ 'node_type' ], $feedInf[ 'node_id' ] );
				$thumbnail = WPPostThumbnail::getPostThumbnailURL( $post_id, $driver );

				if ( $pMethod === 2 )
				{
					if ( ! empty( $thumbnail ) )
					{
						$sendType = 'image';
						$images   = [ $thumbnail ];
					}
				}
				else if ( $pMethod === 3 )
				{
					$images = WPPostThumbnail::getPostGalleryURL( $postInf, $postType );

					if ( ! empty( $images ) )
					{
						$sendType = 'image';
					}
				}
				else if ( $pMethod === 4 )
				{
					$sendType = 'custom_message';
				}
			}

			if ( empty( $options ) ) // App method
			{
				if ( $feedInf[ 'feed_type' ] === 'story' )
				{
					$res = [
						'status'    => 'error',
						'error_msg' => fsp__( 'Facebook API does not support sharing posts on the story so that accounts have to be added to the plugin via the cookie method to share posts on the story.' )
					];
				}
				else
				{
					if ( ! is_null( $poster_id ) )
					{
						$accessToken = DB::DB()->get_row( DB::DB()->prepare( 'SELECT access_token FROM ' . DB::table( 'account_nodes' ) . ' WHERE node_id = %d AND driver = "fb" AND blog_id = %d', [
							$poster_id,
							Helper::getBlogId()
						] ), ARRAY_A );
						$accessToken = $accessToken[ 'access_token' ];
					}

                    $appInfo = DB::fetch( 'apps', [ 'id' => $appId, 'driver' => 'fb' ] );

                    $fb  = new Facebook( $appInfo, $accessToken, $proxy );
					$res = $fb->sendPost( $nodeProfileId, $sendType, $message, 0, $link, $images, $videoURL, $poster_id );
				}

			}
			else // Cookie method
			{
				$fbDriver = new FacebookCookieApi( $accoundId, $options, $proxy );

				if ( $feedInf[ 'feed_type' ] === 'story' )
				{
					$thumbnailPath = WPPostThumbnail::getPostThumbnail( $post_id, $driver );

					if ( $sendType == 'image' )
					{
						$thumbnailPath = ! empty( reset( $imagesLocale ) ) ? reset( $imagesLocale ) : $thumbnailPath;
					}

					if ( empty( $thumbnailPath ) )
					{
						$res = [
							'status'    => 'error',
							'error_msg' => fsp__( 'Error! An image is required to share a story on Facebook. Please add media to the post.' )
						];
					}
					else if ( $feedInf[ 'node_type' ] !== 'account' )
					{
						$res = [
							'status'    => 'error',
							'error_msg' => fsp__( 'Sharing posts only on the account story is supported right now.' )
						];
					}
					else
					{
						$res = $fbDriver->sendStory( $feedInf[ 'node_id' ], $message, $thumbnailPath );
					}
				}
				else
				{
					$res = $fbDriver->sendPost( $nodeProfileId, $feedInf[ 'node_id' ], $feedInf[ 'node_type' ], $sendType, $message, 0, $link, $images, $videoURL );
				}
			}
		}
		else if ( $driver === 'plurk' )
		{

			if ( $sendType != 'image' && $sendType != 'video' )
			{
				$pMethod = (int) Helper::getCustomSetting( 'posting_type', Helper::getOption( 'plurk_posting_type', '2' ), $feedInf[ 'node_type' ], $feedInf[ 'node_id' ] );

				if ( $pMethod === 1 )
				{
					$sendType = 'custom_message';
				}
				else if ( $pMethod === 2 )
				{
					$sendType = 'link';
				}
				else if ( $pMethod == 3 )
				{
					$thumbnail = WPPostThumbnail::getPostThumbnailURL( $post_id, $driver );

					if ( ! empty( $thumbnail ) )
					{
						$sendType = 'image';
						$images   = [ $thumbnail ];
					}
				}
				else if ( $pMethod == 4 )
				{
					$images = WPPostThumbnail::getPostGalleryURL( $postInf, $postType );

					if ( ! empty( $images ) )
					{
						$sendType = 'image';
					}
				}
			}

			$autoCut = Helper::getOption( 'fs_plurk_auto_cut_plurks' );
			$appInfo = DB::fetch( 'apps', [ 'id' => $appId, 'driver' => 'plurk' ] );

			if ( ! $appInfo )
			{
				return [
					'status'    => 'error',
					'error_msg' => fsp__( 'Error! There isn\'t a Plurk App!' )
				];
			}

			$qualifier = (string) Helper::getOption( 'plurk_qualifier', ':' );

			$plurk = new Plurk( $appInfo[ 'app_key' ], $appInfo[ 'app_secret' ], $proxy );

			$res = $plurk->sendPost( $accessToken, $accessTokenSecret, $sendType, $message, $qualifier, $autoCut, $link, $images );
		}
		else if ( $driver == 'instagram' )
		{
			if ( $sendType != 'image' && $sendType != 'video' )
			{
				$thumbnailPath = WPPostThumbnail::getPostThumbnail( $post_id, $driver );
				$thumbnailURL  = WPPostThumbnail::getPostThumbnailURL( $post_id, $driver );

				if ( ! empty( $thumbnailPath ) )
				{
					$sendType     = 'image';
					$imagesLocale = [ $thumbnailPath ];
				}

				if ( ! empty( $thumbnailURL ) )
				{
					$sendType = 'image';
					$images   = [ $thumbnailURL ];
				}
			}
			if ( ! empty( $imagesLocale ) || ! empty( $videoURLLocale ) || ! empty( $images ) || ! empty( $videoURL ) )
			{
				if ( $feedInf[ 'feed_type' ] == 'story' )
				{
					try
					{
						$res = InstagramApi::sendStory( $node_info[ 'info' ], $sendType, $message, $link, $imagesLocale, $videoURLLocale );
					}
					catch ( Exception $e )
					{
						$res = [
							'status'    => 'error',
							'error_msg' => fsp__( 'Error! %s', [ $e->getMessage() ] )
						];
					}
				}
				else
				{
					if ( empty( $node_info[ 'info' ][ 'account_id' ] ) )
					{
						$res = InstagramApi::sendPost( $node_info[ 'info' ], $sendType, $message, $link, $imagesLocale, $videoURLLocale );
					}
					else
					{
						$res = InstagramApi::sendPost( $node_info[ 'info' ], $sendType, $message, $link, $images, $videoURL );
					}
				}
			}
			else
			{
				$res = [
					'status'    => 'error',
					'error_msg' => fsp__( 'Error! An image or video is required to share a post on Instagram. Please add media to the post.' )
				];
			}
		}
		else if ( $driver == 'linkedin' )
		{
			if ( $postType == 'attachment' && strpos( $postInf[ 'post_mime_type' ], 'image' ) !== FALSE )
			{
				$sendType     = 'image';
				$imagesLocale = [ $postInf[ 'guid' ] ];
			}
			else if ( $postType == 'attachment' && strpos( $postInf[ 'post_mime_type' ], 'video' ) !== FALSE )
			{
				$sendType = 'video';
				$videoURL = $postInf[ 'guid' ];
			}

			if ( $postType == 'fs_post' || $postType == 'fs_post_tmp' )
			{
				$thumbnailPath = WPPostThumbnail::getPostThumbnailURL( $post_id, $driver );

				if ( ! empty( $thumbnailPath ) )
				{
					$sendType     = 'image';
					$imagesLocale = [ $thumbnailPath ];
				}
			}

			if ( $sendType != 'image' && $sendType != 'video' )
			{
				$pMethod = (int) Helper::getCustomSetting( 'posting_type', Helper::getOption( 'linkedin_posting_type', '1' ), $feedInf[ 'node_type' ], $feedInf[ 'node_id' ] );

				if ( $pMethod === 1 )
				{
					$thumbnailPath = WPPostThumbnail::getPostThumbnailURL( $post_id, $driver );
					if ( ! empty( $thumbnailPath ) )
					{
						$imagesLocale = [ $thumbnailPath ];
					}
				}
				else if ( $pMethod === 2 )
				{
					$thumbnailPath = WPPostThumbnail::getPostThumbnailURL( $post_id, $driver );

					if ( ! empty( $thumbnailPath ) )
					{
						$sendType     = 'image';
						$imagesLocale = [ $thumbnailPath ];
					}
				}
				else if ( $pMethod === 3 )
				{
					$imagesLocale = WPPostThumbnail::getPostGalleryURL( $postInf, $postType );

					if ( ! empty( $imagesLocale ) )
					{
						$sendType = 'image';
					}
				}
				else if ( $pMethod === 4 )
				{
					$sendType = 'custom_message';
				}
			}

			$res = Linkedin::sendPost( $accoundId, $node_info[ 'info' ], $sendType, $message, $postInf[ 'post_title' ], $link, $imagesLocale, $videoURL, $accessToken, $proxy );
		}
		else if ( $driver == 'vk' )
		{
			if ( Helper::getOption( 'vk_upload_image', '1' ) == 1 && $sendType != 'image' && $sendType != 'video' )
			{
				$thumbnailPath = WPPostThumbnail::getPostThumbnail( $post_id, $driver );

				if ( ! empty( $thumbnailPath ) )
				{
					$sendType     = 'image_link';
					$imagesLocale = [ $thumbnailPath ];
				}

				$pMethod = (int) Helper::getCustomSetting( 'posting_type', Helper::getOption( 'vk_posting_type', '1' ), $feedInf[ 'node_type' ], $feedInf[ 'node_id' ] );

				if ( $pMethod === 2 )
				{
					if ( ! empty( $thumbnailPath ) )
					{
						$sendType = 'image';
					}
				}
				else if ( $pMethod === 3 )
				{
					$images = WPPostThumbnail::getPostGalleryURL( $postInf, $postType );

					if ( ! empty( $images ) )
					{
						$sendType     = 'image';
						$imagesLocale = $images;
					}
				}
				else if ( $pMethod === 4 && ! empty( $message ) )
				{
					$sendType = 'text';
				}
			}

			$res = Vk::sendPost( $nodeProfileId, $sendType, $message, $link, $imagesLocale, $videoURLLocale, $accessToken, $proxy );
		}
		else if ( $driver == 'pinterest' )
		{
			$altText = Helper::getOption( 'alt_text_pinterest', '' );

			if ( ! empty( $altText ) )
			{
				$altText = Helper::replaceAltTextTags( $altText, $postInf );
				$altText = Helper::cutText( strip_tags( $altText ), 497 );
			}

			if ( Helper::getOption( 'pinterest_autocut_title', '1' ) == 1 && mb_strlen( $postTitle ) > 100 )
			{
				$postTitle = mb_substr( $postTitle, 0, 97 ) . '...';
			}

            if ( $sendType != 'image' )
            {
                $thumbURL = WPPostThumbnail::getPostThumbnailURL( $post_id, $driver );

                if ( ! empty( $thumbURL ) )
                {
                    $sendType = 'image';
                    $images   = [ $thumbURL ];
                }
            }

			if ( empty( $options ) ) // App method
			{
				$res = Pinterest::sendPost( $nodeProfileId, $sendType, $postTitle, $message, $longLink, $altText, $images, $accessToken, $proxy );
			}
			else // Cookie method
			{
				$getCookie = DB::fetch( 'account_sessions', [
					'driver'   => 'pinterest',
					'username' => $node_info[ 'username' ]
				] );

				$pinterest = new PinterestCookieApi( $getCookie[ 'cookies' ], $proxy );

				$res = $pinterest->sendPost( $nodeProfileId, $postTitle, $message, $longLink, $images, $altText );
			}
		}
		else if ( $driver == 'reddit' )
		{
			$pMethod = (int) Helper::getCustomSetting( 'posting_type', Helper::getOption( 'reddit_posting_type', '1' ), $feedInf[ 'node_type' ], $feedInf[ 'node_id' ] );

			if ( $pMethod === 3 )
			{
				$thumbnailPath = WPPostThumbnail::getPostThumbnailURL( $post_id, $driver );

				if ( ! empty( $thumbnailPath ) )
				{
					$sendType = 'image';
					$images   = [ $thumbnailPath ];
				}
			}

			$res = Reddit::sendPost( $node_info[ 'info' ], $sendType, $postTitle, $message, $longLink, $images, $videoURL, $accessToken, $proxy, $pMethod );
		}
		else if ( $driver == 'tumblr' )
		{
			$postTitleTumblr = Helper::getOption( 'post_title_tumblr', "" );
			$postTitleTumblr = Helper::replaceTags( $postTitleTumblr, $postInf, $longLink, $shortLink );

			$thumbnail = WPPostThumbnail::getPostThumbnailURL( $post_id, $driver );

			if ( $sendType != 'image' && $sendType != 'video' )
			{
				$pMethod = (int) Helper::getCustomSetting( 'posting_type', Helper::getOption( 'tumblr_posting_type', '1' ), $feedInf[ 'node_type' ], $feedInf[ 'node_id' ] );

				if ( ! empty( $message ) )
				{
					$message = strip_tags( $message, '<b><u><i><a>' );
				}

				if ( $pMethod === 2 )
				{
					if ( ! empty( $thumbnail ) )
					{
						$sendType     = 'image';
						$imagesLocale = [ $thumbnail ];
					}
				}
				else if ( $pMethod === 3 )
				{
					$imagesLocale = WPPostThumbnail::getPostGalleryURL( $postInf, $postType );

					if ( ! empty( $imagesLocale ) )
					{
						$sendType = 'image';
					}
				}
				else if ( ! empty( $message ) )
				{
					if ( $pMethod === 4 )
					{
						$sendType = 'text';
					}
					else if ( $pMethod === 5 )
					{
						$sendType = 'quote';
					}
				}
			}

			$excerpt = '';
			if ( $sendType == 'link' )
			{
				$excerpt = $postType == 'fs_post' || $postType == 'fs_post_tmp' ? $message : strip_tags( get_the_excerpt( $post_id ) );

				$excerpt = preg_replace( '/\n{2,}/', "\n\n", $excerpt );
				$excerpt = preg_replace( '/[\t ]+/', ' ', $excerpt );

				$excerpt = empty( $excerpt ) ? TwitterAutoCut::cut( $message, FALSE ) : $excerpt;
			}

			if ( Helper::getOption( 'tumblr_send_tags', '0' ) == '1' )
			{
				$tags = implode( ',', Helper::getPostTerms( $postInf, NULL, FALSE ) );
			}
			else
			{
				$tags = '';
			}

			if ( empty( $node_info[ 'password' ] ) )
			{
				$res = Tumblr::sendPost( $node_info[ 'info' ], $sendType, $postTitleTumblr, $message, $link, $imagesLocale, $videoURLLocale, $accessToken, $accessTokenSecret, $appId, $proxy, $tags, $thumbnail, $excerpt );
			}
			else
			{
				$tumblr = new TumblrLoginPassMethod( $node_info[ 'email' ], $node_info[ 'password' ], $proxy );
				$res    = $tumblr->sendPost( $node_info[ 'info' ][ 'screen_name' ], $sendType, $postTitleTumblr, $message, $link, $tags, $imagesLocale, $videoURLLocale, $thumbnail, $excerpt );
			}
		}
		else if ( $driver == 'twitter' )
		{
			if ( $sendType != 'image' && $sendType != 'video' )
			{
				$pMethod = (int) Helper::getCustomSetting( 'posting_type', Helper::getOption( 'twitter_posting_type', '1' ), $feedInf[ 'node_type' ], $feedInf[ 'node_id' ] );

				if ( $pMethod === 2 )
				{
					$thumbnailPath = WPPostThumbnail::getPostThumbnail( $post_id, $driver );

					if ( ! empty( $thumbnailPath ) )
					{
						$sendType     = 'image';
						$imagesLocale = [ $thumbnailPath ];
					}
				}
				else if ( $pMethod === 3 )
				{
					$imagesLocale = WPPostThumbnail::getPostGallery( $postInf, $postType );

					if ( ! empty( $imagesLocale ) )
					{
						$sendType = 'image';
					}
				}
				else if ( $pMethod === 4 )
				{
					$sendType = 'custom_message';
				}
			}

			if ( Helper::getOption( 'twitter_auto_cut_tweets', '1' ) == 1 )
			{
				$message = preg_replace( '/\n{2,}/', "\n\n", $message );
				$message = preg_replace( '/[\t ]+/', ' ', $message );

				$message = TwitterAutoCut::cut( $message, $sendType === 'link' );
			}

			if ( empty( $options ) )
			{
				$res = Twitter::sendPost( $appId, $sendType, $message, $link, $imagesLocale, $videoURLLocale, $accessToken, $accessTokenSecret, $proxy );
			}
			else
			{
				$twitterPrivateAPI = new TwitterPrivateAPI( $options, $proxy );
				$res               = $twitterPrivateAPI->sendPost( $sendType, $message, $link, $imagesLocale, $videoURLLocale );
			}
		}
		else if ( $driver == 'ok' )
		{
			if ( $sendType != 'image' && $sendType != 'video' )
			{
				$pMethod = (int) Helper::getCustomSetting( 'posting_type', Helper::getOption( 'ok_posting_type', '1' ), $feedInf[ 'node_type' ], $feedInf[ 'node_id' ] );

				if ( $pMethod === 2 )
				{
					$thumbnailPath = WPPostThumbnail::getPostThumbnail( $post_id, $driver );

					if ( ! empty( $thumbnailPath ) )
					{
						$sendType     = 'image';
						$imagesLocale = [ $thumbnailPath ];
					}
				}
				else if ( $pMethod === 3 )
				{
					$imagesLocale = WPPostThumbnail::getPostGallery( $postInf, $postType );

					if ( ! empty( $imagesLocale ) )
					{
						$sendType = 'image';
					}
				}
				else if ( $pMethod === 4 )
				{
					$sendType = 'custom_message';
				}
			}

			$appInf = DB::fetch( 'apps', [ 'id' => $appId ] );

			$res = OdnoKlassniki::sendPost( $node_info[ 'info' ], $sendType, $message, $link, $imagesLocale, $videoURLLocale, $accessToken, $appInf[ 'app_key' ], $appInf[ 'app_secret' ], $proxy );
		}
		else if ( $driver == 'google_b' )
		{
			if ( empty( $options ) )
			{
				$thumbnail = WPPostThumbnail::getPostThumbnailURL( $post_id, $driver );

				if ( ! empty( $thumbnail ) )
				{
					$images = [ $thumbnail ];
				}

				if ( $sendType != 'image' && $sendType != 'video' )
				{
					$pMethod = (int) Helper::getCustomSetting( 'posting_type', Helper::getOption( 'google_b_posting_type', '1' ), $feedInf[ 'node_type' ], $feedInf[ 'node_id' ] );

					if ( $pMethod === 2 )
					{
						$thumbnail = WPPostThumbnail::getPostThumbnailURL( $post_id, $driver );

						if ( ! empty( $thumbnail ) )
						{
							$sendType = 'image';
							$images   = [ $thumbnail ];
						}
					}
					else if ( $pMethod === 3 )
					{
						$images = WPPostThumbnail::getPostGalleryURL( $postInf, $postType );

						if ( ! empty( $images ) )
						{
							$sendType = 'image';
						}
					}
					else if ( $pMethod === 4 )
					{
						$sendType = 'custom_message';
					}
				}

				$res = GoogleMyBusinessAPI::sendPost( $appId, $nodeProfileId, $sendType, $message, $link, $images, $videoURL, $accessToken, $proxy );
			}
			else // Cookie method
			{
				if ( $sendType == 'video' )
				{
					$res = [
						'status'    => 'error',
						'error_msg' => fsp__( 'Google My Business doesn\'t support sharing videos by the cookie method!' )
					];
				}
				else
				{
					if ( $sendType != 'image' )
					{
						$thumbURL = WPPostThumbnail::getPostThumbnail( $post_id, $driver );

						if ( ! empty( $thumbURL ) )
						{
							$sendType     = 'image';
							$imagesLocale = [ $thumbURL ];
						}
					}

					$imageUrl = is_array( $imagesLocale ) ? reset( $imagesLocale ) : '';

					$options     = json_decode( $options, TRUE );
					$cookie_sid  = isset( $options[ 'sid' ] ) ? $options[ 'sid' ] : '';
					$cookie_hsid = isset( $options[ 'hsid' ] ) ? $options[ 'hsid' ] : '';
					$cookie_ssid = isset( $options[ 'ssid' ] ) ? $options[ 'ssid' ] : '';

					$linkButton = Helper::getOption( 'google_b_button_type', 'LEARN_MORE' );

					$fs_google_b_share_as_product = ( $postType == 'product' || $postType == 'product_variation' ) && Helper::getOption( 'google_b_share_as_product', '0' ) && function_exists( 'wc_get_product' );

					$productName     = $fs_google_b_share_as_product ? $postTitle : NULL;
					$productPrice    = $fs_google_b_share_as_product ? Helper::getProductPrice( $postInf, 'price' ) : NULL;
					$productCurrency = $fs_google_b_share_as_product ? get_woocommerce_currency() : NULL;
					$productCategory = NULL;

					if ( $fs_google_b_share_as_product )
					{
						$productCategory = wp_get_post_terms( $post_id, 'product_cat' );

						if ( isset( $productCategory[ 0 ] ) )
						{
							$productCategory = $productCategory[ 0 ]->name;
						}
						else
						{
							$productCategory = fsp__( 'Product' );
						}
					}

					$google = new GoogleMyBusiness( $cookie_sid, $cookie_hsid, $cookie_ssid, $proxy );

					$res = $google->sendPost( $nodeProfileId, $message, $link, $linkButton, $imageUrl, $productName, $productPrice, $productCurrency, $productCategory );
				}
			}
		}
		else if ( $driver == 'blogger' )
		{
			$page_as_page       = Helper::getCustomSetting( 'posting_type', Helper::getOption( 'blogger_posting_type', '0' ), $feedInf[ 'node_type' ], $feedInf[ 'node_id' ] ) == '1';
			$blogger_post_type  = ( $postType === 'page' && $page_as_page ) ? 'page' : 'post';
			$isDraft            = Helper::getOption( 'blogger_post_status', 'publish' ) !== 'publish';
			$post_title_blogger = Helper::getOption( 'post_title_blogger', "{title}" );
			$post_title_blogger = Helper::replaceTags( $post_title_blogger, $postInf, $longLink, $shortLink );
			$labels             = [];

			if ( Helper::getOption( 'blogger_post_with_terms', 1 ) == 1 )
			{
				$labels = Helper::getPostTerms( $postInf, NULL, FALSE, TRUE, ',' );
			}

			$labels_cut = [];

			foreach ( $labels as $label )
			{
				$labels_cut_next   = $labels_cut;
				$labels_cut_next[] = $label;

				if ( strlen( implode( ',', $labels_cut_next ) ) > 200 )
				{
					break;
				}

				$labels_cut[] = $label;
			}

			if ( $sendType === 'image' )
			{
				$message .= '<img src="' . reset( $images ) . '" width="100%" height="auto">';
			}
			else if ( $sendType === 'video' )
			{
				$message .= '<video width="100%" height="auto" controls><source src="' . $videoURL . '"></video>';
			}
			else
			{
				$message .= "<style>[class^='wp-image']{width:100%;height:auto;}</style>";
			}

			$res = Blogger::sendPost( $post_title_blogger, $message, $labels_cut, $blogger_post_type, $isDraft, $nodeProfileId, $accoundId, $accessToken, $proxy );
		}
		else if ( $driver == 'telegram' )
		{
			$fs_telegram_type_of_sharing = (int) Helper::getCustomSetting( 'posting_type', Helper::getOption( 'telegram_type_of_sharing', '1' ), $feedInf[ 'node_type' ], $feedInf[ 'node_id' ] );

			if ( ( $fs_telegram_type_of_sharing === 1 || $fs_telegram_type_of_sharing === 4 ) && ! empty( $link ) )
			{
				$message .= "\n" . $link;
			}

			if ( ( $fs_telegram_type_of_sharing === 3 || $fs_telegram_type_of_sharing === 4 ) && $sendType != 'image' && $sendType != 'video' )
			{
				$thumbURL = WPPostThumbnail::getPostThumbnailURL( $post_id, $driver );

				if ( ! empty( $thumbURL ) )
				{
					$sendType = 'image';
					$images   = [ $thumbURL ];
				}
			}

			if ( $sendType == 'image' )
			{
				$mediaURL = reset( $images );
			}
			else if ( $sendType == 'video' )
			{
				$mediaURL = $videoURL;
			}
			else
			{
				$mediaURL = '';
			}

			$tg = new Telegram( $options, $proxy );

			$res = $tg->sendPost( $nodeProfileId, $message, $sendType, $mediaURL );
		}
		else if ( $driver == 'medium' )
		{
			if ( Helper::getOption( 'medium_send_tags', '0' ) == '1' )
			{
				$tags = Helper::getPostTerms( $postInf, NULL, FALSE );
			}
			else
			{
				$tags = [];
			}

			$res = Medium::sendPost( $node_info[ 'info' ], $postTitle, $message, $accessToken, $proxy, $tags );
		}
		else if ( $driver == 'wordpress' )
		{
			$thumbnailPath = WPPostThumbnail::getPostThumbnail( $post_id, $driver );

			$post_title_wordpress   = Helper::getOption( 'post_title_wordpress', "{title}" );
			$post_excerpt_wordpress = Helper::getOption( 'post_excerpt_wordpress', "{excerpt}" );

			$post_title_wordpress   = Helper::replaceTags( $post_title_wordpress, $postInf, $longLink, $shortLink );
			$post_excerpt_wordpress = Helper::replaceTags( $post_excerpt_wordpress, $postInf, $longLink, $shortLink );

			$node_info[ 'password' ] = substr( $node_info[ 'password' ], 0, 9 ) === '(-F-S-P-)' ? explode( '(-F-S-P-)', base64_decode( str_rot13( substr( $node_info[ 'password' ], 9 ) ) ) )[ 0 ] : $node_info[ 'password' ];

			$wordpress = new Wordpress( $options, $node_info[ 'username' ], $node_info[ 'password' ], $proxy );

			$res = $wordpress->sendPost( $postInf, $postType, $post_title_wordpress, $post_excerpt_wordpress, $message, $feedInf, $thumbnailPath );
		}
		else
		{
			$res = [
				'status'    => 'error',
				'error_msg' => fsp__( 'Driver error! Driver type: %s', [ htmlspecialchars( $driver ) ] )
			];
		}

		WPPostThumbnail::clearCache();

		if ( ! Helper::getOption( 'keep_logs', '1' ) )
		{
			DB::DB()->delete( DB::table( 'feeds' ), [
				'id' => $feedId
			] );
		}
		else
		{
			$updateDate = [
				'is_sended'       => 1,
				'send_time'       => Date::dateTimeSQL(),
				'status'          => $res[ 'status' ],
				'error_msg'       => isset( $res[ 'error_msg' ] ) ? Helper::cutText( $res[ 'error_msg' ], 250 ) : '',
				'driver_post_id'  => isset( $res[ 'id' ] ) ? $res[ 'id' ] : NULL,
				'driver_post_id2' => isset( $res[ 'id2' ] ) ? $res[ 'id2' ] : NULL
			];

            if( $driver === 'blogger' )
            {
                $updateDate[ 'feed_type' ] = isset( $res[ 'feed_type' ] ) ? $res[ 'feed_type' ] : NULL;
            }

			DB::DB()->update( DB::table( 'feeds' ), $updateDate, [ 'id' => $feedId ] );
		}

		if ( isset( $res[ 'id' ] ) )
		{
			if ( $driver == 'google_b' )
			{
				if ( ! empty( $options ) )
				{
					$username = $nodeProfileId;
				}
				else
				{
					$username = '';
				}
			}
			else if ( $driver == 'blogger' )
			{
				$username = $res[ 'id2' ];
			}
			else if ( $driver == 'wordpress' )
			{
				$username = $options;
			}
			else
			{
				$username = isset( $node_info[ 'info' ][ 'screen_name' ] ) ? $node_info[ 'info' ][ 'screen_name' ] : $node_info[ 'username' ];
			}

			if ( ! isset( $res[ 'post_link' ] ) )
			{
				$res[ 'post_link' ] = Helper::postLink( $res[ 'id' ], $driver . ( $driver == 'instagram' ? $feedInf[ 'feed_type' ] : '' ), $username );
			}
		}

		return $res;
	}
}
