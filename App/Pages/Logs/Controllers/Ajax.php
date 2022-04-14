<?php

namespace FSPoster\App\Pages\Logs\Controllers;

use FSPoster\App\Providers\DB;
use FSPoster\App\Providers\Date;
use FSPoster\App\Libraries\vk\Vk;
use FSPoster\App\Providers\Helper;
use FSPoster\App\Providers\Request;
use FSPoster\App\Libraries\fb\Facebook;
use FSPoster\App\Libraries\plurk\Plurk;
use FSPoster\App\Libraries\reddit\Reddit;
use FSPoster\App\Libraries\twitter\Twitter;
use FSPoster\App\Libraries\ok\OdnoKlassniki;
use FSPoster\App\Libraries\linkedin\Linkedin;
use FSPoster\App\Libraries\pinterest\Pinterest;
use FSPoster\App\Libraries\fb\FacebookCookieApi;
use FSPoster\App\Libraries\instagram\InstagramApi;
use FSPoster\App\Libraries\twitter\TwitterPrivateAPI;

trait Ajax
{
	public function report1_data ()
	{
		$type    = Request::post( 'type', '', 'string' );
		$user_id = get_current_user_id();

		if ( ! in_array( $type, [
			'dayly',
			'monthly',
			'yearly'
		] ) )
		{
			exit();
		}

		$query = [
			'dayly'   => "SELECT CAST(send_time AS DATE) AS date , COUNT(0) AS c FROM " . DB::table( 'feeds' ) . " tb1 WHERE tb1.blog_id='" . Helper::getBlogId() . "' AND ( (node_type='account' AND (SELECT COUNT(0) FROM " . DB::table( 'accounts' ) . " tb2 WHERE tb2.blog_id='" . Helper::getBlogId() . "' AND tb2.id=tb1.node_id AND (tb2.user_id='$user_id' OR tb2.is_public=1))>0) OR (node_type<>'account' AND (SELECT COUNT(0) FROM " . DB::table( 'account_nodes' ) . " tb2 WHERE tb2.id=tb1.node_id AND (tb2.user_id='$user_id')>0 OR tb2.is_public=1)) ) AND is_sended=1 GROUP BY CAST(send_time AS DATE)",
			'monthly' => "SELECT CONCAT(YEAR(send_time), '-', MONTH(send_time) , '-01') AS date , COUNT(0) AS c FROM " . DB::table( 'feeds' ) . " tb1 WHERE tb1.blog_id='" . Helper::getBlogId() . "' AND ( (node_type='account' AND (SELECT COUNT(0) FROM " . DB::table( 'accounts' ) . " tb2 WHERE tb2.blog_id='" . Helper::getBlogId() . "' AND tb2.id=tb1.node_id AND (tb2.user_id='$user_id' OR tb2.is_public=1))>0) OR (node_type<>'account' AND (SELECT COUNT(0) FROM " . DB::table( 'account_nodes' ) . " tb2 WHERE tb2.id=tb1.node_id AND (tb2.user_id='$user_id')>0 OR tb2.is_public=1)) ) AND is_sended=1 AND send_time > ADDDATE(now(),INTERVAL -1 YEAR) GROUP BY YEAR(send_time), MONTH(send_time)",
			'yearly'  => "SELECT CONCAT(YEAR(send_time), '-01-01') AS date , COUNT(0) AS c FROM " . DB::table( 'feeds' ) . " tb1 WHERE tb1.blog_id='" . Helper::getBlogId() . "' AND ( (node_type='account' AND (SELECT COUNT(0) FROM " . DB::table( 'accounts' ) . " tb2 WHERE tb2.blog_id='" . Helper::getBlogId() . "' AND tb2.id=tb1.node_id AND (tb2.user_id='$user_id' OR tb2.is_public=1))>0) OR (node_type<>'account' AND (SELECT COUNT(0) FROM " . DB::table( 'account_nodes' ) . " tb2 WHERE tb2.id=tb1.node_id AND (tb2.user_id='$user_id')>0 OR tb2.is_public=1)) ) AND is_sended=1 GROUP BY YEAR(send_time)"
		];

		$dateFormat = [
			'dayly'   => 'Y-m-d',
			'monthly' => 'Y M',
			'yearly'  => 'Y',
		];

		$dataSQL = DB::DB()->get_results( $query[ $type ], ARRAY_A );

		$labels = [];
		$datas  = [];
		foreach ( $dataSQL as $dInf )
		{
			$datas[]  = $dInf[ 'c' ];
			$labels[] = Date::format( $dateFormat[ $type ], $dInf[ 'date' ] );
		}

		Helper::response( TRUE, [
			'data'   => $datas,
			'labels' => $labels
		] );
	}

	public function report2_data ()
	{
		$type    = Request::post( 'type', '', 'string' );
		$user_id = get_current_user_id();

		if ( ! in_array( $type, [
			'dayly',
			'monthly',
			'yearly'
		] ) )
		{
			exit();
		}

		$query = [
			'dayly'   => "SELECT CAST(send_time AS DATE) AS date , SUM(visit_count) AS c FROM " . DB::table( 'feeds' ) . " tb1 WHERE tb1.blog_id='" . Helper::getBlogId() . "' AND ( (node_type='account' AND (SELECT COUNT(0) FROM " . DB::table( 'accounts' ) . " tb2 WHERE tb2.blog_id='" . Helper::getBlogId() . "' AND tb2.id=tb1.node_id AND (tb2.user_id='$user_id' OR tb2.is_public=1))>0) OR (node_type<>'account' AND (SELECT COUNT(0) FROM " . DB::table( 'account_nodes' ) . " tb2 WHERE tb2.id=tb1.node_id AND (tb2.user_id='$user_id')>0 OR tb2.is_public=1)) ) AND is_sended=1 GROUP BY CAST(send_time AS DATE)",
			'monthly' => "SELECT CONCAT(YEAR(send_time), '-', MONTH(send_time) , '-01') AS date , SUM(visit_count) AS c FROM " . DB::table( 'feeds' ) . " tb1 WHERE tb1.blog_id='" . Helper::getBlogId() . "' AND ( (node_type='account' AND (SELECT COUNT(0) FROM " . DB::table( 'accounts' ) . " tb2 WHERE tb2.blog_id='" . Helper::getBlogId() . "' AND tb2.id=tb1.node_id AND (tb2.user_id='$user_id' OR tb2.is_public=1))>0) OR (node_type<>'account' AND (SELECT COUNT(0) FROM " . DB::table( 'account_nodes' ) . " tb2 WHERE tb2.id=tb1.node_id AND (tb2.user_id='$user_id')>0 OR tb2.is_public=1)) ) AND send_time > ADDDATE(now(),INTERVAL -1 YEAR) AND is_sended=1 GROUP BY YEAR(send_time), MONTH(send_time)",
			'yearly'  => "SELECT CONCAT(YEAR(send_time), '-01-01') AS date , SUM(visit_count) AS c FROM " . DB::table( 'feeds' ) . " tb1 WHERE tb1.blog_id='" . Helper::getBlogId() . "' AND ( (node_type='account' AND (SELECT COUNT(0) FROM " . DB::table( 'accounts' ) . " tb2 WHERE tb2.blog_id='" . Helper::getBlogId() . "' AND tb2.id=tb1.node_id AND (tb2.user_id='$user_id' OR tb2.is_public=1))>0) OR (node_type<>'account' AND (SELECT COUNT(0) FROM " . DB::table( 'account_nodes' ) . " tb2 WHERE tb2.id=tb1.node_id AND (tb2.user_id='$user_id')>0 OR tb2.is_public=1)) ) AND is_sended=1 GROUP BY YEAR(send_time)"
		];

		$dateFormat = [
			'dayly'   => 'Y-m-d',
			'monthly' => 'Y M',
			'yearly'  => 'Y',
		];

		$dataSQL = DB::DB()->get_results( $query[ $type ], ARRAY_A );

		$labels = [];
		$datas  = [];
		foreach ( $dataSQL as $dInf )
		{
			$datas[]  = $dInf[ 'c' ];
			$labels[] = Date::format( $dateFormat[ $type ], $dInf[ 'date' ] );
		}

		Helper::response( TRUE, [
			'data'   => $datas,
			'labels' => $labels
		] );
	}

	public function report3_data ()
	{
		$page           = Request::post( 'page', '1', 'num' );
		$schedule_id    = Request::post( 'schedule_id', '0', 'num' );
		$rows_count     = Request::post( 'rows_count', '4', 'int', [ '4', '8', '15' ] );
		$filter_results = Request::post( 'filter_results', 'all', 'string', [ 'all', 'error', 'ok' ] );
		$sn             = Request::post( 'sn', 'all', 'string', [ 'all', 'fb', 'twitter', 'instagram', 'linkedin', 'vk', 'pinterest', 'reddit', 'tumblr', 'ok', 'plurk', 'google_b', 'blogger', 'telegram', 'medium', 'wordpress' ] );
		$show_logs_of   = Request::post( 'show_logs_of', 'own', 'string', [ 'all', 'own' ] );

        Helper::setOption('show_logs_of', $show_logs_of );

        $feedId = Request::post( 'feed_id', 0, 'num' );

		$page = empty( $feedId ) ? $page : 1;

		if ( ! ( $page > 0 ) )
		{
			Helper::response( FALSE );
		}

		$query_add = '';

		if ( ! empty( $feedId ) )
		{
			$query_add = 'AND id = ' . $feedId;
		}

		if ( $schedule_id > 0 )
		{
			$query_add = ' AND schedule_id=\'' . (int) $schedule_id . '\'';
		}

		if ( $filter_results === 'error' || $filter_results === 'ok' )
		{
			$query_add .= ' AND status = \'' . $filter_results . '\'';
		}

        if ( !empty( $sn ) && $sn !== 'all'  )
        {
            $query_add .= ' AND driver = \'' . $sn . '\'';
        }

		$userId   = get_current_user_id();

        if( $show_logs_of === 'all' && current_user_can('administrator')  ){
            $user_sort = '';
        }
        else
        {
            $user_sort = ' AND is_sended=1 AND ( user_id=\'' . $userId . '\' OR ( user_id IS NULL AND ( ( node_type=\'account\' AND ( (SELECT COUNT(0) FROM ' . DB::table( 'accounts' ) . ' tb2 WHERE tb2.id=tb1.node_id AND (tb2.user_id=\'' . $userId . '\' OR tb2.is_public=1))>0 OR node_id NOT IN (SELECT id FROM ' . DB::table( 'accounts' ) . ') ) ) OR (node_type<>\'account\' AND ( (SELECT COUNT(0) FROM ' . DB::table( 'account_nodes' ) . ' tb2 WHERE tb2.id=tb1.node_id AND (tb2.user_id=\'' . $userId . '\')>0 OR tb2.is_public=1) OR node_id NOT IN (SELECT id FROM ' . DB::table( 'account_nodes' ) . ') ) ) ) ) )';
        }

		$allCount = DB::DB()->get_row( "SELECT COUNT(0) AS c FROM " . DB::table( 'feeds' ) . ' tb1 WHERE blog_id=\'' . Helper::getBlogId() . '\'' . $user_sort . $query_add, ARRAY_A );
		$pages    = ceil( $allCount[ 'c' ] / $rows_count );

		Helper::setOption( 'logs_rows_count_' . get_current_user_id(), $rows_count );

		$offset     = ( $page - 1 ) * $rows_count;
		$getData    = DB::DB()->get_results( 'SELECT * FROM ' . DB::table( 'feeds' ) . ' tb1 WHERE blog_id=\'' . Helper::getBlogId() . '\'' . $user_sort . $query_add . " ORDER BY send_time DESC LIMIT $offset , $rows_count", ARRAY_A );
		$resultData = [];

		foreach ( $getData as $feedInf )
		{
			$postInf        = get_post( $feedInf[ 'post_id' ] );
			$node_infoTable = $feedInf[ 'node_type' ] === 'account' ? 'accounts' : 'account_nodes';
			$node_info      = DB::fetch( $node_infoTable, $feedInf[ 'node_id' ] );

			if ( $node_info && $feedInf[ 'node_type' ] === 'account' )
			{
				$node_info[ 'node_type' ] = 'account';
			}

			if ( $feedInf[ 'driver' ] === 'wordpress' )
			{
				$feedInf[ 'node_type' ] = 'website';
			}

			$insights = [
				'like'     => 0,
				'details'  => '',
				'comments' => 0,
				'shares'   => 0
			];

			if ( ! empty( $feedInf[ 'driver_post_id' ] ) )
			{
				$node_info2 = Helper::getAccessToken( $feedInf[ 'node_type' ], $feedInf[ 'node_id' ] );

				$proxy             = $node_info2[ 'info' ][ 'proxy' ];
				$accessToken       = $node_info2[ 'access_token' ];
				$accessTokenSecret = $node_info2[ 'access_token_secret' ];
				$options           = $node_info2[ 'options' ];
				$accountId         = $node_info2[ 'account_id' ];
				$appId             = $node_info2[ 'app_id' ];

				$appInf = DB::fetch( 'apps', $appId );

				if ( $feedInf[ 'driver' ] === 'fb' )
				{
					if ( empty( $options ) )
					{
                        $fb       = new Facebook( $appInf, $accessToken, $proxy );
						$insights = $fb->getStats( $feedInf[ 'driver_post_id' ] );
					}
					else
					{
						$fbDriver = new FacebookCookieApi( $accountId, $options, $proxy );
						$insights = $fbDriver->getStats( $feedInf[ 'driver_post_id' ] );
					}
				}
				else if ( $feedInf[ 'driver' ] === 'plurk' )
				{
					$plurk    = new Plurk( $appInf[ 'app_key' ], $appInf[ 'app_secret' ], $proxy );
					$insights = $plurk->getStats( $accessToken, $accessTokenSecret, $feedInf[ 'driver_post_id' ] );
				}
				else if ( $feedInf[ 'driver' ] === 'vk' )
				{
					$insights = Vk::getStats( $feedInf[ 'driver_post_id' ], $accessToken, $proxy );
				}
				else if ( $feedInf[ 'driver' ] === 'twitter' )
				{
					if ( empty( $options ) )
					{
						$insights = Twitter::getStats( $feedInf[ 'driver_post_id' ], $accessToken, $node_info2[ 'access_token_secret' ], $appId, $proxy );
					}
					else
					{
						$tw       = new TwitterPrivateAPI( $options, $proxy );
						$insights = $tw->getStats( $feedInf[ 'driver_post_id' ] );
					}
				}
				else if ( $feedInf[ 'driver' ] === 'instagram' )
				{
					$insights = InstagramApi::getStats( $feedInf[ 'driver_post_id2' ], $feedInf[ 'driver_post_id' ], $node_info2[ 'info' ] );
				}
				else if ( $feedInf[ 'driver' ] === 'linkedin' )
				{
					$insights = Linkedin::getStats( NULL, $proxy );
				}
				else if ( $feedInf[ 'driver' ] === 'pinterest' )
				{
					$insights = Pinterest::getStats( $feedInf[ 'driver_post_id' ], $accessToken, $proxy );
				}
				else if ( $feedInf[ 'driver' ] === 'reddit' )
				{
					$insights = Reddit::getStats( $feedInf[ 'driver_post_id' ], $accessToken, $proxy );
				}
				else if ( $feedInf[ 'driver' ] === 'ok' )
				{
					$post_id2 = explode( '/', $feedInf[ 'driver_post_id' ] );
					$post_id2 = end( $post_id2 );
					$insights = OdnoKlassniki::getStats( $post_id2, $accessToken, $appInf[ 'app_key' ], $appInf[ 'app_secret' ], $proxy );
				}
			}

			if ( $feedInf[ 'driver' ] === 'fb' )
			{
				$icon = 'fab fa-facebook';
			}
			else if ( $feedInf[ 'driver' ] === 'vk' )
			{
				$icon = 'fab fa-vk';
			}
			else if ( $feedInf[ 'driver' ] === 'twitter' )
			{
				$icon = 'fab fa-twitter';
			}
			else if ( $feedInf[ 'driver' ] === 'instagram' )
			{
				$icon = 'fab fa-instagram';
			}
			else if ( $feedInf[ 'driver' ] === 'linkedin' )
			{
				$icon = 'fab fa-linkedin';
			}
			else if ( $feedInf[ 'driver' ] === 'pinterest' )
			{
				$icon = 'fab fa-pinterest';
			}
			else if ( $feedInf[ 'driver' ] === 'reddit' )
			{
				$icon = 'fab fa-reddit';
			}
			else if ( $feedInf[ 'driver' ] === 'ok' )
			{
				$icon = 'fab fa-odnoklassniki';
			}
			else if ( $feedInf[ 'driver' ] === 'tumblr' )
			{
				$icon = 'fab fa-tumblr';
			}
			else if ( $feedInf[ 'driver' ] === 'wordpress' )
			{
				$icon = 'fab fa-wordpress';
			}
			else if ( $feedInf[ 'driver' ] === 'google_b' )
			{
				$icon = 'fab fa-google';
			}
			else if ( $feedInf[ 'driver' ] === 'blogger' )
			{
				$icon = 'fab fa-blogger';
			}
			else if ( $feedInf[ 'driver' ] === 'telegram' )
			{
				$icon = 'fab fa-telegram';
			}
			else if ( $feedInf[ 'driver' ] === 'medium' )
			{
				$icon = 'fab fa-medium';
			}
			else if ( $feedInf[ 'driver' ] === 'plurk' )
			{
				$icon = 'fas fa-parking';
			}

			if ( $feedInf[ 'driver' ] === 'google_b' )
			{
				$username = $node_info[ 'node_id' ];
			}
			else if ( $feedInf[ 'driver' ] === 'blogger' )
			{
				$username = $feedInf[ 'driver_post_id2' ];
			}
			else if ( $feedInf[ 'driver' ] === 'wordpress' )
			{
				$username = isset( $node_info2[ 'options' ] ) ? $node_info2[ 'options' ] : '';
			}
			else
			{
				$username = isset( $node_info[ 'screen_name' ] ) ? $node_info[ 'screen_name' ] : ( isset( $node_info[ 'username' ] ) ? $node_info[ 'username' ] : '-' );
			}

			$hide_stats = in_array( $feedInf[ 'driver' ], [
				'linkedin',
				'reddit',
				'tumblr',
				'google_b',
				'telegram',
				'medium',
				'wordpress',
				'blogger'
			] );

			$hide_stats = $hide_stats || ( $feedInf[ 'driver' ] == 'instagram' && ! empty( $node_info[ 'account_id' ] ) ) || ( $feedInf[ 'driver' ] == 'pinterest' && empty( $options ) );

			$sharedFrom = $feedInf[ 'shared_from' ];

			if ( ! empty( $sharedFrom ) )
			{
				$sharedFromArray = [
					'manual_share'         => fsp__( 'Shared Manually' ),
					'direct_share'         => fsp__( 'Shared by the Direct Share' ),
					'schedule'             => fsp__( 'Shared by the Schedule Module' ),
					'auto_post'            => fsp__( 'Auto-posted' ),
					'manual_share_retried' => fsp__( 'Shared Manually (Retried)' ),
					'direct_share_retried' => fsp__( 'Shared by the Direct Share (Retried)' ),
					'schedule_retried'     => fsp__( 'Shared by the Schedule Module (Retried)' ),
					'auto_post_retried'    => fsp__( 'Auto-posted (Retried)' ),
				];

				if ( array_key_exists( $sharedFrom, $sharedFromArray ) )
				{
					$sharedFrom = $sharedFromArray[ $sharedFrom ];
				}
			}

			$resultData[] = [
				'id'           => $feedInf[ 'id' ],
				'name'         => $node_info ? htmlspecialchars( $node_info[ 'name' ] ) : fsp__( 'Account deleted' ),
				'post_id'      => htmlspecialchars( $feedInf[ 'driver_post_id' ] ),
				'post_title'   => htmlspecialchars( isset( $postInf->post_title ) ? $postInf->post_title : 'Deleted' ),
				'cover'        => Helper::profilePic( $node_info ),
				'profile_link' => Helper::profileLink( $node_info ),
				'is_sended'    => $feedInf[ 'is_sended' ],
				'post_link'    => Helper::postLink( $feedInf[ 'driver_post_id' ], $feedInf[ 'driver' ] . ( $feedInf[ 'driver' ] === 'instagram' ? $feedInf[ 'feed_type' ] : '' ), $username ),
				'status'       => $feedInf[ 'status' ],
				'error_msg'    => $feedInf[ 'error_msg' ],
				'hits'         => $feedInf[ 'visit_count' ],
				'driver'       => $feedInf[ 'driver' ],
				'icon'         => $icon,
				'insights'     => $insights,
				'node_type'    => $feedInf[ 'node_type' ],
				'feed_type'    => ucfirst( (string) $feedInf[ 'feed_type' ] ),
				'date'         => Date::dateTimeSQL( $feedInf[ 'send_time' ] ),
				'wp_post_id'   => $feedInf[ 'post_id' ],
				'hide_stats'   => $hide_stats,

				'shared_from'   => $sharedFrom,
				'has_post_link' => ! ( $postInf->post_type === 'fs_post_tmp' ),
				'is_deleted'    => ! $node_info

			];
		}

		if ( ! ( $pages > 0 ) )
		{
			$pages = 1;
		}

		$show_pages = [ 1, $page, $pages ];

		if ( ( $page - 3 ) >= 1 )
		{
			for ( $i = $page; $i >= $page - 3; $i-- )
			{
				$show_pages[] = $i;
			}
		}
		else if ( ( $page - 2 ) >= 1 )
		{
			for ( $i = $page; $i >= $page - 2; $i-- )
			{
				$show_pages[] = $i;
			}
		}
		else if ( ( $page - 1 ) >= 1 )
		{
			for ( $i = $page; $i >= $page - 1; $i-- )
			{
				$show_pages[] = $i;
			}
		}

		if ( ( $page + 3 ) <= $pages )
		{
			for ( $i = $page; $i <= $page + 3; $i++ )
			{
				$show_pages[] = $i;
			}
		}
		else if ( ( $page + 2 ) <= $pages )
		{
			for ( $i = $page; $i <= $page + 2; $i++ )
			{
				$show_pages[] = $i;
			}
		}
		else if ( ( $page + 1 ) <= $pages )
		{
			for ( $i = $page; $i <= $page + 1; $i++ )
			{
				$show_pages[] = $i;
			}
		}

		$show_pages = array_unique( $show_pages );
		sort( $show_pages );

		Helper::response( TRUE, [
			'data'  => $resultData,
			'pages' => [
				'page_number'  => $show_pages,
				'current_page' => $page,
				'count'        => $pages
			],
			'total' => $allCount[ 'c' ] ? $allCount[ 'c' ] : 0
		] );
	}

	public function fs_clear_logs ()
	{
		$schedule_id      = Request::post( 'schedule_id', '0', 'num' );
		$selectedAccounts = Request::post( 'selected_accounts', [], 'array' );
		$type             = Request::post( 'type', 'all', 'string', [
			'all',
			'only_successful_logs',
			'only_errors',
			'only_selected_logs'
		] );

		$query_add = '';

		if ( $schedule_id > 0 )
		{
			$query_add = ' AND schedule_id=\'' . (int) $schedule_id . '\'';
		}

		$userId = get_current_user_id();

		$deleteQuery = "DELETE FROM " . DB::table( 'feeds' ) . ' WHERE blog_id=\'' . Helper::getBlogId() . '\' AND (is_sended=1 OR (send_time+INTERVAL 1 DAY)<NOW()) AND ( user_id=\'' . $userId . '\' OR ( user_id IS NULL AND ( (node_type=\'account\' AND (SELECT COUNT(0) FROM ' . DB::table( 'accounts' ) . ' tb2 WHERE tb2.blog_id=\'' . Helper::getBlogId() . '\' AND tb2.id=' . DB::table( 'feeds' ) . '.node_id AND (tb2.user_id=\'' . $userId . '\' OR tb2.is_public=1))>0) OR (node_type<>\'account\' AND (SELECT COUNT(0) FROM ' . DB::table( 'account_nodes' ) . ' tb2 WHERE tb2.id=' . DB::table( 'feeds' ) . '.node_id AND (tb2.user_id=\'' . $userId . '\')>0 OR tb2.is_public=1)) )))';

		if ( $type === 'all' )
		{
			DB::DB()->query( $deleteQuery . ' OR user_id IS NULL OR user_id=0' );
		}
		else if ( $type === 'only_successful_logs' )
		{
			DB::DB()->query( $deleteQuery . ' AND status="ok" ' );
		}
		else if ( $type === 'only_errors' )
		{
			DB::DB()->query( $deleteQuery . ' AND status="error" ' );
		}
		else if ( $type === 'only_selected_logs' )
		{
			if ( ! empty( $selectedAccounts ) )
			{
				$set = implode( ', ', $selectedAccounts );

				DB::DB()->query( $deleteQuery . ' AND id IN(' . $set . ') ' );
			}
			else
			{
				Helper::response( FALSE, fsp__( 'No logs are selected!' ) );
			}
		}
		else
		{
			Helper::response( FALSE );
		}

		Helper::response( TRUE );
	}

	public function export_logs_to_csv ()
	{
        $schedule_id    = Request::post( 'schedule_id', '0', 'num' );
        $filter_results = Request::post( 'filter_results', 'all', 'string', [ 'all', 'error', 'ok' ] );
        $sn             = Request::post( 'sn', 'all', 'string', [ 'all', 'fb', 'twitter', 'instagram', 'linkedin', 'vk', 'pinterest', 'reddit', 'tumblr', 'ok', 'plurk', 'google_b', 'blogger', 'telegram', 'medium', 'wordpress' ] );
        $show_logs_of   = Helper::getOption('show_logs_of', 'own' );

        $query_add = '';

        if ( $schedule_id > 0 )
        {
            $query_add = ' AND schedule_id=\'' . (int) $schedule_id . '\'';
        }

        if ( $filter_results === 'error' || $filter_results === 'ok' )
        {
            $query_add .= ' AND status = \'' . $filter_results . '\'';
        }

        if ( !empty( $sn ) && $sn !== 'all'  )
        {
            $query_add .= ' AND driver = \'' . $sn . '\'';
        }

        $userId   = get_current_user_id();

        if( $show_logs_of === 'all' && current_user_can('administrator')  ){
            $user_sort = '';
        }
        else
        {
            $user_sort = ' AND is_sended=1 AND ( user_id=\'' . $userId . '\' OR ( user_id IS NULL AND ( ( node_type=\'account\' AND ( (SELECT COUNT(0) FROM ' . DB::table( 'accounts' ) . ' tb2 WHERE tb2.id=tb1.node_id AND (tb2.user_id=\'' . $userId . '\' OR tb2.is_public=1))>0 OR node_id NOT IN (SELECT id FROM ' . DB::table( 'accounts' ) . ') ) ) OR (node_type<>\'account\' AND ( (SELECT COUNT(0) FROM ' . DB::table( 'account_nodes' ) . ' tb2 WHERE tb2.id=tb1.node_id AND (tb2.user_id=\'' . $userId . '\')>0 OR tb2.is_public=1) OR node_id NOT IN (SELECT id FROM ' . DB::table( 'account_nodes' ) . ') ) ) ) ) )';
        }

        $getData    = DB::DB()->get_results( 'SELECT * FROM ' . DB::table( 'feeds' ) . ' tb1 WHERE blog_id=\'' . Helper::getBlogId() . '\'' . $user_sort . $query_add . " ORDER BY send_time DESC", ARRAY_A );


        $f         = fopen( 'php://memory', 'w' );
		$delimiter = ',';
		$filename  = 'FS-Poster_logs_' . date( 'Y-m-d' ) . '.csv';
		$fields    = [
			//fsp__( 'ID' ),
			fsp__( 'Account Name' ),
			fsp__( 'Account Link' ),
			fsp__( 'Date' ),
			fsp__( 'Post Link' ),
			fsp__( 'Publication Link' ),
			fsp__( 'Social Network' ),
			fsp__( 'Share Method' ),
			fsp__( 'Status' ),
			fsp__( 'Error Message' )
		];

		fputcsv( $f, $fields, $delimiter );

		$networks = [
			'fb'        => 'Facebook',
			'twitter'   => 'Twitter',
			'instagram' => 'Instagram',
			'linkedin'  => 'LinkedIn',
			'vk'        => 'VKontakte',
			'pinterest' => 'Pinterest',
			'reddit'    => 'Reddit',
			'tumblr'    => 'Tumblr',
			'ok'        => 'Odnoklassniki',
			'google_b'  => 'Google My Business',
			'telegram'  => 'Telegram',
			'medium'    => 'Medium',
			'wordpress' => 'WordPress',
			'plurk'     => 'Plurk'
		];

		foreach ( $getData as $feedInf )
		{
			$postType      = get_post_type( $feedInf[ 'post_id' ] );
			$nodeInfoTable = $feedInf[ 'node_type' ] === 'account' ? 'accounts' : 'account_nodes';
			$nodeInfo      = DB::fetch( $nodeInfoTable, $feedInf[ 'node_id' ] );
			$nodeInfo2     = Helper::getAccessToken( $feedInf[ 'node_type' ], $feedInf[ 'node_id' ] );

			if ( $feedInf[ 'driver' ] === 'google_b' )
			{
				$username = $nodeInfo[ 'node_id' ];
			}
			else if ( $feedInf[ 'driver' ] === 'blogger' )
			{
				$username = $feedInf[ 'driver_post_id2' ];
			}
			else if ( $feedInf[ 'driver' ] === 'wordpress' )
			{
				$username = isset( $nodeInfo2[ 'options' ] ) ? $nodeInfo2[ 'options' ] : '';
			}
			else
			{
				$username = isset( $nodeInfo[ 'screen_name' ] ) ? $nodeInfo[ 'screen_name' ] : ( isset( $nodeInfo[ 'username' ] ) ? $nodeInfo[ 'username' ] : '-' );
			}

			if ( $feedInf[ 'status' ] === 'ok' )
			{
				$status = fsp__( 'SUCCESS' );
			}
			else if ( $feedInf[ 'status' ] === 'error' )
			{
				$status = fsp__( 'ERROR' );
			}
			else
			{
				$status = fsp__( 'NOT SENT' );
			}

			$arr = [
				//$feedInf[ 'id' ],
				$nodeInfo ? htmlspecialchars( $nodeInfo[ 'name' ] ) : fsp__( 'Account deleted' ),
				$nodeInfo ? Helper::profileLink( $nodeInfo ) : '',
				$feedInf[ 'send_time' ],
				! ( $postType === 'fs_post_tmp' ) ? site_url() . '/?p=' . $feedInf[ 'post_id' ] : '',
				Helper::postLink( $feedInf[ 'driver_post_id' ], $feedInf[ 'driver' ] . ( $feedInf[ 'driver' ] === 'instagram' ? $feedInf[ 'feed_type' ] : '' ), $username ),
				$networks[ $feedInf[ 'driver' ] ],
				$feedInf[ 'shared_from' ],
				$status,
				$feedInf[ 'error_msg' ]
			];

			fputcsv( $f, $arr, $delimiter );
		}

		fseek( $f, 0 );

		header( 'Content-Type: text/csv' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '";' );
		ob_start();
		fpassthru( $f );

		$file = ob_get_clean();
		$data = [
			'file'     => 'data:application/vnd.ms-excel;base64,' . base64_encode( $file ),
			'filename' => $filename
		];

		fclose( $f );

		Helper::response( TRUE, $data );
	}
}
