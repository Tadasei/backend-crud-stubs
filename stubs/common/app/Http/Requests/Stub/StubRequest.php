<?php

namespace App\Http\Requests\Stub;

use Illuminate\Foundation\Http\FormRequest;

class StubRequest extends FormRequest
{
	/**
	 * Get the validation rules that apply to the request.
	 *
	 * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
	 */
	public function rules(): array
	{
		return [
				//Define rules here
			];
	}
}
