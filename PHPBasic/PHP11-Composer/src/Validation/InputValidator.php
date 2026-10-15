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
            $stringMode = in_array('string', $fieldRules, true) && !in_array('integer', $fieldRules, true);
            foreach ($fieldRules as $rule) {
                [$name, $argument] = array_pad(explode(':', $rule, 2), 2, null);
                $message = match ($name) {
                    'required' => ($value === null || $value === '' || (is_string($value) && trim($value) === '')) ? 'is required' : null,
                    'string' => ($value !== null && !is_string($value)) ? 'must be a string' : null,
                    'integer' => ($value !== null && filter_var($value, FILTER_VALIDATE_INT) === false) ? 'must be an integer' : null,
                    'email' => ($value !== null && filter_var($value, FILTER_VALIDATE_EMAIL) === false) ? 'must be a valid email' : null,
                    'max' => $this->exceedsMaximum($value, $argument, $stringMode) ? ($stringMode ? 'is too long' : 'exceeds maximum value') : null,
                    'min' => $this->belowMinimum($value, $argument, $stringMode) ? ($stringMode ? 'is too short' : 'is below minimum value') : null,
                    default => throw new \InvalidArgumentException('Unknown validation rule: ' . $name),
                };
                if ($message !== null) $errors[$field][] = $message;
            }
        }
        return $errors;
    }

    private function exceedsMaximum(mixed $value, ?string $limit, bool $stringMode): bool
    {
        if ($value === null || $limit === null || !is_numeric($limit)) return false;
        if ($stringMode) return is_string($value) && mb_strlen($value) > (int) $limit;
        return is_numeric($value) && (float) $value > (float) $limit;
    }

    private function belowMinimum(mixed $value, ?string $limit, bool $stringMode): bool
    {
        if ($value === null || $limit === null || !is_numeric($limit)) return false;
        if ($stringMode) return is_string($value) && mb_strlen($value) < (int) $limit;
        return is_numeric($value) && (float) $value < (float) $limit;
    }
}
