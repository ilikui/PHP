<?php
declare(strict_types=1);

namespace Learning\Validation;

final class InputValidator
{
    /** @param array<string, mixed> $input @param array<string, list<string>> $rules @return array<string, list<string>> */
    public function validate(array $input, array $rules): array
    {
        $errors = [];
        foreach ($rules as $field => $fieldRules) {
            $value = $input[$field] ?? null;
            foreach ($fieldRules as $rule) {
                [$name, $argument] = array_pad(explode(':', $rule, 2), 2, null);
                $message = match ($name) {
                    'required' => ($value === null || $value === '' || (is_string($value) && trim($value) === '')) ? 'is required' : null,
                    'string' => ($value !== null && !is_string($value)) ? 'must be a string' : null,
                    'integer' => ($value !== null && filter_var($value, FILTER_VALIDATE_INT) === false) ? 'must be an integer' : null,
                    'email' => ($value !== null && filter_var($value, FILTER_VALIDATE_EMAIL) === false) ? 'must be a valid email' : null,
                    'max' => $this->tooLarge($value, $argument) ? 'exceeds maximum length/value' : null,
                    'min' => $this->tooSmall($value, $argument) ? 'is below minimum length/value' : null,
                    default => throw new \InvalidArgumentException('Unknown validation rule: ' . $name),
                };
                if ($message !== null) $errors[$field][] = $message;
            }
        }
        return $errors;
    }

    private function tooLarge(mixed $value, ?string $limit): bool
    {
        if ($value === null || $limit === null || !is_numeric($limit)) return false;
        if (is_string($value) && !is_numeric($value)) return mb_strlen($value) > (int) $limit;
        return is_numeric($value) && (float) $value > (float) $limit;
    }

    private function tooSmall(mixed $value, ?string $limit): bool
    {
        if ($value === null || $limit === null || !is_numeric($limit)) return false;
        if (is_string($value) && !is_numeric($value)) return mb_strlen($value) < (int) $limit;
        return is_numeric($value) && (float) $value < (float) $limit;
    }
}
