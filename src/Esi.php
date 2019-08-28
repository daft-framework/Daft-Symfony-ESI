<?php
/**
* @author SignpostMarv
*/
declare(strict_types=1);

namespace SignpostMarv\Symfony\HttpCache;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpCache\Esi as Base;
use Symfony\Component\HttpKernel\HttpCache\HttpCache;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Throwable;

class Esi extends Base
{
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
		}

		if ( ! ($response instanceof Response)) {
			throw new EsiFailureException('Unable to generate response!');
		} elseif ( ! $response->isSuccessful()) {
			throw new EsiResponseFailureException($uri, $response->getStatusCode());
		}

		return (string) $response->getContent();
	}
}
