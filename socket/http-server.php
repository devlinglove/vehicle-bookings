<?php

use Workerman\Worker;

require_once __DIR__ . '/vendor/autoload.php';

$http = new Worker('http://127.0.0.1:8081');

$http->count = 1;

$http->onMessage = function ($connection, $request) {

    if (
        $request->method() !== 'POST' ||
        $request->path() !== '/internal/refresh-seats'
    ) {
        $connection->send(
            json_encode([
                'success' => false,
                'message' => 'Not found'
            ])
        );

        return;
    }

    $token = $request->header('X-Internal-Token');

    if ($token !== 'YOUR_SECRET_TOKEN') {

        $connection->send(
            json_encode([
                'success' => false,
                'message' => 'Unauthorized'
            ])
        );

        return;
    }

    $data = json_decode(
        $request->rawBody(),
        true
    );

    if (
        !$data ||
        empty($data['trip_ids'])
    ) {

        $connection->send(
            json_encode([
                'success' => false,
                'message' => 'trip_ids required'
            ])
        );

        return;
    }

    $tripIds = array_map(
        'intval',
        $data['trip_ids']
    );

    /*
     * At this point we know which trips
     * need to be refreshed.
     *
     * But this HTTP worker does NOT have
     * access to the WebSocket connections
     * in websocket.php.
     */

    echo "Refresh requested for trips: "
        . implode(',', $tripIds)
        . "\n";

    $connection->send(
        json_encode([
            'success' => true
        ])
    );
};

Worker::runAll();
