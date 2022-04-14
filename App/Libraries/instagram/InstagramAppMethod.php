<?php

namespace FSPoster\App\Libraries\instagram;

use FSP_GuzzleHttp\Client;
use FSPoster\App\Providers\DB;
use FSPoster\App\Providers\Curl;
use FSPoster\App\Providers\Date;
use FSPoster\App\Providers\Helper;
use FSPoster\App\Providers\Session;
use FSPoster\App\Providers\Request;
use FSPoster\App\Providers\SocialNetwork;
use FSP_GuzzleHttp\Exception\GuzzleException;

class InstagramAppMethod extends SocialNetwork
{
	public function uploadPhoto ( $accountId, $photo, $message, $link )
	{
		if ( empty( $photo ) )
		{
			return [
				'status'    => 'error',
				'error_msg' => 'AAn image or a video is required to share the post!'
			];
		}

		$node   = DB::fetch( 'account_nodes', [ 'id' => $accountId ] );
		$parent = DB::fetch( 'accounts', [ 'id' => $node[ 'account_id' ] ] );

		$accessToken = DB::fetch( 'account_access_tokens', [ 'account_id' => $node[ 'account_id' ] ] );

		$upload = self::cmd( $node[ 'node_id' ] . '/media', 'POST', $accessToken[ 'access_token' ], [
			'image_url' => $photo,
			'caption'   => $message
		], $parent[ 'proxy' ] );

		if ( isset( $upload[ 'error' ] ) )
		{
			$error_msg = isset( $upload[ 'error' ][ 'message' ] ) ? $upload[ 'error' ][ 'message' ] : fsp__( 'Error!' );

			return [
				'status'    => 'error',
				'error_msg' => $error_msg
			];
		}

		if ( empty( $upload[ 'id' ] ) )
		{
			return [
				'status'    => 'error',
				'error_msg' => fsp__( 'Error!' )
			];
		}

		$creation = self::cmd( $node[ 'node_id' ] . '/media_publish', 'POST', $accessToken[ 'access_token' ], [
			'creation_id' => $upload[ 'id' ]
		], $parent[ 'proxy' ] );

		if ( isset( $creation[ 'error' ] ) )
		{
			$error_msg = isset( $creation[ 'error' ][ 'message' ] ) ? $creation[ 'error' ][ 'message' ] : fsp__( 'Error!' );

			return [
				'status'    => 'error',
				'error_msg' => $error_msg
			];
		}

		if ( empty( $creation[ 'id' ] ) )
		{
			return [
				'status'    => 'error',
				'error_msg' => fsp__( 'Error!' )
			];
		}

		$shortcode = self::cmd( $creation[ 'id' ], 'GET', $accessToken[ 'access_token' ], [ 'fields' => 'shortcode' ], 'proxy' );

		if ( isset( $shortcode[ 'error' ] ) )
		{
			$error_msg = isset( $shortcode[ 'error' ][ 'message' ] ) ? $shortcode[ 'error' ][ 'message' ] : fsp__( 'Error!' );

			return [
				'status'    => 'error',
				'error_msg' => $error_msg
			];
		}

		return [
			'status' => 'ok',
			'id'     => $shortcode[ 'shortcode' ],
			'id2'    => $creation[ 'id' ]
		];
	}

	public function uploadVideo ( $accountId, $video, $message, $link, $target )
	{
		if ( empty( $photo ) )
		{
			return [
				'status'    => 'error',
				'error_msg' => 'An image or video should be provided.'
			];
		}

		$node   = DB::fetch( 'account_nodes', [ 'id' => $accountId ] );
		$parent = DB::fetch( 'accounts', [ 'id' => $node[ 'account_id' ] ] );

		$accessToken = DB::fetch( 'account_access_tokens', [ 'account_id' => $node[ 'account_id' ] ] );

		$upload = self::cmd( $node[ 'node_id' ] . '/media', 'POST', $accessToken[ 'access_token' ], [
			'media_type' => 'VIDEO',
			'video_url'  => $video,
			'caption'    => $message
		], $parent[ 'proxy' ] );

		if ( isset( $upload[ 'error' ] ) )
		{
			$error_msg = isset( $upload[ 'error' ][ 'message' ] ) ? $upload[ 'error' ][ 'message' ] : fsp__( 'Error!' );

			return [
				'status'    => 'error',
				'error_msg' => $error_msg
			];
		}

		if ( empty( $upload[ 'id' ] ) )
		{
			return [
				'status'    => 'error',
				'error_msg' => fsp__( 'Error!' )
			];
		}

		$creation = self::cmd( $node[ 'node_id' ] . '/media_publish', 'POST', $accessToken[ 'access_token' ], [
			'creation_id' => $upload[ 'id' ]
		], $parent[ 'proxy' ] );

		if ( isset( $creation[ 'error' ] ) )
		{
			$error_msg = isset( $creation[ 'error' ][ 'message' ] ) ? $creation[ 'error' ][ 'message' ] : fsp__( 'Error!' );

			return [
				'status'    => 'error',
				'error_msg' => $error_msg
			];
		}

		if ( empty( $creation[ 'id' ] ) )
		{
			return [
				'status'    => 'error',
				'error_msg' => fsp__( 'Error!' )
			];
		}

		$shortcode = self::cmd( $creation[ 'id' ], 'GET', $accessToken[ 'access_token' ], [ 'fields' => 'shortcode' ], 'proxy' );

		if ( isset( $shortcode[ 'error' ] ) )
		{
			$error_msg = isset( $shortcode[ 'error' ][ 'message' ] ) ? $shortcode[ 'error' ][ 'message' ] : fsp__( 'Error!' );

			return [
				'status'    => 'error',
				'error_msg' => $error_msg
			];
		}

		return [
			'status' => 'ok',
			'id'     => $shortcode[ 'shortcode' ],
			'id2'    => $creation[ 'id' ]
		];
	}

	public static function checkApp ( $appId, $appSecret )
	{
		$getInfo = json_decode( Curl::getContents( 'https://graph.facebook.com/' . $appId . '?fields=permissions{permission},roles,name,link,category&access_token=' . $appId . '|' . $appSecret ), TRUE );

		$appInfo = is_array( $getInfo ) && ! isset( $getInfo[ 'error' ] ) && isset( $getInfo[ 'name' ] ) ? $getInfo : FALSE;

		if ( ! $appInfo )
		{
			Helper::response( FALSE, [ 'error_msg' => fsp__( 'The App ID or the App Secret is invalid!' ) ] );
		}

		return $appInfo[ 'name' ];
	}

	public static function callbackURL ()
	{
		return site_url() . '/?instagram_callback=1';
	}

	/**
	 * Fetch login URL...
	 *
	 * @param integer $appId
	 *
	 * @return string
	 */
	public static function getLoginURL ( $appId )
	{
		Session::set( 'app_id', $appId );
		Session::set( 'proxy', Request::get( 'proxy', '', 'string' ) );

		$appInf = DB::fetch( 'apps', [ 'id' => $appId, 'driver' => 'instagram' ] );
		$appId  = $appInf[ 'app_id' ];

		$permissions = [
			'instagram_basic',
			'business_management',
			'instagram_content_publish',
			'instagram_manage_insights',
			'pages_read_engagement',
			'pages_show_list'
		];

		$permissions = implode( ',', array_map( 'urlencode', $permissions ) );

		$callbackUrl = self::callbackUrl();

		return "https://www.facebook.com/dialog/oauth?redirect_uri={$callbackUrl}&scope={$permissions}&response_type=code&client_id={$appId}";
	}

	public static function cmd ( $cmd, $method, $accessToken, array $data = [], $proxy = '' )
	{
		$data[ 'access_token' ] = $accessToken;
		$v                      = 'v12.0';
		$url                    = 'https://graph.facebook.com/' . $cmd; //. '?' . http_build_query( $data );
		$method                 = $method === 'POST' ? 'POST' : ( $method === 'DELETE' ? 'DELETE' : 'GET' );

		$client = new Client();

		try
		{
			$data1 = $client->request( $method, $url, [ 'query' => $data ] )->getBody();
		}
		catch ( GuzzleException $e )
		{
			$data1 = $e->getResponse()->getBody()->getContents();
		}

		$data = json_decode( $data1, TRUE );

		if ( $data === FALSE )
		{
			$data = $data1;
		}

		if ( ! is_array( $data ) )
		{
			$data = [
				'error' => [ 'message' => 'Error data! (' . $data1 . ')' ]
			];
		}

		return $data;
	}

	public static function fetchPages ( $accessToken, $proxy = '' )
	{
		$pages = [];

		$accounts_list = self::cmd( 'me/accounts', 'GET', $accessToken, [
			'fields' => 'id',
			'limit'  => 100
		], $proxy );

		// If Facebook Developer APP doesn't approved for Business use... ( set limit 3 )
		if ( isset( $accounts_list[ 'error' ][ 'code' ] ) && $accounts_list[ 'error' ][ 'code' ] === '4' && isset( $accounts_list[ 'error' ][ 'error_subcode' ] ) && $accounts_list[ 'error' ][ 'error_subcode' ] === '1349193' )
		{
			$accounts_list = self::cmd( 'me/accounts', 'GET', $accessToken, [
				'fields' => 'id',
				'limit'  => '3'
			], $proxy );

			if ( isset( $accounts_list[ 'data' ] ) && is_array( $accounts_list[ 'data' ] ) )
			{
				$pages = $accounts_list[ 'data' ];
			}

			return $pages;
		}

		if ( isset( $accounts_list[ 'data' ] ) )
		{
			$pages = array_merge( $pages, $accounts_list[ 'data' ] );
		}

		// paginaeting...
		while ( isset( $accounts_list[ 'paging' ][ 'cursors' ][ 'after' ] ) )
		{
			$after = $accounts_list[ 'paging' ][ 'cursors' ][ 'after' ];

			$accounts_list = self::cmd( 'me/accounts', 'GET', $accessToken, [
				'fields' => 'access_token,category,name,id',
				'limit'  => 100,
				'after'  => $accounts_list[ 'paging' ][ 'cursors' ][ 'after' ]
			], $proxy );

			if ( isset( $accounts_list[ 'data' ] ) )
			{
				$pages = array_merge( $pages, $accounts_list[ 'data' ] );
			}
		}

		return $pages;
	}

	public static function authorize ( $appId, $accessToken, $proxy )
	{
		$me = self::cmd( 'me', 'GET', $accessToken, [ 'fields' => 'id,name,email' ], $proxy );

		if ( isset( $me[ 'error' ] ) )
		{
			Helper::response( FALSE, isset( $me[ 'error' ][ 'message' ] ) ? $me[ 'error' ][ 'message' ] : 'Error!' );
		}

		if ( ! isset( $me[ 'id' ] ) )
		{
			$me[ 'id' ] = 0;
		}

		if ( ! isset( $me[ 'name' ] ) )
		{
			$me[ 'name' ] = '?';
		}

		if ( ! isset( $me[ 'email' ] ) )
		{
			$me[ 'email' ] = '?';
		}

		$meId = isset( $me[ 'id' ] ) ? $me[ 'id' ] : 0;

		if ( ! get_current_user_id() > 0 )
		{
			Helper::response( FALSE, fsp__( 'The current WordPress user ID is not available. Please, check if your security plugins prevent user authorization.' ) );
		}

		$checkLoginRegistered = DB::fetch( 'accounts', [
			'blog_id'    => Helper::getBlogId(),
			'user_id'    => get_current_user_id(),
			'driver'     => 'instagram',
			'profile_id' => $meId
		] );

		$dataSQL = [
			'blog_id'    => Helper::getBlogId(),
			'user_id'    => get_current_user_id(),
			'name'       => $me[ 'name' ],
			'driver'     => 'instagram',
			'profile_id' => $meId,
			'email'      => $me[ 'email' ],
			'password'   => '#####',
			'status'     => NULL,
			'error_msg'  => NULL,
			'proxy'      => $proxy
		];

		if ( ! $checkLoginRegistered )
		{
			DB::DB()->insert( DB::table( 'accounts' ), $dataSQL );

			$fb_accId = DB::DB()->insert_id;
		}
		else
		{
			$fb_accId = $checkLoginRegistered[ 'id' ];

			DB::DB()->update( DB::table( 'accounts' ), $dataSQL, [ 'id' => $fb_accId ] );

			DB::DB()->delete( DB::table( 'account_access_tokens' ), [ 'account_id' => $fb_accId ] );
		}

		$expiresOn = self::getAccessTokenExpiresDate( $accessToken, $proxy );

		// acccess token
		DB::DB()->insert( DB::table( 'account_access_tokens' ), [
			'account_id'   => $fb_accId,
			'app_id'       => $appId,
			'expires_on'   => $expiresOn,
			'access_token' => $accessToken
		] );

		self::refetch_account( $fb_accId, $accessToken, $proxy );

		self::closeWindow();
	}

	public static function getAccessToken ()
	{
		$appId = (int) Session::get( 'app_id' );

		if ( empty( $appId ) )
		{
			return FALSE;
		}

		$code = Request::get( 'code', '', 'string' );

		if ( empty( $code ) )
		{
			$errorMsg = Request::get( 'error_message', '', 'str' );

			self::error( $errorMsg );
		}

		$proxy = Session::get( 'proxy' );

		Session::remove( 'app_id' );
		Session::remove( 'proxy' );

		$appInf    = DB::fetch( 'apps', [ 'id' => $appId, 'driver' => 'instagram' ] );
		$appSecret = $appInf[ 'app_key' ];
		$clientId  = $appInf[ 'app_id' ];

		$token_url = "https://graph.facebook.com/oauth/access_token?" . "client_id=" . $clientId . "&redirect_uri=" . urlencode( self::callbackUrl() ) . "&client_secret=" . $appSecret . "&code=" . $code;

		$response = Curl::getURL( $token_url, $proxy );

		$params = json_decode( $response, TRUE );

		if ( isset( $params[ 'error' ][ 'message' ] ) )
		{
			self::error( $params[ 'error' ][ 'message' ] );
		}

		$accessToken = esc_html( $params[ 'access_token' ] );

		self::authorize( $appId, $accessToken, $proxy );
	}

	public static function refetch_account ( $accountId, $accessToken, $proxy )
	{
		$pages = self::fetchPages( $accessToken, $proxy );

		$addedPages = [];

		foreach ( $pages as $page )
		{
			$instagram_business_account = self::cmd( $page[ 'id' ], 'GET', $accessToken, [ 'fields' => 'instagram_business_account' ], $proxy );

			if ( isset( $instagram_business_account[ 'instagram_business_account' ][ 'id' ] ) )
			{
				$profileId = $instagram_business_account[ 'instagram_business_account' ][ 'id' ];

				$instaPage = self::cmd( $profileId, 'GET', $accessToken, [ 'fields' => 'name,username,profile_picture_url' ], $proxy );

				$nodeExists = DB::fetch( 'account_nodes', [ 'account_id' => $accountId, 'node_id' => $profileId ] );

				$nodeSQL = [
					'blog_id'     => Helper::getBlogId(),
					'user_id'     => get_current_user_id(),
					'driver'      => 'instagram',
					'node_type'   => 'page',
					'account_id'  => $accountId,
					'node_id'     => $profileId,
					'cover'       => isset( $instaPage[ 'profile_picture_url' ] ) ? $instaPage[ 'profile_picture_url' ] : NULL,
					'screen_name' => $instaPage[ 'username' ],
					'name'        => isset( $instaPage[ 'name' ] ) ? $instaPage[ 'name' ] : NULL
				];

				if ( $nodeExists )
				{
					$addedPages[] = $nodeExists[ 'id' ];
					DB::DB()->update( DB::table( 'account_nodes' ), $nodeSQL, [ 'id' => $nodeExists[ 'id' ] ] );
				}
				else
				{
					DB::DB()->insert( DB::table( 'account_nodes' ), $nodeSQL );
					$addedPages[] = DB::DB()->insert_id;
				}
			}
		}

		if ( empty( $addedPages ) )
		{
			DB::DB()->delete( DB::table( 'account_nodes' ), [ 'account_id' => $accountId ] );
		}
		else
		{
			DB::DB()->query( DB::DB()->prepare( 'DELETE FROM ' . DB::table( 'account_nodes' ) . ' WHERE id NOT IN (' . implode( ',', $addedPages ) . ') AND account_id=%d', [ $accountId ] ) );
		}

		DB::DB()->query( 'DELETE FROM ' . DB::table( 'account_node_status' ) . ' WHERE node_id NOT IN (SELECT id FROM ' . DB::table( 'account_nodes' ) . ')' );

		return [ 'status' => TRUE ];
	}

	public static function getAccessTokenExpiresDate ( $accessToken, $proxy )
	{
		$url = 'https://graph.facebook.com/oauth/access_token_info?fields=id,category,company,name&access_token=' . $accessToken;

		$data = json_decode( Curl::getContents( $url, 'GET', [], [], $proxy ), TRUE );

		return is_array( $data ) && isset( $data[ 'expires_in' ] ) && $data[ 'expires_in' ] > 0 ? Date::dateTimeSQL( 'now', '+' . (int) $data[ 'expires_in' ] . ' seconds' ) : NULL;
	}

	public static function checkAccount ( $accessToken, $proxy )
	{
		$result = [
			'error'     => TRUE,
			'error_msg' => NULL
		];
		$me     = self::cmd( 'me', 'GET', $accessToken, [ 'fields' => 'id,name,email' ], $proxy );

		if ( isset( $me[ 'error' ] ) && isset( $me[ 'error' ][ 'message' ] ) )
		{
			$result[ 'error_msg' ] = $me[ 'error' ][ 'message' ];
		}
		else if ( ! isset( $me[ 'error' ] ) )
		{
			$result[ 'error' ] = FALSE;
		}

		return $result;
	}
}