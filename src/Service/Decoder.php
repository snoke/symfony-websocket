<?php

namespace Snoke\Websocket\Service;

class Decoder
{
    public function decodeFrame(string $data): array
    {
        $firstByte = ord($data[0]);
        $secondByte = ord($data[1]);

        $fin = ($firstByte & 0x80) >> 7;
        $opcode = $firstByte & 0x0F;
        $isMasked = ($secondByte & 0x80) >> 7;
        $payloadLength = $secondByte & 0x7F;

        $index = 2;

        if ($payloadLength === 126) {
            $payloadLength = unpack('n', substr($data, $index, 2))[1];
            $index += 2;
        } elseif ($payloadLength === 127) {
            $payloadLength = unpack('J', substr($data, $index, 8))[1];
            $index += 8;
        }

        $masks = '';
        if ($isMasked) {
            $masks = substr($data, $index, 4);
            $index += 4;
        }

        $payload = substr($data, $index, $payloadLength);
        if ($isMasked) {
            $payload = $this->applyMask($payload, $masks);
        }

        return [
            'fin' => $fin,
            'opcode' => $opcode,
            'isMasked' => $isMasked,
            'payload' => $payload
        ];
    }

    private function applyMask(string $payload, string $masks): string
    {
        $maskedPayload = '';
        for ($i = 0, $len = strlen($payload); $i < $len; ++$i) {
            $maskedPayload .= $payload[$i] ^ $masks[$i % 4];
        }
        return $maskedPayload;
    }

    public function unmask($payload)
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
}