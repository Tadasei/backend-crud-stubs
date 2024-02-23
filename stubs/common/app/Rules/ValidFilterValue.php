<?php

namespace App\Rules;

use Closure;
use Illuminate\Support\Arr;
use InvalidArgumentException;

use Illuminate\Contracts\Validation\{DataAwareRule, ValidationRule};

class ValidFilterValue implements ValidationRule, DataAwareRule
{
	private Closure $dataTypesResolutionCallback;

	/**
	 * All of the data under validation.
	 *
	 * @var array<string, mixed>
	 */
	protected $data = [];

	// ...

	public function __construct(Closure $dataTypesResolutionCallback = null)
	{
		$this->dataTypesResolutionCallback =
			$dataTypesResolutionCallback ??
			function (string $matchMode) {
				return match ($matchMode) {
					"startsWith",
					"endsWith",
					"contains",
					"notContains",
					"dateAfter",
					"dateBefore",
					"dateIs",
					"dateIsNot"
						=> ["string"],
					"equals", "notEquals" => [
						"integer",
						"double",
						"boolean",
						"string",
					],
					"gt", "gte", "lt", "lte" => ["integer", "double"],
					"between", "in", "inMany", "notInMany" => ["array"],
					default => null,
				};
			};
	}

	/**
	 * Set the data under validation.
	 *
	 * @param  array<string, mixed>  $data
	 */
	public function setData(array $data): static
	{
		$this->data = $data;

		return $this;
	}

	/**
	 * Run the validation rule.
	 *
	 * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
	 */
	public function validate(
		string $attribute,
		mixed $value,
		Closure $fail
	): void {
		$matchModeAttribute = str($attribute)
			->replaceLast("value", "matchMode")
			->value();

		if (Arr::has($this->data, $matchModeAttribute)) {
			$matchMode = Arr::get($this->data, $matchModeAttribute);

			$matchModeDataTypes = ($this->dataTypesResolutionCallback)(
				$matchMode
			);

			if (!is_null($matchModeDataTypes)) {
				if (
					!is_array($matchModeDataTypes) ||
					empty($matchModeDataTypes)
				) {
					throw new InvalidArgumentException(
						"Invalid value data types for the selected match mode"
					);
				}

				if (!in_array(gettype($value), $matchModeDataTypes)) {
					$fail(__("Invalid filter value data type"));
				} else {
					switch ($matchMode) {
						case "in":
						case "inMany":
						case "notInMany":
							if (
								Arr::first(
									$value,
									fn($item) => !is_scalar($item)
								)
							) {
								$fail(
									__(
										"Filter value items for match mode '$matchMode' must be scalars only"
									)
								);
							}
							break;

						case "between":
							if (count($value) === 1 || count($value) > 2) {
								$fail(
									__(
										"Filter value items count for match mode '$matchMode' must be exactly 2"
									)
								);
								break;
							}

							if (
								Arr::first(
									$value,
									fn($item) => !is_numeric($item)
								)
							) {
								$fail(
									__(
										"Filter value items for match mode '$matchMode' must be numeric only"
									)
								);
								break;
							}

						default:
							break;
					}
				}
			}
		}
	}
}
