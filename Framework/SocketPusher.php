<?php

namespace Framework; 

class SocketPusher
{
    private const HOST = 'tcp://127.0.0.1:5678';

    public static function broadcast($data): bool
    {
        return self::push(['data' => $data]);
    }

    public static function toUser($uid, $data): bool
    {
        return self::push(['uid' => $uid, 'data' => $data]);
    }

    private static function push(array $message): bool
    {
        $client = @stream_socket_client(self::HOST, $errno, $errstr, 1);
        if (!$client) {
            error_log("WebSocket server unreachable: $errstr");
            return false;
        }

        fwrite($client, json_encode($message) . "\n");
        $response = fgets($client);
        fclose($client);

        return trim((string) $response) === 'ok';
    }
}