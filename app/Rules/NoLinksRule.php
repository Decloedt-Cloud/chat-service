<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidatorAwareRule;

class NoLinksRule implements ValidationRule, DataAwareRule, ValidatorAwareRule
{
    /**
     * @var array
     */
    protected $patterns = [
        // Protocoles http/https
        '/https?:\/\/[^\s<>"{}|\\^`\[\]]+/i',
        // www. sans protocole
        '/www\.[^\s<>"{}|\\^`\[\]]+\.[^\s<>"{}|\\^`\[\]]+/i',
        // Domaines (ex: example.com, example.org)
        '/[a-z0-9]+([\-\.]{1}[a-z0-9]+)*\.[a-z]{2,}(:[0-9]{1,5})?(\/.*)?/i',
    ];

    /**
     * @var \Illuminate\Validation\Validator
     */
    protected $validator;

    /**
     * @var array
     */
    protected $data;

    /**
     * Run the validation rule.
     *
     * @param  string $attribute
     * @param  mixed $value
     * @param  \Closure  $fail
     * @return bool
     */
    public function validate(string $attribute, mixed $value, Closure $fail): bool
    {
        // Si vide, pas de validation nécessaire
        if (empty($value)) {
            return true;
        }

        $content = (string) $value;

        // Vérifier chaque pattern
        foreach ($this->patterns as $pattern) {
            if (preg_match($pattern, $content)) {
                $fail('Les liens sont interdits dans les messages');
                return false;
            }
        }

        return true;
    }

    /**
     * Set the current validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return $this
     */
    public function setValidator($validator): static
    {
        $this->validator = $validator;
        return $this;
    }

    /**
     * Set the current data under validation.
     *
     * @param  array  $data
     * @return $this
     */
    public function setData($data): static
    {
        $this->data = $data;
        return $this;
    }
}

