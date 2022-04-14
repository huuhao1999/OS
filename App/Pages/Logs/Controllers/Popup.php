<?php

namespace FSPoster\App\Pages\Logs\Controllers;

use FSPoster\App\Providers\Pages;
use FSPoster\App\Providers\Request;

trait Popup
{
    public function logs_filter()
    {
        $filter_results = Request::post( 'filter_results', 'all', 'string', [ 'all', 'error', 'ok' ] );
        $sn             = Request::post( 'sn_filter', 'all', 'string', [ 'all', 'fb', 'twitter', 'instagram', 'linkedin', 'vk', 'pinterest', 'reddit', 'tumblr', 'ok', 'plurk', 'google_b', 'blogger', 'telegram', 'medium', 'wordpress' ] );

        Pages::modal( 'Logs', 'logs_filter', [
            'filter_results' => $filter_results,
            'sn_filter'      => $sn
        ] );
    }
}