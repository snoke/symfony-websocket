# Symfony Websocket Bundle

## installation

Add the custom repository to your composer.json:

```php
"repositories": [
    {
        "type": "vcs",
        "url": "git@github.com:snoke/symfony-websocket.git"
    }
],
```

checkout library `composer req snoke/symfony-websocket:dev-master`

## usage

run `php bin/console websocket:start`

you can now register EventSubscriber to the following Events:
- ServerStarted
- ConnectionClosed
- ConnectionEstablished
- MessageSent
- MessageBeforeSend
- MessageRecieved
- Error
