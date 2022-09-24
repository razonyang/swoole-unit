<?php

declare(strict_types=1);

namespace RazonYang\Swoole\Unit\Tests;

use PHPUnit\Framework\TestCase;
use RazonYang\Swoole\Unit\RequestBuilder;
use RazonYang\UnitHelper\ReflectionHelper;

class RequestBuilderTest extends TestCase
{
    public function testGet(): void
    {
        $request = RequestBuilder::get('/')
            ->create();

        $this->assertSame('GET', $request->getMethod());
        $this->assertSame('/', $request->server['request_uri']);
    }

    public function testPost(): void
    {
        $request = RequestBuilder::post('/users')
            ->create();

        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('/users', $request->server['request_uri']);
    }

    public function testPut(): void
    {
        $request = RequestBuilder::put('/users/foo')
            ->create();

        $this->assertSame('PUT', $request->getMethod());
        $this->assertSame('/users/foo', $request->server['request_uri']);
    }

    public function testDelete(): void
    {
        $request = RequestBuilder::delete('/users/bar')
            ->create();

        $this->assertSame('DELETE', $request->getMethod());
        $this->assertSame('/users/bar', $request->server['request_uri']);
    }

    public function testHeaders(): void
    {
        $headers = [
            'Host'         => ['localhost'],
            'Content-Type' => ['text/html'],
        ];

        $builder = RequestBuilder::get('/')
            ->headers($headers);

        $this->assertSame($headers, ReflectionHelper::getPropertyValue($builder, 'headers'));

        $request = $builder->create();

        $this->assertSame($headers['Host'][0], $request->header['host']);
        $this->assertSame($headers['Content-Type'][0], $request->header['content-type']);
    }

    public function testSetHeader(): void
    {
        $builder = RequestBuilder::get('/');

        $this->assertSame([], ReflectionHelper::getPropertyValue($builder, 'headers'));

        $header = ['Bar'];
        $builder->setHeader('X-Foo', $header);

        $this->assertSame($header, ReflectionHelper::getPropertyValue($builder, 'headers')['X-Foo']);
    }

    public function testAddHeader(): void
    {
        $builder = RequestBuilder::get('/');

        $this->assertSame([], ReflectionHelper::getPropertyValue($builder, 'headers'));

        $builder->addHeader('X-Foo', 'Bar');
        $this->assertSame(['Bar'], ReflectionHelper::getPropertyValue($builder, 'headers')['X-Foo']);

        $builder->addHeader('X-Foo', 'Fizz');
        $this->assertSame(['Bar', 'Fizz'], ReflectionHelper::getPropertyValue($builder, 'headers')['X-Foo']);
    }

    public function protocolProvider(): array
    {
        return [
            ['HTTP/1.0'],
            ['HTTP/1.1'],
            ['HTTP/2'],
        ];
    }

    /**
     * @dataProvider protocolProvider
     */
    public function testProtocol(string $protocol): void
    {
        $builder = RequestBuilder::get('/');
        $builder->protocol($protocol);

        $this->assertSame($protocol, ReflectionHelper::getPropertyValue($builder, 'protocol'));
    }

    public function hostProvider(): array
    {
        return [
            ['localhost'],
            ['127.0.0.1'],
        ];
    }

    /**
     * @dataProvider hostProvider
     */
    public function testHost(string $host): void
    {
        $builder = RequestBuilder::get('/');
        $builder->host($host);

        $headers = ReflectionHelper::getPropertyValue($builder, 'headers');
        $this->assertArrayHasKey('Host', $headers);
        $this->assertSame($host, $headers['Host'][0]);
    }

    public function contentTypeProvider(): array
    {
        return [
            ['text/html'],
            ['application/json'],
            ['multipart/form-data'],
        ];
    }

    /**
     * @dataProvider contentTypeProvider
     */
    public function testContentType(string $type): void
    {
        $builder = RequestBuilder::get('/');
        $builder->contentType($type);

        $headers = ReflectionHelper::getPropertyValue($builder, 'headers');
        $this->assertArrayHasKey('Content-Type', $headers);
        $this->assertSame($type, $headers['Content-Type'][0]);
    }

    public function contentLengthProvider(): array
    {
        return [
            [1],
            [2],
            [4],
            [8],
            [16],
            [32],
        ];
    }

    /**
     * @dataProvider contentLengthProvider
     */
    public function testContentLength(int $length): void
    {
        $builder = RequestBuilder::get('/');
        $builder->contentLength($length);

        $headers = ReflectionHelper::getPropertyValue($builder, 'headers');
        $this->assertArrayHasKey('Content-Length', $headers);
        $this->assertSame($length, $headers['Content-Length'][0]);
    }

    public function bodyProvider(): array
    {
        return [
            [
                'foo',
            ],
            [
                'bar',
            ],
        ];
    }

    /**
     * @dataProvider bodyProvider
     */
    public function testBody(string $body): void
    {
        $builder = RequestBuilder::post('/users');
        $builder->body($body);

        $this->assertSame($body, ReflectionHelper::getPropertyValue($builder, 'body'));
        $this->assertSame(strlen($body), ReflectionHelper::getPropertyValue($builder, 'headers')['Content-Length'][0]);
    }

    public function dataProvider(): array
    {
        return [
            [
                [
                    'name' => 'foo',
                    'age'  => '18',
                ],
            ],
            [
                [
                    'name' => 'bar',
                    'age'  => '28',
                ],
            ],
        ];
    }

    /**
     * @dataProvider dataProvider
     */
    public function testFormData(array $data): void
    {
        $request = RequestBuilder::post('/users')
            ->formData($data)
            ->create();

        $this->assertSame($data, $request->post);
    }

    /**
     * @dataProvider dataProvider
     */
    public function testJsonData(array $data): void
    {
        $request = RequestBuilder::post('/users')
            ->jsonData($data)
            ->create();

        $this->assertSame($data, json_decode($request->getContent(), true));
    }

    /**
     * @dataProvider dataProvider
     */
    public function testMultipart(array $data): void
    {
        $request = RequestBuilder::post('/users')
            ->multipart($data)
            ->create();

        $this->assertSame($data, $request->post);
    }

    public function multipartProvider(): array
    {
        return [
            [
                [
                    'name' => 'fizz',
                    'age'  => '38',
                ],
                [
                    'avatar' => __FILE__,
                ],
            ],
            [
                [
                    'name' => 'fizz',
                    'age'  => '38',
                ],
                [
                    'avatar' => __FILE__,
                    'readme' => dirname(__DIR__).\DIRECTORY_SEPARATOR.'README.md',
                ],
            ],
        ];
    }

    /**
     * @dataProvider multipartProvider
     */
    public function testMultipartWithFiles(array $data, array $files): void
    {
        $request = RequestBuilder::post('/users')
            ->multipart($data, $files)
            ->create();

        $this->assertSame($data, $request->post);
        $this->assertCount(count($files), $request->files);

        foreach ($request->files as $filename => $file) {
            $this->assertArrayHasKey($filename, $files);
            $this->assertSame(filesize($files[$filename]), $file['size']);
        }
    }
}
