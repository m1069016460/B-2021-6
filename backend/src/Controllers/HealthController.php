<?php
/**
 * 健康检查控制器
 */

namespace App\Controllers;

use App\Utils\Response;

class HealthController
{
    /**
     * 健康检查
     */
    public function check(): void
    {
        Response::success([
            'status' => 'ok',
            'timestamp' => date('Y-m-d H:i:s'),
            'service' => 'Student Grade Management System API'
        ], '服务运行正常');
    }
}
