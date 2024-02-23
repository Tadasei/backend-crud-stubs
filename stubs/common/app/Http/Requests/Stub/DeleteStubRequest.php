<?php

namespace App\Http\Requests\Stub;

use App\Models\Stub;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeleteStubRequest extends FormRequest
{
	/**
	 * Get the validation rules that apply to the request.
	 *
	 * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
	 */
	public function rules(): array
	{
		return [
			"stubs" => ["required", "array"],
			"stubs.*" => [
				"distinct:strict",
				"numeric",
				"integer",
				Rule::exists(Stub::class, "id"),
			],
		];
	}
}
