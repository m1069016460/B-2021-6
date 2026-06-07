<?php
/**
 * 学生模型
 */

namespace App\Models;

class Student extends BaseModel
{
    protected string $table = 'students';
    
    /**
     * 根据学号查找
     */
    public function findByStudentNo(string $studentNo): ?array
    {
        return $this->findBy('student_no', $studentNo);
    }
    
    /**
     * 分页查询带搜索
     */
    public function search(int $page, int $pageSize, ?string $keyword = null, ?int $classId = null, ?int $status = null): array
    {
        $where = ['1=1'];
        $params = [];
        
        if ($keyword) {
            $where[] = "(s.student_no LIKE ? OR s.name LIKE ? OR s.phone LIKE ?)";
            $params[] = "%{$keyword}%";
            $params[] = "%{$keyword}%";
            $params[] = "%{$keyword}%";
        }
        
        if ($classId !== null) {
            $where[] = "s.class_id = ?";
            $params[] = $classId;
        }
        
        if ($status !== null) {
            $where[] = "s.status = ?";
            $params[] = $status;
        }
        
        $whereStr = implode(' AND ', $where);
        $offset = ($page - 1) * $pageSize;
        
        // 获取数据（关联班级）
        $sql = "SELECT s.*, c.name as class_name, c.grade as class_grade
                FROM {$this->table} s
                LEFT JOIN classes c ON s.class_id = c.id
                WHERE {$whereStr}
                ORDER BY s.id DESC
                LIMIT {$pageSize} OFFSET {$offset}";
        $items = $this->query($sql, $params);
        
        // 获取总数
        $countSql = "SELECT COUNT(*) as count FROM {$this->table} s WHERE {$whereStr}";
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
     * 获取班级学生列表
     */
    public function getByClassId(int $classId): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE class_id = ? ORDER BY student_no";
        return $this->query($sql, [$classId]);
    }
    
    /**
     * 批量创建
     */
    public function batchCreate(array $students): int
    {
        $count = 0;
        foreach ($students as $student) {
            try {
                $this->create($student);
                $count++;
            } catch (\Exception $e) {
                // 跳过重复记录
                continue;
            }
        }
        return $count;
    }
    
    /**
     * 获取所有学生（用于下拉选择）
     */
    public function getAllForSelect(): array
    {
        $sql = "SELECT s.id, s.student_no, s.name, c.name as class_name 
                FROM {$this->table} s
                LEFT JOIN classes c ON s.class_id = c.id
                WHERE s.status = 1
                ORDER BY s.student_no";
        return $this->query($sql);
    }
}
