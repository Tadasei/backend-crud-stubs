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
		return array_merge_recursive(parent::rules(), [
			//Add custom rules here for the store method only
		]);
	}
}
