<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Stub;

use Tests\{
	Traits\HandlesUsers,
	TestCase
};

class StubControllerTest extends TestCase
{
	use HandlesUsers;

	/**
	 * store method request validation test.
	 */
	public function test_store_endpoint_rejects_invalid_data(): void
	{
		$user = $this->createUserWithPermissions(["create stubs"]);

		$response = $this->actingAs($user)->post(route("stubs.store"), [
			"name" => null,
		]);

		$response->assertRedirectBack();

		$response->assertInvalid(["name"]);
	}

	/**
	 * update method request validation test.
	 */
	public function test_update_endpoint_rejects_invalid_data(): void
	{
		$user = $this->createUserWithPermissions(["edit stubs"]);

		$stub = Stub::factory()->createOne();

		$response = $this->actingAs($user)->patch(
			route("stubs.update", ["stub" => $stub->id]),
			[
				"name" => null,
			]
		);

		$response->assertRedirectBack();

		$response->assertInvalid(["name"]);
	}

	/**
	 * destroy method request validation test.
	 */
	public function test_destroy_endpoint_rejects_invalid_data(): void
	{
		$user = $this->createUserWithPermissions(["delete stubs"]);

		$stub = Stub::factory()->createOne();

		$response = $this->actingAs($user)->delete(route("stubs.destroy"), [
			"stubs" => [
				-1, // Invalid ID to test validation
				$stub->id,
			],
		]);

		$response->assertRedirectBack();

		$response->assertInvalid(["stubs.0"]);
	}

	/**
	 * lazy method authorization test.
	 */
	public function test_lazy_endpoint_denies_unauthorized_user(): void
	{
		$user = $this->createUserWithoutPermissions(["view stubs"]);

		$response = $this->actingAs($user)->putJson(route("stubs.lazy"), [
			"paginate" => false,
		]);

		$response->assertForbidden();
	}

	/**
	 * index method authorization test.
	 */
	public function test_index_endpoint_denies_unauthorized_user(): void
	{
		$user = $this->createUserWithoutPermissions(["view stubs"]);

		$response = $this->actingAs($user)->get(route("stubs.index"));

		$response->assertForbidden();
	}

	/**
	 * store method authorization test.
	 */
	public function test_store_endpoint_denies_unauthorized_user(): void
	{
		$user = $this->createUserWithoutPermissions(["create stubs"]);

		$stub = Stub::factory()->makeOne();

		$response = $this->actingAs($user)->post(
			route("stubs.store"),
			$stub->only(["name"])
		);

		$response->assertForbidden();
	}

	/**
	 * show method authorization test.
	 */
	public function test_show_endpoint_denies_unauthorized_user(): void
	{
		$user = $this->createUserWithoutPermissions(["view stubs"]);

		$stub = Stub::factory()->createOne();

		$response = $this->actingAs($user)->get(
			route("stubs.show", [
				"stub" => $stub->id,
			])
		);

		$response->assertForbidden();
	}

	/**
	 * update method authorization test.
	 */
	public function test_update_endpoint_denies_unauthorized_user(): void
	{
		$user = $this->createUserWithoutPermissions(["edit stubs"]);

		$stub = Stub::factory()->createOne();

		$updatedStub = Stub::factory()->makeOne();

		$response = $this->actingAs($user)->patch(
			route("stubs.update", [
				"stub" => $stub->id,
			]),
			$updatedStub->only(["name"])
		);

		$response->assertForbidden();
	}

	/**
	 * destroy method authorization test.
	 */
	public function test_destroy_endpoint_denies_unauthorized_user(): void
	{
		$user = $this->createUserWithoutPermissions(["delete stubs"]);

		$stub = Stub::factory()->createOne();

		$response = $this->actingAs($user)->delete(route("stubs.destroy"), [
			"stubs" => [$stub->id],
		]);

		$response->assertForbidden();
	}

	/**
	 * lazy method exceptions test.
	 */
	public function test_lazy_endpoint_throws_no_exception(): void
	{
		$user = $this->createUserWithPermissions(["view stubs"]);

		$response = $this->actingAs($user)->putJson(route("stubs.lazy"), [
			"paginate" => false,
		]);

		$response->assertOk();
	}

	/**
	 * index method exceptions test.
	 */
	public function test_index_endpoint_throws_no_exception(): void
	{
		$user = $this->createUserWithPermissions(["view stubs"]);

		$response = $this->actingAs($user)->get(route("stubs.index"));

		$response->assertOk();
	}

	/**
	 * store method exceptions test.
	 */
	public function test_store_endpoint_throws_no_exception(): void
	{
		$user = $this->createUserWithPermissions(["create stubs"]);

		$stub = Stub::factory()->makeOne();

		$response = $this->actingAs($user)->post(
			route("stubs.store"),
			$stub->only(["name"])
		);

		$response->assertRedirect();

		$this->assertDatabaseHas(Stub::class, $stub->only(["name"]));
	}

	/**
	 * show method exceptions test.
	 */
	public function test_show_endpoint_throws_no_exception(): void
	{
		$user = $this->createUserWithPermissions(["view stubs"]);

		$stub = Stub::factory()->createOne();

		$response = $this->actingAs($user)->get(
			route("stubs.show", [
				"stub" => $stub->id,
			])
		);

		$response->assertOk();
	}

	/**
	 * update method exceptions test.
	 */
	public function test_update_endpoint_throws_no_exception(): void
	{
		$user = $this->createUserWithPermissions(["edit stubs"]);

		$stub = Stub::factory()->createOne();

		$updatedStub = Stub::factory()->makeOne();

		$response = $this->actingAs($user)->patch(
			route("stubs.update", [
				"stub" => $stub->id,
			]),
			$updatedStub->only(["name"])
		);

		$response->assertRedirect();

		$this->assertDatabaseHas(Stub::class, $updatedStub->only(["name"]));
	}

	/**
	 * destroy method exceptions test.
	 */
	public function test_destroy_endpoint_throws_no_exception(): void
	{
		$user = $this->createUserWithPermissions(["delete stubs"]);

		$stub = Stub::factory()->createOne();

		$response = $this->actingAs($user)->delete(route("stubs.destroy"), [
			"stubs" => [$stub->id],
		]);

		$response->assertRedirect();

		$this->assertModelMissing($stub);
	}
}
