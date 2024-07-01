# Symfony Websocket Bundle
Work in progress

## installation

checkout library 

`composer req snoke/symfony-websocket:dev-master`

modify config/packages/snoke_websocket.yaml:
````
snoke_websocket:
    context:
        tls:
            local_cert: 'path/to/server.pem'
            local_pk: 'path/to/private.key'
            allow_self_signed: true
            verify_peer: false
````

if you want to use no SSL:
````
snoke_websocket:
    context: []
````
note that websockets without SSL only work on localhost (you can still use Stunnel to wrap them into an SSL Connection)

## usage
### Starting the WebSocket Server

Use the Symfony console command to start the WebSocket server

`php bin/console websocket:start`

You can optionally specify the IP address and port:

`php bin/console websocket:start --ip=127.0.0.1 --port=9000`

### Registering Event Listeners

o react to WebSocket events, create your own listeners and register them with the Symfony event dispatcher.

Websocket-Request
```
['type' => 'command', 'command  => 'auth', 'payload' => [
    "identifier" => "john@doe.com","password" => "test
"]]
```

Example of a listener:

```php
namespace App\EventListener;

use Snoke\Websocket\Event\RequestReceived;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: RequestReceived::class, method: 'onRequestReceived')]
final class AuthListener
{
    public function onRequestReceived(RequestReceived $event): void
    {
        $request = $event->getRequest();
        $connection = $event->getConnection();
        if ($request['type'] === 'auth') {
            $user = $this->authenticator->authenticate($payload['identifier'],$payload['password']);
            if ($user) {
                $connection->setUser($user);
                
                $connection->sendResponse($user->getRoles());
            }
        }
    }
}
```

### Available Events
- ServerStarted: Triggered when the server is started
- ConnectionOpened: Triggered when a new connection is established.
- RequestReceived: Triggered when a message is received.
- ConnectionClosed: Triggered when a connection is closed.
- Error: Triggered when an error occurs.

