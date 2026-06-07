<?php
/**
 * 班级管理控制器
 */

namespace App\Controllers;

use App\Models\ClassModel;
use App\Models\Student;
use App\Models\User;
use App\Utils\Response;
use App\Utils\Validator;
use App\Utils\Logger;

class ClassController
{
    private ClassModel $classModel;
    private Student $studentModel;
    private User $userModel;
    
    public function __construct()
    {
        $this->classModel = new ClassModel();
        $this->studentModel = new Student();
        $this->userModel = new User();
    }
    
    /**
     * 获取班级列表（分页）
     */
    public function index(array $params): void
    {
        $page = (int) ($_GET['page'] ?? 1);
        $pageSize = (int) ($_GET['pageSize'] ?? 20);
        $keyword = $_GET['keyword'] ?? null;
        $grade = $_GET['grade'] ?? null;
        
        $result = $this->classModel->search($page, $pageSize, $keyword, $grade);
        
        Response::paginate($result['items'], $result['total'], $result['page'], $result['pageSize']);
    }
    
    /**
     * 获取所有班级（下拉选择）
     */
    public function all(array $params): void
    {
        $classes = $this->classModel->getAllForSelect();
        $grades = $this->classModel->getGrades();
        $teachers = $this->userModel->getTeachers();
        
        Response::success([
            'classes' => $classes,
            'grades' => $grades,
            'teachers' => $teachers
        ]);
    }
    
    /**
     * 获取班级详情
     */
    public function show(array $params): void
    {
        $id = (int) $params['id'];
        $class = $this->classModel->find($id);
        
        if (!$class) {
            Response::error('班级不存在', 404);
            return;
        }
        
        Response::success($class);
    }
    
    /**
     * 创建班级
     */
    public function store(array $params): void
    {
        $data = $this->getInput();
        
        $validator = new Validator($data);
        $validator->required('name', '班级名称不能为空')
                  ->required('grade', '年级不能为空');
        
        if ($validator->fails()) {
            Response::error($validator->getFirstError(), 400);
            return;
        }
        
        $classId = $this->classModel->create([
            'name' => $data['name'],
            'grade' => $data['grade'],
            'major' => $data['major'] ?? null,
            'teacher_id' => $data['teacherId'] ?? null,
            'description' => $data['description'] ?? null,
            'student_count' => 0
        ]);
        
        Logger::info("Class created: {$data['name']}");
        
        Response::success(['id' => $classId], '创建成功');
    }
    
    /**
     * 更新班级
     */
    public function update(array $params): void
    {
        $id = (int) $params['id'];
        $class = $this->classModel->find($id);
        
        if (!$class) {
            Response::error('班级不存在', 404);
            return;
        }
        
        $data = $this->getInput();
        
        $validator = new Validator($data);
        $validator->required('name', '班级名称不能为空')
                  ->required('grade', '年级不能为空');
        
        if ($validator->fails()) {
            Response::error($validator->getFirstError(), 400);
            return;
        }
        
        $this->classModel->update($id, [
            'name' => $data['name'],
            'grade' => $data['grade'],
            'major' => $data['major'] ?? null,
            'teacher_id' => $data['teacherId'] ?? null,
            'description' => $data['description'] ?? null
        ]);
        
        Logger::info("Class updated: {$data['name']}");
        
        Response::success(null, '更新成功');
    }
    
    /**
     * 删除班级
     */
    public function destroy(array $params): void
    {
        $id = (int) $params['id'];
        $class = $this->classModel->find($id);
        
        if (!$class) {
            Response::error('班级不存在', 404);
            return;
        }
        
        // 检查是否有关联学生
        $students = $this->studentModel->getByClassId($id);
        if (count($students) > 0) {
            Response::error("该班级有 " . count($students) . " 名学生，无法删除", 400);
            return;
        }
        
        $this->classModel->delete($id);
        
        Logger::info("Class deleted: {$class['name']}");
        
        Response::success(null, '删除成功');
    }
    
    /**
     * 获取班级学生列表
     */
    public function students(array $params): void
    {
        $id = (int) $params['id'];
        $class = $this->classModel->find($id);
        
        if (!$class) {
            Response::error('班级不存在', 404);
            return;
        }
        
        $students = $this->studentModel->getByClassId($id);
        
        Response::success([
            'class' => $class,
            'students' => $students
        ]);
    }
    
    private function getInput(): array
    {
        $input = file_get_contents('php://input');
        return json_decode($input, true) ?? [];
    }
}
