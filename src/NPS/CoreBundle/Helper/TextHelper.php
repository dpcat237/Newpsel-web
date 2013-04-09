<?php
namespace NPS\CoreBundle\Helper;

use Symfony\Component\Templating\Helper\Helper;

/**
 * Class for time functions
 */
class TextHelper extends Helper
{
    public $name = 'TextHelper';

    /**
     * Returns the canonical name of this helper.
     *
     * @return string The canonical name
     *
     * @api
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Fix url
     * @param string $url
     *
     * @return array
     */
    public static function fixUrl($url)
    {
        if (strpos($url, '://') === false) {
            $url = 'http://' . $url;
        } else if (substr($url, 0, 5) == 'feed:') {
            $url = 'http:' . substr($url, 5);
        }

        //prepend slash if the URL has no slash in it
        // "http://www.example" -> "http://www.example/"
        if (strpos($url, '/', strpos($url, ':') + 3) === false) {
            $url .= '/';
        }

        if ($url != "http:///")
            return $url;
        else
            return '';
    }

    /**
     * Validete feed's url
     * @param string $url
     *
     * @return array
     */
    public static function validateFeedUrl($url)
    {
        $parts = parse_url($url);

        return ($parts['scheme'] == 'http' || $parts['scheme'] == 'feed' || $parts['scheme'] == 'https');
    }

    /**
     * Fetch file contents
     * @param string $url
     * @param bool $type
     * @param bool $login
     * @param bool $pass
     * @param bool $postQuery
     * @param bool $timeout
     * @param int $timestamp
     *
     * @return array
     */
    public static function fetchFileContents($url, $type = false, $login = false, $pass = false, $postQuery = false, $timeout = false, $timestamp = 0)
    {
        $error = null;
        if (function_exists('curl_init') && !ini_get("open_basedir")) {
            if (ini_get("safe_mode")) {
                $ch = curl_init(geturl($url));
            } else {
                $ch = curl_init($url);
            }

            if ($timestamp) {
                curl_setopt($ch, CURLOPT_HTTPHEADER, array("If-Modified-Since: ".gmdate('D, d M Y H:i:s \G\M\T', $timestamp)));
            }

            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout ? $timeout : 15);
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeout ? $timeout : 45);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, !ini_get("safe_mode"));
            curl_setopt($ch, CURLOPT_MAXREDIRS, 20);
            curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
            curl_setopt($ch, CURLOPT_USERAGENT, 'user_agent');
            curl_setopt($ch, CURLOPT_ENCODING , "gzip");
            curl_setopt($ch, CURLOPT_REFERER, $url);

            if ($postQuery) {
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $postQuery);
            }

            if ($login && $pass) {
                curl_setopt($ch, CURLOPT_USERPWD, "$login:$pass");
            }

            $contents = @curl_exec($ch);

            if (curl_errno($ch) === 23 || curl_errno($ch) === 61) {
                curl_setopt($ch, CURLOPT_ENCODING, 'none');
                $contents = @curl_exec($ch);
            }

            if ($contents === false) {
                $error = curl_errno($ch) . " " . curl_error($ch);
                curl_close($ch);
                return false;
            }

            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

            if ($http_code != 200 || $type && strpos($content_type, "$type") === false) {
                if (curl_errno($ch) != 0) {
                    $error = curl_errno($ch) . " " . curl_error($ch);
                } else {
                    $error = "HTTP Code: $http_code";
                }
                curl_close($ch);
                return false;
            }

            curl_close($ch);
            $return['error'] = $error;
            $return['contents'] = $contents;

            return $return;
        } else {
            //TODO: review
            /*if ($login && $pass){
                $url_parts = array();

                preg_match("/(^[^:]*):\/\/(.*)/", $url, $url_parts);

                $pass = urlencode($pass);

                if ($url_parts[1] && $url_parts[2]) {
                    $url = $url_parts[1] . "://$login:$pass@" . $url_parts[2];
                }
            }

            $data = @file_get_contents($url);

            @$gzdecoded = gzdecode($data);
            if ($gzdecoded) $data = $gzdecoded;

            if (!$data && function_exists('error_get_last')) {
                $error = error_get_last();
                $fetch_last_error = $error["message"];
            }

            return $data;*/
        }
    }

    /**
     * Is HTML
     * @param text $content
     *
     * @return boolean
     */
    public static function isHtml($content)
    {
        return preg_match("/<html|DOCTYPE html/i", substr($content, 0, 20)) !== 0;
    }
}
