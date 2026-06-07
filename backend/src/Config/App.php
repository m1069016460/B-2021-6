<?php
/**
 * 应用配置类
 */

namespace App\Config;

class App
{
    // JWT配置
    public const JWT_SECRET = 'student_grade_system_jwt_secret_key_2021';
    public const JWT_EXPIRY = 86400; // 24小时
    
    // 分页配置
    public const PAGE_SIZE = 20;
    
    // 成绩等级配置
    public const GRADE_LEVELS = [
        'A' => ['min' => 90, 'max' => 100],
        'B' => ['min' => 80, 'max' => 89.99],
        'C' => ['min' => 70, 'max' => 79.99],
        'D' => ['min' => 60, 'max' => 69.99],
        'F' => ['min' => 0, 'max' => 59.99]
    ];
    
    // 文件上传配置
    public const UPLOAD_MAX_SIZE = 10 * 1024 * 1024; // 10MB
    public const ALLOWED_EXTENSIONS = ['xlsx', 'xls', 'csv'];
    
    /**
     * 获取JWT密钥
     */
    public static function getJwtSecret(): string
    {
        return getenv('JWT_SECRET') ?: self::JWT_SECRET;
    }
    
    /**
     * 根据分数计算成绩等级
     */
    public static function calculateGradeLevel(float $score): string
    {
        foreach (self::GRADE_LEVELS as $level => $range) {
            if ($score >= $range['min'] && $score <= $range['max']) {
                return $level;
            }
        }
        return 'F';
    }
}
