<?php

use think\facade\Db;
use Overtrue\Pinyin\Pinyin;
use think\facade\Request;

/*
     * return : plaintext or array
     * */
function httpRequest($url, $method = 'GET', $param = array())
{
    $post = null;
    switch ($method) {
        case 'GET':
            $url = $url . '?' . http_build_query($param);
            break;
        case 'POST':
            $post = $param;
            break;
    }
    $response = send($url, 0, $post, null, null, null, 10);
    $json = json_decode($response, true);
    return $json == null ? $response : $json;
}

/*
     * send http request
     * @param: url
     * @post: string or array
     * @cookie: string, e.g. "key1=xx; key2=xx"
     * @return: mixed
     * @usage:
     *  send get request: request($url.'?'.http_build_query($get_array))
     *  send post request: request($url, 0, $post_array)
     */
function send($url, $limit = 0, $post = array(), $head = array(), $cookie = '', $timeout = 10)
{
    $return = '';
    $matches = parse_url($url);
    $scheme = $matches['scheme'];
    $host = $matches['host'];
    $path = $matches['path'] ? $matches['path'] . (@$matches['query'] ? '?' . $matches['query'] : '') : '/';
    $is_https = preg_match("/^https:\/\/.*/", $url);
    $port = !empty($matches['port']) ? $matches['port'] : ($is_https ? 443 : 80);

    if (function_exists('curl_init') && function_exists('curl_exec')) {
        $head['Host'] = $host;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $head);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        if ($post) {
            $content = is_array($post) ? http_build_query($post) : $post;
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, urldecode($content));
        }
        if ($cookie) {
            curl_setopt($ch, CURLOPT_COOKIE, $cookie);
        }
        if ($is_https) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            //curl_setopt($ch, CURLOPT_SSLVERSION, 3);
        }
        $data = curl_exec($ch);
        $info = curl_getinfo($ch);
        $errno = curl_errno($ch);
        curl_close($ch);
        if ($errno || $info['http_code'] != 200) {
            $error = array('errno' => $errno, 'url' => $url, '$info' => $info);
            return $error;
        } else {
            return !$limit ? $data : substr($data, 0, $limit);
        }
    }
    // no cURL, use fsockopen/pfsockopen instead
    $errno = 0;
    $errstr = '';
    if ($post) {
        $content = is_array($post) ? urldecode(http_build_query($post)) : $post;
        $out = "POST $path HTTP/1.0\r\n";
        $header = "Accept: */*\r\n";
        $header .= "Accept-Language: zh-cn\r\n";
        $header .= "Content-Type: application/x-www-form-urlencoded\r\n";
        $header .= "User-Agent: " . @$_SERVER['HTTP_USER_AGENT'] . "\r\n";
        $header .= "Host: $host:$port\r\n";
        $header .= 'Content-Length: ' . strlen($content) . "\r\n";
        $header .= "Connection: Close\r\n";
        $header .= "Cache-Control: no-cache\r\n";
        $header .= "Cookie: $cookie\r\n\r\n";
        $out .= $header . $content;
    } else {
        $out = "GET $path HTTP/1.0\r\n";
        $header = "Accept: */*\r\n";
        $header .= "Accept-Language: zh-cn\r\n";
        $header .= "User-Agent: " . @$_SERVER['HTTP_USER_AGENT'] . "\r\n";
        $header .= "Host: $host:$port\r\n";
        $header .= "Connection: Close\r\n";
        $header .= "Cookie: $cookie\r\n\r\n";
        $out .= $header;
    }
    $fpflag = 0;
    if (function_exists('fsockopen')) {
        $fp = @fsockopen($host, $port, $errno, $errstr, $timeout);
    } elseif (function_exists('pfsockopen')) {
        $fp = @pfsockopen($host, $port, $errno, $errstr, $timeout);
    } else {
        $fp = false;
    }
    if (!$fp) {
        return '';
    } else {
        stream_set_blocking($fp, true);
        stream_set_timeout($fp, $timeout);
        @fwrite($fp, $out);
        $status = stream_get_meta_data($fp);
        if (!$status['timed_out']) {
            while (!feof($fp) && !$fpflag) {
                if (($header = @fgets($fp)) && ($header == "\r\n" ||  $header == "\n")) {
                    break;
                }
            }
            if ($limit) {
                $return = stream_get_contents($fp, $limit);
            } else {
                $return = stream_get_contents($fp);
            }
        }
        @fclose($fp);
        return $return;
    }
}
