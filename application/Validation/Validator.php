<?php
/**
 * Application: DbM Framework
 * A lightweight PHP framework for building web applications.
 *
 * @author Artur Malinowski
 * @copyright Design by Malina (All Rights Reserved)
 * @license MIT
 * @link https://www.dbm.org.pl
 */

declare(strict_types=1);

namespace Dbm\Validation;

use Dbm\Classes\Translation;

/**
 * Base validation class for handling form input validation.
 *
 * Supports translation via the Translation component if provided.
 *
 * To add translations, configure APP_LANGUAGES in the .env file and add translation files to the `templates` directory,
 * e.g., validation.en.php, validation.pl.php, with the contents of an array with the appropriate keys provided in the applyRule() method.
 * Code:
 * // Validation translation (English en-EN)
 * return [
 *     'validation.required' => 'The :field field is required.', // etc.
 * ];
 *
 * Example:
 * a) Use without translation
 * $validator = new ExampleForm();
 * $errors = $validator->validate($data);
 * b) Use with translations
 * $validator = new ExampleForm($this->translation);
 * $errors = $validator->validate($data);
 */
class Validator
{
    protected array $errors = [];
    protected ?Translation $translation = null;

    /**
     * @param Translation|null $translation
     */
    public function __construct(?Translation $translation = null)
    {
        $this->translation = $translation;
    }

    /**
     * Apply validation rules to the provided data set.
     *
     * @param array $rules Validation rules in format ['field' => ['rule1', 'rule2']]
     * @param array $data Input data to validate.
     * @return array List of validation errors (empty if valid).
     */
    public function rules(array $rules, array $data): array
    {
        $this->errors = [];

        foreach ($rules as $field => $constraints) {
            foreach ($constraints as $rule) {
                $this->applyRule($field, $rule, $data[$field] ?? null);
            }
        }

        return $this->errors;
    }

    /**
     * Returns true if validation passed.
     */
    public function isValid(): bool
    {
        return empty($this->errors);
    }

    /**
     * Retrieve validation errors.
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Apply a single validation rule to a field.
     *
     * @param string $field
     * @param string $rule
     * @param mixed  $value
     */
    protected function applyRule(string $field, string $rule, mixed $value): void
    {
        if ($rule === 'required' && (is_null($value) || $value === '')) {
            $this->errors[$field] = $this->t('validation.required', "Field {$field} is required.");
            return;
        }

        if ($rule === 'string' && !is_string($value)) {
            $this->errors[$field] = $this->t('validation.string', "Field {$field} must be a string.");
            return;
        }

        if (str_starts_with($rule, 'min:')) {
            $min = (int) substr($rule, 4);
            if (mb_strlen((string) $value) < $min) {
                $this->errors[$field] = $this->t('validation.min', "Field {$field} must be at least {$min} characters.");
                return;
            }
        }

        if (str_starts_with($rule, 'max:')) {
            $max = (int) substr($rule, 4);
            if (mb_strlen((string) $value) > $max) {
                $this->errors[$field] = $this->t('validation.max', "Field {$field} cannot exceed {$max} characters.");
                return;
            }
        }

        if ($rule === 'email' && !empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = $this->t('validation.email', "Field {$field} must be a valid email address.");
            return;
        }

        if (str_starts_with($rule, 'regex:')) {
            $pattern = substr($rule, 6);
            if (!preg_match($pattern, (string) $value)) {
                $this->errors[$field] = $this->t('validation.regex', "Field {$field} format is invalid.");
                return;
            }
        }
    }

    /**
     * Translate a validation message if translation is available.
     *
     * @param string $key Translation key
     * @param string $fallback English message (used if translation not found)
     * @return string
     */
    protected function t(string $key, string $fallback): string
    {
        if ($this->translation !== null) {
            return $this->translation->trans($key) ?: $fallback;
        }
        return $fallback;
    }

    /**
     * Normalize input data by trimming whitespace.
     *
     * @param array $data
     * @return array
     */
    protected function normalize(array $data): array
    {
        return array_map(
            static fn ($value) =>
            is_string($value) ? trim($value) : $value,
            $data
        );
    }
}
