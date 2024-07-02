<?php

namespace Snoke\Websocket;

enum WebSocketOpcode: int
{
    case ContinuationFrame = 0;    // 0x0
    case TextFrame = 1;            // 0x1
    case BinaryFrame = 2;          // 0x2
    case ConnectionCloseFrame = 8; // 0x8
    case PingFrame = 9;            // 0x9
    case PongFrame = 10;           // 0xA
}