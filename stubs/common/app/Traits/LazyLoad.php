<?php

namespace App\Traits;

use App\Http\Requests\LazyLoadRequest;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;

use Illuminate\Database\Eloquent\{Builder, Collection};

trait LazyLoad
{
	private function getFilterQueryClause(
		Builder $query,
		string $fieldName,
		string $matchMode,
		$value,
		string $operator = "and"
	): Builder {
		return match ($operator) {
			"and" => match ($matchMode) {
				"startsWith" => $query->where($fieldName, "like", "$value%"),
				"endsWith" => $query->where($fieldName, "like", "%$value"),
				"contains" => $query->where($fieldName, "like", "%$value%"),
				"notContains" => $query->where(
					$fieldName,
					"not like",
					"%$value%"
				),
				"equals" => $query->where($fieldName, "=", $value),
				"notEquals" => $query->where($fieldName, "!=", $value),
				"gt" => $query->where($fieldName, ">", $value),
				"gte" => $query->where($fieldName, ">=", $value),
				"lt" => $query->where($fieldName, "<", $value),
				"lte" => $query->where($fieldName, "<=", $value),
				"between" => $query->whereBetween($fieldName, $value),
				"in" => $query->whereIn($fieldName, $value),
				"notIn" => $query->whereNotIn($fieldName, $value),
				"inMany" => $query->whereHas($fieldName, function (
					Builder $nestedQuery
				) use ($value, $operator) {
					$nestedQuery = $this->getFilterQueryClause(
						$nestedQuery,
						"id",
						"in",
						$value,
						$operator
					);
				}),
				"notInMany" => $query->whereDoesntHave($fieldName, function (
					Builder $nestedQuery
				) use ($value, $operator) {
					$nestedQuery = $this->getFilterQueryClause(
						$nestedQuery,
						"id",
						"in",
						$value,
						$operator
					);
				}),
				"inMorphMany" => $query->whereHasMorph(
					$fieldName,
					collect($value)
						->pluck("morphType")
						->all(),
					function (Builder $nestedQuery, string $type) use (
						$value,
						$operator
					) {
						$nestedQuery = $this->getFilterQueryClause(
							$nestedQuery,
							"id",
							"in",
							collect($value)
								->where("morphType", $type)
								->pluck("id")
								->all(),
							$operator
						);
					}
				),
				"notInMorphMany" => $query->whereDoesntHaveMorph(
					$fieldName,
					collect($value)
						->pluck("morphType")
						->all(),
					function (Builder $nestedQuery, string $type) use (
						$value,
						$operator
					) {
						$nestedQuery = $this->getFilterQueryClause(
							$nestedQuery,
							"id",
							"in",
							collect($value)
								->where("morphType", $type)
								->pluck("id")
								->all(),
							$operator
						);
					}
				),
				"dateAfter", "dateBefore", "dateIs", "dateIsNot" => match (
				$matchMode
				) {
					"dateAfter" => $query->whereDate(
						$fieldName,
						">",
						Carbon::parse($value)->toDateString()
					),
					"dateBefore" => $query->whereDate(
						$fieldName,
						"<",
						Carbon::parse($value)->toDateString()
					),
					"dateIs" => $query->whereDate(
						$fieldName,
						"=",
						Carbon::parse($value)->toDateString()
					),
					"dateIsNot" => $query->whereDate(
						$fieldName,
						"!=",
						Carbon::parse($value)->toDateString()
					),
				},
			},
			"or" => match ($matchMode) {
				"startsWith" => $query->orWhere($fieldName, "like", "$value%"),
				"endsWith" => $query->orWhere($fieldName, "like", "%$value"),
				"contains" => $query->orWhere($fieldName, "like", "%$value%"),
				"notContains" => $query->orWhere(
					$fieldName,
					"not like",
					"%$value%"
				),
				"equals" => $query->orWhere($fieldName, "=", $value),
				"notEquals" => $query->orWhere($fieldName, "!=", $value),
				"gt" => $query->orWhere($fieldName, ">", $value),
				"gte" => $query->orWhere($fieldName, ">=", $value),
				"lt" => $query->orWhere($fieldName, "<", $value),
				"lte" => $query->orWhere($fieldName, "<=", $value),
				"between" => $query->orWhereBetween($fieldName, $value),
				"in" => $query->orWhereIn($fieldName, $value),
				"notIn" => $query->orWhereNotIn($fieldName, $value),
				"inMany" => $query->orWhereHas($fieldName, function (
					Builder $nestedQuery
				) use ($value, $operator) {
					$nestedQuery = $this->getFilterQueryClause(
						$nestedQuery,
						"id",
						"in",
						$value,
						$operator
					);
				}),
				"notInMany" => $query->orWhereDoesntHave($fieldName, function (
					Builder $nestedQuery
				) use ($value, $operator) {
					$nestedQuery = $this->getFilterQueryClause(
						$nestedQuery,
						"id",
						"in",
						$value,
						$operator
					);
				}),
				"inMorphMany" => $query->orWhereHasMorph(
					$fieldName,
					collect($value)
						->pluck("morphType")
						->all(),
					function (Builder $nestedQuery, string $type) use (
						$value,
						$operator
					) {
						$nestedQuery = $this->getFilterQueryClause(
							$nestedQuery,
							"id",
							"in",
							collect($value)
								->where("morphType", $type)
								->pluck("id")
								->all(),
							$operator
						);
					}
				),
				"notInMorphMany" => $query->orWhereDoesntHaveMorph(
					$fieldName,
					collect($value)
						->pluck("morphType")
						->all(),
					function (Builder $nestedQuery, string $type) use (
						$value,
						$operator
					) {
						$nestedQuery = $this->getFilterQueryClause(
							$nestedQuery,
							"id",
							"in",
							collect($value)
								->where("morphType", $type)
								->pluck("id")
								->all(),
							$operator
						);
					}
				),
				"dateAfter", "dateBefore", "dateIs", "dateIsNot" => match (
				$matchMode
				) {
					"dateAfter" => $query->orWhereDate(
						$fieldName,
						">",
						Carbon::parse($value)->toDateString()
					),
					"dateBefore" => $query->orWhereDate(
						$fieldName,
						"<",
						Carbon::parse($value)->toDateString()
					),
					"dateIs" => $query->orWhereDate(
						$fieldName,
						"=",
						Carbon::parse($value)->toDateString()
					),
					"dateIsNot" => $query->orWhereDate(
						$fieldName,
						"!=",
						Carbon::parse($value)->toDateString()
					),
				},
			},
		};
	}

	private function getLazyLoadedData(
		LazyLoadRequest $request,
		Builder $query
	): LengthAwarePaginator|Collection {
		$filters = $request->input("filters");

		if ($filters) {
			$globalFilter = Arr::pull($filters, "global");

			foreach ($filters as $field => $filterMeta) {
				$query = $query->where(function (Builder $metaQuery) use (
					$field,
					$filterMeta
				) {
					if (!array_key_exists("constraints", $filterMeta)) {
						$metaQuery = $this->getFilterQueryClause(
							$metaQuery,
							$field,
							$filterMeta["matchMode"],
							$filterMeta["value"]
						);
					} else {
						foreach (
							$filterMeta["constraints"]
							as ["value" => $value, "matchMode" => $matchMode]
						) {
							$metaQuery = $this->getFilterQueryClause(
								$metaQuery,
								$field,
								$matchMode,
								$value,
								$filterMeta["operator"]
							);
						}
					}
				});
			}

			$query = $query->when(
				$globalFilter,
				fn(
					Builder $conditionalWrapperQuery
				) => $conditionalWrapperQuery->where(function (
					Builder $globalFilterQuery
				) use ($globalFilter, $request) {
					foreach (
						$request->globalFilterFields
						as $globalFilterField
					) {
						$globalFilterQuery = $this->getFilterQueryClause(
							$globalFilterQuery,
							$globalFilterField,
							$globalFilter["matchMode"],
							str($globalFilter["value"])->lower(),
							"or"
						);
					}
				})
			);
		}

		if ($request->has("multiSortMeta")) {
			foreach (
				$request->multiSortMeta
				as ["field" => $sortField, "order" => $sortOrder]
			) {
				$query = $query->orderBy(
					$sortField,
					$sortOrder === -1 ? "desc" : "asc"
				);
			}
		}

		return $request->paginate
			? $query->paginate($request->rows)
			: $query->get();
	}
}
