<?php
/*
 * Plugin Name: FS Poster
 * Description: FS Poster gives you a great opportunity to auto-publish WordPress posts on Facebook, Instagram, Twitter, Linkedin, Pinterest, Google My Business, Telegram, Reddit, Tumblr, VK, OK.ru, Telegram, Medium, Blogger, Plurk and WordPress based sites automatically.
 * Version: 5.3.0
 * Author: FS-Code
 * Author URI: https://www.fs-code.com
 * License: Commercial
 * Text Domain: fs-poster
 */

namespace FSPoster;

use FSPoster\App\Providers\Bootstrap;

defined( 'ABSPATH' ) or exit;

require_once __DIR__ . '/vendor/autoload.php';

new Bootstrap();
