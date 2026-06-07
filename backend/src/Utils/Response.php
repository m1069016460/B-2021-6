<?php
/**
 * 响应工具类
 */

namespace App\Utils;

class Response
{
    /**
     * 成功响应
     */
    public static function success($data = null, string $message = '操作成功', int $code = 200): void
    {
        self::json([
            'code' => $code,
            'message' => $message,
            'data' => $data
        ]);
    }
    
    /**
     * 错误响应
     */
    public static function error(string $message = '操作失败', int $code = 400, $data = null): void
    {
        http_response_code($code >= 400 && $code < 600 ? $code : 400);
        self::json([
            'code' => $code,
            'message' => $message,
            'data' => $data
        ]);
    }
    
    /**
     * 分页响应
     */
    public static function paginate(array $items, int $total, int $page, int $pageSize): void
    {
        self::success([
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'pageSize' => $pageSize,
            'totalPages' => ceil($total / $pageSize)
        ]);
    }
    
    /**
     * 输出JSON
     */
    private static function json(array $data): void
    {
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit();
    }
}
