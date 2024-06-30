<?php

namespace Snoke\Websocket\Service;

class Decoder
{

    public function unmask($data)
    {
        $bytes = unpack('C*', $data);
        $length = $bytes[2] & 127;

        if ($length == 126) {
            $masks = array_slice($bytes, 4, 4);
            $data = array_slice($bytes, 8);
        } elseif ($length == 127) {
            $masks = array_slice($bytes, 10, 4);
            $data = array_slice($bytes, 14);
        } else {
            $masks = array_slice($bytes, 2, 4);
            $data = array_slice($bytes, 6);
        }

        for ($i = 0; $i < count($data); ++$i) {
            $data[$i] = $data[$i] ^ $masks[$i % 4];
        }

        return json_decode(implode(array_map("chr", $data)), true);
    }
}