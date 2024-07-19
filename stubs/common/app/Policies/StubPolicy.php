<?php

namespace App\Policies;

use App\Models\{Stub, User};

class StubPolicy
{
	/**
	 * Determine whether the user can view any models.
	 */
	public function viewAny(User $user): bool
	{
		return $user->can("view stubs");
	}

	/**
	 * Determine whether the user can create models.
	 */
	public function create(User $user): bool
	{
		return $user->can("create stubs");
	}

	/**
	 * Determine whether the user can store the model.
	 */
	public function store(User $user, $context = null): bool
	{
		return $this->create($user);
	}

	/**
	 * Determine whether the user can view the model.
	 */
	public function view(User $user, Stub $stub): bool
	{
		return $this->viewAny($user);
	}

	/**
	 * Determine whether the user can edit the model.
	 */
	public function edit(User $user, Stub $stub): bool
	{
		return $user->can("edit stubs");
	}

	/**
	 * Determine whether the user can update the model.
	 */
	public function update(User $user, Stub $stub, $context = null): bool
	{
		return $this->edit($user, $stub);
	}

	/**
	 * Determine whether the user can delete the model.
	 */
	public function delete(User $user, Stub $stub, $context = null): bool
	{
		return $user->can("delete stubs");
	}

	/**
	 * Determine whether the user can restore the model.
	 */
	public function restore(User $user, Stub $stub): bool
	{
		return $user->can("restore stubs");
	}

	/**
	 * Determine whether the user can permanently delete the model.
	 */
	public function forceDelete(User $user, Stub $stub): bool
	{
		return $user->can("force delete stubs");
	}
}
