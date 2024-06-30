# Symfony Websocket Bundle
Work in progress

## installation

checkout library `composer req snoke/symfony-websocket:dev-master`

## usage

run `php bin/console websocket:start`

with following websocket message body a client can sign in via websockets
['type' => 'login', 'payload' => ["identifier" => "john@doe.com","password" => "test"]]

you can now register EventSubscriber to the following Events:
- LoginSuccessful
- LoginFailed
- ConnectionEstablished
- ConnectionClosed
- MessageRecieved
- Error
