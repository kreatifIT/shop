<?php

/**
 * This file is part of the Simpleshop package.
 *
 * @author FriendsOfREDAXO
 * @author a.platter@kreatif.it
 * @author jan.kristinus@yakamara.de
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FriendsOfREDAXO\Simpleshop;

class Utils
{
    protected static $origLocale = '';

    public static function setCalcLocale()
    {
        self::$origLocale = setlocale(LC_NUMERIC, 0);
        $locales          = \ResourceBundle::getLocales('');

        foreach (['en_US', 'C'] as $_locale) {
            if (in_array($_locale, $locales)) {
                break;
            }
        }
        setlocale(LC_NUMERIC, $_locale);
    }

    public static function resetLocale()
    {
        setlocale(LC_NUMERIC, self::$origLocale);
    }

    public static function log($code, $msg, $type, $send_mail = false)
    {
        $email    = \rex_addon::get('simpleshop')->getProperty('debug_email');
        $log_path = \rex_path::addonData('simpleshop', 'log/');
        $log_file = $log_path . date('d') . '.log';
        $msg      = "{$code}: {$msg}\n";
        $type     = strtoupper($type);

        if (!file_exists($log_path)) {
            \rex_dir::create($log_path, true);
        }
        // save to log file
        $append = (int) date('d') == (int) date('d', @filemtime($log_file)) ? FILE_APPEND : null;
        file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . "] {$type} - " . $msg, $append);

        if ($email && $send_mail) {
            $Mail = new \rex_mailer();
            $Mail->addAddress($email);
            $Mail->isHTML(true);
            $Mail->Subject = "Simpleshop Notice [{$type}]";
            $Mail->Body    = '<div style="font-family:courier;font-size:12px;line-height:14px;width:760px;">' . str_replace("    ", "&nbsp;&nbsp;", nl2br($msg)) . '</div>';
            $Mail->send();
        }
    }

    public static function getImageTag($file, $type, $params = [], $callback = null)
    {
        $imageTag = '<img src="' . \rex_url::media($file) . '" />';
        return \rex_extension::registerPoint(new \rex_extension_point('simpleshop.getImageTag', $imageTag, [
            'file'     => $file,
            'type'     => $type,
            'params'   => $params,
            'callback' => $callback,
        ]));
    }

    public static function getPagination($totalElements, $elementsPerPage, $gets = [], $_params = [])
    {
        $params = array_merge([
            'get_name'             => 'page',
            'get_params'           => [],
            'rex_geturl_params'    => [],
            'pager_elements_count' => 8,
            'use_request_uri'      => false,
            'show_first_link'      => true,
            'show_last_link'       => true,
            'show_prev_link'       => true,
            'show_next_link'       => true,
        ], $_params);

        $pagination  = [];
        $currentPage = rex_get($params['get_name'], "int", 0);

        if ($params['use_request_uri']) {
            $ss         = explode('?', $_SERVER['REQUEST_URI']);
            $paging_url = $ss[0];
        }
        else {
            $ss         = explode('?', rex_getUrl(\rex_article::getCurrentId(), null, $params['rex_geturl_params']));
            $paging_url = $ss[0];

            parse_str($ss[1], $g_params);
            $params['get_params'] = array_merge($params['get_params'], $g_params);
        }
        if ($totalElements > $elementsPerPage) {
            if ($params['show_first_link'] && $currentPage > 0) {
                $params['get_params'][$params['get_name']] = 0;
                $pagination[]                              = [
                    "type"  => "first",
                    "class" => "pagination-first",
                    "text"  => "",
                    "url"   => $paging_url . self::getParamString(array_merge($gets, $params['get_params'])),
                ];
            }
            if ($currentPage > 0 && $params['show_prev_link']) {
                $params['get_params'][$params['get_name']] = $currentPage - 1;
                $pagination[]                              = [
                    "type"  => "prev",
                    "class" => "pagination-previous",
                    "text"  => "",
                    "url"   => $paging_url . self::getParamString(array_merge($gets, $params['get_params'])),
                ];
            }
            for ($i = 1; $i <= (int) ceil($totalElements / $elementsPerPage); $i++) {
                //dots prefix
                if ($i < ($currentPage - floor($params['pager_elements_count'] / 2))) {
                    $i                                         = $currentPage - floor($params['pager_elements_count'] / 2);
                    $params['get_params'][$params['get_name']] = $i - 1;
                    $pagination[]                              = [
                        "type"  => "ellipsis",
                        "class" => "pagination-ellipsis",
                        "text"  => "",
                        "url"   => $paging_url . self::getParamString(array_merge($gets, $params['get_params'])),
                    ];
                }
                //dots suffix
                else if ($i > $currentPage + ceil($params['pager_elements_count'] / 2)) {
                    $params['get_params'][$params['get_name']] = $i - 1;
                    $pagination[]                              = [
                        "type"  => "ellipsis",
                        "class" => "pagination-ellipsis",
                        "text"  => "",
                        "url"   => $paging_url . self::getParamString(array_merge($gets, $params['get_params'])),
                    ];
                    break; //stops iteration
                }
                else {
                    if ($currentPage != $i - 1) {
                        $params['get_params'][$params['get_name']] = $i - 1;
                        $pagination[]                              = [
                            "type"  => "page",
                            "class" => "",
                            "text"  => $i,
                            "url"   => $paging_url . self::getParamString(array_merge($gets, $params['get_params'])),
                        ];
                    }
                    else {
                        $params['get_params'][$params['get_name']] = $i - 1;
                        $pagination[]                              = [
                            "type"  => "active",
                            "class" => "pagination-active current",
                            "text"  => $i,
                            "url"   => $paging_url . self::getParamString(array_merge($gets, $params['get_params'])),
                        ];
                    }
                }
            }
            if (($currentPage + 1) <= ((int) ceil($totalElements / $elementsPerPage) - 1) && $params['show_next_link']) {
                $params['get_params'][$params['get_name']] = $currentPage + 1;
                $pagination[]                              = [
                    "type"  => "next",
                    "class" => "pagination-next",
                    "text"  => "",
                    "url"   => $paging_url . self::getParamString(array_merge($gets, $params['get_params'])),
                ];
            }
            if ($currentPage < ((int) ceil($totalElements / $elementsPerPage) - 1) && $params['show_last_link']) {
                $params['get_params'][$params['get_name']] = ((int) ceil($totalElements / $elementsPerPage) - 1);
                $pagination[]                              = [
                    "type"  => "last",
                    "class" => "pagination-last",
                    "text"  => "",
                    "url"   => $paging_url . self::getParamString(array_merge($gets, $params['get_params'])),
                ];
            }
        }
        return $pagination;
    }

    private static function getParamString($params, $divider = '&amp;')
    {
        $_p = [];
        if (is_array($params)) {
            foreach ($params as $key => $value) {
                $_p[] = urlencode($key) . '=' . urlencode($value);
            }
        }
        elseif ($params != '') {
            $_p[] = $params;
        }
        $string = implode($divider, $_p);
        return strlen($string) ? '?' . $string : '';
    }

    public static function ext_register_tables($params = null)
    {
        $Addon          = \rex_addon::get('simpleshop');
        $sql            = \rex_sql::factory();
        $_table_classes = $Addon->getProperty('table_classes');
        $db_tables      = $sql->getArray("SHOW TABLES", [], \PDO::FETCH_COLUMN);
        $table_classes  = [];

        foreach ($_table_classes as $table => $class) {
            if (in_array($table, $db_tables)) {
                $table_classes[$table] = $class;
            }
        }
        $Addon->setConfig('table_classes', $table_classes);
    }
}