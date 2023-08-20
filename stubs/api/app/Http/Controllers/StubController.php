<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Stub\DeleteStubRequest;
use App\Http\Requests\Stub\StoreStubRequest;
use App\Http\Requests\Stub\UpdateStubRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use App\Models\Stub;

class StubController extends Controller
{
	/**
	 * Display a listing of the resource.
	 */
	public function index(): JsonResponse
	{
		$this->authorize("viewAny", Stub::class);

		return response()->json([
			"stubs" => Stub::all(),
			"status" => session("status"),
		]);
	}

	/**
	 * Show the form for creating a new resource.
	 */
	public function create(): JsonResponse
	{
		$this->authorize("create", Stub::class);

		return response()->json([
			"status" => session("status"),
		]);
	}

	/**
	 * Store a newly created resource in storage.
	 */
	public function store(StoreStubRequest $request): JsonResponse
	{
		$this->authorize("store", [Stub::class, $request->validated()]);

		$stub = DB::transaction(function () use ($request) {
			return Stub::create($request->validated());
		});

		return response()->json(["id" => $stub->id]);
	}

	/**
	 * Display the specified resource.
	 */
	public function show(Stub $stub): JsonResponse
	{
		$this->authorize("view", $stub);

		return response()->json([
			"stub" => $stub,
			"status" => session("status"),
		]);
	}

	/**
	 * Update the specified resource in storage.
	 */
	public function update(UpdateStubRequest $request, Stub $stub): Response
	{
		$this->authorize("update", [$stub, $request->validated()]);

		DB::transaction(function () use ($request, $stub) {
			$stub->fill($request->validated());
			$stub->save();
		});

		return response()->noContent();
	}

	/**
	 * Remove the specified resource from storage.
	 */
	public function destroy(DeleteStubRequest $request): Response
	{
		$this->authorize("delete", [Stub::class, $request->ids]);

		DB::transaction(function () use ($request) {
			Stub::whereIn("id", $request->ids)->delete();
		});

		return response()->noContent();
	}
}
