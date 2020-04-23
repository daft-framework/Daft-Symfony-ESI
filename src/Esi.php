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
	const DEFAULT_EXCEPTION_CODE = 0;

	/**
	 * {@inheritdoc}
	 *
	 * @param string $uri
	 * @param string $alt
	 * @param bool $ignoreErrors
	 */
	public function handle(HttpCache $cache, $uri, $alt, $ignoreErrors)
	{
		$request = $this->handleRequestGeneration($cache, $uri);

		/**
		 * @var Response|null
		 */
		$response = null;

		try {
			$response = $cache->handle($request, HttpKernelInterface::SUB_REQUEST, $ignoreErrors);
		} catch (Throwable $e) {
			if ('' !== $alt) {
				return $this->handle($cache, $alt, '', $ignoreErrors);
			}

			if ( ! $ignoreErrors) {
				throw new EsiRequestFailureException(
					$uri,
					self::DEFAULT_EXCEPTION_CODE,
					$e
				);
			}
		}

		if ( ! ($response instanceof Response)) {
			throw new EsiFailureException('Unable to generate response!');
		} elseif ( ! $response->isSuccessful()) {
			throw new EsiResponseFailureException($uri, $response->getStatusCode());
		}

		/**
		 * @var string
		 */
		return $response->getContent();
	}

	protected function handleRequestGeneration(
		HttpCache $cache,
		string $uri
	) : Request {
		$request = Request::create(
			$uri,
			Request::METHOD_GET,
			[],
			$cache->getRequest()->cookies->all(),
			[],
			$cache->getRequest()->server->all()
		);

		return $request;
	}
}
