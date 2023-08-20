<?php

namespace App\Http\Requests\Stub;

class StoreStubRequest extends StubRequest
{
	/**
	 * Get the validation rules that apply to the request.
	 *
	 * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
	 */
	public function rules(): array
	{
		$rules = parent::rules();

		//Add custom entries to $rules here for store method only

		return $rules;
	}
}
