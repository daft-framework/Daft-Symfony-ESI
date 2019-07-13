<?php
/**
* @author SignpostMarv
*/
declare(strict_types=1);

namespace SignpostMarv\Symfony\HttpCache\Tests\Fixtures;

use SignpostMarv\Symfony\HttpCache\Esi as Base;
use Symfony\Component\HttpFoundation\Request;

class Esi extends Base
{
	public static function FlagRequestAsEsiPublic(Request $request) : void
	{
		parent::FlagRequestAsEsi($request);
	}

	public static function UnflagRequestAsEsiPublic(Request $request) : void
	{
		parent::UnflagRequestAsEsi($request);
	}
}
