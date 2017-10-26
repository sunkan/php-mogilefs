# Php MogileFs client

## Installation


The preferred method of installing this library is with
[Composer](https://getcomposer.org/) by running the following from your project
root:

	$ composer require sunkan/php-mogilefs


## Documentation

 - [Instantiating](#instantiating)
 - [Domain client](#domain-client)
	 - [Create domain](#create-domain)
	 - [Delete domain](#delete-domain)
	 - [List domains](#list-domains)
 - [Class client](#domain-client)
	 - [Instantiating](#instantiating-1)
	 - [Create class](#create-class)
	 - [Update class](#update-class)
	 - [Delete class](#delete-class)
 - [File client](#file-client)
	 - [Instantiating](#instantiating-2)
	 - [Get paths](#get-paths)
	 - [Get file info](#get-file-info)
	 - [Delete file](#delete-file)
	 - [Rename file](#rename-file)
	 - [List files](#list-files)
	 - [Upload file](#upload-file)
		 - [File types](#file-types)
			 - [Blob](#blob)
			 - [Local](#local-file)
			 - [Psr-7](#psr-7-file-upload)
			 - [Resource](#resource)
			 - [Factory](#factory)
		 - [Example](#upload)

### Instantiating

```php
<?php
$connection = new MogileFs\Connection([
    '127.0.0.1:7001'
]);

//or

$connection = new MogileFs\Connection([
    [
        'host' => '127.0.0.1',
        'port' => 7001
    ]
]);

```

### Domain client

#### Create domain
```php

$domainClient = new MogileFs\Client\DomainClient($connection);
try {
	$domain = $domainClient->create('example.com');

	$domain->getDomain(); // domain name
	$domain->getClasses(); // array of classes
} catch(MogileFs\Exception $e) {
	$e->getMessage() === 'domain_exists';
}

```

#### Delete domain

```php
$domainClient = new MogileFs\Client\DomainClient($connection);
try {
	$response = $domainClient->delete('example.com');
	$response->isSuccess();
} catch(MogileFs\Exception $e) {
	$e->getMessage() === 'domain_not_found';
}

```

#### List domains

```php
$domainClient = new MogileFs\Client\DomainClient($connection);

$collection = $domainClient->all();

foreach ($collection as $domain) {
	$domain->getDomain(); // domain name
	$domain->getClasses(); // array of classes
}

```

### Class client

#### Instantiating
```php
$classClient = new MogileFs\Client\ClassClient($connection, 'example.com');
//or
$classClient = new MogileFs\Client\ClassClient($connection);
$classClient->setDomain('example.com');
```

#### Create class
```php
$classClient = new MogileFs\Client\ClassClient($connection, 'example.com');

try {
	$replicationCount = 2;
	$class = $classClient->create('assets', $replicationCount);
	$class->getName(); // class name
	$class->getCount(); // replication count
	$class->getPolicy(); // replication policy
	$class->getHash(); // hash policy
	$class->getDomain(); // domain name
} catch(MogileFs\Exception $e) {
	$e->getMessage() === 'class_exists';
}
```

#### Update class
```php
$classClient = new MogileFs\Client\ClassClient($connection, 'example.com');

try {
	$newReplicationCount = 4;
	$class = $classClient->update('assets', $replicationCount);
} catch(MogileFs\Exception $e) {
	$e->getMessage() === 'class_not_found';
}
```

#### Delete class
```php
$classClient = new MogileFs\Client\ClassClient($connection, 'example.com');

try {
	$response = $classClient->delete('assets');
	$response->isSuccess();
} catch(MogileFs\Exception $e) {
	$e->getMessage() === 'class_not_found';
}

```

### File client

#### Instantiating
```php
$fileClient = new MogileFs\Client\FileClient($connection, 'example.com');
//or
$fileClient = new MogileFs\Client\FileClient($connection);
$fileClient->setDomain('example.com');
```

#### Get paths
```php
$fileClient = new MogileFs\Client\FileClient($connection, 'example.com');

try {
	$path = $fileClient->get('test/key');
	$path->getPath(); // first path
	$path->getPaths(); // array of paths
	$path->getCount(); // number of paths
} catch(MogileFs\Exception $e) {
	$e->getMessage() === 'unknown_key';
}
```

#### Get file info
```php
$fileClient = new MogileFs\Client\FileClient($connection, 'example.com');

try {
	$file = $fileClient->info('test/key');
	$file->getFid(); // file id
	$file->getKey(); // file key - test/key
	$file->getSize(); // file size
	$file->getFileCount(); // replication count
	$file->getDomain(); // file domain
	$file->getClass(); // file class
} catch(MogileFs\Exception $e) {
	$e->getMessage() === 'unknown_key';
}
```

#### Delete file
```php
$fileClient = new MogileFs\Client\FileClient($connection, 'example.com');

$response = $fileClient->delete('test/key');
$response->isSuccess();
```

#### Rename file
```php
$fileClient = new MogileFs\Client\FileClient($connection, 'example.com');

$response = $fileClient->rename('test/key', 'test/key2');
$response->isSuccess();
```

#### List files
```php
$fileClient = new MogileFs\Client\FileClient($connection, 'example.com');

$collection = $fileClient->listKeys($prefix, $suffix, $limit);
$collection; // contains file keys nothing else

$collection = $fileClient->listFids($fromFid, $toFid);
$collection; // contains File objects with information about all files in range

```

#### Upload file

The upload function accepts a any objects that implements `MogileFs\File\FileInterface`

##### File types

###### Blob
```php
$class = "blob-class";
$file = new MogileFs\File\BlobFile('raw data', $class)
```

###### Local file
```php
$class = "local-file-class";
$file = new MogileFs\File\LocalFile('/path/to/file', $class)
```

###### Psr-7 file upload
```php
$class = "psr7-file-class";

$uploadFile = $request->getUploadedFiles()['file'];
$file = new MogileFs\File\Psr7File($uploadFile, $class)

//or

$stream;// instance of Psr\Http\Message\StreamInterface
$file = new MogileFs\File\Psr7File($stream, $class)
```

###### Resource
```php
$class = "resource-file-class";
$resource = fopen('file', 'r'); // should work with any stream context
$file = new MogileFs\File\ResourceFile($resource, $class)
```

###### Factory

```php
$factory = new MogileFs\File\Factory();
$file = $factory($fileContent, $class);
```


##### Upload

```php
$fileClient = new MogileFs\Client\FileClient($connection, 'example.com');
$factory = new MogileFs\File\Factory();

$key = 'test/key';
$file = $factory($fileContent, $class);
$response = $fileClient->upload($key, $file);
if ($response->isSuccess()) {
	$path = $fileClient->get($key);
}
```
