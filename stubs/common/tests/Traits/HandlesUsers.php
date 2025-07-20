<?php

namespace Tests\Traits;

use App\Models\User;

use Spatie\Permission\Models\{Permission, Role};

trait HandlesUsers
{
	/**
	 * Create and return a user with a test role having the provided permissions.
	 */
	protected function createUserWithPermissions(
		array $allowedPermissions,
	): User {
		$user = User::factory()->createOne();

		$role = Role::create([
			"name" => "test",
			"guard_name" => "sanctum",
		]);

		$role->givePermissionTo($allowedPermissions);

		$user->assignRole($role);

		return $user;
	}

	/**
	 * Create and return a user with a test role having all permissions except the provided ones.
	 */
	protected function createUserWithoutPermissions(
		array $deniedPermissions,
	): User {
		$user = User::factory()->createOne();

		$role = Role::create([
			"name" => "test",
			"guard_name" => "sanctum",
		]);

		$allowedPermissions = Permission::whereNotIn(
			"name",
			$deniedPermissions,
		)->get();

		$role->givePermissionTo($allowedPermissions);

		$user->assignRole($role);

		return $user;
	}
}
