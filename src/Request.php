<?php
declare(strict_types=1);

namespace FrontLayer\OpenApi;

class Request
{
    /**
     * Request path
     * @var string|null
     */
    protected $path = null;

    /**
     * Request content type
     * @var string|null
     */
    protected $contentType = null;

    /**
     * Request method
     * @var string|null
     */
    protected $method = null;

    /**
     * Request post
     * @var object|null
     */
    protected $post = null;

    /**
     * Request query
     * @var object|null
     */
    protected $query = null;

    /**
     * Request cookies
     * @var object|null
     */
    protected $cookies = null;

    /**
     * Request body
     * @var object|null
     */
    protected $body = null;

    /**
     * Request headers
     * @var object|null
     */
    protected $headers = null;

    /**
     * Build the whole request automatically
     * @throws RequestException
     */
    public function buildAutomatically(): void
    {
        // Set path
        $this->setPath(preg_replace('[\?.*]', '', $_SERVER['REQUEST_URI']));

        // Set content type
        $contentType = $_SERVER['CONTENT_TYPE'];
        $contentType = preg_replace('[;.*]', '', $contentType);
        $this->setContentType($contentType ?: null);

        // Set method
        $this->setMethod($_SERVER['REQUEST_METHOD']);

        // Set post
        $post = $_POST ? json_decode(json_encode($_POST)) : null; // Cast it from associative array to object
        $this->setPost($post);

        // Set query
        $get = $_GET ? json_decode(json_encode($_GET)) : null; // Cast it from associative array to object
        $this->setQuery($get);

        // Set cookies
        $cookies = $_COOKIE ? json_decode(json_encode($_COOKIE)) : null; // Cast it from associative array to object
        $this->setCookies($cookies);

        // Set body
        $body = null;
        switch ($this->getContentType()) {
            case 'application/json':
            {
                $body = file_get_contents('php://input');

                if ($this->getContentType() == 'application/json') {
                    try {
                        $body = json_decode($body);
                    } catch (\Exception $e) {
                        throw new RequestException('Wrong JSON input');
                    }
                }

                break;
            }
            case 'multipart/form-data':
            {
                $body = (object)[];

                foreach ($_FILES as $fileId => $row) {
                    $body->{$fileId} = file_get_contents($row['tmp_name']);
                }

                break;
            }
        }
        $this->setBody($body);

        // Set headers
        $headers = (object)[];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headerName = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
                $headers->{$headerName} = $value;
            }
        }
        $this->setHeaders($headers);
    }

    /**
     * Get request path
     * @param string|null $path
     */
    public function setPath(?string $path): void
    {
        $this->path = $path;
    }

    /**
     * Get request path
     * @return string|null
     */
    public function getPath(): ?string
    {
        return $this->path;
    }

    /**
     * Get request content type
     * @param string|null $contentType
     */
    public function setContentType(?string $contentType): void
    {
        $this->contentType = $contentType;
    }

    /**
     * Get request content type
     * @return string|null
     */
    public function getContentType(): ?string
    {
        return $this->contentType;
    }

    /**
     * Get request method
     * @param string|null $method
     * @throws RequestException
     */
    public function setMethod(?string $method): void
    {
        $method = strtolower($method);

        if ($method !== null && !in_array($method, ['get', 'post', 'put', 'patch', 'delete', 'head', 'options', 'trace'])) {
            throw new RequestException(sprintf('Unknown request method "%s"', $method));
        }

        $this->method = $method;
    }

    /**
     * Get request method
     * @return string|null
     */
    public function getMethod(): ?string
    {
        return $this->method;
    }

    /**
     * Get request post
     * @param object|null $post
     */
    public function setPost(?object $post): void
    {
        $this->post = $post;
    }

    /**
     * Get request post
     * @return object|null
     */
    public function getPost(): ?object
    {
        return $this->post;
    }

    /**
     * Get request query
     * @param object|null $query
     */
    public function setQuery(?object $query): void
    {
        $this->query = $query;
    }

    /**
     * Get request query
     * @return object|null
     */
    public function getQuery(): ?object
    {
        return $this->query;
    }

    /**
     * Get request cookies
     * @param object|null $cookies
     */
    public function setCookies(?object $cookies): void
    {
        $this->cookies = $cookies;
    }

    /**
     * Get request cookies
     * @return object|null
     */
    public function getCookies(): ?object
    {
        return $this->cookies;
    }

    /**
     * Get request body
     * @param mixed $body
     */
    public function setBody($body): void
    {
        $this->body = $body;
    }

    /**
     * Get request body
     * @return mixed
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Get request headers
     * @param object|null $headers
     */
    public function setHeaders(?object $headers): void
    {
        $this->headers = $headers;
    }

    /**
     * Get request headers
     * @return object|null
     */
    public function getHeaders(): ?object
    {
        return $this->headers;
    }
}
