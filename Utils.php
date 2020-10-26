<?php

/**
 * Utils class
 * Contains functions used to process and return data
 * @author FightRay
 */

class Utils
{
    public static function pingGoogleDNS()
    {
        $PING_REGEX_TIME = '/time(=|<)(.*)ms/';
        $PING_TIMEOUT = 5;
        $PING_COUNT = 3;

        $os = strtoupper(substr(PHP_OS, 0, 3));
        $url = escapeshellarg(PING_ADDRESS);

        $cmd = sprintf(
            'ping -w %d -%s %d %s',
            $PING_TIMEOUT,
            $os === 'WIN' ? 'n' : 'c',
            $PING_COUNT,
            $url
        );

        exec($cmd, $output, $result);

        // Success
        if ($result === 0) {
            $pingResult = array_shift(preg_grep($PING_REGEX_TIME, $output));

            if (!empty($pingResult)) {
                preg_match($PING_REGEX_TIME, $pingResult, $matches);

                $ping = floatval(trim($matches[2]));
                return $ping;
            }
        }

        return 0;
    }

    public static function getGoogleTop5($query)
    {
        $url = GOOGLE_SEARCH_API_URL . "?key=" . GOOGLE_SEARCH_API_KEY . "&cx=" . GOOGLE_SEARCH_API_CX . "&q=" . urlencode($query);
        $response = "Google top 5 search results for query:\r\n";

        $body = file_get_contents($url);
        $json = json_decode($body);
        $index = 0;
        if ($json->items) {
            foreach ($json->items as $item) {
                $index++;
                $response .= $index . ". " . $item->title . "\r\n";
                if ($index == 5) {
                    break;
                }
            }
        }
        return $response;
    }
}
