# Symfony Websocket Bundle
Work in progress

## installation

checkout library `composer req snoke/symfony-websocket:dev-master`

## usage

run `php bin/console websocket:start`

with following websocket request body a client can sign in via websockets
['type' => 'login', 'payload' => ["identifier" => "john@doe.com","password" => "test"]]
using the default userprovider 

you can now register EventSubscriber to the following Events:
- LoginSuccessful
- LoginFailed
- ConnectionEstablished
- ConnectionClosed
- MessageRecieved
- Error

### example usage:
```php
namespace App\EventSubscriber;

use Snoke\Websocket\Event\LoginSuccessful;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class LoginSuccessfulSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            LoginSuccessful::NAME => 'onLoginSuccessful',
        ];
    }

    public function onLoginSuccessful(LoginSuccessful $event)
    {
        $user = $event->getConnection()->getUser();
        $user->setLastLogin(new \DateTime());
        ...
```
