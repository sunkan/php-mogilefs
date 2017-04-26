#Php MogileFs client

##Install with composer:

composer.json

```json
{
    "require": {
        "sunkan/php-mogilefs": "2.*"
    }
}
```

##Example

```php
$connection = new MogileFs\Connection([
    [
        'host' => '127.0.0.1',
        'port' => 7001
    ]
]);

//Domain methods
$domainClient = new MogileFs\Clients\DomainClient($connection);
$domains = $domainClient ->all();
$domainClient->create('example.com');
$domainClient->delete('example.com')

//Class methods

$classClient = new MogileFs\Client\ClassClient($connection, 'example.com');
//or
$classClient = new MogileFs\Client\ClassClient($connection);
$classClient->setDomain('example.com');

$classClient->create('assets', 2);
$classClient->update('assets', 3);
$classClient->delete('assets');

//File methods

$fileClient = new MogileFs\Client\FileClient($connection, 'example.com');
//or
$fileClient = new MogileFs\Client\FileClient($connection);
$fileClient->setDomain('example.com');

$paths = $fileClient->get('test/key');
$info = $fileClient->info('test/key');
$rs = $fileClient->delete('test/key');
$rs = $fileClient->rename('test/key', 'test/new_key');

//upload file from a psr-7 request
$file = new MogileFs\File($request, 'assets');
//upload file from file system
$file = new MogileFs\File('./path/to/image.jpg', 'assets');
//upload file from file handler
$file = new MogileFs\File(fopen('world.txt', 'r'), 'assets');
//upload text
$file = new MogileFs\File($text, 'assets');

$rs = $fileClient->upload('test/uploaded-file', $file);

```