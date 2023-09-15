<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Arr;
use InvalidArgumentException;

class PresentWithout implements ValidationRule, DataAwareRule
{
    private array $targetAttributes;

    /**
     * Indicates whether the rule should be implicit.
     *
     * @var bool
     */
    public $implicit = true;

    /**
     * All of the data under validation.
     *
     * @var array<string, mixed>
     */
    protected $data = [];

    // ...

    public function __construct(array $targetAttributes = [])
    {
        $this->targetAttributes = $targetAttributes;
    }

    /**
     * Set the data under validation.
     *
     * @param  array<string, mixed>  $data
     */
    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(
        string $attribute,
        mixed $value,
        Closure $fail
    ): void {
        if (
            !array_is_list($this->targetAttributes) ||
            Arr::first($this->targetAttributes, function ($targetAttribute) {
                return !is_string($targetAttribute) ||
                    str_contains($targetAttribute, ".");
            })
        ) {
            throw new InvalidArgumentException(
                "Invalid target attributes argument"
            );
        }

        if (
            !(
                Arr::has($this->data, $attribute) xor
                Arr::has($this->data, $this->targetAttributes)
            )
        ) {
            $fail(
                __(
                    "The $attribute field must be present when " .
                        Arr::join($this->targetAttributes, ", ", " and ") .
                        " fields are not"
                )
            );
        }
    }
}
