<?php
/**
* @author SignpostMarv
*/
declare(strict_types=1);

namespace SignpostMarv\Symfony\HttpCache\Tests;

use Symfony\Component\HttpFoundation\Request;

class FlagTest extends TestCase
{
	public function testFlagUnflag() : void
	{
		$request = new Request();

		static::assertFalse(Fixtures\Esi::IsRequestEsi($request));

		Fixtures\Esi::FlagRequestAsEsiPublic($request);

		static::assertTrue(Fixtures\Esi::IsRequestEsi($request));
		static::assertFalse(Fixtures\Esi::IsRequestEsi(clone $request));

		Fixtures\Esi::UnflagRequestAsEsiPublic($request);

		static::assertFalse(Fixtures\Esi::IsRequestEsi($request));
	}
}
