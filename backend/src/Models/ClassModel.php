<?php
/**
 * 班级模型
 */

namespace App\Models;

class ClassModel extends BaseModel
{
    protected string $table = 'classes';
    
    /**
     * 获取所有班级（用于下拉选择）
     */
    public function getAllForSelect(): array
    {
        $sql = "SELECT id, name, grade, major FROM {$this->table} ORDER BY grade DESC, name";
        return $this->query($sql);
    }
    
    /**
     * 分页查询带搜索
     */
    public function search(int $page, int $pageSize, ?string $keyword = null, ?string $grade = null): array
    {
        $where = ['1=1'];
        $params = [];
        
        if ($keyword) {
            $where[] = "(c.name LIKE ? OR c.major LIKE ?)";
            $params[] = "%{$keyword}%";
            $params[] = "%{$keyword}%";
        }
        
        if ($grade) {
            $where[] = "c.grade = ?";
            $params[] = $grade;
        }
        
        $whereStr = implode(' AND ', $where);
        $offset = ($page - 1) * $pageSize;
        
        // 获取数据（关联教师）
        $sql = "SELECT c.*, u.real_name as teacher_name
                FROM {$this->table} c
                LEFT JOIN users u ON c.teacher_id = u.id
                WHERE {$whereStr}
                ORDER BY c.grade DESC, c.name
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
     * 更新学生人数
     */
    public function updateStudentCount(int $classId): void
    {
        $sql = "UPDATE {$this->table} SET student_count = (
                    SELECT COUNT(*) FROM students WHERE class_id = ?
                ) WHERE id = ?";
        $this->execute($sql, [$classId, $classId]);
    }
    
    /**
     * 获取所有年级
     */
    public function getGrades(): array
    {
        $sql = "SELECT DISTINCT grade FROM {$this->table} ORDER BY grade DESC";
        $result = $this->query($sql);
        return array_column($result, 'grade');
    }
}
