<?php
class Helper_Http
{
    public static function redirect($redirect_uri, $code = 302)
    {
        header("Location: {$redirect_uri}", TRUE, $code);
        exit;
    }

    public static function clientIp()
    {
        $ip = NULL;
        if (getenv('HTTP_CLIENT_IP')) {
            $ip = getenv('HTTP_CLIENT_IP');
        }
        elseif (getenv('HTTP_X_FORWARDED_FOR')) {
            $ip = getenv('HTTP_X_FORWARDED_FOR');
        }
        elseif (getenv('HTTP_X_FORWARDED')) {
            $ip = getenv('HTTP_X_FORWARDED');
        }
        elseif (getenv('HTTP_FORWARDED_FOR')) {
            $ip = getenv('HTTP_FORWARDED_FOR');
        }
        elseif (getenv('HTTP_FORWARDED')) {
            $ip = getenv('HTTP_FORWARDED');
        }
        else {
            $ip = @$_SERVER['REMOTE_ADDR'];
        }
        if ($pos=strpos($ip, ',')){
            $ip = substr($ip,0,$pos);
        }

        return $ip;
    }
}
