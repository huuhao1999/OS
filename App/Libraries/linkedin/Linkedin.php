<?php

namespace FSPoster\App\Libraries\linkedin;

use Exception;
use FSP_GuzzleHttp\Client;
use FSPoster\App\Providers\DB;
use FSPoster\App\Providers\Date;
use FSPoster\App\Providers\Curl;
use FSPoster\App\Providers\Pages;
use FSPoster\App\Providers\Helper;
use FSPoster\App\Providers\Request;
use FSPoster\App\Providers\Session;
use FSPoster\App\Providers\SocialNetwork;
use FSPoster\App\Providers\AccountService;

class Linkedin extends SocialNetwork
{
	public static function sendPost ( $profileId, $node_info, $type, $message, $title, $link, $images, $video, $accessToken, $proxy )
	{
		$client = new Client();

		if ( Helper::getOption( 'linkedin_autocut_text', '1' ) == 1 && mb_strlen( $message ) > 1300 )
		{
			$message = mb_substr( $message, 0, 1297 ) . '...';
		}

		$sendData = [
			'lifecycleState'  => 'PUBLISHED',
			'specificContent' => [
				'com.linkedin.ugc.ShareContent' => [
					'shareCommentary'    => [ 'text' => $message ],
					'shareMediaCategory' => 'ARTICLE'
				]
			],
			'visibility'      => [ 'com.linkedin.ugc.MemberNetworkVisibility' => 'PUBLIC' ]
		];

		if ( isset( $node_info[ 'node_type' ] ) && $node_info[ 'node_type' ] === 'company' )
		{
			$sendData[ 'author' ] = 'urn:li:organization:' . $node_info[ 'node_id' ];
		}
		else if ( isset( $node_info[ 'node_type' ] ) && $node_info[ 'node_type' ] === 'company' )
		{
			$sendData[ 'author' ]          = 'urn:li:person:' . $profileId;
			$sendData[ 'containerEntity' ] = 'urn:li:group:' . $node_info[ 'node_id' ];
		}
		else
		{
			$sendData[ 'author' ] = 'urn:li:person:' . $node_info[ 'profile_id' ];
		}

		if ( $type === 'link' && ! empty( $link ) )
		{
			$sendData[ 'specificContent' ][ 'com.linkedin.ugc.ShareContent' ][ 'media' ] = [
				[
					'status'      => 'READY',
					'originalUrl' => $link
				]
			];
		}
		else if ( $type === 'image' && ! empty( $images ) && is_array( $images ) )
		{
			$send_upload_data      = [
				'registerUploadRequest' => [
					'owner'                    => $sendData[ 'author' ],
					'recipes'                  => [
						'urn:li:digitalmediaRecipe:feedshare-image'
					],
					'serviceRelationships'     => [
						[
							'identifier'       => 'urn:li:userGeneratedContent',
							'relationshipType' => 'OWNER'
						]
					],
					'supportedUploadMechanism' => [
						'SYNCHRONOUS_UPLOAD'
					]
				]
			];
			$uploaded_images       = [];
			$uploaded_images_count = 0;

			foreach ( $images as $imageURL )
			{
				if ( $uploaded_images_count > 4 )
				{
					break;
				}

				if ( empty( $imageURL ) || ! is_string( $imageURL ) )
				{
					continue;
				}

				try
				{
					$result = self::cmd( 'assets?action=registerUpload', 'POST', $accessToken, $send_upload_data, $proxy );

					if ( ! isset( $result[ 'value' ][ 'uploadMechanism' ][ 'com.linkedin.digitalmedia.uploading.MediaUploadHttpRequest' ][ 'uploadUrl' ] ) || empty( $result[ 'value' ][ 'uploadMechanism' ][ 'com.linkedin.digitalmedia.uploading.MediaUploadHttpRequest' ][ 'uploadUrl' ] ) )
					{
						throw new Exception();
					}

					$uploadURL = $result[ 'value' ][ 'uploadMechanism' ][ 'com.linkedin.digitalmedia.uploading.MediaUploadHttpRequest' ][ 'uploadUrl' ];
					$mediaID   = explode( ':', $result[ 'value' ][ 'asset' ] )[ 3 ];

					$resp = $client->request( 'PUT', $uploadURL, [
						'body'    => Curl::getContents( $imageURL ),
						'headers' => [
							'Authorization' => 'Bearer ' . $accessToken,
							'proxy'         => empty( $proxy ) ? NULL : $proxy,
						]
					] );

					$mediaStatus = self::cmd( 'assets/' . $mediaID, 'GET', $accessToken, [], $proxy );

					if ( isset( $mediaStatus[ 'recipes' ][ 0 ][ 'status' ] ) && $mediaStatus[ 'recipes' ][ 0 ][ 'status' ] === 'AVAILABLE' )
					{
						$uploaded_images[] = $result[ 'value' ][ 'asset' ];
					}
					else
					{
						throw new Exception();
					}

					$uploaded_images_count++;
				}
				catch ( Exception $e )
				{
				}
			}

			$sendData[ 'specificContent' ][ 'com.linkedin.ugc.ShareContent' ][ 'shareMediaCategory' ] = 'IMAGE';
			$sendData[ 'specificContent' ][ 'com.linkedin.ugc.ShareContent' ][ 'media' ]              = [];

			foreach ( $uploaded_images as $uplImage )
			{
				$sendData[ 'specificContent' ][ 'com.linkedin.ugc.ShareContent' ][ 'media' ][] = [
					'media'  => $uplImage,
					'status' => 'READY',
				];
			}
		}
		else if ( $type === 'video' )
		{
			$sendData[ 'specificContent' ][ 'com.linkedin.ugc.ShareContent' ][ 'media' ] = [
				[
					'status'      => 'READY',
					'originalUrl' => $video,
					'description' => [ 'text' => $message ],
					'title'       => [ 'text' => $title ]
				]
			];
		}
		else
		{
			$sendData[ 'specificContent' ][ 'com.linkedin.ugc.ShareContent' ][ 'shareMediaCategory' ] = 'NONE';
		}

		$result = self::cmd( 'ugcPosts', 'POST', $accessToken, $sendData, $proxy );

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
				'error_msg' => isset( $result[ 'message' ] ) ? $result[ 'message' ] : fsp__( 'Error!' )
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

	public static function cmd ( $cmd, $method, $accessToken, array $data = [], $proxy = '' )
	{
		$url = 'https://api.linkedin.com/v2/' . $cmd;

		$method = $method === 'POST' ? 'POST' : ( $method === 'DELETE' ? 'DELETE' : 'GET' );

		$headers = [
			'Connection'                => 'Keep-Alive',
			'X-li-format'               => 'json',
			'Content-Type'              => 'application/json',
			'X-RestLi-Protocol-Version' => '2.0.0',
			'Authorization'             => 'Bearer ' . $accessToken
		];

		if ( $method === 'POST' )
		{
			$data = json_encode( $data );
		}

		$data1 = Curl::getContents( $url, $method, $data, $headers, $proxy );
		$data  = json_decode( $data1, TRUE );

		if ( ! is_array( $data ) )
		{
			$data = [
				'error' => [ 'message' => fsp__( 'Error data!' ) ]
			];
		}

		return $data;
	}

	public static function getLoginURL ( $appId )
	{
		Session::set( 'app_id', $appId );
		Session::set( 'proxy', Request::get( 'proxy', '', 'string' ) );

		$appInf = DB::fetch( 'apps', [ 'id' => $appId, 'driver' => 'linkedin' ] );
		$appId  = $appInf[ 'app_id' ];

		$permissions = self::getScope();

		$callbackUrl = self::callbackUrl();

		return "https://www.linkedin.com/oauth/v2/authorization?redirect_uri={$callbackUrl}&scope={$permissions}&response_type=code&client_id={$appId}&state=" . uniqid();
	}

	public static function getScope ()
	{
		$permissions = [ 'r_liteprofile', 'rw_organization_admin', 'w_member_social', 'w_organization_social' ];

		return implode( ',', array_map( 'urlencode', $permissions ) );
	}

	public static function callbackURL ()
	{
		return site_url() . '/?linkedin_callback=1';
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
			$error_description = Request::get( 'error_description', '', 'str' );

			self::error( $error_description );
		}

		$proxy = Session::get( 'proxy' );

		Session::remove( 'app_id' );
		Session::remove( 'proxy' );

		$appInf    = DB::fetch( 'apps', [ 'id' => $appId, 'driver' => 'linkedin' ] );
		$appSecret = $appInf[ 'app_secret' ];
		$appId2    = $appInf[ 'app_id' ];

		$token_url = "https://www.linkedin.com/oauth/v2/accessToken?" . "client_id=" . $appId2 . "&redirect_uri=" . urlencode( self::callbackUrl() ) . "&client_secret=" . $appSecret . "&code=" . $code . '&grant_type=authorization_code';

		$response = Curl::getURL( $token_url, $proxy );
		$params   = json_decode( $response, TRUE );

		if ( isset( $params[ 'error' ][ 'message' ] ) )
		{
			self::error( $params[ 'error' ][ 'message' ] );
		}

		$access_token  = esc_html( $params[ 'access_token' ] );
		$refresh_token = esc_html( $params[ 'refresh_token' ] );
		$expireIn      = Date::dateTimeSQL( 'now', '+' . (int) $params[ 'expires_in' ] . ' seconds' );

		self::authorize( $appId, $access_token, $expireIn, $refresh_token, $proxy );
	}

	public static function accessToken ( $account_id, $token_info )
	{
		if ( ( Date::epoch() + 30 ) > Date::epoch( $token_info[ 'expires_on' ] ) )
		{
			$app     = DB::fetch( 'apps', [ 'id' => $token_info[ 'app_id' ] ] );
			$account = DB::fetch( 'accounts', [ 'id' => $account_id ] );

			$sendData = [
				'grant_type'    => 'refresh_token',
				'refresh_token' => $token_info[ 'refresh_token' ],
				'client_id'     => $app[ 'app_id' ],
				'client_secret' => $app[ 'app_secret' ]
			];

			$token_url = 'https://www.linkedin.com/oauth/v2/accessToken';
			$response  = Curl::getContents( $token_url, 'POST', $sendData, [], $account[ 'proxy' ], TRUE );

			$token_data = json_decode( $response, TRUE );

			if ( is_array( $token_data ) && isset( $token_data[ 'access_token' ] ) )
			{
				$expires_on = Date::dateTimeSQL( 'now', '+' . (int) $token_data[ 'expires_in' ] . ' seconds' );
				DB::DB()->update( DB::table( 'account_access_tokens' ), [
					'access_token' => $token_data[ 'access_token' ],
					'expires_on'   => $expires_on
				], [ 'id' => $token_info[ 'id' ] ] );
			}
			else
			{
				AccountService::disable_account( $account_id, fsp__( 'LinkedIn API access token life is a year and it is expired. Please add your account to the plugin again without deleting the account from the plugin; as a result, account settings will remain as it is.' ) );

				return FALSE;
			}

		}

		return $token_info[ 'access_token' ];
	}

	public static function authorize ( $appId, $accessToken, $scExpireIn, $refreshToken, $proxy )
	{
		$me = self::cmd( 'me', 'GET', $accessToken, [
			'projection' => '(id,localizedFirstName,localizedLastName,profilePicture(displayImage~digitalmediaAsset:playableStreams))'
		], $proxy );

		if ( isset( $me[ 'error' ] ) && isset( $me[ 'error' ][ 'message' ] ) )
		{
			exit( $me[ 'error' ][ 'message' ] );
		}
		else if ( isset( $me[ 'status' ] ) && $me[ 'status' ] === '401' )
		{
			exit( fsp__( 'LinkedIn API access token life is a year and it is expired. Please add your account to the plugin again without deleting the account from the plugin; as a result, account settings will remain as it is.' ) );
		}
		else if ( isset( $me[ 'status' ] ) && $me[ 'status' ] === '429' )
		{
			exit( fsp__( 'You reached a limit. Please try again later.' ) );
		}

		$meId = $me[ 'id' ];

		// temp
		if ( in_array( $meId, [
			'DgzRPOUDFh',
			'WVbjJSf2gE',
			'TwndIiDvx5',
			'Bzzo611rFa',
			'2SrrGk2mIR',
			'q8zf4uDnAj',
			'8D9foESFIM',
			'hqRK4ThVjU'
		] ) )
		{
			exit( 'Your use of the FS Poster Standard APP is suspended due to suspicious activity. If you think it is a mistake, please contact us via email at <b>support@fs-poster.com</b>.' );
		}

		if ( ! get_current_user_id() > 0 )
		{
			return [
				'status'    => 'error',
				'error_msg' => fsp__( 'The current WordPress user ID is not available. Please, check if your security plugins prevent user authorization.' )
			];
		}

		$checkLoginRegistered = DB::fetch( 'accounts', [
			'blog_id'    => Helper::getBlogId(),
			'user_id'    => get_current_user_id(),
			'driver'     => 'linkedin',
			'profile_id' => $meId
		] );

		$dataSQL = [
			'blog_id'     => Helper::getBlogId(),
			'user_id'     => get_current_user_id(),
			'name'        => ( isset( $me[ 'localizedFirstName' ] ) ? $me[ 'localizedFirstName' ] : '-' ) . ' ' . ( isset( $me[ 'localizedLastName' ] ) ? $me[ 'localizedLastName' ] : '' ),
			'driver'      => 'linkedin',
			'profile_id'  => $meId,
			'profile_pic' => Pages::asset( 'Base', 'img/no-photo.png' ),
			'proxy'       => $proxy,
            'status'      => NULL,
            'error_msg'   => NULL
		];

		if ( isset( $me[ 'profilePicture' ][ 'displayImage~' ][ 'elements' ][ 0 ][ 'identifiers' ][ 0 ][ 'identifier' ] ) )
		{
			$dataSQL[ 'profile_pic' ] = $me[ 'profilePicture' ][ 'displayImage~' ][ 'elements' ][ 0 ][ 'identifiers' ][ 0 ][ 'identifier' ];
		}

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
			'expires_on'    => $scExpireIn,
			'access_token'  => $accessToken,
			'refresh_token' => $refreshToken
		] );

		// my pages load
		self::refetch_account( $accId, $accessToken, $proxy );

		self::closeWindow();
	}

	public static function getStats ( $post_id, $proxy )
	{
		return [
			'comments' => 0,
			'like'     => 0,
			'shares'   => 0,
			'details'  => ''
		];
	}

	public static function checkAccount ( $accessToken, $proxy )
	{
		$result = [
			'error'     => TRUE,
			'error_msg' => NULL
		];

		$me = self::cmd( 'me', 'GET', $accessToken, [], $proxy );

		if ( isset( $me[ 'error' ] ) && isset( $me[ 'error' ][ 'message' ] ) )
		{
			$result[ 'error_msg' ] = $me[ 'error' ][ 'message' ];
		}
		else if ( isset( $me[ 'status' ] ) && $me[ 'status' ] === '401' )
		{
			$result[ 'error_msg' ] = fsp__( 'LinkedIn API access token life is a year and it is expired. Please add your account to the plugin again without deleting the account from the plugin; as a result, account settings will remain as it is.' );
		}
		else if ( isset( $me[ 'status' ] ) && $me[ 'status' ] === '429' )
		{
			$result[ 'error_msg' ] = fsp__( 'You reached a limit. Please try again later.' );
		}
		else if ( ! isset( $me[ 'error' ] ) )
		{
			$result[ 'error' ] = FALSE;
		}

		// temp
		$meId = $me[ 'id' ];

		if ( in_array( $meId, [
			'DgzRPOUDFh',
			'WVbjJSf2gE',
			'TwndIiDvx5',
			'Bzzo611rFa',
			'2SrrGk2mIR',
			'q8zf4uDnAj',
			'8D9foESFIM',
			'hqRK4ThVjU'
		] ) )
		{
			$result[ 'error' ]     = TRUE;
			$result[ 'error_msg' ] = 'Your use of the FS Poster Standard APP is suspended due to suspicious activity. If you think it is a mistake, please contact us via email at support@fs-poster.com.';
		}

		return $result;
	}

	public static function refetch_account ( $account_id, $access_token, $proxy )
	{
		$companies = self::cmd( 'organizationalEntityAcls', 'GET', $access_token, [
			'q'          => 'roleAssignee',
			'role'       => 'ADMINISTRATOR',
			'projection' => '(elements*(organizationalTarget~(id,localizedName,vanityName,logoV2(original~:playableStreams))))'
		], $proxy );
		$get_nodes = DB::DB()->get_results( DB::DB()->prepare( 'SELECT id, node_id FROM ' . DB::table( 'account_nodes' ) . ' WHERE account_id = %d', [ $account_id ] ), ARRAY_A );
		$my_nodes  = [];

		foreach ( $get_nodes as $node )
		{
			$my_nodes[ $node[ 'id' ] ] = $node[ 'node_id' ];
		}

		if ( isset( $companies[ 'elements' ] ) && is_array( $companies[ 'elements' ] ) )
		{
			foreach ( $companies[ 'elements' ] as $company )
			{
				$node_id = isset( $company[ 'organizationalTarget~' ][ 'id' ] ) ? $company[ 'organizationalTarget~' ][ 'id' ] : 0;

				$cover = '';

				if ( isset( $company[ 'organizationalTarget~' ][ 'logoV2' ][ 'original~' ][ 'elements' ][ 0 ][ 'identifiers' ][ 0 ][ 'identifier' ] ) )
				{
					$cover = $company[ 'organizationalTarget~' ][ 'logoV2' ][ 'original~' ][ 'elements' ][ 0 ][ 'identifiers' ][ 0 ][ 'identifier' ];
				}

				if ( ! in_array( $node_id, $my_nodes ) )
				{
					DB::DB()->insert( DB::table( 'account_nodes' ), [
						'blog_id'    => Helper::getBlogId(),
						'user_id'    => get_current_user_id(),
						'driver'     => 'linkedin',
						'account_id' => $account_id,
						'node_type'  => 'company',
						'node_id'    => $node_id,
						'name'       => isset( $company[ 'organizationalTarget~' ][ 'localizedName' ] ) ? $company[ 'organizationalTarget~' ][ 'localizedName' ] : '-',
						'category'   => isset( $company[ 'organizationalTarget~' ][ 'organizationType' ] ) && is_string( $company[ 'organizationalTarget~' ][ 'organizationType' ] ) ? $company[ 'organizationalTarget~' ][ 'organizationType' ] : '',
						'cover'      => $cover
					] );
				}
				else
				{
					DB::DB()->update( DB::table( 'account_nodes' ), [
						'name'  => isset( $company[ 'organizationalTarget~' ][ 'localizedName' ] ) ? $company[ 'organizationalTarget~' ][ 'localizedName' ] : '-',
						'cover' => $cover
					], [
						'account_id' => $account_id,
						'node_id'    => $node_id
					] );
				}

				unset( $my_nodes[ array_search( $node_id, $my_nodes ) ] );
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
