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

use Dbm\Interfaces\TranslationInterface;

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
    protected ?TranslationInterface $translation = null;

    /**
     * Własne reguły walidacji dodane dynamicznie przez addRule().
     * Format: ['ruleName' => callable($field, $value, $data): ?string]
     */
    protected array $customRules = [];

    /**
     * @param TranslationInterface|null $translation
     */
    public function __construct(?TranslationInterface $translation = null)
    {
        $this->translation = $translation;
    }

    /**
     * Walidacja zestawu danych na podstawie tablicy reguł.
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
                $this->applyRule($field, $rule, $data[$field] ?? null, $data);
            }
        }

        return $this->errors;
    }

    /**
     * Zwraca wartość true, jeśli walidacja przebiegła pomyślnie.
     */
    public function isValid(): bool
    {
        return empty($this->errors);
    }

    /**
     * Zwraca błędy walidacji.
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Zwraca błędy w formacie oryginalnym lub z usuniętym prefiksem "error_".
     * Przydatne np. dla API lub logów.
     *
     * @param bool $stripErrorPrefix
     */
    public function getNormalizedErrors(bool $stripErrorPrefix = true): array
    {
        if (!$stripErrorPrefix) {
            return $this->errors;
        }

        $normalized = [];
        foreach ($this->errors as $key => $value) {
            $field = preg_replace('/^error_/', '', $key);
            $normalized[$field] = $value;
        }

        return $normalized;
    }

    /**
     * Dodaje niestandardową regułę walidacji.
     * @example $validator->addRule('alpha_dash', fn($f, $v) => preg_match('/^[A-Za-z0-9_-]+$/', $v) ? null : 'Invalid format');
     *
     * @param string $name
     * @param callable $callback
     */
    public function addRule(string $name, callable $callback): void
    {
        $this->customRules[$name] = $callback;
    }

    /**
     * Wewnętrzna logika stosowania reguł walidacji.
     *
     * @param string $field
     * @param string $rule
     * @param mixed  $value
     * @param array  $data
     */
    protected function applyRule(string $field, string $rule, mixed $value, array $data): void
    {
        $fieldName = $this->translateFieldName($field);

        if (isset($this->customRules[$rule])) {
            $message = ($this->customRules[$rule])($field, $value, $data);
            if (is_string($message) && $message !== '') {
                $this->registerError($field, $message);
            }
            return;
        }

        if ($rule === 'required' && (is_null($value) || $value === '')) {
            $this->registerError($field, $this->trans('validation.required', "Field {$field} is required.", ['field' => $fieldName]));
            return;
        }

        if (str_starts_with($rule, 'min:')) {
            $min = (int) substr($rule, 4);
            if (mb_strlen((string) $value) < $min) {
                $this->registerError($field, $this->trans('validation.min', "Field {$field} must be at least {$min} characters.", ['field' => $fieldName]));
                return;
            }
        }

        if (str_starts_with($rule, 'max:')) {
            $max = (int) substr($rule, 4);
            if (mb_strlen((string) $value) > $max) {
                $this->registerError($field, $this->trans('validation.max', "Field {$field} cannot exceed {$max} characters.", ['field' => $fieldName]));
                return;
            }
        }

        if ($rule === 'string' && !is_string($value)) {
            $this->registerError($field, $this->trans('validation.string', "Field {$field} must be a string.", ['field' => $fieldName]));
            return;
        }

        if ($rule === 'email' && !empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->registerError($field, $this->trans('validation.email', "Field {$field} must be a valid email address.", ['field' => $fieldName]));
            return;
        }

        if ($rule === 'url' && !empty($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
            $this->registerError($field, $this->trans('validation.url', "Field {$field} must be a valid URL.", ['field' => $fieldName]));
            return;
        }

        if ($rule === 'phone' && !empty($value)) {
            if (!preg_match('/^(\d{3}\s?\d{3}\s?\d{3}|\+?\d{2}\s?\d{3}\s?\d{3}\s?\d{3})$/', (string)$value)) {
                $this->registerError($field, $this->trans('validation.phone', "Field {$field} must be a valid phone number.", ['field' => $fieldName]));
                return;
            }
        }

        if ($rule === 'letters_spaces' && !empty($value)) {
            if (!preg_match('/^[\pL \'-]*$/u', (string)$value)) {
                $this->registerError($field, $this->trans('validation.letters_spaces', "Field {$field} must contain only letters and spaces.", ['field' => $fieldName]));
                return;
            }
        }

        if ($rule === 'password' && !empty($value)) {
            if (!preg_match("/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,30}$/", (string)$value)) {
                $this->registerError($field, $this->trans('validation.password', "Field {$field} must meet password strength requirements.", ['field' => $fieldName]));
                return;
            }
        }

        if ($rule === 'confirmed') {
            $confirmationField = "{$field}_confirmation";
            $repeatField = "{$field}_repeat";
            $confirmValue = $data[$confirmationField] ?? $data[$repeatField] ?? null;

            if ($confirmValue === null || $confirmValue !== $value) {
                $this->registerError($field, $this->trans('validation.confirmed', "Field {$field} confirmation does not match.", ['field' => $fieldName]));
                return;
            }
        }

        if (str_starts_with($rule, 'regex:')) {
            $pattern = substr($rule, 6);
            if (!preg_match($pattern, (string) $value)) {
                $this->registerError($field, $this->trans('validation.regex', "Field {$field} format is invalid.", ['field' => $fieldName]));
                return;
            }
        }
    }

    /**
     * Centralny rejestrator błędów — można rozbudować np. o logowanie, eventy, JSON.
     *
     * @param string $field
     * @param string $message
     */
    protected function registerError(string $field, string $message): void
    {
        $errorKey = $this->formatErrorKey($field);
        $this->errors[$errorKey] = $message;
    }

    /**
     * Generuje klucz błędu w formacie `error_field`, niezależnie od nazwy pola.
     *
     * @param string $field
     */
    private function formatErrorKey(string $field): string
    {
        $base = preg_replace('/^.*_/', '', $field);
        return 'error_' . strtolower($base);
    }

    /**
     * Tłumaczy komunikat walidacyjny, jeśli dostępne są tłumaczenia.
     *
     * @param string $key Klucz tłumaczenia
     * @param string $fallback Komunikat domyślny
     * @param array $replacements Tablica zastąpień symboli zastępczych
     * @return string
     */
    protected function trans(string $key, string $fallback, array $replacements = []): string
    {
        $message = $this->translation?->trans($key) ?: $fallback;

        return $this->replacePlaceholders($message, $replacements);
    }

    /**
     * Tłumaczy nazwę pola dla komunikatów walidacyjnych.
     *
     * @param string $field
     * @return string
     */
    protected function translateFieldName(string $field): string
    {
        $base = preg_replace('/^.*_/', '', strtolower($field));
        $translated = strtolower($this->translation?->trans($base));

        return $translated ?: $base;
    }

    /**
     * Przydatne do wstępnego czyszczenia danych (usuwanie spacji, itp.)
     *
     * @param array $data
     * @return array
     */
    protected function normalize(array $data): array
    {
        return array_map(
            static fn ($value) => is_string($value) ? trim($value) : $value,
            $data
        );
    }

    /**
     * Zastępuje symbole zastępcze w komunikatach tłumaczeń.
     *
     * @param string $message
     * @param array $replacements
     * @return string
     */
    private function replacePlaceholders(string $message, array $replacements): string
    {
        foreach ($replacements as $key => $value) {
            $message = str_replace(':' . $key, (string) $value, $message);
        }

        return $message;
    }
}
