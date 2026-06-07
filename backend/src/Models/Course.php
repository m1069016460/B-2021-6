<?php
/**
 * 课程模型
 */

namespace App\Models;

class Course extends BaseModel
{
    protected string $table = 'courses';
    
    /**
     * 根据课程代码查找
     */
    public function findByCode(string $code): ?array
    {
        return $this->findBy('code', $code);
    }
    
    /**
     * 获取所有课程（用于下拉选择）
     */
    public function getAllForSelect(): array
    {
        $sql = "SELECT id, code, name, credits, semester FROM {$this->table} ORDER BY code";
        return $this->query($sql);
    }
    
    /**
     * 分页查询带搜索
     */
    public function search(int $page, int $pageSize, ?string $keyword = null, ?string $semester = null, ?string $courseType = null): array
    {
        $where = ['1=1'];
        $params = [];
        
        if ($keyword) {
            $where[] = "(c.code LIKE ? OR c.name LIKE ?)";
            $params[] = "%{$keyword}%";
            $params[] = "%{$keyword}%";
        }
        
        if ($semester) {
            $where[] = "c.semester = ?";
            $params[] = $semester;
        }
        
        if ($courseType) {
            $where[] = "c.course_type = ?";
            $params[] = $courseType;
        }
        
        $whereStr = implode(' AND ', $where);
        $offset = ($page - 1) * $pageSize;
        
        // 获取数据（关联教师）
        $sql = "SELECT c.*, u.real_name as teacher_name
                FROM {$this->table} c
                LEFT JOIN users u ON c.teacher_id = u.id
                WHERE {$whereStr}
                ORDER BY c.code
                LIMIT {$pageSize} OFFSET {$offset}";
        $items = $this->query($sql, $params);
        
        // 获取总数
        $countSql = "SELECT COUNT(*) as count FROM {$this->table} c WHERE {$whereStr}";
        $countResult = $this->query($countSql, $params);
        $total = (int) $countResult[0]['count'];
        
        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'pageSize' => $pageSize
        ];
    }
    
    /**
     * 获取所有学期
     */
    public function getSemesters(): array
    {
        $sql = "SELECT DISTINCT semester FROM {$this->table} WHERE semester IS NOT NULL ORDER BY semester DESC";
        $result = $this->query($sql);
        return array_column($result, 'semester');
    }
}
