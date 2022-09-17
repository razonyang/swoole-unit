<?php

declare(strict_types=1);

namespace RazonYang\Swoole\Unit;

use Nyholm\Psr7\Uri;
use Psr\Http\Message\UriInterface;
use RuntimeException;
use Swoole\Http\Request;

final class RequestBuilder
{
    private string $method;

    private UriInterface $uri;

    private string $protocol = 'HTTP/1.1';

    private array $headers = [];

    private string $body = '';

    public function __construct(
        string $method,
        string $uri,
    ) {
        $this->method = strtoupper($method);
        $this->uri = new Uri($uri);
    }

    public function protocol(string $protocol): self
    {
        $this->protocol = $protocol;

        return $this;
    }

    /**
     * @param string[][] $headers.
     */
    public function headers(array $headers): self
    {
        $this->headers = $headers;

        return $this;
    }

    public function addHeader(string $header, string $value): self
    {
        if (!isset($this->headers[$header])) {
            $this->headers[$header] = [];
        }

        $this->headers[$header][] = $value;

        return $this;
    }

    public function setHeader(string $header, array $values): self
    {
        $this->headers[$header] = $values;

        return $this;
    }

    public function host(string $host): self
    {
        $this->setHeader('Host', [$host]);

        return $this;
    }

    public function contentType(string $type): self
    {
        $this->setHeader('Content-Type', [$type]);

        return $this;
    }

    public function contentLength(int $length): self
    {
        $this->setHeader('Content-Length', [$length]);

        return $this;
    }

    public function body(string $body): self
    {
        $this->setHeader('Content-Length', [strlen($body)]);
        $this->body = $body;

        return $this;
    }

    public function formData(array $data): self
    {
        $tmp = [];
        foreach ($data as $name => $value) {
            $tmp[] = sprintf('%s=%s', \urlencode($name), \urlencode($value));
        }
        $body = \implode('&', $tmp);

        $this->contentType('application/x-www-form-urlencoded');
        $this->contentLength(strlen($body));
        $this->body = $body;

        return $this;
    }

    public function jsonData(array $data): self
    {
        $body = \json_encode($data);

        $this->contentType('application/json');
        $this->contentLength(strlen($body));
        $this->body = $body;

        return $this;
    }

    public function multipart(array $data, array $files = []): self
    {
        $boundary = substr(\str_shuffle(\str_repeat('ABCDEFGHIJKLMNOPQRSQ', 3)), 0, 6);

        $body = '';

        foreach ($data as $name => $value) {
            $body .= sprintf(
                "--%s\r\nContent-Disposition: form-data; name=\"%s\"\r\n\r\n%s\r\n",
                $boundary,
                $name,
                $value
            );
        }

        foreach ($files as $name => $filename) {
            $content = \file_get_contents($filename);
            $body .= \sprintf(
                "--%s\r\nContent-Disposition: form-data; name=\"%s\"; filename=\"%s\"\r\n",
                $boundary,
                $name,
                $filename,
            );
            $fileContentType = 'application/octet-stream';
            $fileTransferEncoding = 'binary';
            $body .= sprintf(
                "Content-Type: %s\r\nContent-Transfer-Encoding: %s\r\n\r\n%s\r\n",
                $fileContentType,
                $fileTransferEncoding,
                $content,
            );
        }

        $body .= sprintf('--%s--', $boundary);

        $this->contentType('multipart/form-data; boundary='.$boundary);
        $this->body($body);

        return $this;
    }

    public function create(array $options = []): Request
    {
        $message = $this->createMessage();
        $request = Request::create($options);
        $parsed = $request->parse($message);
        if ($parsed === false) {
            throw new RuntimeException('Unable to parse HTTP message.');
        }

        return $request;
    }

    private function createMessage(): string
    {
        $message = sprintf(
            "%s %s %s\r\n",
            $this->method,
            $this->uri->__toString(),
            $this->protocol
        );

        foreach ($this->headers as $name => $values) {
            $message .= sprintf("%s: %s\r\n", $name, implode(", ", $values));
        }

        $message .= "\r\n";

        if ($this->body !== '') {
            $message .= $this->body;
        }

        return $message;
    }

    public static function get(string $uri): self
    {
        return new RequestBuilder('GET', $uri);
    }

    public static function post(string $uri): self
    {
        return new RequestBuilder('POST', $uri);
    }

    public static function put(string $uri): self
    {
        return new RequestBuilder('PUT', $uri);
    }

    public static function delete(string $uri): self
    {
        return new RequestBuilder('DELETE', $uri);
    }
}
