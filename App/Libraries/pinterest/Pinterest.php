<?php

namespace FSPoster\App\Libraries\pinterest;

use FSPoster\App\Providers\DB;
use FSPoster\App\Providers\Curl;
use FSPoster\App\Providers\Date;
use FSPoster\App\Providers\Helper;
use FSPoster\App\Providers\Request;
use FSPoster\App\Providers\Session;
use FSPoster\App\Providers\SocialNetwork;

class Pinterest extends SocialNetwork
{
	/**
	 * @param array $account_info
	 * @param string $type
	 * @param string $title
	 * @param string $message
	 * @param string $link
	 * @param string $altText
	 * @param array $images
	 * @param string $accessToken
	 * @param string $proxy
	 *
	 * @return array
	 */
	public static function sendPost ( $boardId, $type, $title, $message, $link, $altText, $images, $accessToken, $proxy )
	{
		$sendData = [
			'board_id'    => $boardId,
			'title'       => $title,
			'description' => $message,
			'link'        => $link,
			'alt_text'    => $altText
		];

		if ( $type === 'image' )
		{
			$sendData[ 'media_source' ][ 'source_type' ] = 'image_url';
			$sendData[ 'media_source' ][ 'url' ]         = reset( $images );
		}
		else
		{
			return [
				'status'    => 'error',
				'error_msg' => 'An image is required to pin on board!'
			];
		}

		$result = self::cmd( 'pins', 'POST', $accessToken, $sendData, $proxy );

		if ( isset( $result[ 'error' ] ) && isset( $result[ 'error' ][ 'message' ] ) )
		{
			$result2 = [
				'status'    => 'error',
				'error_msg' => $result[ 'error' ][ 'message' ]
			];
		}
		else if ( isset( $result[ 'message' ] ) )
		{
			$result2 = [
				'status'    => 'error',
				'error_msg' => $result[ 'message' ]
			];
		}
		else
		{
			$result2 = [
				'status' => 'ok',
				'id'     => $result[ 'id' ]
			];
		}

		return $result2;
	}

	/**
	 * @param string $cmd
	 * @param string $method
	 * @param string $accessToken
	 * @param array $data
	 * @param string $proxy
	 *
	 * @return array|mixed|object
	 */
	public static function cmd ( $cmd, $method, $accessToken, array $data = [], $proxy = '' )
	{
		$data[ 'access_token' ] = $accessToken;

		$url = 'https://api.pinterest.com/v5/' . trim( $cmd, '/' ) . '/';

		$method = $method === 'POST' ? 'POST' : ( $method === 'DELETE' ? 'DELETE' : 'GET' );

		$headers = [
			'Authorization' => 'Bearer ' . $accessToken
		];

		if ( $method === 'POST' )
		{
			$headers[ 'Content-Type' ] = 'application/json';
			$data                      = json_encode( $data );
		}

		$data1 = Curl::getContents( $url, $method, $data, $headers, $proxy );

		$data = json_decode( $data1, TRUE );

		if ( ! is_array( $data ) )
		{
			$data = [ 'message' => 'Error data!' ];
		}

		return $data;
	}

	/**
	 * @param integer $appId
	 *
	 * @return string
	 */
	public static function getLoginURL ( $appId )
	{
		Session::set( 'app_id', $appId );
		Session::set( 'proxy', Request::get( 'proxy', '', 'string' ) );

		$appInf = DB::fetch( 'apps', [ 'id' => $appId, 'driver' => 'pinterest' ] );
		if ( ! $appInf )
		{
			self::error( fsp__( 'Error! The App isn\'t found!' ) );
		}

		$appId = urlencode( $appInf[ 'app_id' ] );

		$callbackUrl = urlencode( self::callbackUrl() );

		return "https://www.pinterest.com/oauth/?client_id=$appId&redirect_uri=$callbackUrl&response_type=code&scope=boards:read,boards:write,pins:read,pins:write,user_accounts:read&state=pinterest_callback";
	}

	/**
	 * @return string
	 */
	public static function callbackURL ()
	{
		//return site_url() . '/?pinterest_callback=1';
		return trim(site_url(), '/') . '/';
	}

	/**
	 * @return bool
	 */
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
			$error_message = Request::get( 'error_message', '', 'str' );

			self::error( $error_message );
		}

		$proxy = Session::get( 'proxy' );

		Session::remove( 'app_id' );
		Session::remove( 'proxy' );

		$appInf    = DB::fetch( 'apps', [ 'id' => $appId, 'driver' => 'pinterest' ] );
		$appSecret = urlencode( $appInf[ 'app_secret' ] );
		$appId2    = urlencode( $appInf[ 'app_id' ] );

		$token_url = "https://api.pinterest.com/v5/oauth/token";

		$response = Curl::getContents( $token_url, 'POST', [
			'grant_type'   => 'authorization_code',
			'code'         => $code,
			'redirect_uri' => self::callbackURL()
		], [
			'Authorization' => 'Basic ' . base64_encode( $appId2 . ':' . $appSecret ),
			'Content-Type'  => 'application/x-www-form-urlencoded'
		], $proxy, TRUE );

		$params = json_decode( $response, TRUE );

		if ( isset( $params[ 'message' ] ) )
		{
			self::error( $params[ 'message' ] );
		}

		$accessToken  = esc_html( $params[ 'access_token' ] );
		$refreshToken = esc_html( $params[ 'refresh_token' ] );
		$expiresIn    = esc_html( $params[ 'expires_in' ] );

		self::authorize( $appId, $accessToken, $refreshToken, $expiresIn, $proxy );
	}

	private static function refreshToken ( $tokenInfo )
	{

		$app_id = $tokenInfo[ 'app_id' ];

		$account_info = DB::fetch( 'accounts', $tokenInfo[ 'account_id' ] );
		$proxy        = $account_info[ 'proxy' ];

		$appInfo      = DB::fetch( 'apps', $app_id );
		$refreshToken = $tokenInfo[ 'refresh_token' ];

		$appSecret = urlencode( $appInfo[ 'app_secret' ] );
		$appId2    = urlencode( $appInfo[ 'app_id' ] );

		$token_url = "https://api.pinterest.com/v5/oauth/token";

		$response = Curl::getContents( $token_url, 'POST', [
			'grant_type'    => 'refresh_token',
			'refresh_token' => $refreshToken
		], [
			'Authorization' => 'Basic ' . base64_encode( $appId2 . ':' . $appSecret ),
			'Content-Type'  => 'application/x-www-form-urlencoded'
		], $proxy, TRUE );

		$params = json_decode( $response, TRUE );

		if ( isset( $params[ 'message' ] ) )
		{
			self::error( $params[ 'message' ] );
		}

		$accessToken = esc_html( $params[ 'access_token' ] );
		$expiresIn   = esc_html( $params[ 'expires_in' ] );

		DB::DB()->update( DB::table( 'account_access_tokens' ), [
			'access_token' => $accessToken,
			'expires_on'   => Date::dateTimeSQL( Date::epoch() + (int) $expiresIn )
		], [ 'id' => $tokenInfo[ 'id' ] ] );

		return $accessToken;
	}

	public static function accessToken ( $tokenInfo )
	{
		if ( ( Date::epoch() + 30 ) > Date::epoch( $tokenInfo[ 'expires_on' ] ) )
		{
			return self::refreshToken( $tokenInfo );
		}

		return $tokenInfo[ 'access_token' ];
	}

	/**
	 * @param integer $appId
	 * @param string $accessToken
	 * @param string $refreshToken
	 * @param string $expiresIn
	 * @param string $proxy
	 */
	public static function authorize ( $appId, $accessToken, $refreshToken, $expiresIn, $proxy )
	{
		$me = self::cmd( 'user_account', 'GET', $accessToken, [], $proxy );

		if ( isset( $me[ 'message' ] ) && is_string( $me[ 'message' ] ) )
		{
			self::error( $me[ 'message' ] );
		}

		if ( ! isset( $me[ 'username' ] ) )
		{
			self::error();
		}

		if ( ! get_current_user_id() > 0 )
		{
			Helper::response( FALSE, fsp__( 'The current WordPress user ID is not available. Please, check if your security plugins prevent user authorization.' ) );
		}

		$checkLoginRegistered = DB::fetch( 'accounts', [
			'blog_id'    => Helper::getBlogId(),
			'user_id'    => get_current_user_id(),
			'driver'     => 'pinterest',
			'profile_id' => $me[ 'username' ]
		] );

		$dataSQL = [
			'blog_id'     => Helper::getBlogId(),
			'user_id'     => get_current_user_id(),
			'name'        => $me[ 'username' ],
			'driver'      => 'pinterest',
			'profile_id'  => $me[ 'username' ],
			'profile_pic' => $me[ 'profile_image' ],
			'username'    => $me[ 'username' ],
			'proxy'       => $proxy,
            'status'      => NULL,
            'error_msg'   => NULL
		];

		if ( ! $checkLoginRegistered )
		{
			DB::DB()->insert( DB::table( 'accounts' ), $dataSQL );

			$accId = DB::DB()->insert_id;
		}
		else
		{
			$accId = $checkLoginRegistered[ 'id' ];

			DB::DB()->update( DB::table( 'accounts' ), $dataSQL, [ 'id' => $accId ] );

			DB::DB()->delete( DB::table( 'account_access_tokens' ), [ 'account_id' => $accId, 'app_id' => $appId ] );
		}

		// acccess token
		DB::DB()->insert( DB::table( 'account_access_tokens' ), [
			'account_id'    => $accId,
			'app_id'        => $appId,
			'access_token'  => $accessToken,
			'refresh_token' => $refreshToken,
			'expires_on'    => Date::dateTimeSQL( Date::epoch() + (int) $expiresIn )
		] );

		// set default board
		self::refetch_account( $accId, $accessToken, $proxy );

		self::closeWindow();
	}

	/**
	 * @param integer $post_id
	 * @param string $accessToken
	 * @param string $proxy
	 *
	 * @return array
	 */
	public static function getStats ( $post_id, $accessToken, $proxy )
	{
		return [
			'comments' => 0,
			'like'     => 0,
			'shares'   => 0,
			'details'  => 0
		];
	}

	/**
	 * @param string $accessToken
	 * @param string $proxy
	 *
	 * @return array
	 */
	public static function checkAccount ( $accessToken, $proxy )
	{
		$result = [
			'error'     => TRUE,
			'error_msg' => NULL
		];

		$me = self::cmd( 'user_account', 'GET', $accessToken, [], $proxy );

		if ( isset( $me[ 'message' ] ) && is_string( $me[ 'message' ] ) )
		{
			$result[ 'error_msg' ] = $me[ 'message' ];
		}
		else if ( isset( $me[ 'username' ] ) )
		{
			$result[ 'error' ] = FALSE;
		}

		return $result;
	}

	public static function get_boards ( $accountId, $accessToken, $proxy )
	{
		$bookmark = NULL;
		$boards   = [];

		do
		{
			$send_data = [ 'page_size' => 100 ];

			if ( ! empty( $bookmark ) )
			{
				$send_data[ 'bookmark' ] = $bookmark;
			}

			$page = self::cmd( 'boards', 'GET', $accessToken, $send_data, $proxy );

			if ( ! empty( $page[ 'items' ] ) )
			{
				foreach ( $page[ 'items' ] as $item )
				{
					$board = [
						'account_id' => $accountId,
						'node_type'  => 'board',
						'user_id'    => get_current_user_id(),
						'blog_id'    => Helper::getBlogId(),
						'driver'     => 'pinterest',
						'name'       => $item[ 'name' ],
						'node_id'    => $item[ 'id' ]
					];

					$boards[] = $board;
				}
				$bookmark = isset( $page[ 'bookmark' ] ) ? $page[ 'bookmark' ] : NULL;
			}
			else
			{
				break;
			}
		} while ( ! empty( $bookmark ) );

		return $boards;
	}

	public static function refetch_account ( $account_id, $access_token, $proxy )
	{
		$boards    = self::get_boards( $account_id, $access_token, $proxy );
		$get_nodes = DB::DB()->get_results( DB::DB()->prepare( 'SELECT id, node_id FROM ' . DB::table( 'account_nodes' ) . ' WHERE account_id = %d', [ $account_id ] ), ARRAY_A );
		$my_nodes  = [];

		foreach ( $get_nodes as $node )
		{
			$my_nodes[ $node[ 'id' ] ] = $node[ 'node_id' ];
		}

		if ( ! empty( $boards ) )
		{
			foreach ( $boards as $board )
			{
				$board_id = $board[ 'node_id' ];

				if ( ! in_array( $board_id, $my_nodes ) )
				{
					DB::DB()->insert( DB::table( 'account_nodes' ), $board );
				}
				else
				{
					DB::DB()->update( DB::table( 'account_nodes' ), [
						'name' => $board[ 'name' ]
					], [
						'account_id' => $account_id,
						'node_id'    => $board_id
					] );
				}

				unset( $my_nodes[ array_search( $board_id, $my_nodes ) ] );
			}
		}

		if ( ! empty( $my_nodes ) )
		{
			DB::DB()->query( 'DELETE FROM ' . DB::table( 'account_nodes' ) . ' WHERE id IN (' . implode( ',', array_keys( $my_nodes ) ) . ')' );
			DB::DB()->query( 'DELETE FROM ' . DB::table( 'account_node_status' ) . ' WHERE node_id IN (' . implode( ',', array_keys( $my_nodes ) ) . ')' );
		}

		return [ 'status' => TRUE ];
	}
}