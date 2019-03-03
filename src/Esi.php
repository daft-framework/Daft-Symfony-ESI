<?php
/**
* @author SignpostMarv
*/
declare(strict_types=1);

namespace SignpostMarv\Symfony\HttpCache;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpCache\Esi as Base;
use Symfony\Component\HttpKernel\HttpCache\HttpCache;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Throwable;

class Esi extends Base
{
    /**
    * @var array<int, Request>
    */
    protected static $esiRequests = [];

    /**
    * {@inheritdoc}
    *
    * @param string $uri
    * @param string $alt
    * @param bool $ignoreErrors
    */
    public function handle(HttpCache $cache, $uri, $alt, $ignoreErrors)
    {
        $request = Request::create(
            $uri,
            Request::METHOD_GET,
            [],
            $cache->getRequest()->cookies->all(),
            [],
            $cache->getRequest()->server->all()
        );

        self::$esiRequests[] = $request;

        /**
        * @var Response|null
        */
        $response = null;

        try {
            $response = $cache->handle($request, HttpKernelInterface::SUB_REQUEST, $ignoreErrors);
        } catch (Throwable $e) {
            if ((bool) $alt) {
                return $this->handle($cache, $alt, '', $ignoreErrors);
            }

            if ( ! $ignoreErrors) {
                throw new EsiRequestFailureException($uri, 0, $e);
            }
        } finally {
            static::UnflagRequestAsEsi($request);
        }

        if ( ! ($response instanceof Response)) {
            throw new EsiFailureException('Unable to generate response!');
        } elseif ( ! $response->isSuccessful()) {
            throw new EsiResponseFailureException($uri, $response->getStatusCode());
        }

        return $response->getContent();
    }

    public static function IsRequestEsi(Request $request) : bool
    {
        return in_array($request, self::$esiRequests, true);
    }

    protected static function FlagRequestAsEsi(Request $request) : void
    {
        static::UnflagRequestAsEsi($request);
        self::$esiRequests[] = $request;
    }

    protected static function UnflagRequestAsEsi(Request $request) : void
    {
        self::$esiRequests = array_filter(
            self::$esiRequests,
            function (Request $maybe) use ($request) : bool {
                return $maybe !== $request;
            }
        );
    }
}
