<?php
require_once("./Utils.php");
require_once("./CommandHandler.php");

/**
 * Server class
 * All server logic and socket handling happens here
 * @author FightRay
 */

class Server
{
    private $accepting;
    private $end;
    private $sock;
    private $clients;

    function __construct()
    {
        $this->clients = [];
        $this->accepting = true;
        $this->end = false;
    }

    public function InitializeSocket()
    {
        $this->sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        $binded = socket_bind($this->sock, BIND_ADDRESS, BIND_PORT);
        $listening = socket_listen($this->sock, 5);

        if ($this->sock === false) {
            echo "Could not create socket.\r\n" . socket_strerror(socket_last_error()) . "\r\n";
        }

        if ($binded  === false) {
            echo "Could not bind socket to address/port.\r\n" . socket_strerror(socket_last_error($this->sock)) . "\r\n";
        }

        if ($listening === false) {
            echo "Could not initiate socket listen.\r\n" . socket_strerror(socket_last_error($this->sock)) . "\r\n";
        }

        if ($this->sock !== false && $binded && $listening) {
            echo "Listening on port " . BIND_PORT . "\r\n";
            $this->serviceStart();
        } else {
            unset($this->sock);
        }
    }

    private function checkServerCommand()
    {
        fscanf(STDIN, "%c", $action);
        switch ($action) {
            case 'q':
                echo "\r\nUntil next time\r\n";
                socket_close($this->sock);
                exit;
                break;
            case 'p':
                $this->accepting = false;
                echo "\r\nServer has stopped accepting incoming connections. Type 'r' to resume.\r\n";
                break;
            case 'r':
                $this->accepting = true;
                echo "\r\nServer is now accepting incoming connections.\r\n";
                break;
            default:
                break;
        }
        unset($action);
    }

    public function sendAll($message, $exceptfor = "")
    {
        foreach ($this->clients as $keyTo => $clientTo) {
            if ($keyTo == $exceptfor) {
                continue;
            }
            socket_write($clientTo, $message, strlen($message));
        }
    }

    function send($client, $message)
    {
        socket_write($client, $message, strlen($message));
    }

    public function getAllClientKeys()
    {
        return array_keys($this->clients);
    }

    public function getOnlineCount()
    {
        return sizeof($this->clients);
    }

    public function getSocket()
    {
        return $this->sock;
    }

    public function disconnect($key)
    {
        socket_close($this->clients[$key]);
        unset($this->clients[$key]);
    }

    public function shutdown()
    {
        $this->accepting = false;
        $this->end = true;
    }

    private function serviceStart()
    {
        while (true) {
            $this->checkServerCommand();

            $read = array();
            $read[] = $this->sock;

            $read = array_merge($read, $this->clients);
            $write = [];
            $except = [];
            $tv_sec = 1;

            // Set up a blocking call to socket_select
            if (socket_select($read, $write, $except, $tv_sec) < 1)
                continue;

            // Accept (or await, in case server is paused) new connections
            if (in_array($this->sock, $read) && $this->accepting) {
                if (($client = socket_accept($this->sock)) === false) {
                    echo "Could not socket accept.\r\n" . socket_strerror(socket_last_error($this->sock)) . "\r\n";
                    continue;
                }

                // Fetch the client's remote IP address and port
                socket_getpeername($client, $ip, $port);
                $remote_address = $ip . ':' . $port;

                echo "Accepted connection from $remote_address.\r\n";

                $this->clients[$remote_address] = $client;
                $this->sendAll("Client $remote_address has connected to the server.\r\n\r\n", $remote_address);

                /* Welcome message & command list */
                $msg = "\r\nWelcome to the Kenshoo PHP Telnet Server by FightRay. \r\n" .
                    "You can talk to all other connected clients here.\r\n" .
                    "Available commands: /disk | /pingavg | /google [text].\r\n" .
                    "Extra commands: /online | /quit | /shutdown.\r\n" .
                    "Type \"/help\" for a detailed explanation on all available commands.\r\n" .
                    "Clients online: " . sizeof($this->clients) . "\r\n\r\n";

                $this->send($client, $msg);
                socket_read($client, 2048, PHP_BINARY_READ); // Read headers to clean garbage from socket
            }

            // Read socket for new messages for all connected clients
            foreach ($this->clients as $key => $client) {
                if (in_array($client, $read)) {
                    $buf = socket_read($client, 2048, PHP_NORMAL_READ);
                    if ($buf === false) {
                        $errno = socket_last_error($client);
                        if ($errno == 104) {
                            socket_close($client);
                            unset($this->clients[$key]);
                            echo "Client $key has disconnected from the server.\r\n";
                        } else {
                            echo "Could not read socket.\r\n" . socket_strerror($errno) . "\r\n";
                        }
                        continue;
                    }

                    if (!$buf = trim($buf)) {
                        continue;
                    }

                    echo $key . " -> ";
                    var_dump($buf);

                    if ($buf[0] == '/') {
                        $cmd = substr($buf, 1);
                        CommandHandler::Handle($this, $client, $key, $cmd);
                    } else {
                        // Send message to everyone
                        $response = $key . " said: " . $buf . "\r\n\r\n";
                        $this->sendAll($response, $key);
                        $response = "Your message was successfully received and sent to the other participants.\r\n\r\n";
                        $this->send($client, $response);
                    }
                }
            }
            if ($this->end) {
                break;
            }
        }

        socket_close($this->sock);
    }
}
