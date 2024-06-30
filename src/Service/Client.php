<?php
namespace Snoke\Websocket\Service;
use Exception;
use React\EventLoop\Loop;
use React\Socket\ConnectionInterface;
use React\Socket\Connector;

class Client
{
    private function performHandshake(ConnectionInterface $connection) {
        // Perform WebSocket handshake
        $headers = "GET / HTTP/1.1\r\n";
        $headers .= "Host: localhost\r\n";
        $headers .= "Upgrade: websocket\r\n";
        $headers .= "Connection: Upgrade\r\n";
        $headers .= "Sec-WebSocket-Key: " . base64_encode(random_bytes(16)) . "\r\n";
        $headers .= "Sec-WebSocket-Version: 13\r\n\r\n";
        $connection->write($headers);
    }
    public function connect(string $host = '127.0.0.1', int $port = 8080, string $message) {

        $loop = Loop::get();
        $connector = new Connector($loop);

        $connector->connect($host.':' . $port)->then(function (ConnectionInterface $connection) use ($message)
        {
            $this->performHandshake($connection);

            $connection->on('data', function ($data) use ($connection, $message) {

                if (strpos($data, 'HTTP/1.1 101') === 0) {
                    $connection->write($this->mask(json_encode(['type' => 'login', 'payload' => ["identifier" => "john@doe.com","password" => "test"]])));
                    $connection->write($this->mask(json_encode(['type' => 'server', 'payload' => $message])));
                } else {
                    $decodedMessage = $this->unmask($data);
                    echo "Received: $decodedMessage\n";
                    $connection->close();
                }
            });

            $connection->on('close', function () {
            });
        }, function (Exception $e) use ($loop) {
            echo "Could not connect: {$e->getMessage()}\n";
            $loop->stop();
        });


        $loop->run();
    }
    private function unmask($payload)
    {
        $length = ord($payload[1]) & 127;

        if ($length == 126) {
            $masks = substr($payload, 4, 4);
            $data = substr($payload, 8);
        } elseif ($length == 127) {
            $masks = substr($payload, 10, 4);
            $data = substr($payload, 14);
        } else {
            $masks = substr($payload, 2, 4);
            $data = substr($payload, 6);
        }

        $text = '';
        for ($i = 0; $i < strlen($data); ++$i) {
            $text .= $data[$i] ^ $masks[$i % 4];
        }
        return $text;
    }
    private function mask($payload, $type = 'text', $masked = true)
    {
        $frameHead = array();
        $payloadLength = strlen($payload);

        switch ($type) {
            case 'text':
                $frameHead[0] = 129;
                break;

            case 'close':
                $frameHead[0] = 136;
                break;

            case 'ping':
                $frameHead[0] = 137;
                break;

            case 'pong':
                $frameHead[0] = 138;
                break;
        }

        if ($payloadLength > 65535) {
            $payloadLengthBin = str_split(sprintf('%064b', $payloadLength), 8);
            $frameHead[1] = ($masked === true) ? 255 : 127;
            for ($i = 0; $i < 8; $i++) {
                $frameHead[$i + 2] = bindec($payloadLengthBin[$i]);
            }
            if ($frameHead[2] > 127) {
                return;
            }
        } elseif ($payloadLength > 125) {
            $payloadLengthBin = str_split(sprintf('%016b', $payloadLength), 8);
            $frameHead[1] = ($masked === true) ? 254 : 126;
            $frameHead[2] = bindec($payloadLengthBin[0]);
            $frameHead[3] = bindec($payloadLengthBin[1]);
        } else {
            $frameHead[1] = ($masked === true) ? $payloadLength + 128 : $payloadLength;
        }

        foreach (array_keys($frameHead) as $i) {
            $frameHead[$i] = chr($frameHead[$i]);
        }

        $frame = implode('', $frameHead);

        $mask = array();
        if ($masked === true) {
            for ($i = 0; $i < 4; $i++) {
                $mask[$i] = chr(rand(0, 255));
            }
            $frame .= implode('', $mask);
        }

        for ($i = 0; $i < $payloadLength; $i++) {
            $frame .= ($masked === true) ? $payload[$i] ^ $mask[$i % 4] : $payload[$i];
        }

        return $frame;
    }
}