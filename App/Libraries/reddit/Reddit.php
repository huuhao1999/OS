<?php

namespace FSPoster\App\Libraries\reddit;

use FSPoster\App\Providers\DB;
use FSPoster\App\Providers\Date;
use FSPoster\App\Providers\Curl;
use FSPoster\App\Providers\Helper;
use FSPoster\App\Providers\Request;
use FSPoster\App\Providers\Session;
use FSPoster\App\Providers\SocialNetwork;

class Reddit extends SocialNetwork
{
	/**
	 * @param array $account_info
	 * @param string $type
	 * @param string $title
	 * @param string $message
	 * @param string $link
	 * @param array $images
	 * @param string $video
	 * @param string $accessToken
	 * @param string $proxy
	 *
	 * @return array
	 */
	public static function sendPost ( $account_info, $type, $title, $message, $link, $images, $video, $accessToken, $proxy, $pMethod, $retry = TRUE )
	{
		$sendData = [
			'title'        => $title,
			'resubmit'     => 'true',
			'send_replies' => 'true',
			'api_type'     => 'json'
		];

		if ( $pMethod !== 2 )
		{
			$sendData[ 'title' ] = $message;
		}

		if ( Helper::getOption( 'reddit_autocut_title', '1' ) == 1 && mb_strlen( $sendData[ 'title' ] ) > 300 )
		{
			$sendData[ 'title' ] = mb_substr( $sendData[ 'title' ], 0, 297 ) . '...';
		}

		if ( isset( $account_info[ 'screen_name' ] ) )
		{
			$sendData[ 'sr' ] = $account_info[ 'screen_name' ];

			if ( ! empty( $account_info[ 'access_token' ] ) )
			{
				$sendData[ 'flair_text' ] = $account_info[ 'category' ];
				$sendData[ 'flair_id' ]   = $account_info[ 'access_token' ];
			}
		}
		else
		{
			$sendData[ 'sr' ] = 'u_' . $account_info[ 'username' ];
		}

		if ( $type === 'image' )
		{
			$sendData[ 'kind' ] = 'image';
			$sendData[ 'url' ]  = reset( $images );
		}
		else if ( $type === 'video' )
		{
			$sendData[ 'kind' ] = 'video';
			$sendData[ 'url' ]  = $video;
		}
		else
		{
			if ( $pMethod === 1 )
			{
				$sendData[ 'kind' ] = 'link';
				$sendData[ 'url' ]  = $link;
			}
			else if ( $pMethod === 2 )
			{
				$sendData[ 'kind' ] = 'self';
				$sendData[ 'text' ] = $message;
			}
			else
			{
				$sendData[ 'kind' ] = 'image';
				$sendData[ 'url' ]  = reset( $images );
			}
		}

		$result = self::cmd( 'https://oauth.reddit.com/api/submit', 'POST', $accessToken, $sendData, $proxy );

		if ( isset( $result[ 'error' ] ) && isset( $result[ 'error' ][ 'message' ] ) )
		{
			$result2 = [
				'status'    => 'error',
				'error_msg' => esc_html( $result[ 'error' ][ 'message' ] )
			];
		}
		else if ( isset( $result[ 'json' ][ 'errors' ] ) && is_array( $result[ 'json' ][ 'errors' ] ) && ! empty( $result[ 'json' ][ 'errors' ] ) )
		{
			$error = reset( $result[ 'json' ][ 'errors' ] );
			$error = self::getErrorMessage( $error );

			$result2 = [
				'status'    => 'error',
				'error_msg' => esc_html( $error )
			];
		}
		else if ( ! isset( $result[ 'json' ] ) )
		{
			if ( isset( $result[ 'error' ] ) && $result[ 'error' ] == 403 )
			{
				$result2_error_text = fsp__( 'It seems that your account is banned by Reddit or you do not have permission to share posts on Reddit' );
			}
			else
			{
				$result2_error_text = fsp__( 'Error result!' ) . esc_html( json_encode( $result ) );
			}
			$result2 = [
				'status'    => 'error',
				'error_msg' => $result2_error_text
			];
		}
		else
		{
			$result2 = [
				'status' => 'ok',
				'id'     => esc_html( $result[ 'json' ][ 'data' ][ 'id' ] )
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
	 * @return mixed
	 */
	public static function cmd ( $cmd, $method, $accessToken, $data = [], $proxy = '' )
	{
		$url = $cmd;

		$method = $method === 'POST' ? 'POST' : ( $method === 'DELETE' ? 'DELETE' : 'GET' );

		$result     = Curl::getContents( $url, $method, $data, [ 'Authorization' => 'bearer ' . $accessToken ], $proxy, TRUE );
		$result_arr = json_decode( $result, TRUE );

		if ( ! is_array( $result_arr ) )
		{
			$result_arr = [
				'error' => [ 'message' => 'Error data!' ]
			];
		}

		return $result_arr;
	}

	/**
	 * @param integer $appId
	 *
	 * @return string
	 */
	public static function getLoginURL ( $appId )
	{
		$state = md5( rand( 111111111, 911111111 ) );

		Session::set( 'app_id', $appId );
		Session::set( 'state', $state );
		Session::set( 'proxy', Request::get( 'proxy', '', 'string' ) );

		$appInf = DB::fetch( 'apps', [ 'id' => $appId, 'driver' => 'reddit' ] );

		if ( ! $appInf )
		{
			self::error( fsp__( 'Error! The App isn\'t found!' ) );
		}

		$appId       = urlencode( $appInf[ 'app_id' ] );
		$callbackUrl = urlencode( self::callbackUrl() );

		return "https://www.reddit.com/api/v1/authorize?client_id={$appId}&response_type=code&redirect_uri={$callbackUrl}&duration=permanent&scope=identity,submit,flair,read&state=" . $state;
	}

	/**
	 * @return string
	 */
	public static function callbackURL ()
	{
		return site_url() . '/?reddit_callback=1';
	}

	/**
	 * @return bool
	 */
	public static function getAccessToken ()
	{
		$appId     = (int) Session::get( 'app_id' );
		$stateSess = Session::get( 'state' );

		if ( empty( $appId ) || empty( $stateSess ) )
		{
			return FALSE;
		}

		$code  = Request::get( 'code', '', 'string' );
		$state = Request::get( 'state', '', 'string' );

		if ( empty( $code ) || $state != $stateSess )
		{
			$error_message = Request::get( 'error_message', '', 'str' );

			self::error( $error_message );
		}

		$proxy = Session::get( 'proxy' );

		Session::remove( 'app_id' );
		Session::remove( 'state' );
		Session::remove( 'proxy' );

		$appInf    = DB::fetch( 'apps', [ 'id' => $appId, 'driver' => 'reddit' ] );
		$appSecret = urlencode( $appInf[ 'app_secret' ] );
		$appId2    = urlencode( $appInf[ 'app_id' ] );

		$url = 'https://www.reddit.com/api/v1/access_token';

		$postData = [
			'grant_type'   => 'authorization_code',
			'code'         => $code,
			'redirect_uri' => self::callbackURL(),
		];

		$headers = [
			'Authorization' => 'Basic ' . base64_encode( $appId2 . ':' . $appSecret )
		];

		$response = Curl::getContents( $url, 'POST', $postData, $headers, $proxy );
		$params   = json_decode( $response, TRUE );

		if ( isset( $params[ 'error' ][ 'message' ] ) )
		{
			self::error( $params[ 'error' ][ 'message' ] );
		}

		$access_token = esc_html( $params[ 'access_token' ] );
		$refreshToken = esc_html( $params[ 'refresh_token' ] );
		$expiresIn    = Date::dateTimeSQL( 'now', '+' . (int) $params[ 'expires_in' ] . ' seconds' );

		self::authorize( $appId, $access_token, $refreshToken, $expiresIn, $proxy );
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
		$me = self::cmd( 'https://oauth.reddit.com/api/v1/me', 'GET', $accessToken, [], $proxy );

		if ( isset( $me[ 'error' ] ) && isset( $me[ 'error' ][ 'message' ] ) )
		{
			self::error( $me[ 'error' ][ 'message' ] );
		}

		$meId = $me[ 'id' ];

		if ( ! get_current_user_id() > 0 )
		{
			Helper::response( FALSE, fsp__( 'The current WordPress user ID is not available. Please, check if your security plugins prevent user authorization.' ) );
		}

		$checkLoginRegistered = DB::fetch( 'accounts', [
			'blog_id'    => Helper::getBlogId(),
			'user_id'    => get_current_user_id(),
			'driver'     => 'reddit',
			'profile_id' => $meId
		] );
		$dataSQL              = [
			'blog_id'     => Helper::getBlogId(),
			'user_id'     => get_current_user_id(),
			'name'        => isset( $me[ 'subreddit' ][ 'title' ] ) && ! empty( $me[ 'subreddit' ][ 'title' ] ) ? $me[ 'subreddit' ][ 'title' ] : $me[ 'name' ],
			'driver'      => 'reddit',
			'profile_id'  => $meId,
			'profile_pic' => $me[ 'icon_img' ],
			'username'    => $me[ 'name' ],
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

		DB::DB()->insert( DB::table( 'account_access_tokens' ), [
			'account_id'    => $accId,
			'app_id'        => $appId,
			'access_token'  => $accessToken,
			'refresh_token' => $refreshToken,
			'expires_on'    => $expiresIn
		] );

		self::closeWindow();
	}

	/**
	 * @param $tokenInfo
	 */
	public static function accessToken ( $tokenInfo )
	{
		if ( ( Date::epoch() + 30 ) > Date::epoch( $tokenInfo[ 'expires_on' ] ) )
		{
			return self::refreshToken( $tokenInfo );
		}

		return $tokenInfo[ 'access_token' ];
	}

	/**
	 * @param array $tokenInfo
	 *
	 * @return string
	 */
	public static function refreshToken ( $tokenInfo )
	{
		$appId = $tokenInfo[ 'app_id' ];

		$account_info = DB::fetch( 'accounts', $tokenInfo[ 'account_id' ] );
		$proxy        = $account_info[ 'proxy' ];

		$appInf    = DB::fetch( 'apps', $appId );
		$appId2    = urlencode( $appInf[ 'app_id' ] );
		$appSecret = urlencode( $appInf[ 'app_secret' ] );

		$url = 'https://www.reddit.com/api/v1/access_token';

		$postData = [
			'grant_type'    => 'refresh_token',
			'refresh_token' => $tokenInfo[ 'refresh_token' ]
		];

		$headers  = [
			'Authorization' => 'Basic ' . base64_encode( $appId2 . ':' . $appSecret )
		];
		$response = Curl::getContents( $url, 'POST', $postData, $headers, $proxy );
		$params   = json_decode( $response, TRUE );

		if ( isset( $params[ 'error' ][ 'message' ] ) )
		{
			return FALSE;
		}

		$access_token = esc_html( $params[ 'access_token' ] );
		$expiresIn    = Date::dateTimeSQL( 'now', '+' . (int) $params[ 'expires_in' ] . ' seconds' );

		DB::DB()->update( DB::table( 'account_access_tokens' ), [
			'access_token' => $access_token,
			'expires_on'   => $expiresIn
		], [ 'id' => $tokenInfo[ 'id' ] ] );

		$tokenInfo[ 'access_token' ] = $access_token;
		$tokenInfo[ 'expires_on' ]   = $expiresIn;

		return $access_token;
	}

	/**
	 * @param integer $post_id
	 * @param string $accessToken
	 *
	 * @return array
	 */
	public static function getStats ( $post_id, $accessToken, $proxy )
	{
		return [
			'comments' => 0,
			'like'     => 0,
			'shares'   => 0,
			'details'  => ''
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
		$me     = self::cmd( 'https://oauth.reddit.com/api/v1/me', 'GET', $accessToken, [], $proxy );

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

	public static function getErrorMessage ( $error )
	{
		$reddit_errors = [
			'NO_URL'   => fsp__( 'Required URL (or featured image path) not found!' ),
			'TOO_LONG' => fsp__( 'Title is too long. Maximum allowed character limit is 300. You can enable auto-cut option from Reddit settings.' ),
			'NO_TEXT'  => fsp__( 'Content can\'t be empty!' )
		];

		if ( isset( $error[ 0 ] ) && ! empty( $error[ 0 ] ) && array_key_exists( $error[ 0 ], $reddit_errors ) )
		{
			return $reddit_errors[ $error[ 0 ] ];
		}
		else
		{
			return json_encode( $error );
		}
	}

	public static function refetch_account ( $account_id, $access_token, $proxy )
	{
		return [];
	}
}