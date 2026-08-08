<?php

namespace App\Core;

/**
 * Validador de formularios simple y encadenable. Uso tipico:
 *
 *   $v = new Validator($_POST);
 *   $v->required('nombres')->required('apellidos')->email('email');
 *   if ($v->fails()) { ... mostrar $v->errors() ... }
 */
class Validator
{
    private array $errores = [];
    private array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function required(string $field, ?string $label = null): self
    {
        $label = $label ?? $field;
        $value = $this->data[$field] ?? null;
        if ($value === null || (is_string($value) && trim($value) === '')) {
            $this->errores[$field] = "El campo {$label} es obligatorio.";
        }
        return $this;
    }

    public function email(string $field, ?string $label = null): self
    {
        $label = $label ?? $field;
        if (!empty($this->data[$field]) && !filter_var($this->data[$field], FILTER_VALIDATE_EMAIL)) {
            $this->errores[$field] = "El campo {$label} debe ser un correo electronico valido.";
        }
        return $this;
    }

    public function min(string $field, int $min, ?string $label = null): self
    {
        $label = $label ?? $field;
        if (!empty($this->data[$field]) && mb_strlen((string) $this->data[$field]) < $min) {
            $this->errores[$field] = "El campo {$label} debe tener al menos {$min} caracteres.";
        }
        return $this;
    }

    public function max(string $field, int $max, ?string $label = null): self
    {
        $label = $label ?? $field;
        if (!empty($this->data[$field]) && mb_strlen((string) $this->data[$field]) > $max) {
            $this->errores[$field] = "El campo {$label} no debe superar {$max} caracteres.";
        }
        return $this;
    }

    public function numeric(string $field, ?string $label = null): self
    {
        $label = $label ?? $field;
        if (isset($this->data[$field]) && $this->data[$field] !== '' && !is_numeric($this->data[$field])) {
            $this->errores[$field] = "El campo {$label} debe ser numerico.";
        }
        return $this;
    }

    public function date(string $field, ?string $label = null): self
    {
        $label = $label ?? $field;
        $value = $this->data[$field] ?? null;
        if (!empty($value) && \DateTime::createFromFormat('Y-m-d', $value) === false) {
            $this->errores[$field] = "El campo {$label} debe ser una fecha valida (AAAA-MM-DD).";
        }
        return $this;
    }

    public function in(string $field, array $opciones, ?string $label = null): self
    {
        $label = $label ?? $field;
        if (!empty($this->data[$field]) && !in_array($this->data[$field], $opciones, true)) {
            $this->errores[$field] = "El valor de {$label} no es valido.";
        }
        return $this;
    }

    public function fails(): bool
    {
        return count($this->errores) > 0;
    }

    public function errors(): array
    {
        return $this->errores;
    }
}
