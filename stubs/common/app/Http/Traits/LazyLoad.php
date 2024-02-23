<?php

namespace App\Http\Traits;

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
		[
			"filters" => $filters,
			"multiSortMeta" => $multiSortMeta,
			"rows" => $rows,
			"globalFilterFields" => $globalFilterFields,
			"paginate" => $paginate,
		] = $request->validated();

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

		$query = $query->when($globalFilter, function (
			Builder $conditionalWrapperQuery
		) use ($globalFilter, $globalFilterFields) {
			$conditionalWrapperQuery->where(function (
				Builder $globalFilterQuery
			) use ($globalFilter, $globalFilterFields) {
				foreach ($globalFilterFields as $globalFilterField) {
					$globalFilterQuery = $this->getFilterQueryClause(
						$globalFilterQuery,
						$globalFilterField,
						$globalFilter["matchMode"],
						str($globalFilter["value"])->lower(),
						"or"
					);
				}
			});
		});

		foreach (
			$multiSortMeta
			as ["field" => $sortField, "order" => $sortOrder]
		) {
			$query = $query->orderBy(
				$sortField,
				$sortOrder === -1 ? "desc" : "asc"
			);
		}

		return $paginate ? $query->paginate($rows) : $query->get();
	}
}
