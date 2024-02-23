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
		return true;
	}

	/**
	 * Determine whether the user can create models.
	 */
	public function create(User $user): bool
	{
		return true;
	}

	/**
	 * Determine whether the user can store the model.
	 */
	public function store(User $user, $context = null): bool
	{
		return true;
	}

	/**
	 * Determine whether the user can view the model.
	 */
	public function view(User $user, Stub $stub): bool
	{
		return true;
	}

	/**
	 * Determine whether the user can edit the model.
	 */
	public function edit(User $user, Stub $stub): bool
	{
		return true;
	}

	/**
	 * Determine whether the user can update the model.
	 */
	public function update(User $user, Stub $stub, $context = null): bool
	{
		return true;
	}

	/**
	 * Determine whether the user can delete the model.
	 */
	public function delete(User $user, Stub $stub, $context = null): bool
	{
		return true;
	}

	/**
	 * Determine whether the user can restore the model.
	 */
	public function restore(User $user, Stub $stub): bool
	{
		return true;
	}

	/**
	 * Determine whether the user can permanently delete the model.
	 */
	public function forceDelete(User $user, Stub $stub): bool
	{
		return true;
	}
}
