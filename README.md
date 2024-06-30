# Symfony Websocket Bundle

## installation

Add the custom repository to your composer.json:

```php
"repositories": [
    {
        "type": "vcs",
        "url": "git@github.com:snoke/websocket.git"
    }
],
```

checkout library `composer req snoke/websocket:dev-master`

## usage

run `php bin/console websocket:start`

you can now register EventSubscriber to the following Events:
- ConnectionClosed
- ConnectionEstablished
- MessageBeforeSend
- MessageBeforeSent
- MessageRecieved
- Error