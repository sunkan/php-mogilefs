<?php

namespace MogileFs\Client;

use MogileFs\Collection;
use MogileFs\Exception;
use MogileFs\File\BlobFile;
use MogileFs\Object\FileInterface;
use MogileFs\Object\PathInterface;
use MogileFs\Response;

class FileClientTest extends AbstractClientTest
{
    private static $domains = [
        'test-file-client-1',
        'test-file-client-2'
    ];

    public function setUp()
    {
        $this->reset();
        $domainClient = new DomainClient($this->getConnection());
        $classClient = new ClassClient($this->getConnection(), 'defualt');

        foreach (self::$domains as $domain) {
            $domainClient->create($domain);
            $classClient->setDomain($domain);
            $classClient->create('images', 1);
        }
    }

    public function testUpload()
    {
        $key = 'test/key';

        $fileClient = new FileClient($this->getConnection(), self::$domains[0]);
        $file = new BlobFile('Test content', 'images');

        $response = $fileClient->upload($key, $file);
        $this->assertInstanceOf(Response::class, $response);
        $this->assertTrue($response->isSuccess());

        $path = $fileClient->get($key);
        $this->assertInstanceOf(PathInterface::class, $path);

        $this->assertGreaterThanOrEqual(1, $path->getCount());
    }

    public function testGetInfoAboutKey()
    {
        $key = 'test/info-key';
        $content = 'Test info content';

        $fileClient = new FileClient($this->getConnection(), self::$domains[0]);
        $file = new BlobFile($content, 'images');

        $response = $fileClient->upload($key, $file);
        $this->assertInstanceOf(Response::class, $response);
        $this->assertTrue($response->isSuccess());

        $file = $fileClient->info($key);
        $this->assertInstanceOf(FileInterface::class, $file);
        $this->assertEquals($key, $file->getKey());
        $this->assertEquals(strlen($content), $file->getSize());
        $this->assertEquals('images', $file->getFileClass());
        $this->assertEquals(self::$domains[0], $file->getDomain());
        $this->assertGreaterThanOrEqual(1, $file->getFileCount());
    }

    public function testDeleteFile()
    {
        $key = 'test/delete-key';
        $content = 'Test delete content';

        $fileClient = new FileClient($this->getConnection(), self::$domains[0]);
        $file = new BlobFile($content, 'images');

        $response = $fileClient->upload($key, $file);
        $this->assertTrue($response->isSuccess());
        $this->assertTrue($fileClient->delete($key)->isSuccess());
    }

    public function testRenameFile()
    {
        $key = 'test/rename-key';
        $renameKey = 'test/rename-key-2';
        $content = 'Test rename content';

        $fileClient = new FileClient($this->getConnection(), self::$domains[0]);
        $file = new BlobFile($content, 'images');

        $uploadResponse = $fileClient->upload($key, $file);
        $this->assertTrue($uploadResponse->isSuccess());

        $renameResponse = $fileClient->rename($key, $renameKey);

        $this->assertTrue($renameResponse->isSuccess());

        $path = $fileClient->get($renameKey);
        $this->assertInstanceOf(PathInterface::class, $path);
        $this->assertGreaterThanOrEqual(1, $path->getCount());
    }

    public function testFindUnkownKeyException()
    {
        try {
            $fileClient = new FileClient($this->getConnection(), self::$domains[0]);
            $fileClient->get('test/unkown-key');
        } catch (Exception $e) {
            $this->assertInstanceOf(Response::class, $e->getResponse());
            $this->assertTrue($e->getResponse()->isError());
            $this->assertEquals('unknown_key', $e->getMessage());
        }
    }

    public function testListFids()
    {
        $fileClient = new FileClient($this->getConnection(), self::$domains[0]);

        $content = 'Test info content';
        for ($i = 0; $i < 3; $i++) {
            $file = new BlobFile($content . ' ' . $i, 'images');
            $fileClient->upload('test/list-key-'.$i, $file);
        }

        $collection = $fileClient->listFids(0, 0);
        $this->assertInstanceOf(Collection::class, $collection);
        $this->assertCount(3, $collection);

        /** @var FileInterface $file */
        $file = $collection[0];
        $this->assertInstanceOf(FileInterface::class, $file);
        $this->assertGreaterThanOrEqual(1, $file->getFid());
        $this->assertGreaterThanOrEqual(1, $file->getFileCount());
        $this->assertEquals('images', $file->getFileClass());
        $this->assertEquals(self::$domains[0], $file->getDomain());
        $this->assertRegExp('/test\/list\-key\-\d/', $file->getKey());
        $this->assertEquals(strlen($content) + 2, $file->getSize());
    }
}
