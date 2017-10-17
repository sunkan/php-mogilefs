<?php

namespace MogileFs\Client;

use MogileFs\Connection;
use MogileFs\File\BlobFile;

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

        $rs = $fileClient->upload($key, $file);
        $this->assertTrue($rs);

        $paths = $fileClient->get($key);
        $this->assertTrue(isset($paths['path1']));
        $this->assertGreaterThanOrEqual(1, $paths['paths']);
    }

    public function testGetInfoAboutKey()
    {
        $key = 'test/info-key';
        $content = 'Test info content';

        $fileClient = new FileClient($this->getConnection(), self::$domains[0]);
        $file = new BlobFile($content, 'images');

        $rs = $fileClient->upload($key, $file);
        $this->assertTrue($rs);

        $info = $fileClient->info($key);
        $this->assertInternalType('array', $info);
        $this->assertEquals($key, $info['key']);
        $this->assertEquals(strlen($content), $info['length']);
        $this->assertEquals('images', $info['class']);
        $this->assertEquals(self::$domains[0], $info['domain']);
        $this->assertGreaterThanOrEqual(1, $info['devcount']);
    }

    public function testInvalidArgumentsForGetKey()
    {
        $this->expectExceptionMessage('MogileFs\Client\FileClient::get() key cannot be null');

        $fileClient = new FileClient($this->getConnection(), self::$domains[0]);
        $fileClient->get(null);
    }

    public function testInvalidArgumentsForInfo()
    {
        $this->expectExceptionMessage('MogileFs\Client\FileClient::info() key cannot be null');

        $fileClient = new FileClient($this->getConnection(), self::$domains[0]);
        $fileClient->info(null);
    }

    public function testDeleteFile()
    {
        $key = 'test/delete-key';
        $content = 'Test delete content';

        $fileClient = new FileClient($this->getConnection(), self::$domains[0]);
        $file = new BlobFile($content, 'images');

        $rs = $fileClient->upload($key, $file);
        $this->assertTrue($rs);

        $this->assertTrue($fileClient->delete($key));
    }

    public function testInvalidArgumentsForDelete()
    {
        $this->expectExceptionMessage('MogileFs\Client\FileClient::delete() key cannot be null');

        $fileClient = new FileClient($this->getConnection(), self::$domains[0]);
        $fileClient->delete(null);
    }

    public function testRenameFile()
    {
        $key = 'test/rename-key';
        $renameKey = 'test/rename-key-2';
        $content = 'Test rename content';

        $fileClient = new FileClient($this->getConnection(), self::$domains[0]);
        $file = new BlobFile($content, 'images');

        $rs = $fileClient->upload($key, $file);
        $this->assertTrue($rs);

        $this->assertTrue($fileClient->rename($key, $renameKey));

        $paths = $fileClient->get($renameKey);
        $this->assertTrue(isset($paths['path1']));
        $this->assertGreaterThanOrEqual(1, $paths['paths']);
    }

    public function testFindUnkownKeyException()
    {
        $this->expectExceptionMessage('MogileFs\Connection::doRequest() unknown_key test/unkown-key');

        $fileClient = new FileClient($this->getConnection(), self::$domains[0]);
        $fileClient->get('test/unkown-key');
    }

    public function testInvalidFromKey()
    {
        $this->expectExceptionMessage('MogileFs\Client\FileClient::rename() fromKey cannot be null');

        $fileClient = new FileClient($this->getConnection(), self::$domains[0]);
        $fileClient->rename(null, 'test/to-key');
    }

    public function testInvalidToKey()
    {
        $this->expectExceptionMessage('MogileFs\Client\FileClient::rename() toKey cannot be null');

        $fileClient = new FileClient($this->getConnection(), self::$domains[0]);
        $fileClient->rename('test/from-key', null);
    }

    public function testListFids()
    {
        $fileClient = new FileClient($this->getConnection(), self::$domains[0]);

        $content = 'Test info content';
        for ($i = 0; $i < 3; $i++) {
            $file = new BlobFile($content . ' ' . $i, 'images');
            $fileClient->upload('test/list-key-'.$i, $file);
        }

        $rs = $fileClient->listFids(0, 0);

        $this->assertInternalType('array', $rs);

        for ($i = (int) $rs['fid_count']; $i > 0; $i--) {
            $this->assertGreaterThanOrEqual(1, $rs['fid_'.$i.'_devcount']);
            $this->assertEquals(self::$domains[0], $rs['fid_'.$i.'_domain']);
            $this->assertRegExp('/test\/list\-key\-\d/', $rs['fid_'.$i.'_key']);
            $this->assertEquals('images', $rs['fid_'.$i.'_class']);
            $this->assertEquals(strlen($content)+2, $rs['fid_'.$i.'_length']);
        }
    }
    public function testInvalidFidFromKey()
    {
        $this->expectExceptionMessage('MogileFs\Client\FileClient::listFids from must be an integer');

        $fileClient = new FileClient($this->getConnection(), self::$domains[0]);
        $fileClient->listFids(null, 0);
    }

    public function testInvalidFidToKey()
    {
        $this->expectExceptionMessage('MogileFs\Client\FileClient::listFids to must be an integer');

        $fileClient = new FileClient($this->getConnection(), self::$domains[0]);
        $fileClient->listFids(0, null);
    }
}
