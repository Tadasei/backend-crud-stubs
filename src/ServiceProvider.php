<?php

namespace Tadasei\BackendCrudStubs;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Tadasei\BackendCrudStubs\Console\InstallCommand;

class ServiceProvider extends BaseServiceProvider implements DeferrableProvider
{
	/**
	 * Register any application services.
	 */
	public function register(): void
	{
		//
	}

	/**
	 * Bootstrap any application services.
	 */
	public function boot(): void
	{
		if (!$this->app->runningInConsole()) {
			return;
		}

		$this->commands([InstallCommand::class]);
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array<int, string>
	 */
	public function provides(): array
	{
		return [InstallCommand::class];
	}
}
