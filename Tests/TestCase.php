<?php
/**
* @author SignpostMarv
*/
declare(strict_types=1);

namespace SignpostMarv\Symfony\HttpCache\Tests;

use PHPUnit\Framework\TestCase as Base;

class TestCase extends Base
{
	public function __construct(string $name = '', array $data = [], string $dataName = '')
	{
		parent::__construct($name, $data, $dataName);

		$this->backupGlobals = false;
		$this->backupStaticAttributes = false;
		$this->runTestInSeparateProcess = false;
	}
}
