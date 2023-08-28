<?php

namespace Tadasei\BackendCrudStubs\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

class InstallCommand extends Command
{
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'make:crud-back {model : The model name to scaffold the CRUD backend for.}
                            {--stack=api : The stack name to integrate the CRUD backend with. Defaults to "api".}';
	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = "Scaffold CRUD backend.";

	/**
	 * Execute the console command.
	 *
	 * @return int|null
	 */
	public function handle()
	{
		$model = $this->argument("model");
		$stack = $this->option("stack");

		// Check stack validity
		if (!in_array($stack, ["blade", "vue", "api"])) {
			$this->components->error(
				"Invalid stack. Supported stacks are [blade], [vue], and [api]."
			);
		} else {
			// Install the stack
			$paths = [
				"delete_request" => app_path(
					"Http/Requests/$model/Delete{$model}Request.php"
				),
				"base_request" => app_path(
					"Http/Requests/$model/{$model}Request.php"
				),
				"store_request" => app_path(
					"Http/Requests/$model/Store{$model}Request.php"
				),
				"update_request" => app_path(
					"Http/Requests/$model/Update{$model}Request.php"
				),
				"policies" => app_path("Policies/{$model}Policy.php"),
				"controllers" => app_path(
					"Http/Controllers/{$model}Controller.php"
				),
				"routes" => base_path(
					"routes/" . str($model)->lower() . ".php"
				),
			];

			foreach (
				[
					base_path("routes"),
					app_path("Http/Controllers"),
					app_path("Http/Requests/$model"),
					app_path("Policies"),
				]
				as $target_directory
			) {
				if (!file_exists($target_directory)) {
					mkdir($target_directory, recursive: true);
				}
			}

			// Common files

			//Requests

			copy(
				__DIR__ .
					"/../../stubs/common/app/Http/Requests/Stub/DeleteStubRequest.php",
				$paths["delete_request"]
			);

			copy(
				__DIR__ .
					"/../../stubs/common/app/Http/Requests/Stub/StubRequest.php",
				$paths["base_request"]
			);

			copy(
				__DIR__ .
					"/../../stubs/common/app/Http/Requests/Stub/StoreStubRequest.php",
				$paths["store_request"]
			);

			copy(
				__DIR__ .
					"/../../stubs/common/app/Http/Requests/Stub/UpdateStubRequest.php",
				$paths["update_request"]
			);

			//Policies

			copy(
				__DIR__ . "/../../stubs/common/app/Policies/StubPolicy.php",
				$paths["policies"]
			);

			// Stack specific files

			//Controllers

			copy(
				__DIR__ .
					"/../../stubs/$stack/app/Http/Controllers/StubController.php",
				$paths["controllers"]
			);

			//Routes

			copy(
				__DIR__ . "/../../stubs/$stack/routes/stub.php",
				$paths["routes"]
			);

			// Updating files content with model name

			foreach ($paths as $path) {
				$this->replaceInFile("Stubs", str($model)->plural(), $path);

				$this->replaceInFile("Stub", $model, $path);

				$this->replaceInFile(
					"stubs",
					str($model)
						->lower()
						->plural(),
					$path
				);

				$this->replaceInFile("stub", str($model)->lower(), $path);
			}

			$this->components->info("Scaffolding complete.");
		}

		return 1;
	}

	/**
	 * Interact with the user to prompt them when the stack argument is missing.
	 *
	 * @param  \Symfony\Component\Console\Input\InputInterface  $input
	 * @param  \Symfony\Component\Console\Output\OutputInterface  $output
	 * @return void
	 */
	protected function interact(InputInterface $input, OutputInterface $output)
	{
		if ($this->argument("model")) {
			return;
		}

		$input->setArgument(
			"model",
			$this->components->ask("Please input the model name to use")
		);
	}

	/**
	 * Install the middleware to a group in the application Http Kernel.
	 *
	 * @param  string  $after
	 * @param  string  $name
	 * @param  string  $group
	 * @return void
	 */
	protected function installMiddlewareAfter($after, $name, $group = "web")
	{
		$httpKernel = file_get_contents(app_path("Http/Kernel.php"));

		$middlewareGroups = Str::before(
			Str::after($httpKernel, '$middlewareGroups = ['),
			"];"
		);
		$middlewareGroup = Str::before(
			Str::after($middlewareGroups, "'$group' => ["),
			"],"
		);

		if (!Str::contains($middlewareGroup, $name)) {
			$modifiedMiddlewareGroup = str_replace(
				$after . ",",
				$after . "," . PHP_EOL . "            " . $name . ",",
				$middlewareGroup
			);

			file_put_contents(
				app_path("Http/Kernel.php"),
				str_replace(
					$middlewareGroups,
					str_replace(
						$middlewareGroup,
						$modifiedMiddlewareGroup,
						$middlewareGroups
					),
					$httpKernel
				)
			);
		}
	}

	/**
	 * Installs the given Composer Packages into the application.
	 *
	 * @param  array  $packages
	 * @param  bool  $asDev
	 * @return bool
	 */
	protected function requireComposerPackages(array $packages, $asDev = false)
	{
		$composer = $this->option("composer");

		if ($composer !== "global") {
			$command = ["php", $composer, "require"];
		}

		$command = array_merge(
			$command ?? ["composer", "require"],
			$packages,
			$asDev ? ["--dev"] : []
		);

		return (new Process($command, base_path(), [
			"COMPOSER_MEMORY_LIMIT" => "-1",
		]))
			->setTimeout(null)
			->run(function ($type, $output) {
				$this->output->write($output);
			}) === 0;
	}

	/**
	 * Removes the given Composer Packages from the application.
	 *
	 * @param  array  $packages
	 * @param  bool  $asDev
	 * @return bool
	 */
	protected function removeComposerPackages(array $packages, $asDev = false)
	{
		$composer = $this->option("composer");

		if ($composer !== "global") {
			$command = ["php", $composer, "remove"];
		}

		$command = array_merge(
			$command ?? ["composer", "remove"],
			$packages,
			$asDev ? ["--dev"] : []
		);

		return (new Process($command, base_path(), [
			"COMPOSER_MEMORY_LIMIT" => "-1",
		]))
			->setTimeout(null)
			->run(function ($type, $output) {
				$this->output->write($output);
			}) === 0;
	}

	/**
	 * Update the "package.json" file.
	 *
	 * @param  callable  $callback
	 * @param  bool  $dev
	 * @return void
	 */
	protected static function updateNodePackages(
		callable $callback,
		$dev = true
	) {
		if (!file_exists(base_path("package.json"))) {
			return;
		}

		$configurationKey = $dev ? "devDependencies" : "dependencies";

		$packages = json_decode(
			file_get_contents(base_path("package.json")),
			true
		);

		$packages[$configurationKey] = $callback(
			array_key_exists($configurationKey, $packages)
				? $packages[$configurationKey]
				: [],
			$configurationKey
		);

		ksort($packages[$configurationKey]);

		file_put_contents(
			base_path("package.json"),
			json_encode($packages, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) .
				PHP_EOL
		);
	}

	/**
	 * Delete the "node_modules" directory and remove the associated lock files.
	 *
	 * @return void
	 */
	protected static function flushNodeModules()
	{
		tap(new Filesystem(), function ($files) {
			$files->deleteDirectory(base_path("node_modules"));

			$files->delete(base_path("yarn.lock"));
			$files->delete(base_path("package-lock.json"));
		});
	}

	/**
	 * Replace a given string within a given file.
	 *
	 * @param  string  $search
	 * @param  string  $replace
	 * @param  string  $path
	 * @return void
	 */
	protected function replaceInFile($search, $replace, $path)
	{
		file_put_contents(
			$path,
			str_replace($search, $replace, file_get_contents($path))
		);
	}

	/**
	 * Get the path to the appropriate PHP binary.
	 *
	 * @return string
	 */
	protected function phpBinary()
	{
		return (new PhpExecutableFinder())->find(false) ?: "php";
	}

	/**
	 * Run the given commands.
	 *
	 * @param  array  $commands
	 * @return void
	 */
	protected function runCommands($commands)
	{
		$process = Process::fromShellCommandline(
			implode(" && ", $commands),
			null,
			null,
			null,
			null
		);

		if (
			"\\" !== DIRECTORY_SEPARATOR &&
			file_exists("/dev/tty") &&
			is_readable("/dev/tty")
		) {
			try {
				$process->setTty(true);
			} catch (RuntimeException $e) {
				$this->output->writeln(
					"  <bg=yellow;fg=black> WARN </> " .
						$e->getMessage() .
						PHP_EOL
				);
			}
		}

		$process->run(function ($type, $line) {
			$this->output->write("    " . $line);
		});
	}
}
