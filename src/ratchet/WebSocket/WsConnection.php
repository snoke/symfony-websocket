<?php
namespace Snoke\Websocket\Ratchet\WebSocket;
use Snoke\Websocket\Ratchet\AbstractConnectionDecorator;
use Snoke\Websocket\Ratchet\RFC6455\Messaging\DataInterface;
use Snoke\Websocket\Ratchet\RFC6455\Messaging\Frame;

/**
 * {@inheritdoc}
 * @property \StdClass $WebSocket
 */
class WsConnection extends AbstractConnectionDecorator {
    /**
     * {@inheritdoc}
     */
    public function send($msg) {
        if (!$this->WebSocket->closing) {
            if (!($msg instanceof DataInterface)) {
                $msg = new Frame($msg);
            }

            $this->getConnection()->send($msg->getContents());
        }

        return $this;
    }

    /**
     * @param int|\Ratchet\RFC6455\Messaging\DataInterface
     */
    public function close($code = 1000) {
        if ($this->WebSocket->closing) {
            return;
        }

        if ($code instanceof DataInterface) {
            $this->send($code);
        } else {
            $this->send(new Frame(pack('n', $code), true, Frame::OP_CLOSE));
        }

        $this->getConnection()->close();

        $this->WebSocket->closing = true;
    }
}
