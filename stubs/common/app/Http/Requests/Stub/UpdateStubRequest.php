<?php

namespace App\Http\Requests\Stub;

class UpdateStubRequest extends StubRequest
{
	/**
	 * Get the validation rules that apply to the request.
	 *
	 * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
	 */
	public function rules(): array
	{
		return array_merge_recursive(parent::rules(), [
			//Add custom rules here for the update method only
		]);
	}
}
