<?php
/**
* @author SignpostMarv
*/
declare(strict_types=1);

namespace SignpostMarv\Symfony\HttpCache\Tests\Fixtures;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpCache\HttpCache as Base;

class HttpCache extends Base
{
	/**
	* @var Request|null
	*/
	private $request;

	public function getRequest() : Request
	{
		return is_null($this->request) ? parent::getRequest() : $this->request;
	}

	public function setRequest(Request $request) : void
	{
		$this->request = $request;
	}
}
