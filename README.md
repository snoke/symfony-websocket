# Symfony Websocket Bundle
Work in progress

## installation

checkout library 

`composer req snoke/symfony-websocket:dev-master`

## usage
### Starting the WebSocket Server

Use the Symfony console command to start the WebSocket server

`php bin/console websocket:start`

You can optionally specify the IP address and port:

`php bin/console websocket:start --ip=127.0.0.1 --port=9000`

### Registering Event Listeners

o react to WebSocket events, create your own listeners and register them with the Symfony event dispatcher.

Example of a listener:

Websocket-Request:
```
['type' => 'command', 'command  => 'auth', 'payload' => [
    "identifier" => "john@doe.com","password" => "test
"]]
```

The Listener:
```php
namespace App\EventSubscriber;

use App\Service\Authenticator;
use Snoke\Websocket\Event\CommandReceived;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

readonly class AuthListener implements EventSubscriberInterface
{
    public function __construct(private Authenticator $authenticator) {

    }
    public static function getSubscribedEvents(): array
    {
        return [
            CommandReceived::NAME => 'onCommandReceived',
        ];
    }
    public function onCommandReceived(CommandReceived $event): void
    {
        $connection = $event->getConnection();
        $request = $event->getRequest();
        $payload = $request->getBody();

        if ($request->getCommand() === 'auth') {
            $user = $this->authenticator->authenticate($payload['identifier'],$payload['password']);
            if ($user) {
                $connection->setUser($user);
                $response = new Response('auth',$user->getRoles());
                $connection->sendResponse($response);
            }
        }
    }
}
```

### Available Events
- ServerStarted: Triggered when the server is started
- ConnectionOpened: Triggered when a new connection is established.
- MessageReceived: Triggered when a message is received.
- CommandReceived: Triggered when a command is received.
- ConnectionClosed: Triggered when a connection is closed.
- Error: Triggered when an error occurs.

