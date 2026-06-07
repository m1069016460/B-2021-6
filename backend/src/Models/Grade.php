<?php
/**
 * 成绩模型
 */

namespace App\Models;

use App\Config\App;

class Grade extends BaseModel
{
    protected string $table = 'grades';
    
    /**
     * 分页查询带搜索
     */
    public function search(int $page, int $pageSize, ?int $studentId = null, ?int $courseId = null, 
                          ?string $semester = null, ?string $examType = null): array
    {
        $where = ['1=1'];
        $params = [];
        
        if ($studentId !== null) {
            $where[] = "g.student_id = ?";
            $params[] = $studentId;
        }
        
        if ($courseId !== null) {
            $where[] = "g.course_id = ?";
            $params[] = $courseId;
        }
        
        if ($semester) {
            $where[] = "g.semester = ?";
            $params[] = $semester;
        }
        
        if ($examType) {
            $where[] = "g.exam_type = ?";
            $params[] = $examType;
        }
        
        $whereStr = implode(' AND ', $where);
        $offset = ($page - 1) * $pageSize;
        
        // 获取数据（关联学生和课程）
        $sql = "SELECT g.*, 
                       s.student_no, s.name as student_name,
                       c.code as course_code, c.name as course_name, c.credits
                FROM {$this->table} g
                JOIN students s ON g.student_id = s.id
                JOIN courses c ON g.course_id = c.id
                WHERE {$whereStr}
                ORDER BY g.created_at DESC
                LIMIT {$pageSize} OFFSET {$offset}";
        $items = $this->query($sql, $params);
        
        // 获取总数
        $countSql = "SELECT COUNT(*) as count FROM {$this->table} g WHERE {$whereStr}";
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
     * 获取学生所有成绩
     */
    public function getStudentGrades(int $studentId, ?string $semester = null): array
    {
        $where = "g.student_id = ?";
        $params = [$studentId];
        
        if ($semester) {
            $where .= " AND g.semester = ?";
            $params[] = $semester;
        }
        
        $sql = "SELECT g.*, c.code as course_code, c.name as course_name, c.credits
                FROM {$this->table} g
                JOIN courses c ON g.course_id = c.id
                WHERE {$where}
                ORDER BY g.semester DESC, c.code";
        return $this->query($sql, $params);
    }
    
    /**
     * 获取课程所有成绩
     */
    public function getCourseGrades(int $courseId, ?string $semester = null): array
    {
        $where = "g.course_id = ?";
        $params = [$courseId];
        
        if ($semester) {
            $where .= " AND g.semester = ?";
            $params[] = $semester;
        }
        
        $sql = "SELECT g.*, s.student_no, s.name as student_name
                FROM {$this->table} g
                JOIN students s ON g.student_id = s.id
                WHERE {$where}
                ORDER BY g.score DESC";
        return $this->query($sql, $params);
    }
    
    /**
     * 检查是否已存在成绩
     */
    public function existsGrade(int $studentId, int $courseId, string $semester, string $examType, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} 
                WHERE student_id = ? AND course_id = ? AND semester = ? AND exam_type = ?";
        $params = [$studentId, $courseId, $semester, $examType];
        
        if ($excludeId !== null) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $result = $this->query($sql, $params);
        return (int) $result[0]['count'] > 0;
    }
    
    /**
     * 根据学生ID、课程ID、学期和考试类型查找成绩
     */
    public function findByStudentCourse(int $studentId, int $courseId, string $semester, string $examType): ?array
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE student_id = ? AND course_id = ? AND semester = ? AND exam_type = ?
                LIMIT 1";
        $result = $this->query($sql, [$studentId, $courseId, $semester, $examType]);
        return $result[0] ?? null;
    }
    
    /**
     * 批量创建成绩
     */
    public function batchCreate(array $grades): int
    {
        $count = 0;
        foreach ($grades as $grade) {
            // 计算成绩等级
            if (isset($grade['score'])) {
                $grade['grade_level'] = App::calculateGradeLevel((float) $grade['score']);
            }
            
            try {
                // 检查是否已存在
                if (!$this->existsGrade($grade['student_id'], $grade['course_id'], 
                                        $grade['semester'], $grade['exam_type'])) {
                    $this->create($grade);
                    $count++;
                }
            } catch (\Exception $e) {
                continue;
            }
        }
        return $count;
    }
    
    /**
     * 获取班级成绩统计
     */
    public function getClassStats(int $classId, ?string $semester = null): array
    {
        $where = "s.class_id = ?";
        $params = [$classId];
        
        if ($semester) {
            $where .= " AND g.semester = ?";
            $params[] = $semester;
        }
        
        $sql = "SELECT 
                    COUNT(*) as total_count,
                    AVG(g.score) as avg_score,
                    MAX(g.score) as max_score,
                    MIN(g.score) as min_score,
                    SUM(CASE WHEN g.score >= 60 THEN 1 ELSE 0 END) as pass_count,
                    SUM(CASE WHEN g.score >= 90 THEN 1 ELSE 0 END) as excellent_count
                FROM {$this->table} g
                JOIN students s ON g.student_id = s.id
                WHERE {$where}";
        $result = $this->query($sql, $params);
        return $result[0] ?? [];
    }
    
    /**
     * 获取课程成绩分布
     */
    public function getCourseDistribution(int $courseId, ?string $semester = null): array
    {
        $where = "g.course_id = ?";
        $params = [$courseId];
        
        if ($semester) {
            $where .= " AND g.semester = ?";
            $params[] = $semester;
        }
        
        $sql = "SELECT 
                    g.grade_level,
                    COUNT(*) as count
                FROM {$this->table} g
                WHERE {$where}
                GROUP BY g.grade_level
                ORDER BY g.grade_level";
        return $this->query($sql, $params);
    }
    
    /**
     * 获取排名
     */
    public function getRanking(?int $classId = null, ?string $semester = null, int $limit = 20): array
    {
        $where = ['1=1'];
        $params = [];
        
        if ($classId !== null) {
            $where[] = "s.class_id = ?";
            $params[] = $classId;
        }
        
        if ($semester) {
            $where[] = "g.semester = ?";
            $params[] = $semester;
        }
        
        $whereStr = implode(' AND ', $where);
        
        $sql = "SELECT 
                    s.id as student_id,
                    s.student_no,
                    s.name as student_name,
                    c.name as class_name,
                    AVG(g.score) as avg_score,
                    SUM(g.score) as total_score,
                    COUNT(g.id) as course_count
                FROM {$this->table} g
                JOIN students s ON g.student_id = s.id
                LEFT JOIN classes c ON s.class_id = c.id
                WHERE {$whereStr}
                GROUP BY s.id, s.student_no, s.name, c.name
                ORDER BY avg_score DESC
                LIMIT {$limit}";
        return $this->query($sql, $params);
    }
}
