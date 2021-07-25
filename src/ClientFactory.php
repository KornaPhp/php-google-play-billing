<?php

namespace Imdhemy\GooglePlay;

use Exception;
use Google\Auth\ApplicationDefaultCredentials;
use Google\Auth\CredentialsLoader;
use Google\Auth\Middleware\AuthTokenMiddleware;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use Psr\Http\Client\RequestExceptionInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Class ClientFactory is responsible for creating an HTTP client for
 * different use cases.
 *
 * @package Imdhemy\GooglePlay
 */
class ClientFactory
{
    public const SCOPE_ANDROID_PUBLISHER = 'https://www.googleapis.com/auth/androidpublisher';
    private const BASE_URI = 'https://www.googleapis.com';
    private const GOOGLE_AUTH = 'google_auth';

    /**
     * Creates a client using the specified scopes. This method requires the
     * `GOOGLE_APPLICATION_CREDENTIALS` environment variable to be set.
     * {@link https://cloud.google.com/docs/authentication/production}
     *
     * @param array $scopes optional scopes @since 2.0.0
     * @return Client
     */
    public static function create(array $scopes = [self::SCOPE_ANDROID_PUBLISHER]): Client
    {
        $middleware = ApplicationDefaultCredentials::getMiddleware($scopes);

        return self::createWithMiddleware($middleware);
    }

    /**
     * @param AuthTokenMiddleware $middleware
     * @return Client
     * @deprecated deprecated since version 2.0.0 use {@see \Imdhemy\GooglePlay\ClientFactory::createWithMiddleware()} instead
     */
    public static function createFromWithMiddleware(AuthTokenMiddleware $middleware): Client
    {
        $stack = HandlerStack::create();
        $stack->push($middleware);

        return new Client([
          'handler' => $stack,
          'base_uri' => self::BASE_URI,
          'auth' => self::GOOGLE_AUTH,
        ]);
    }

    /**
     * Instead of setting the `GOOGLE_APPLICATION_CREDENTIALS` environment variable
     * you can the json key contents as an associative array to create an instance of a client
     *
     * @param array $jsonKey
     * @param array $scopes optional scopes @since 2.0.0
     * @return Client
     * @throws Exception
     */
    public static function createWithJsonKey(array $jsonKey, array $scopes = [self::SCOPE_ANDROID_PUBLISHER]): Client
    {
        $credentials = CredentialsLoader::makeCredentials($scopes, $jsonKey);
        $middleware = new AuthTokenMiddleware($credentials);

        return self::createWithMiddleware($middleware);
    }

    /**
     * Creates a client using Auth token middleware
     *
     * @param AuthTokenMiddleware $middleware
     * @return Client
     */
    public static function createWithMiddleware(AuthTokenMiddleware $middleware): Client
    {
        $stack = HandlerStack::create();
        $stack->push($middleware);

        return new Client([
          'handler' => $stack,
          'base_uri' => self::BASE_URI,
          'auth' => self::GOOGLE_AUTH,
        ]);
    }

    /**
     * Creates a client that returns the specified response
     *
     * @param ResponseInterface $responseMock
     * @return Client
     */
    public static function mock(ResponseInterface $responseMock): Client
    {
        $mockHandler = new MockHandler([$responseMock]);
        $handlerStack = HandlerStack::create($mockHandler);

        return new Client(['handler' => $handlerStack]);
    }

    /**
     * Creates a client that returns the specified array of responses in queue order
     *
     * @param array|ResponseInterface[]|RequestExceptionInterface[] $responseQueue
     * @return Client
     */
    public static function mockQueue(array $responseQueue): Client
    {
        $mockHandler = new MockHandler($responseQueue);
        $handlerStack = HandlerStack::create($mockHandler);

        return new Client(['handler' => $handlerStack]);
    }

    /**
     * Creates a client that returns the specified request exception
     *
     * @param RequestExceptionInterface $error
     * @return Client
     */
    public static function mockError(RequestExceptionInterface $error): Client
    {
        $mockHandler = new MockHandler([$error]);
        $handlerStack = HandlerStack::create($mockHandler);

        return new Client(['handler' => $handlerStack]);
    }
}
