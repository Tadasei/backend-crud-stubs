<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Stub;
use App\Traits\LazyLoad;
use Illuminate\Support\Facades\DB;

use App\Http\Requests\{
	Stub\DeleteStubRequest,
	Stub\StoreStubRequest,
	Stub\UpdateStubRequest,
	LazyLoadRequest
};
use Illuminate\Http\{JsonResponse, Response};

class StubController extends Controller
{
	use LazyLoad;

	public function lazy(LazyLoadRequest $request): JsonResponse
	{
		$this->authorize("viewAny", Stub::class);

		return response()->json([
			"stubs" => $this->getLazyLoadedData($request, Stub::query()),
		]);
	}

	/**
	 * Display a listing of the resource.
	 */
	public function index(): JsonResponse
	{
		$this->authorize("viewAny", Stub::class);

		return response()->json([
			"stubs" => Stub::latest()->paginate(5),
		]);
	}

	/**
	 * Store a newly created resource in storage.
	 */
	public function store(StoreStubRequest $request): JsonResponse
	{
		$this->authorize("store", [Stub::class, $request->validated()]);

		$stub = DB::transaction(fn() => Stub::create($request->validated()));

		return response()->json(["id" => $stub->id], 201);
	}

	/**
	 * Display the specified resource.
	 */
	public function show(Stub $stub): JsonResponse
	{
		$this->authorize("view", $stub);

		return response()->json([
			"stub" => $stub,
		]);
	}

	/**
	 * Update the specified resource in storage.
	 */
	public function update(UpdateStubRequest $request, Stub $stub): Response
	{
		$this->authorize("update", [$stub, $request->validated()]);

		DB::transaction(fn() => $stub->update($request->validated()));

		return response()->noContent();
	}

	/**
	 * Remove the specified resource from storage.
	 */
	public function destroy(DeleteStubRequest $request): Response
	{
		$this->authorize("delete", [Stub::class, $request->stubs]);

		DB::transaction(fn() => Stub::whereIn("id", $request->stubs)->delete());

		return response()->noContent();
	}
}
