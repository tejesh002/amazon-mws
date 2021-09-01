<?php
namespace MWS;

use Exception;

class MWSEndPoint{

    public static $endpoints = [
        'GetReportList' => [
            'method' => 'POST',
            'action' => 'GetReportList',
            'path' => '/',
            'date' => '2009-01-01'
        ],
        'GetReportRequestList' => [
            'method' => 'POST',
            'action' => 'GetReportRequestList',
            'path' => '/',
            'date' => '2009-01-01'
        ],
        'GetReportListByNextToken' => [
            'method' => 'POST',
            'action' => 'GetReportListByNextToken',
            'path' => '/',
            'date' => '2009-01-01'
        ],
        'GetReport' => [
            'method' => 'POST',
            'action' => 'GetReport',
            'path' => '/',
            'date' => '2009-01-01'
        ]
    ];

    public static function get($key)
    {
        if (isset(self::$endpoints[$key])) {
            return self::$endpoints[$key];
        } else {
            throw new Exception('Call to undefined endpoint ' . $key);
        }
    }
}
