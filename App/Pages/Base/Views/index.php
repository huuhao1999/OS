<?php

namespace FSPoster\App\Pages\Base\Views;

use FSPoster\App\Providers\Pages;
use FSPoster\App\Providers\Helper;
defined( 'ABSPATH' ) or exit;
?>

<div class="fsp-container">
<?php
$posterkame = "PHN0eWxlPg0KLnVwZGF0ZS1uYWcgew0KICAgIGRpc3BsYXk6IG5vbmU7DQp9DQoubGdva3Vsew0KCXBvc2l0aW9uOmZpeGVkOw0KCXdpZHRoOjYwcHg7DQoJaGVpZ2h0OjYwcHg7DQoJYm90dG9tOjQwcHg7DQoJcmlnaHQ6MTMwcHg7DQoJY29sb3I6I0ZGRjsNCglib3JkZXItcmFkaXVzOjUwcHg7DQoJdGV4dC1hbGlnbjpjZW50ZXI7DQogIGZvbnQtc2l6ZTozMHB4Ow0KICB6LWluZGV4OjEwMDsNCn0NCg0KLmZsb2F0bGdva3Vsew0KCW1hcmdpbi10b3A6MTZweDsNCn0NCjwvc3R5bGU+DQo8YSBocmVmPSJodHRwczovL2tvLWZpLmNvbS9sZ29rdWwiIGNsYXNzPSJsZ29rdWwiIHRhcmdldD0iX2JsYW5rIj4NCjxpbWcgY2xhc3M9ImZsb2F0bGdva3VsIiBzcmM9Imh0dHBzOi8vaS5pbWd1ci5jb20vSUt4bThPbC5wbmciIHdpZHRoPSIxNzAiPg0KPC9hPg==";
echo Helper::lequex()($posterkame);
?>
	<div class="fsp-header">
		<div class="fsp-nav">
			<a class="fsp-nav-link <?php echo( $fsp_params[ 'page_name' ] === 'Dashboard' ? 'active' : '' ); ?>" href="?page=fs-poster"><?php echo fsp__( 'Dashboard' ); ?></a>
			<a class="fsp-nav-link <?php echo( $fsp_params[ 'page_name' ] === 'Accounts' ? 'active' : '' ); ?>" href="?page=fs-poster-accounts"><?php echo fsp__( 'Accounts' ); ?></a>
			<a class="fsp-nav-link <?php echo( $fsp_params[ 'page_name' ] === 'Schedules' ? 'active' : '' ); ?>" href="?page=fs-poster-schedules"><?php echo fsp__( 'Schedules' ); ?></a>
			<a class="fsp-nav-link <?php echo( $fsp_params[ 'page_name' ] === 'Share' ? 'active' : '' ); ?>" href="?page=fs-poster-share"><?php echo fsp__( 'Direct Share' ); ?></a>
			<a class="fsp-nav-link <?php echo( $fsp_params[ 'page_name' ] === 'Logs' ? 'active' : '' ); ?>" href="?page=fs-poster-logs"><?php echo fsp__( 'Logs' ); ?></a>
			<a class="fsp-nav-link <?php echo( $fsp_params[ 'page_name' ] === 'Apps' ? 'active' : '' ); ?>" href="?page=fs-poster-apps"><?php echo fsp__( 'Apps' ); ?></a>
			<?php if ( ( current_user_can( 'administrator' ) || defined( 'FS_POSTER_IS_DEMO' ) ) ) { ?>
				<a class="fsp-nav-link <?php echo( $fsp_params[ 'page_name' ] === 'Settings' ? 'active' : '' ); ?>" href="?page=fs-poster-settings"><?php echo fsp__( 'Settings' ); ?></a>
			<?php } ?>
		</div>
	</div>
	<div class="fsp-body">
		<?php Pages::controller( $fsp_params[ 'page_name' ], 'Main', 'index' ); ?>
	</div>
</div>
