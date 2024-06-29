<?php
namespace Snoke\Websocket\Ratchet\Http;
use Snoke\Websocket\Ratchet\ConnectionInterface;
use GuzzleHttp\Psr7\Message;
use GuzzleHttp\Psr7\Response;

trait CloseResponseTrait {
    private function close(ConnectionInterface $conn, $code = 400, array $additional_headers = []) {
        $response = new Response($code, array_merge([
            'X-Powered-By' => \Ratchet\VERSION
        ], $additional_headers));

        $conn->send(Message::toString($response));
        $conn->close();
    }
}
