#Php MogileFs client

##Example

```php
$client = new MogileFs\Client();

$client->connect([
  '127.0.0.1:7001'
]);
//or
$client->connect('127.0.0.1', 7001, 'example.com');


//Domain methods
$client->getDomains();

$client->createDomain('example.com')
$client->deleteDomain('example.com')

//Class methods
$client->createClass('example.com', 'assets', 3);
$client->updateClass('example.com', 'assets', 3);
$client->deleteClass('example.com', 'assets');

//File methods
$client->setDomain('example.com');

$paths = $client->get('test/key');
$info = $client->fileInfo('test/key');
$rs = $client->delete('test/key');
$rs = $client->rename('test/key', 'test/new_key');

$data = 'hello world';
$client->put($data, 'hello/world', 'assets', false);

$file = 'hello.txt';
$client->put('./hello.txt', 'hello_world.txt', 'assets');

$handler = fopen('world.txt', 'r');
$client->put($handler, 'world_hello.txt', 'assets');
```




