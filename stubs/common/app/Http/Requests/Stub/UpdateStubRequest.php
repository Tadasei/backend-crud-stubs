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
		$rules = parent::rules();

		//Add custom entries to $rules here for update method only

		return $rules;
	}
}
