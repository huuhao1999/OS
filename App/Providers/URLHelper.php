<?php

namespace FSPoster\App\Providers;

trait URLHelper
{
	/**
	 * @param $url
	 *
	 * @return string
	 */
	public static function shortenerURL ( $url, $node_type, $node_id )
	{
		if ( ! Helper::getCustomSetting( 'url_shortener', '0', $node_type, $node_id ) )
		{
			return $url;
		}

		$shortener_service = Helper::getCustomSetting( 'shortener_service', '', $node_type, $node_id );

		if ( $shortener_service === 'tinyurl' )
		{
			return self::shortURLtinyurl( $url );
		}
		else if ( $shortener_service === 'bitly' )
		{
			return self::shortURLbitly( $url, $node_type, $node_id );
		}

		return $url;
	}

	/**
	 * @param $url
	 *
	 * @return string
	 */
	public static function shortURLtinyurl ( $url )
	{
		if ( empty( $url ) )
		{
			return $url;
		}

        $shortenURL = Curl::getURL( 'https://tinyurl.com/api-create.php?url=' . urlencode( $url ) );

		return filter_var( $shortenURL, FILTER_VALIDATE_URL ) ? $shortenURL : $url ;
	}

	/**
	 * @param $url
	 *
	 * @return string
	 */
	public static function shortURLbitly ( $url, $nodeType, $nodeId )
	{
		$params = [
			'access_token' => Helper::getCustomSetting( 'url_short_access_token_bitly', '', $nodeType, $nodeId ),
			'longUrl'      => $url
		];

		if ( empty( $url ) || empty( $params[ 'access_token' ] ) )
		{
			return $url;
		}

		$requestUrl = 'https://api-ssl.bit.ly/v3/shorten?' . http_build_query( $params );

		$result = json_decode( Curl::getURL( $requestUrl ), TRUE );

		return ! empty( $result[ 'data' ][ 'url' ] ) ? $result[ 'data' ][ 'url' ] : $url;
	}

	/**
	 * @param $post_id
	 * @param $driver
	 * @param string $username
	 *
	 * @return string
	 */
	public static function postLink ( $post_id, $driver, $username = '' )
	{
		if ( $driver === 'fb' )
		{
			return 'https://fb.com/' . $post_id;
		}
		else if ( $driver === 'plurk' )
		{
			return 'https://plurk.com/p/' . base_convert( $post_id, 10, 36 );
		}
		else if ( $driver === 'twitter' )
		{
			return 'https://twitter.com/' . $username . '/status/' . $post_id;
		}
		else if ( $driver === 'instagram' )
		{
			return 'https://www.instagram.com/p/' . $post_id . '/';
		}
		else if ( $driver === 'instagramstory' )
		{
			return 'https://www.instagram.com/stories/' . $username . '/';
		}
		else if ( $driver === 'linkedin' )
		{
			return 'https://www.linkedin.com/feed/update/' . $post_id . '/';
		}
		else if ( $driver === 'vk' )
		{
			return 'https://vk.com/wall' . $post_id;
		}
		else if ( $driver === 'pinterest' )
		{
			return 'https://www.pinterest.com/pin/' . $post_id;
		}
		else if ( $driver === 'reddit' )
		{
			return 'https://www.reddit.com/' . $post_id;
		}
		else if ( $driver === 'tumblr' )
		{
			return 'https://' . $username . '.tumblr.com/post/' . $post_id;
		}
		else if ( $driver === 'ok' )
		{
			if ( strpos( $post_id, 'topic' ) !== FALSE )
			{
				return 'https://ok.ru/group/' . $post_id;
			}
			else
			{
				return 'https://ok.ru/profile/' . $post_id;
			}
		}
		else if ( $driver === 'google_b' )
		{
			if ( ! empty( $username ) )
			{
                if( strpos( $username, '/' ) )
                {
                    $node_id = explode('/', esc_html( $username ) );
                    $node_id = isset( $node_id[ 3 ] ) ? $node_id[ 3 ] : '';
                }
                else
                {
                    $node_id = $username;
                }

				$getPostType = explode( ':', $post_id );

				if ( isset( $getPostType[ 1 ] ) && $getPostType[ 1 ] === 'product' )
				{
					return 'https://business.google.com/products/l/' . esc_html( $node_id );
				}

				return 'https://business.google.com/posts/l/' . esc_html( $node_id );
			}

			return 'https://local.google.com/place?use=posts&lspid=' . esc_html( $post_id );
		}
		else if ( $driver === 'blogger' )
		{
			return $username;
		}
		else if ( $driver === 'telegram' )
		{
			return "http://t.me/" . esc_html( $username );
		}
		else if ( $driver === 'medium' )
		{
			return "https://medium.com/p/" . esc_html( $post_id );
		}
		else if ( $driver === 'wordpress' )
		{
			return rtrim( $username, '/' ) . '/?p=' . $post_id;
		}
	}
}
