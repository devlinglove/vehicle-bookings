<?php

namespace Framework;


class UserValidator
{

    public static function isEmail(string $value)
    {
        return filter_var(trim($value), FILTER_VALIDATE_EMAIL);
    }

    public static function validate(array $data): array
    {
        $validators = [
            'name' => function ($value) {
                if (empty(trim($value))) return 'Please enter your name';
                return '';
            },
            
            'email' => function ($value) {
                if (empty(trim($value))) return 'Please enter your email';
                return self::isEmail($value) ? '' : 'Please enter a valid email address';
            },
            'password' => function ($value) {
                if (empty(trim($value))) return 'Please enter your password';
                if (strlen($value) < 8) {
                    return 'Password must be at least 8 characters long';
                }

                if (!preg_match('/[A-Z]/', $value)) {
                    return 'Password must contain at least one uppercase letter';
                }

                if (!preg_match('/[a-z]/', $value)) {
                    return 'Password must contain at least one lowercase letter';
                }

                if (!preg_match('/[0-9]/', $value)) {
                    return 'Password must contain at least one number';
                }

                return '';
            },
            'password_confirmation' => function ($value) use (&$data) {
                if (empty($value)) {
                    return 'Please enter your confirm password';
                }

                if ($value !== ($data['password'] ?? '')) {
                    return 'Passwords do not match';
                }

                return '';
            },
           'transporter_id' => function ($value) {
                if ($value === '' || $value === null) {
                    return 'Please enter transporter ID';
                }

                if (filter_var($value, FILTER_VALIDATE_INT) === false) {
                    return 'Transporter ID must be an integer';
                }

                if ((int) $value <= 0) {
                    return 'Transporter ID must be greater than zero';
                }

                return '';
            },
        ];

        $errors = [];

        foreach ($validators as $field => $validate) {
            $value = $data[$field] ?? '';

            if (!is_string($value)) {
                $errors[$field] = 'Invalid input.';
                continue;
            }

            if (array_key_exists($field, $data)) {
                $error = $validate($value);

                if ($error !== '') {
                    $errors[$field] = $error;
                }
            }

           
        }

        return $errors;
    }
}
