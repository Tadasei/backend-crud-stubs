<?php

namespace App\Rules;

use Closure;
use InvalidArgumentException;
use Throwable;

use Illuminate\Contracts\Validation\{DataAwareRule, ValidationRule};
use Illuminate\Support\{Arr, Carbon};

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

	public function __construct(
		protected array $validMorphTypes,
		?Closure $dataTypesResolutionCallback = null
	) {
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
					"dateIsNot",
					"dateTimeAfter",
					"dateTimeBefore",
					"dateTimeIs",
					"dateTimeIsNot"
						=> ["string"],
					"equals", "notEquals" => [
						"integer",
						"double",
						"boolean",
						"string",
					],
					"gt", "gte", "lt", "lte" => ["integer", "double"],
					"between",
					"in",
					"notIn",
					"inMany",
					"notInMany",
					"inMorphMany",
					"notInMorphMany"
						=> ["array"],
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
						case "dateAfter":
						case "dateBefore":
						case "dateIs":
						case "dateIsNot":
						case "dateTimeAfter":
						case "dateTimeBefore":
						case "dateTimeIs":
						case "dateTimeIsNot":
							try {
								Carbon::parse($value);
							} catch (Throwable $th) {
								$fail(
									__(
										"Filter value for match mode '$matchMode' must be a valid date string"
									)
								);
							}

							break;

						case "in":
						case "notIn":
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

						case "inMorphMany":
						case "notInMorphMany":
							if (
								Arr::first(
									$value,
									fn($item) => !$this->isAValidMorphValue(
										$item
									)
								)
							) {
								$fail(
									__(
										"Filter value items for match mode '$matchMode' must be arrays with valid id and morphType keys"
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
									fn($item) => !is_scalar($item) ||
										is_bool($item)
								)
							) {
								$fail(
									__(
										"Filter value items for match mode '$matchMode' must be numeric or string only"
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

	private function isAValidMorphValue($value): bool
	{
		return is_array($value) &&
			collect(array_keys($value))
				->sort()
				->values()
				->all() === ["id", "morphType"] &&
			in_array($value["morphType"], $this->validMorphTypes) &&
			is_scalar($value["id"]);
	}
}
