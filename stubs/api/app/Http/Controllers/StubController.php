<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Stub;
use App\Traits\LazyLoad;

use App\Http\Requests\{
	Stub\DeleteStubRequest,
	Stub\StoreStubRequest,
	Stub\UpdateStubRequest,
	LazyLoadRequest
};
use Illuminate\Http\{JsonResponse, Response};
use Illuminate\Support\Facades\{DB, Gate};

class StubController extends Controller
{
	use LazyLoad;

	public function lazy(LazyLoadRequest $request): JsonResponse
	{
		Gate::authorize("viewAny", Stub::class);

		return response()->json([
			"stubs" => $this->getLazyLoadedData($request, Stub::query()),
		]);
	}

	/**
	 * Display a listing of the resource.
	 */
	public function index(): JsonResponse
	{
		Gate::authorize("viewAny", Stub::class);

		return response()->json([
			"stubs" => Stub::latest()->paginate(5),
		]);
	}

	/**
	 * Store a newly created resource in storage.
	 */
	public function store(StoreStubRequest $request): JsonResponse
	{
		Gate::authorize("store", [Stub::class, $request->validated()]);

		$stub = DB::transaction(fn() => Stub::create($request->validated()));

		return response()->json(["id" => $stub->id], 201);
	}

	/**
	 * Display the specified resource.
	 */
	public function show(Stub $stub): JsonResponse
	{
		Gate::authorize("view", $stub);

		return response()->json([
			"stub" => $stub,
		]);
	}

	/**
	 * Update the specified resource in storage.
	 */
	public function update(UpdateStubRequest $request, Stub $stub): Response
	{
		Gate::authorize("update", [$stub, $request->validated()]);

		DB::transaction(fn() => $stub->update($request->validated()));

		return response()->noContent();
	}

	/**
	 * Remove the specified resource from storage.
	 */
	public function destroy(DeleteStubRequest $request): Response
	{
		Gate::authorize("delete", [Stub::class, $request->stubs]);

		DB::transaction(fn() => Stub::whereIn("id", $request->stubs)->delete());

		return response()->noContent();
	}
}
