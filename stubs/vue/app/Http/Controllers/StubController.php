<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Stub;
use App\Traits\LazyLoad;
use Throwable;

use App\Http\Requests\{
	Stub\DeleteStubRequest,
	Stub\StoreStubRequest,
	Stub\UpdateStubRequest,
	LazyLoadRequest
};
use Illuminate\Http\{JsonResponse, RedirectResponse};
use Illuminate\Support\Facades\{DB, Gate};
use Inertia\{Inertia, Response};

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
	public function index(): Response
	{
		Gate::authorize("viewAny", Stub::class);

		return Inertia::render("Stub/Index", [
			"stubs" => Stub::latest()->paginate(5),
		]);
	}

	/**
	 * Show the form for creating a new resource.
	 */
	public function create(): Response
	{
		Gate::authorize("create", Stub::class);

		return Inertia::render("Stub/Create");
	}

	/**
	 * Store a newly created resource in storage.
	 */
	public function store(StoreStubRequest $request): RedirectResponse
	{
		Gate::authorize("store", [Stub::class, $request->validated()]);

		$response = redirect()->route("stubs.index");

		try {
			DB::transaction(fn() => Stub::create($request->validated()));

			$response->with("message", [
				"severity" => "success",
				"content" => __("Stub has been created successfully !"),
			]);
		} catch (Throwable $th) {
			$response->with("message", [
				"severity" => "error",
				"content" => __("Failed to create stub"),
			]);
		} finally {
			return $response;
		}
	}

	/**
	 * Display the specified resource.
	 */
	public function show(Stub $stub): Response
	{
		Gate::authorize("view", $stub);

		return Inertia::render("Stub/Show", [
			"stub" => $stub,
		]);
	}

	/**
	 * Show the form for editing the specified resource.
	 */
	public function edit(Stub $stub): Response
	{
		Gate::authorize("edit", $stub);

		return Inertia::render("Stub/Edit", [
			"stub" => $stub,
		]);
	}

	/**
	 * Update the specified resource in storage.
	 */
	public function update(
		UpdateStubRequest $request,
		Stub $stub
	): RedirectResponse {
		Gate::authorize("update", [$stub, $request->validated()]);

		$response = redirect()->route("stubs.index");

		try {
			DB::transaction(fn() => $stub->update($request->validated()));

			$response->with("message", [
				"severity" => "success",
				"content" => __("Stub has been updated successfully !"),
			]);
		} catch (Throwable $th) {
			$response->with("message", [
				"severity" => "error",
				"content" => __("Failed to update stub"),
			]);
		} finally {
			return $response;
		}
	}

	/**
	 * Remove the specified resource from storage.
	 */
	public function destroy(DeleteStubRequest $request): RedirectResponse
	{
		Gate::authorize("delete", [Stub::class, $request->stubs]);

		$response = redirect()->route("stubs.index");

		try {
			DB::transaction(
				fn() => Stub::whereIn("id", $request->stubs)->delete()
			);

			$response->with("message", [
				"severity" => "success",
				"content" => __("Stubs have been deleted successfully !"),
			]);
		} catch (Throwable $th) {
			$response->with("message", [
				"severity" => "error",
				"content" => __("Failed to delete stubs"),
			]);
		} finally {
			return $response;
		}
	}
}
