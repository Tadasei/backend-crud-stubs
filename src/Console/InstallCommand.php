<?php

namespace Tadasei\BackendCrudStubs\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\Process\{
	PhpExecutableFinder,
	Process
};

class InstallCommand extends Command
{
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'crud:generate {resource : The CRUD resource name.}
                            {--stack=api : The stack to integrate the CRUD backend with. Defaults to "api".}';
	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = "Generate CRUD backend.";

	/**
	 * Execute the console command.
	 *
	 * @return int|null
	 */
	public function handle()
	{
		$model = $this->argument("resource");
		$stack = $this->option("stack");

		// Check stack validity
		if (!in_array($stack, ["blade", "vue", "api"])) {
			$this->components->error(
				"Invalid stack. Supported stacks are [blade], [vue], and [api]."
			);
		} else {
			// Install the stack

			$publishedFiles = collect([
				// Common stubs
				__DIR__ . "/../../stubs/common",

				// Stack specific stubs
				__DIR__ . "/../../stubs/$stack",
			])->flatMap(
				fn(string $directory) => $this->publishDirectory($directory)
			);

			// Update model specific stubs content with model name

			$publishedFiles
				->where("isModelSpecific", true)
				->each(function (array $file) use ($model) {
					$this->replaceInFile(
						"Stubs",
						str($model)->plural(),
						$file["target"]
					);

					$this->replaceInFile("Stub", $model, $file["target"]);

					$this->replaceInFile(
						'$stubs',
						str($model)
							->plural()
							->snake()
							->prepend('$'),
						$file["target"]
					);

					$this->replaceInFile(
						'$stub',
						str($model)
							->snake()
							->prepend('$'),
						$file["target"]
					);

					$this->replaceInFile(
						"->stubs",
						str($model)
							->plural()
							->snake()
							->prepend("->"),
						$file["target"]
					);

					$this->replaceInFile(
						"->stub",
						str($model)
							->snake()
							->prepend("->"),
						$file["target"]
					);

					if ($this->isControllerTestFile($file)) {
						$this->replaceInFile(
							'stubs"]',
							str($model)
								->plural()
								->headline()
								->lower() . '"]',
							$file["target"]
						);
					}

					if ($this->isPolicyFile($file)) {
						$this->replaceInFile(
							"stubs",
							str($model)
								->plural()
								->headline()
								->lower(),
							$file["target"]
						);

						$this->replaceInFile(
							"stub",
							str($model)
								->headline()
								->lower(),
							$file["target"]
						);
					} else {
						$this->replaceInFile(
							"stubs",
							str($model)
								->plural()
								->snake(),
							$file["target"]
						);

						$this->replaceInFile(
							"stub",
							str($model)->snake(),
							$file["target"]
						);
					}
				});

			// Notify of completion

			$this->components->info("Scaffolding complete.");
		}

		return 0;
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
		if ($this->argument("resource")) {
			return;
		}

		$input->setArgument(
			"resource",
			$this->components->ask("Please input the CRUD resource name to use")
		);
	}

	protected function isPolicyFile(array $file): bool
	{
		return str_ends_with($file["name"], "Policy.php");
	}

	protected function isControllerTestFile(array $file): bool
	{
		return str_ends_with($file["name"], "ControllerTest.php");
	}

	protected function renameStub(string $stub, string $model): string
	{
		return str_replace(
			["Stub", "stub"],
			[
				$model,
				str($model)
					->snake()
					->value(),
			],
			$stub
		);
	}

	protected function isModelSpecificFile(array $file): bool
	{
		return str_contains($file["name"], "Stub") ||
			str_contains($file["name"], "stub");
	}

	protected function getTargetFile(array $file): array
	{
		$model = $this->argument("resource");

		["name" => $name, "target" => $target] = $file;

		$isModelSpecific = $this->isModelSpecificFile($file);

		if ($isModelSpecific) {
			$name = $this->renameStub($name, $model);

			$target = $this->renameStub($target, $model);
		}

		return [
			"source" => $file["source"],

			"name" => $name,

			"target" => $target,

			"isModelSpecific" => $isModelSpecific,
		];
	}

	protected function publishDirectory(
		string $directory,
		?string $prefix = null
	): array {
		$files = collect($this->listDirectoryFiles($directory, $prefix))
			->map(fn(array $file) => $this->getTargetFile($file))
			->all();

		// Ensuring target directories exist

		$this->ensureTargetDirectoriesExist($files);

		// Copying files

		$copiedFiles = $this->copyFiles($files);

		return $copiedFiles;
	}

	protected function copyFiles(array $files): array
	{
		return collect($files)
			->filter(fn(array $file) => !file_exists($file["target"]))
			->each(fn(array $file) => copy($file["source"], $file["target"]))
			->all();
	}

	protected function ensureTargetDirectoriesExist(array $files): void
	{
		collect($files)
			->map(
				fn(array $file) => str_replace(
					"/{$file["name"]}",
					"",
					$file["target"]
				)
			)
			->unique()
			->filter(
				fn(string $targetDirectory) => !file_exists($targetDirectory)
			)
			->each(
				fn(string $targetDirectory) => mkdir(
					$targetDirectory,
					recursive: true
				)
			);
	}

	protected function listDirectoryFiles(
		string $directory,
		?string $prefix = null
	): array {
		$directoryMap = $this->getDirectoryMap($directory, $prefix);

		return $this->getDirectoryMapFiles($directoryMap);
	}

	protected function getDirectoryMapFiles(array $directoryMap): array
	{
		return collect($directoryMap)
			->flatMap(
				fn(array $item) => key_exists("map", $item)
					? $this->getDirectoryMapFiles($item["map"])
					: [$item]
			)
			->all();
	}

	protected function getDirectoryMap(
		string $directory,
		?string $prefix = null
	): array {
		$prefix ??= "$directory/";

		return collect(scandir($directory))
			->reject(fn(string $name) => in_array($name, [".", ".."]))
			->values()
			->map(function (string $name) use ($directory, $prefix) {
				$source = "$directory/$name";

				return [
					"name" => $name,
					"source" => $source,
					...is_dir($source)
						? [
							"map" => $this->getDirectoryMap($source, $prefix),
						]
						: [
							"target" => base_path(
								str_replace($prefix, "", $directory) . "/$name"
							),
						],
				];
			})
			->all();
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
