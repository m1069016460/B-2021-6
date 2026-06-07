<?php
/**
 * 验证工具类
 */

namespace App\Utils;

class Validator
{
    private array $errors = [];
    private array $data;
    
    public function __construct(array $data)
    {
        $this->data = $data;
    }
    
    /**
     * 必填验证
     */
    public function required(string $field, string $message = null): self
    {
        if (!isset($this->data[$field]) || trim($this->data[$field]) === '') {
            $this->errors[$field] = $message ?? "{$field} 不能为空";
        }
        return $this;
    }
    
    /**
     * 最小长度验证
     */
    public function minLength(string $field, int $length, string $message = null): self
    {
        if (isset($this->data[$field]) && mb_strlen($this->data[$field]) < $length) {
            $this->errors[$field] = $message ?? "{$field} 长度不能小于 {$length}";
        }
        return $this;
    }
    
    /**
     * 最大长度验证
     */
    public function maxLength(string $field, int $length, string $message = null): self
    {
        if (isset($this->data[$field]) && mb_strlen($this->data[$field]) > $length) {
            $this->errors[$field] = $message ?? "{$field} 长度不能大于 {$length}";
        }
        return $this;
    }
    
    /**
     * 邮箱验证
     */
    public function email(string $field, string $message = null): self
    {
        if (isset($this->data[$field]) && !empty($this->data[$field])) {
            if (!filter_var($this->data[$field], FILTER_VALIDATE_EMAIL)) {
                $this->errors[$field] = $message ?? "邮箱格式不正确";
            }
        }
        return $this;
    }
    
    /**
     * 手机号验证
     */
    public function phone(string $field, string $message = null): self
    {
        if (isset($this->data[$field]) && !empty($this->data[$field])) {
            if (!preg_match('/^1[3-9]\d{9}$/', $this->data[$field])) {
                $this->errors[$field] = $message ?? "手机号格式不正确";
            }
        }
        return $this;
    }
    
    /**
     * 数字验证
     */
    public function numeric(string $field, string $message = null): self
    {
        if (isset($this->data[$field]) && !is_numeric($this->data[$field])) {
            $this->errors[$field] = $message ?? "{$field} 必须是数字";
        }
        return $this;
    }
    
    /**
     * 范围验证
     */
    public function between(string $field, float $min, float $max, string $message = null): self
    {
        if (isset($this->data[$field]) && is_numeric($this->data[$field])) {
            $value = floatval($this->data[$field]);
            if ($value < $min || $value > $max) {
                $this->errors[$field] = $message ?? "{$field} 必须在 {$min} 到 {$max} 之间";
            }
        }
        return $this;
    }
    
    /**
     * 枚举验证
     */
    public function in(string $field, array $values, string $message = null): self
    {
        if (isset($this->data[$field]) && !in_array($this->data[$field], $values)) {
            $this->errors[$field] = $message ?? "{$field} 值不在允许范围内";
        }
        return $this;
    }
    
    /**
     * 日期验证
     */
    public function date(string $field, string $format = 'Y-m-d', string $message = null): self
    {
        if (isset($this->data[$field]) && !empty($this->data[$field])) {
            $date = \DateTime::createFromFormat($format, $this->data[$field]);
            if (!$date || $date->format($format) !== $this->data[$field]) {
                $this->errors[$field] = $message ?? "日期格式不正确";
            }
        }
        return $this;
    }
    
    /**
     * 是否通过验证
     */
    public function passes(): bool
    {
        return empty($this->errors);
    }
    
    /**
     * 是否验证失败
     */
    public function fails(): bool
    {
        return !empty($this->errors);
    }
    
    /**
     * 获取错误信息
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
    
    /**
     * 获取第一个错误信息
     */
    public function getFirstError(): ?string
    {
        return !empty($this->errors) ? reset($this->errors) : null;
    }
}
