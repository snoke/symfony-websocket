<?php

namespace Snoke\Websocket\Service;

use Snoke\Websocket\WebSocketOpcode;

class Encoder
{
    public function mask(string $payload, WebSocketOpcode $opcode, bool $masked = true, bool $fin = true): string
    {
        $frameHead = [];
        $payloadLength = strlen($payload);

        // Set the FIN bit based on the $fin parameter
        $frameHead[0] = ($fin ? 0x80 : 0x00) | $opcode->value;

        if ($payloadLength <= 125) {
            $frameHead[1] = $masked ? 0x80 | $payloadLength : $payloadLength;
        } elseif ($payloadLength >= 126 && $payloadLength <= 65535) {
            $frameHead[1] = $masked ? 0xFE : 126;
            $frameHead = array_merge($frameHead, str_split(pack('n', $payloadLength)));
        } else {
            $frameHead[1] = $masked ? 0xFF : 127;
            $frameHead = array_merge($frameHead, str_split(pack('J', $payloadLength)));
        }

        foreach ($frameHead as &$val) {
            $val = chr($val);
        }
        $mask = '';

        if ($masked) {
            for ($i = 0; $i < 4; $i++) {
                $mask .= chr(rand(0, 255));
            }
            for ($i = 0; $i < $payloadLength; $i++) {
                $payload[$i] = $payload[$i] ^ $mask[$i % 4];
            }
        }

        return implode('', $frameHead) . $mask . $payload;
    }
}
