<?php
require_once("./Config.php");
require_once("./Server.php");

/**
 * Application class
 * This is where the main routine starts
 * @author FightRay
 */

class Application
{
    private $server;
    function __construct()
    {
        stream_set_blocking(STDIN, false);
        error_reporting(E_ERROR | E_PARSE);
        set_time_limit(0);
        ob_implicit_flush();
    }

    public function Init()
    {
        echo "Kenshoo PHP Telnet Server has started\r\n";
        echo "Author: FightRay\r\n";
        echo "Actions: q - Shut Down | p - Pause/Block | r - Resume/Accept\r\n";
        $this->server = new Server();
        $this->server->InitializeSocket();
    }
}
