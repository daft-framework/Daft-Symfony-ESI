<?php
/**
* @author SignpostMarv
*/
declare(strict_types=1);

namespace SignpostMarv\Symfony\HttpCache\Tests;

use Closure;
use RuntimeException;
use SignpostMarv\Symfony\HttpCache\Esi;
use SignpostMarv\Symfony\HttpCache\EsiFailureException;
use SignpostMarv\Symfony\HttpCache\EsiRequestFailureException;
use SignpostMarv\Symfony\HttpCache\EsiResponseFailureException;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Controller\ArgumentResolver;
use Symfony\Component\HttpKernel\Controller\ControllerResolver;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpCache\Store;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class EsiTest extends TestCase
{
    /**
    * @psalm-return array<int, array{0:string, 1:Closure(GetResponseEvent):void, 2:string}>
    */
    public function DataProviderTestEsi() : array
    {
        return [
            [
                '/test',
                function (GetResponseEvent $e) : void {
                    if ( ! $e->hasResponse()) {
                        switch ($e->getRequest()->getUri()) {
                            case 'http://localhost/test':
                                $response = new Response(
                                    '<esi:include src="foo" />'
                                );
                                $response->headers->set('Surrogate-Control', 'content="ESI/1.0"');
                                $e->setResponse($response);
                                break;
                            case 'http://localhost/foo':
                                $e->setResponse(new Response('foo'));
                                break;
                        }
                    }
                },
                'foo',
            ],
            [
                '/test',
                function (GetResponseEvent $e) : void {
                    if ( ! $e->hasResponse()) {
                        switch ($e->getRequest()->getUri()) {
                            case 'http://localhost/test':
                                $response = new Response(
                                    '<esi:include src="foo" alt="bar" />',
                                    Response::HTTP_OK
                                );
                                $response->headers->set('Surrogate-Control', 'content="ESI/1.0"');
                                $e->setResponse($response);
                                break;
                            case 'http://localhost/foo':
                                throw new RuntimeException('thrown on purpose');
                            case 'http://localhost/bar':
                                $e->setResponse(new Response('bat', Response::HTTP_OK));
                                break;
                        }
                    }
                },
                'bat',
            ],
        ];
    }

    /**
    * @psalm-return array<int, array{0:string, 1:Closure(GetResponseEvent):void, 2:class-string<\Throwable>, 3:string}>
    */
    public function DataProviderTestEsiBad() : array
    {
        return [
            [
                '/test',
                function (GetResponseEvent $e) : void {
                    if ( ! $e->hasResponse()) {
                        switch ($e->getRequest()->getUri()) {
                            case 'http://localhost/test':
                                $response = new Response(
                                    '<esi:include src="foo" />',
                                    Response::HTTP_OK
                                );
                                $response->headers->set('Surrogate-Control', 'content="ESI/1.0"');
                                $e->setResponse($response);
                                break;
                            case 'http://localhost/foo':
                                throw new RuntimeException('thrown on purpose');
                        }
                    }
                },
                EsiRequestFailureException::class,
                'Error when rendering request for foo',
            ],
            [
                '/test',
                function (GetResponseEvent $e) : void {
                    if ( ! $e->hasResponse()) {
                        switch ($e->getRequest()->getUri()) {
                            case 'http://localhost/test':
                                $response = new Response(
                                    '<esi:include src="foo" />',
                                    Response::HTTP_OK
                                );
                                $response->headers->set('Surrogate-Control', 'content="ESI/1.0"');
                                $e->setResponse($response);
                                break;
                            case 'http://localhost/foo':
                                $e->setResponse(new Response('bat', Response::HTTP_BAD_REQUEST));
                                break;
                        }
                    }
                },
                EsiResponseFailureException::class,
                'Error when rendering request for foo, received http status 400',
            ],
        ];
    }

    /**
    * @dataProvider DataProviderTestEsi
    */
    public function testEsi(string $uri, Closure $eventHandler, string $expectedContent) : void
    {
        $dispatcher = new EventDispatcher();
        $dispatcher->addListener(KernelEvents::REQUEST, $eventHandler);
        $cache = static::ObtainHttpCache($dispatcher);

        $request = Request::create($uri);
        $response = $cache->handle($request, HttpKernelInterface::MASTER_REQUEST, true);
        $cache->terminate($request, $response);

        static::assertSame($expectedContent, $response->getContent());
    }

    /**
    * @dataProvider DataProviderTestEsiBad
    *
    * @psalm-param class-string<\Throwable> $expectedException
    */
    public function testEsiBad(
        string $uri,
        Closure $eventHandler,
        string $expectedException,
        string $expectedExceptionMessage
    ) : void {
        $dispatcher = new EventDispatcher();
        $dispatcher->addListener(KernelEvents::REQUEST, $eventHandler);
        $cache = static::ObtainHttpCache($dispatcher);

        $request = Request::create($uri);

        static::expectException($expectedException);
        static::expectExceptionMessage($expectedExceptionMessage);

        while (ob_get_level()) {
            ob_end_clean();
        }

        $cache->handle($request, HttpKernelInterface::MASTER_REQUEST, false);
    }

    public function testEsiNoResponse() : void
    {
        $dispatcher = new EventDispatcher();
        $esi = new Esi();
        $cache = static::ObtainHttpCache($dispatcher, $esi);
        $cache->setRequest(new Request());

        static::expectException(EsiFailureException::class);
        static::expectExceptionMessage('Unable to generate response!');

        $esi->handle($cache, '/foo', '', true);
    }

    protected static function ObtainHttpCache(
        EventDispatcher $dispatcher,
        Esi $esi = null
    ) : Fixtures\HttpCache {
        return new Fixtures\HttpCache(
            new HttpKernel(
                $dispatcher,
                new ControllerResolver(),
                new RequestStack(),
                new ArgumentResolver()
            ),
            new Store(__DIR__ . '/../symfony-http-cache/'),
            is_null($esi) ? new Esi() : $esi
        );
    }
}
