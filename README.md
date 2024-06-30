# Symfony Websocket Bundle
Work in progress

## installation

checkout library `composer req snoke/symfony-websocket:dev-master`

## usage

run `php bin/console websocket:start`


you can now register EventSubscriber to the following Events:
- ServerStarted
- ConnectionEstablished
- ConnectionClosed
- CommandReceived
- MessageRecieved
- Error

### example usage:
the following code will authenticate the connection using the default userprovider on following websocket request body

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
            }
        }
    }
}
```
