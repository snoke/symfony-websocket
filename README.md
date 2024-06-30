# Symfony Websocket Bundle

## installation

checkout library `composer req snoke/symfony-websocket:dev-master`

## usage

run `php bin/console websocket:start`

you can now register EventSubscriber to the following Events:
- ServerStarted
- ConnectionEstablished
- ConnectionClosed
- MessageBeforeSend
- MessageSent
- MessageRecieved
- Error
