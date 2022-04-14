<?php

namespace FSPoster\App\Providers;

abstract class SocialNetwork
{
	abstract public static function callbackURL ();

	protected static function error ( $message = '', $esc_html = TRUE )
	{
		if ( empty( $message ) )
		{
			$message = fsp__( 'An error occurred while processing your request! Please close the window and try again!' );
		}

        $message = $esc_html === TRUE ? esc_html( $message ) : $message;

		echo '<div>' . $message . '</div>';

		?>
		<script type="application/javascript">
			if ( typeof window.opener.accountAdded === 'function' )
			{
				window.opener.FSPoster.alert( "<?php echo addslashes($message); ?>" );
				window.close();
			}
		</script>
		<?php

		exit();
	}

	protected static function closeWindow ()
	{
		echo '<div>' . fsp__( 'Loading...' ) . '</div>';

		?>
		<script type="application/javascript">
			if ( typeof window.opener.accountAdded === 'function' )
			{
				window.opener.accountAdded();
				window.close();
			}
		</script>
		<?php

		exit;
	}
}