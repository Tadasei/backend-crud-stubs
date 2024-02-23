<?php

namespace App\Http\Requests;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

use App\Rules\{PresentWithout, ValidFilterValue};

class LazyLoadRequest extends FormRequest
{
	/**
	 * Get the validation rules that apply to the request.
	 *
	 * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
	 */
	public function rules(): array
	{
		return [
			"first" => ["required", "numeric", "integer", "min:0"],
			"rows" => ["required", "numeric", "integer", "min:0"],
			"sortField" => [
				"missing_with:multiSortMeta",
				new PresentWithout(["multiSortMeta"]),
				"required_with:sortOrder",
				"string",
				"max:255",
			],
			"sortOrder" => [
				"missing_with:multiSortMeta",
				new PresentWithout(["multiSortMeta"]),
				"required_with:sortField",
				Rule::in([0, 1, -1]),
			],
			"multiSortMeta" => [
				"missing_with:sortField,sortOrder",
				new PresentWithout(["sortField", "sortOrder"]),
				"array",
			],
			"multiSortMeta.*" => ["array:field,order"],
			"multiSortMeta.*.field" => [
				"required",
				"distinct:strict",
				"string",
				"max:255",
			],
			"multiSortMeta.*.order" => [
				"present",
				"nullable",
				Rule::in([0, 1, -1]),
			],
			"filters" => ["present", "array"],
			"filters.*" => [
				"array:value,matchMode,operator,constraints",
				function (string $attribute, mixed $value, Closure $fail) {
					$possibleFields = [
						"value",
						"matchMode",
						"operator",
						"constraints",
					];

					if (!Arr::hasAny($value, $possibleFields)) {
						$fail(
							__(
								"The fields $attribute.value and $attribute.matchMode or $attribute.operator and $attribute.constraints must be present"
							)
						);
					} else {
						$errors = [];

						foreach ($possibleFields as $presentField) {
							$prohibitedFields = match ($presentField) {
								"value", "matchMode" => [
									"operator",
									"constraints",
								],
								"operator", "constraints" => [
									"value",
									"matchMode",
								],
							};

							if (Arr::has($value, $presentField)) {
								foreach (
									$prohibitedFields
									as $prohibitedField
								) {
									if (Arr::has($value, $prohibitedField)) {
										$errors[] = __(
											"The $attribute.$prohibitedField field must be missing when $attribute.$presentField is present"
										);
									}
								}
							}
						}

						if ($errors) {
							$fail(Arr::join($errors, "\n"));
						}
					}
				},
			],
			"filters.*.value" => [
				"required_with:filters.*.matchMode",
				new ValidFilterValue(),
			],
			"filters.*.matchMode" => [
				"required_with:filters.*.value",
				Rule::in($this->getValidMatchModes()),
			],
			"filters.*.operator" => [
				"required_with:filters.*.constraints",
				Rule::in(["and", "or"]),
			],
			"filters.*.constraints" => [
				"required_with:filters.*.operator",
				"array",
			],
			"filters.*.constraints.*" => ["array:value,matchMode"],
			"filters.*.constraints.*.value" => [
				"required",
				new ValidFilterValue(),
			],
			"filters.*.constraints.*.matchMode" => [
				"required_with:filters.*.constraints.*.value",
				Rule::in($this->getValidMatchModes()),
			],
			"globalFilterFields" => ["array"],
			"globalFilterFields.*" => [
				"required",
				"distinct:strict",
				"string",
				"max:255",
			],
			"page" => ["required", "numeric", "integer", "min:1"],
			"pageCount" => ["sometimes", "numeric", "integer", "min:1"],
			"paginate" => ["required", "boolean"],
		];
	}

	private function getValidMatchModes(): array
	{
		return [
			"startsWith",
			"endsWith",
			"contains",
			"notContains",
			"equals",
			"notEquals",
			"gt",
			"gte",
			"lt",
			"lte",
			"between",
			"in",
			"inMany",
			"notInMany",
			"dateAfter",
			"dateBefore",
			"dateIs",
			"dateIsNot",
		];
	}
}
