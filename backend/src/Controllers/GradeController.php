<?php
/**
 * 成绩管理控制器
 */

namespace App\Controllers;

use App\Models\Grade;
use App\Models\Student;
use App\Models\Course;
use App\Config\App;
use App\Utils\Response;
use App\Utils\Validator;
use App\Utils\Logger;
use App\Services\ExcelService;

class GradeController
{
    private Grade $gradeModel;
    private Student $studentModel;
    private Course $courseModel;
    
    public function __construct()
    {
        $this->gradeModel = new Grade();
        $this->studentModel = new Student();
        $this->courseModel = new Course();
    }
    
    /**
     * 获取成绩列表
     */
    public function index(array $params): void
    {
        $page = (int) ($_GET['page'] ?? 1);
        $pageSize = (int) ($_GET['pageSize'] ?? 20);
        $studentId = isset($_GET['studentId']) ? (int) $_GET['studentId'] : null;
        $courseId = isset($_GET['courseId']) ? (int) $_GET['courseId'] : null;
        $semester = $_GET['semester'] ?? null;
        $examType = $_GET['examType'] ?? null;
        
        $result = $this->gradeModel->search($page, $pageSize, $studentId, $courseId, $semester, $examType);
        
        // 获取选项数据
        $students = $this->studentModel->getAllForSelect();
        $courses = $this->courseModel->getAllForSelect();
        $semesters = $this->courseModel->getSemesters();
        
        Response::success([
            'items' => $result['items'],
            'total' => $result['total'],
            'page' => $result['page'],
            'pageSize' => $result['pageSize'],
            'totalPages' => ceil($result['total'] / $result['pageSize']),
            'students' => $students,
            'courses' => $courses,
            'semesters' => $semesters
        ]);
    }
    
    /**
     * 获取成绩详情
     */
    public function show(array $params): void
    {
        $id = (int) $params['id'];
        $grade = $this->gradeModel->find($id);
        
        if (!$grade) {
            Response::error('成绩记录不存在', 404);
            return;
        }
        
        // 获取学生和课程信息
        $student = $this->studentModel->find($grade['student_id']);
        $course = $this->courseModel->find($grade['course_id']);
        
        $grade['student_no'] = $student['student_no'] ?? null;
        $grade['student_name'] = $student['name'] ?? null;
        $grade['course_code'] = $course['code'] ?? null;
        $grade['course_name'] = $course['name'] ?? null;
        
        Response::success($grade);
    }
    
    /**
     * 创建成绩
     */
    public function store(array $params): void
    {
        $data = $this->getInput();
        
        $validator = new Validator($data);
        $validator->required('studentId', '学生不能为空')
                  ->required('courseId', '课程不能为空')
                  ->required('semester', '学期不能为空')
                  ->required('examType', '考试类型不能为空')
                  ->numeric('score', '成绩必须是数字')
                  ->between('score', 0, 100, '成绩必须在0-100之间')
                  ->in('examType', ['期中', '期末', '平时', '补考'], '考试类型无效');
        
        if ($validator->fails()) {
            Response::error($validator->getFirstError(), 400);
            return;
        }
        
        // 检查学生和课程是否存在
        $student = $this->studentModel->find($data['studentId']);
        if (!$student) {
            Response::error('学生不存在', 400);
            return;
        }
        
        $course = $this->courseModel->find($data['courseId']);
        if (!$course) {
            Response::error('课程不存在', 400);
            return;
        }
        
        // 检查是否已存在成绩
        if ($this->gradeModel->existsGrade($data['studentId'], $data['courseId'], $data['semester'], $data['examType'])) {
            Response::error('该学生本学期本课程的此类考试成绩已存在', 400);
            return;
        }
        
        // 计算成绩等级
        $score = (float) $data['score'];
        $gradeLevel = App::calculateGradeLevel($score);
        
        $gradeId = $this->gradeModel->create([
            'student_id' => $data['studentId'],
            'course_id' => $data['courseId'],
            'score' => $score,
            'grade_level' => $gradeLevel,
            'semester' => $data['semester'],
            'exam_type' => $data['examType'],
            'remark' => $data['remark'] ?? null
        ]);
        
        Logger::info("Grade created: Student {$student['name']} - Course {$course['name']} - Score {$score}");
        
        Response::success(['id' => $gradeId], '创建成功');
    }
    
    /**
     * 更新成绩
     */
    public function update(array $params): void
    {
        $id = (int) $params['id'];
        $grade = $this->gradeModel->find($id);
        
        if (!$grade) {
            Response::error('成绩记录不存在', 404);
            return;
        }
        
        $data = $this->getInput();
        
        $validator = new Validator($data);
        $validator->required('studentId', '学生不能为空')
                  ->required('courseId', '课程不能为空')
                  ->required('semester', '学期不能为空')
                  ->required('examType', '考试类型不能为空')
                  ->numeric('score', '成绩必须是数字')
                  ->between('score', 0, 100, '成绩必须在0-100之间');
        
        if ($validator->fails()) {
            Response::error($validator->getFirstError(), 400);
            return;
        }
        
        // 检查是否已存在成绩（排除自己）
        if ($this->gradeModel->existsGrade($data['studentId'], $data['courseId'], $data['semester'], $data['examType'], $id)) {
            Response::error('该学生本学期本课程的此类考试成绩已存在', 400);
            return;
        }
        
        $score = (float) $data['score'];
        $gradeLevel = App::calculateGradeLevel($score);
        
        $this->gradeModel->update($id, [
            'student_id' => $data['studentId'],
            'course_id' => $data['courseId'],
            'score' => $score,
            'grade_level' => $gradeLevel,
            'semester' => $data['semester'],
            'exam_type' => $data['examType'],
            'remark' => $data['remark'] ?? null
        ]);
        
        Logger::info("Grade updated: ID {$id} - Score {$score}");
        
        Response::success(null, '更新成功');
    }
    
    /**
     * 删除成绩
     */
    public function destroy(array $params): void
    {
        $id = (int) $params['id'];
        $grade = $this->gradeModel->find($id);
        
        if (!$grade) {
            Response::error('成绩记录不存在', 404);
            return;
        }
        
        $this->gradeModel->delete($id);
        
        Logger::info("Grade deleted: ID {$id}");
        
        Response::success(null, '删除成功');
    }
    
    /**
     * 获取学生成绩
     */
    public function studentGrades(array $params): void
    {
        $studentId = (int) $params['studentId'];
        $semester = $_GET['semester'] ?? null;
        
        $student = $this->studentModel->find($studentId);
        if (!$student) {
            Response::error('学生不存在', 404);
            return;
        }
        
        $grades = $this->gradeModel->getStudentGrades($studentId, $semester);
        
        Response::success([
            'student' => $student,
            'grades' => $grades
        ]);
    }
    
    /**
     * 导入成绩
     */
    public function import(array $params): void
    {
        if (!isset($_FILES['file'])) {
            Response::error('请上传文件', 400);
            return;
        }
        
        $file = $_FILES['file'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($ext, ['xlsx', 'xls', 'csv'])) {
            Response::error('仅支持 xlsx、xls、csv 格式', 400);
            return;
        }
        
        try {
            $excelService = new ExcelService();
            $result = $excelService->importGrades($file['tmp_name'], $ext);
            
            Logger::info("Grades imported: {$result['success']} success, {$result['failed']} failed");
            
            Response::success($result, "导入完成，成功 {$result['success']} 条，失败 {$result['failed']} 条");
        } catch (\Exception $e) {
            Logger::error("Import grades failed: " . $e->getMessage());
            Response::error('导入失败：' . $e->getMessage(), 500);
        }
    }
    
    /**
     * 粘贴导入成绩
     */
    public function pasteImport(array $params): void
    {
        $data = $this->getInput();
        
        if (empty($data['content'])) {
            Response::error('请输入要导入的数据', 400);
            return;
        }
        
        try {
            $excelService = new ExcelService();
            $result = $excelService->pasteImportGrades(
                $data['content'], 
                $data['semester'] ?? null,
                $data['courseId'] ?? null
            );
            
            Logger::info("Grades paste imported: {$result['success']} success, {$result['failed']} failed");
            
            Response::success($result, "导入完成，成功 {$result['success']} 条，失败 {$result['failed']} 条");
        } catch (\Exception $e) {
            Logger::error("Paste import grades failed: " . $e->getMessage());
            Response::error('导入失败：' . $e->getMessage(), 500);
        }
    }
    
    /**
     * 导出成绩
     */
    public function export(array $params): void
    {
        $courseId = isset($_GET['courseId']) ? (int) $_GET['courseId'] : null;
        $semester = $_GET['semester'] ?? null;
        
        try {
            $excelService = new ExcelService();
            $excelService->exportGrades($courseId, $semester);
        } catch (\Exception $e) {
            Logger::error("Export grades failed: " . $e->getMessage());
            Response::error('导出失败：' . $e->getMessage(), 500);
        }
    }
    
    /**
     * 下载成绩导入模板
     */
    public function template(array $params): void
    {
        try {
            $excelService = new ExcelService();
            $excelService->downloadGradeTemplate();
        } catch (\Exception $e) {
            Logger::error("Grade template download failed: " . $e->getMessage());
            Response::error('模板下载失败：' . $e->getMessage(), 500);
        }
    }
    
    private function getInput(): array
    {
        $input = file_get_contents('php://input');
        return json_decode($input, true) ?? [];
    }
}
