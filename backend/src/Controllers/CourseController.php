<?php
/**
 * 课程管理控制器
 */

namespace App\Controllers;

use App\Models\Course;
use App\Models\User;
use App\Models\Grade;
use App\Utils\Response;
use App\Utils\Validator;
use App\Utils\Logger;

class CourseController
{
    private Course $courseModel;
    private User $userModel;
    private Grade $gradeModel;
    
    public function __construct()
    {
        $this->courseModel = new Course();
        $this->userModel = new User();
        $this->gradeModel = new Grade();
    }
    
    /**
     * 获取课程列表
     */
    public function index(array $params): void
    {
        $page = (int) ($_GET['page'] ?? 1);
        $pageSize = (int) ($_GET['pageSize'] ?? 20);
        $keyword = $_GET['keyword'] ?? null;
        $semester = $_GET['semester'] ?? null;
        $courseType = $_GET['courseType'] ?? null;
        
        $result = $this->courseModel->search($page, $pageSize, $keyword, $semester, $courseType);
        
        // 获取学期列表和教师列表
        $semesters = $this->courseModel->getSemesters();
        $teachers = $this->userModel->getTeachers();
        
        Response::success([
            'items' => $result['items'],
            'total' => $result['total'],
            'page' => $result['page'],
            'pageSize' => $result['pageSize'],
            'totalPages' => ceil($result['total'] / $result['pageSize']),
            'semesters' => $semesters,
            'teachers' => $teachers
        ]);
    }
    
    /**
     * 获取所有课程（下拉选择）
     */
    public function all(array $params): void
    {
        $courses = $this->courseModel->getAllForSelect();
        $semesters = $this->courseModel->getSemesters();
        $teachers = $this->userModel->getTeachers();
        
        Response::success([
            'courses' => $courses,
            'semesters' => $semesters,
            'teachers' => $teachers
        ]);
    }
    
    /**
     * 获取课程详情
     */
    public function show(array $params): void
    {
        $id = (int) $params['id'];
        $course = $this->courseModel->find($id);
        
        if (!$course) {
            Response::error('课程不存在', 404);
            return;
        }
        
        // 获取教师信息
        if ($course['teacher_id']) {
            $teacher = $this->userModel->find($course['teacher_id']);
            $course['teacher_name'] = $teacher['real_name'] ?? null;
        }
        
        Response::success($course);
    }
    
    /**
     * 创建课程
     */
    public function store(array $params): void
    {
        $data = $this->getInput();
        
        $validator = new Validator($data);
        $validator->required('code', '课程代码不能为空')
                  ->required('name', '课程名称不能为空')
                  ->numeric('credits', '学分必须是数字')
                  ->between('credits', 0.5, 10, '学分必须在0.5-10之间')
                  ->in('courseType', ['必修', '选修'], '课程类型无效');
        
        if ($validator->fails()) {
            Response::error($validator->getFirstError(), 400);
            return;
        }
        
        if ($this->courseModel->exists('code', $data['code'])) {
            Response::error('课程代码已存在', 400);
            return;
        }
        
        $courseId = $this->courseModel->create([
            'code' => $data['code'],
            'name' => $data['name'],
            'credits' => $data['credits'] ?? 0,
            'hours' => $data['hours'] ?? 0,
            'course_type' => $data['courseType'] ?? '必修',
            'semester' => $data['semester'] ?? null,
            'teacher_id' => $data['teacherId'] ?? null,
            'description' => $data['description'] ?? null
        ]);
        
        Logger::info("Course created: {$data['code']} - {$data['name']}");
        
        Response::success(['id' => $courseId], '创建成功');
    }
    
    /**
     * 更新课程
     */
    public function update(array $params): void
    {
        $id = (int) $params['id'];
        $course = $this->courseModel->find($id);
        
        if (!$course) {
            Response::error('课程不存在', 404);
            return;
        }
        
        $data = $this->getInput();
        
        $validator = new Validator($data);
        $validator->required('code', '课程代码不能为空')
                  ->required('name', '课程名称不能为空')
                  ->numeric('credits', '学分必须是数字')
                  ->between('credits', 0.5, 10, '学分必须在0.5-10之间');
        
        if ($validator->fails()) {
            Response::error($validator->getFirstError(), 400);
            return;
        }
        
        // 检查课程代码是否重复（排除自己）
        if ($this->courseModel->exists('code', $data['code'], $id)) {
            Response::error('课程代码已存在', 400);
            return;
        }
        
        $this->courseModel->update($id, [
            'code' => $data['code'],
            'name' => $data['name'],
            'credits' => $data['credits'] ?? $course['credits'],
            'hours' => $data['hours'] ?? $course['hours'],
            'course_type' => $data['courseType'] ?? $course['course_type'],
            'semester' => $data['semester'] ?? null,
            'teacher_id' => $data['teacherId'] ?? null,
            'description' => $data['description'] ?? null
        ]);
        
        Logger::info("Course updated: {$data['code']} - {$data['name']}");
        
        Response::success(null, '更新成功');
    }
    
    /**
     * 删除课程
     */
    public function destroy(array $params): void
    {
        $id = (int) $params['id'];
        $course = $this->courseModel->find($id);
        
        if (!$course) {
            Response::error('课程不存在', 404);
            return;
        }
        
        // 检查是否有关联成绩
        $gradesCount = $this->gradeModel->count(['course_id' => $id]);
        if ($gradesCount > 0) {
            Response::error("该课程有 {$gradesCount} 条成绩记录，无法删除", 400);
            return;
        }
        
        $this->courseModel->delete($id);
        
        Logger::info("Course deleted: {$course['code']} - {$course['name']}");
        
        Response::success(null, '删除成功');
    }
    
    private function getInput(): array
    {
        $input = file_get_contents('php://input');
        return json_decode($input, true) ?? [];
    }
}
