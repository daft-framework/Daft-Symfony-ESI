<?php
/**
* @author SignpostMarv
*/
declare(strict_types=1);

namespace SignpostMarv\Symfony\HttpCache;

use Throwable;

class EsiResponseFailureException extends EsiFailureException
{
	public function __construct(string $uri, int $code, Throwable $previous = null)
	{
		parent::__construct(
			sprintf(
				'Error when rendering request for %s, received http status %u',
				$uri,
				$code
			),
			$code,
			$previous
		);
	}
}
