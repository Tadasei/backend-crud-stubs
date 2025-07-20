<?php

namespace Tests;

use Illuminate\Foundation\Testing\{RefreshDatabase, TestCase as BaseTestCase};

abstract class TestCase extends BaseTestCase
{
	use RefreshDatabase;

	/**
	 * Indicates whether the default seeder should run before each test.
	 *
	 * @var bool
	 */
	protected $seed = true;
}
