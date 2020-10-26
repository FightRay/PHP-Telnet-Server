<?php

/**
 * CommandHandler class
 * Handles and responds to client commands
 * @author FightRay
 */

class CommandHandler
{
    public static function Handle(&$server, &$client, &$key, $cmd)
    {
        $splitat = strpos($cmd, ' ');
        $cmd_header = $splitat > -1 ? substr($cmd, 0, $splitat) : $cmd;

        switch ($cmd_header) {
            case "help": {
                    $response = "List of available commands:\r\n";
                    $response .= "/disk -> Show both total and free disk space available on the server in bytes\r\n";
                    $response .= "/pingavg -> Test average ping to 8.8.8.8 on the server\r\n";
                    $response .= "/google [text] -> Search for top 5 results on google (shows their titles)\r\n";
                    $response .= "/online -> Show client online count and the list of their IP addresses\r\n";
                    $response .= "/quit -> Disconnect from the server\r\n";
                    $response .= "/shutdown -> Send a shutdown request to the server\r\n";
                    $response .= "** Other than the available commands, you can freely send messages for all other connected clients to see.\r\n\r\n";
                    $server->sendAll($response);
                }
                break;
            case "quit": {
                    $server->disconnect($key);
                    $response = "Client $key has disconnected from the server.\r\n\r\n";
                    echo $response;
                    $server->sendAll($response);
                }
                break;
            case "shutdown": {
                    $server->shutdown();
                }
                break;
            case "online": {
                    $response = "Online clients: " . $server->getOnlineCount() . "\r\n";
                    foreach ($server->getAllClientKeys() as $keyc) {
                        $response .= $keyc . "\r\n";
                    }
                    $response .= "\r\n";
                    $server->send($client, $response);
                }
                break;
            case "disk": {
                    $response = "Total disk space:\t" . number_format(disk_total_space("/")) . "\tbytes\r\n" . "Free disk space:\t" . number_format(disk_free_space("/")) . "\tbytes\r\n\r\n";
                    $server->send($client, $response);
                }
                break;
            case "pingavg": {
                    $response = "Pinging 8.8.8.8, please wait...\r\n";
                    $server->send($client, $response);

                    $response = "Average ping to 8.8.8.8: " . Utils::pingGoogleDNS() . "ms\r\n\r\n";
                    $server->send($client, $response);
                }
                break;
            case "google": {
                    $query = substr($cmd, strpos($cmd, ' ') + 1);
                    $response = Utils::getGoogleTop5($query);
                    $response .= "\r\n";
                    $server->send($client, $response);
                }
                break;
            default:
                $response = "Command \"" . $cmd . "\" does not exist. Please type \"/help\" for details about the available commands.\r\n";
                $server->send($client, $response);
                break;
        }
    }
}
