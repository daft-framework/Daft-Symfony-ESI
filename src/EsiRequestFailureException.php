<?php
/**
* @author SignpostMarv
*/
declare(strict_types=1);

namespace SignpostMarv\Symfony\HttpCache;

use Throwable;

class EsiRequestFailureException extends EsiFailureException
{
	public function __construct(string $uri, int $status, Throwable $previous = null)
	{
		parent::__construct(
			sprintf('Error when rendering request for %s', $uri),
			$status,
			$previous
		);
	}
}
