<?php
/**
 * 统计分析控制器
 */

namespace App\Controllers;

use App\Models\Grade;
use App\Models\Student;
use App\Models\Course;
use App\Models\ClassModel;
use App\Utils\Response;
use App\Utils\Logger;

class StatisticsController
{
    private Grade $gradeModel;
    private Student $studentModel;
    private Course $courseModel;
    private ClassModel $classModel;
    
    public function __construct()
    {
        $this->gradeModel = new Grade();
        $this->studentModel = new Student();
        $this->courseModel = new Course();
        $this->classModel = new ClassModel();
    }
    
    /**
     * 总览统计
     */
    public function overview(array $params): void
    {
        // 基本统计
        $studentCount = $this->studentModel->count(['status' => 1]);
        $courseCount = $this->courseModel->count();
        $classCount = $this->classModel->count();
        $gradeCount = $this->gradeModel->count();
        
        // 总体成绩统计
        $sql = "SELECT 
                    AVG(score) as avg_score,
                    MAX(score) as max_score,
                    MIN(score) as min_score,
                    SUM(CASE WHEN score >= 60 THEN 1 ELSE 0 END) as pass_count,
                    SUM(CASE WHEN score >= 90 THEN 1 ELSE 0 END) as excellent_count,
                    COUNT(*) as total_count
                FROM grades";
        $gradeStats = $this->gradeModel->query($sql);
        $stats = $gradeStats[0] ?? [];
        
        $passRate = $stats['total_count'] > 0 
            ? round(($stats['pass_count'] / $stats['total_count']) * 100, 2) 
            : 0;
        $excellentRate = $stats['total_count'] > 0 
            ? round(($stats['excellent_count'] / $stats['total_count']) * 100, 2) 
            : 0;
        
        // 成绩等级分布
        $sql = "SELECT grade_level, COUNT(*) as count FROM grades GROUP BY grade_level ORDER BY grade_level";
        $gradeDistribution = $this->gradeModel->query($sql);
        
        // 班级成绩排名
        $sql = "SELECT 
                    c.id, c.name as class_name, c.grade,
                    AVG(g.score) as avg_score,
                    COUNT(DISTINCT s.id) as student_count
                FROM classes c
                LEFT JOIN students s ON s.class_id = c.id
                LEFT JOIN grades g ON g.student_id = s.id
                GROUP BY c.id, c.name, c.grade
                HAVING avg_score IS NOT NULL
                ORDER BY avg_score DESC
                LIMIT 10";
        $classRanking = $this->gradeModel->query($sql);
        
        // 最近成绩趋势（按月统计）
        $sql = "SELECT 
                    DATE_FORMAT(created_at, '%Y-%m') as month,
                    AVG(score) as avg_score,
                    COUNT(*) as count
                FROM grades
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                ORDER BY month";
        $monthlyTrend = $this->gradeModel->query($sql);
        
        Response::success([
            'summary' => [
                'studentCount' => $studentCount,
                'courseCount' => $courseCount,
                'classCount' => $classCount,
                'gradeCount' => $gradeCount,
                'avgScore' => round($stats['avg_score'] ?? 0, 2),
                'maxScore' => $stats['max_score'] ?? 0,
                'minScore' => $stats['min_score'] ?? 0,
                'passRate' => $passRate,
                'excellentRate' => $excellentRate
            ],
            'gradeDistribution' => $gradeDistribution,
            'classRanking' => $classRanking,
            'monthlyTrend' => $monthlyTrend
        ]);
    }
    
    /**
     * 班级统计
     */
    public function classStats(array $params): void
    {
        $classId = (int) $params['classId'];
        $semester = $_GET['semester'] ?? null;
        
        $class = $this->classModel->find($classId);
        if (!$class) {
            Response::error('班级不存在', 404);
            return;
        }
        
        // 班级成绩统计
        $stats = $this->gradeModel->getClassStats($classId, $semester);
        
        $passRate = $stats['total_count'] > 0 
            ? round(($stats['pass_count'] / $stats['total_count']) * 100, 2) 
            : 0;
        $excellentRate = $stats['total_count'] > 0 
            ? round(($stats['excellent_count'] / $stats['total_count']) * 100, 2) 
            : 0;
        
        // 学生成绩排名
        $sql = "SELECT 
                    s.id, s.student_no, s.name,
                    AVG(g.score) as avg_score,
                    COUNT(g.id) as course_count
                FROM students s
                LEFT JOIN grades g ON g.student_id = s.id
                WHERE s.class_id = ?";
        $params = [$classId];
        
        if ($semester) {
            $sql .= " AND g.semester = ?";
            $params[] = $semester;
        }
        
        $sql .= " GROUP BY s.id, s.student_no, s.name
                  HAVING avg_score IS NOT NULL
                  ORDER BY avg_score DESC";
        $studentRanking = $this->gradeModel->query($sql, $params);
        
        // 课程成绩分布
        $sql = "SELECT 
                    c.id, c.code, c.name,
                    AVG(g.score) as avg_score,
                    MAX(g.score) as max_score,
                    MIN(g.score) as min_score
                FROM courses c
                JOIN grades g ON g.course_id = c.id
                JOIN students s ON g.student_id = s.id
                WHERE s.class_id = ?";
        $courseParams = [$classId];
        
        if ($semester) {
            $sql .= " AND g.semester = ?";
            $courseParams[] = $semester;
        }
        
        $sql .= " GROUP BY c.id, c.code, c.name ORDER BY avg_score DESC";
        $courseStats = $this->gradeModel->query($sql, $courseParams);
        
        Response::success([
            'class' => $class,
            'summary' => [
                'avgScore' => round($stats['avg_score'] ?? 0, 2),
                'maxScore' => $stats['max_score'] ?? 0,
                'minScore' => $stats['min_score'] ?? 0,
                'passRate' => $passRate,
                'excellentRate' => $excellentRate,
                'totalCount' => $stats['total_count'] ?? 0
            ],
            'studentRanking' => $studentRanking,
            'courseStats' => $courseStats
        ]);
    }
    
    /**
     * 课程统计
     */
    public function courseStats(array $params): void
    {
        $courseId = (int) $params['courseId'];
        $semester = $_GET['semester'] ?? null;
        
        $course = $this->courseModel->find($courseId);
        if (!$course) {
            Response::error('课程不存在', 404);
            return;
        }
        
        // 成绩分布
        $distribution = $this->gradeModel->getCourseDistribution($courseId, $semester);
        
        // 成绩统计
        $sql = "SELECT 
                    AVG(score) as avg_score,
                    MAX(score) as max_score,
                    MIN(score) as min_score,
                    COUNT(*) as total_count,
                    SUM(CASE WHEN score >= 60 THEN 1 ELSE 0 END) as pass_count,
                    SUM(CASE WHEN score >= 90 THEN 1 ELSE 0 END) as excellent_count
                FROM grades
                WHERE course_id = ?";
        $statsParams = [$courseId];
        
        if ($semester) {
            $sql .= " AND semester = ?";
            $statsParams[] = $semester;
        }
        
        $statsResult = $this->gradeModel->query($sql, $statsParams);
        $stats = $statsResult[0] ?? [];
        
        $passRate = $stats['total_count'] > 0 
            ? round(($stats['pass_count'] / $stats['total_count']) * 100, 2) 
            : 0;
        $excellentRate = $stats['total_count'] > 0 
            ? round(($stats['excellent_count'] / $stats['total_count']) * 100, 2) 
            : 0;
        
        // 班级对比
        $sql = "SELECT 
                    c.id, c.name as class_name,
                    AVG(g.score) as avg_score,
                    COUNT(g.id) as student_count
                FROM grades g
                JOIN students s ON g.student_id = s.id
                JOIN classes c ON s.class_id = c.id
                WHERE g.course_id = ?";
        $classParams = [$courseId];
        
        if ($semester) {
            $sql .= " AND g.semester = ?";
            $classParams[] = $semester;
        }
        
        $sql .= " GROUP BY c.id, c.name ORDER BY avg_score DESC";
        $classComparison = $this->gradeModel->query($sql, $classParams);
        
        // 分数段分布
        $sql = "SELECT 
                    CASE 
                        WHEN score >= 90 THEN '90-100'
                        WHEN score >= 80 THEN '80-89'
                        WHEN score >= 70 THEN '70-79'
                        WHEN score >= 60 THEN '60-69'
                        ELSE '0-59'
                    END as score_range,
                    COUNT(*) as count
                FROM grades
                WHERE course_id = ?";
        $rangeParams = [$courseId];
        
        if ($semester) {
            $sql .= " AND semester = ?";
            $rangeParams[] = $semester;
        }
        
        $sql .= " GROUP BY score_range ORDER BY score_range DESC";
        $scoreRanges = $this->gradeModel->query($sql, $rangeParams);
        
        Response::success([
            'course' => $course,
            'summary' => [
                'avgScore' => round($stats['avg_score'] ?? 0, 2),
                'maxScore' => $stats['max_score'] ?? 0,
                'minScore' => $stats['min_score'] ?? 0,
                'passRate' => $passRate,
                'excellentRate' => $excellentRate,
                'totalCount' => $stats['total_count'] ?? 0
            ],
            'distribution' => $distribution,
            'classComparison' => $classComparison,
            'scoreRanges' => $scoreRanges
        ]);
    }
    
    /**
     * 学生成绩统计
     */
    public function studentStats(array $params): void
    {
        $studentId = (int) $params['studentId'];
        
        $student = $this->studentModel->find($studentId);
        if (!$student) {
            Response::error('学生不存在', 404);
            return;
        }
        
        // 获取班级信息
        $class = $student['class_id'] ? $this->classModel->find($student['class_id']) : null;
        
        // 成绩统计
        $sql = "SELECT 
                    AVG(score) as avg_score,
                    MAX(score) as max_score,
                    MIN(score) as min_score,
                    COUNT(*) as total_count,
                    SUM(CASE WHEN score >= 60 THEN 1 ELSE 0 END) as pass_count
                FROM grades
                WHERE student_id = ?";
        $statsResult = $this->gradeModel->query($sql, [$studentId]);
        $stats = $statsResult[0] ?? [];
        
        // 各科成绩
        $sql = "SELECT 
                    c.id, c.code, c.name as course_name, c.credits,
                    g.score, g.grade_level, g.semester, g.exam_type
                FROM grades g
                JOIN courses c ON g.course_id = c.id
                WHERE g.student_id = ?
                ORDER BY g.semester DESC, c.code";
        $grades = $this->gradeModel->query($sql, [$studentId]);
        
        // 学期成绩趋势
        $sql = "SELECT 
                    semester,
                    AVG(score) as avg_score,
                    COUNT(*) as course_count
                FROM grades
                WHERE student_id = ?
                GROUP BY semester
                ORDER BY semester";
        $semesterTrend = $this->gradeModel->query($sql, [$studentId]);
        
        // 班级排名
        $classRank = null;
        if ($class) {
            $sql = "SELECT student_id, AVG(score) as avg_score
                    FROM grades g
                    JOIN students s ON g.student_id = s.id
                    WHERE s.class_id = ?
                    GROUP BY student_id
                    ORDER BY avg_score DESC";
            $rankings = $this->gradeModel->query($sql, [$class['id']]);
            
            foreach ($rankings as $index => $rank) {
                if ($rank['student_id'] == $studentId) {
                    $classRank = $index + 1;
                    break;
                }
            }
        }
        
        Response::success([
            'student' => $student,
            'class' => $class,
            'summary' => [
                'avgScore' => round($stats['avg_score'] ?? 0, 2),
                'maxScore' => $stats['max_score'] ?? 0,
                'minScore' => $stats['min_score'] ?? 0,
                'totalCourses' => $stats['total_count'] ?? 0,
                'passCount' => $stats['pass_count'] ?? 0,
                'classRank' => $classRank,
                'totalStudents' => $class ? $class['student_count'] : null
            ],
            'grades' => $grades,
            'semesterTrend' => $semesterTrend
        ]);
    }
    
    /**
     * 排名统计
     */
    public function ranking(array $params): void
    {
        $classId = isset($_GET['classId']) ? (int) $_GET['classId'] : null;
        $semester = $_GET['semester'] ?? null;
        $limit = (int) ($_GET['limit'] ?? 20);
        
        $ranking = $this->gradeModel->getRanking($classId, $semester, $limit);
        
        // 添加排名
        foreach ($ranking as $index => &$item) {
            $item['rank'] = $index + 1;
        }
        
        Response::success([
            'ranking' => $ranking,
            'classId' => $classId,
            'semester' => $semester
        ]);
    }
    
    /**
     * 成绩分布
     */
    public function distribution(array $params): void
    {
        $courseId = isset($_GET['courseId']) ? (int) $_GET['courseId'] : null;
        $semester = $_GET['semester'] ?? null;
        
        $where = ['1=1'];
        $queryParams = [];
        
        if ($courseId) {
            $where[] = "course_id = ?";
            $queryParams[] = $courseId;
        }
        
        if ($semester) {
            $where[] = "semester = ?";
            $queryParams[] = $semester;
        }
        
        $whereStr = implode(' AND ', $where);
        
        // 成绩等级分布
        $sql = "SELECT grade_level, COUNT(*) as count FROM grades WHERE {$whereStr} GROUP BY grade_level";
        $gradeDistribution = $this->gradeModel->query($sql, $queryParams);
        
        // 分数段分布
        $sql = "SELECT 
                    CASE 
                        WHEN score >= 90 THEN '90-100'
                        WHEN score >= 80 THEN '80-89'
                        WHEN score >= 70 THEN '70-79'
                        WHEN score >= 60 THEN '60-69'
                        ELSE '0-59'
                    END as score_range,
                    COUNT(*) as count
                FROM grades
                WHERE {$whereStr}
                GROUP BY score_range
                ORDER BY score_range DESC";
        $scoreDistribution = $this->gradeModel->query($sql, $queryParams);
        
        Response::success([
            'gradeDistribution' => $gradeDistribution,
            'scoreDistribution' => $scoreDistribution
        ]);
    }
    
    /**
     * 成绩趋势
     */
    public function trend(array $params): void
    {
        $classId = isset($_GET['classId']) ? (int) $_GET['classId'] : null;
        $courseId = isset($_GET['courseId']) ? (int) $_GET['courseId'] : null;
        
        $where = ['1=1'];
        $queryParams = [];
        
        if ($classId) {
            $where[] = "s.class_id = ?";
            $queryParams[] = $classId;
        }
        
        if ($courseId) {
            $where[] = "g.course_id = ?";
            $queryParams[] = $courseId;
        }
        
        $whereStr = implode(' AND ', $where);
        
        // 按学期统计
        $sql = "SELECT 
                    g.semester,
                    AVG(g.score) as avg_score,
                    MAX(g.score) as max_score,
                    MIN(g.score) as min_score,
                    COUNT(*) as count
                FROM grades g
                JOIN students s ON g.student_id = s.id
                WHERE {$whereStr}
                GROUP BY g.semester
                ORDER BY g.semester";
        $trend = $this->gradeModel->query($sql, $queryParams);
        
        Response::success([
            'trend' => $trend
        ]);
    }
}
