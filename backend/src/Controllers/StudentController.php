<?php
/**
 * 学生管理控制器
 */

namespace App\Controllers;

use App\Models\Student;
use App\Models\ClassModel;
use App\Utils\Response;
use App\Utils\Validator;
use App\Utils\Logger;
use App\Services\ExcelService;

class StudentController
{
    private Student $studentModel;
    private ClassModel $classModel;
    
    public function __construct()
    {
        $this->studentModel = new Student();
        $this->classModel = new ClassModel();
    }
    
    /**
     * 获取学生列表
     */
    public function index(array $params): void
    {
        $page = (int) ($_GET['page'] ?? 1);
        $pageSize = (int) ($_GET['pageSize'] ?? 20);
        $keyword = $_GET['keyword'] ?? null;
        $classId = isset($_GET['classId']) ? (int) $_GET['classId'] : null;
        $status = isset($_GET['status']) ? (int) $_GET['status'] : null;
        
        $result = $this->studentModel->search($page, $pageSize, $keyword, $classId, $status);
        
        // 获取班级列表和学生总数信息
        $classes = $this->classModel->getAllForSelect();
        
        Response::success([
            'items' => $result['items'],
            'total' => $result['total'],
            'page' => $result['page'],
            'pageSize' => $result['pageSize'],
            'totalPages' => ceil($result['total'] / $result['pageSize']),
            'classes' => $classes
        ]);
    }
    
    /**
     * 获取学生详情
     */
    public function show(array $params): void
    {
        $id = (int) $params['id'];
        $student = $this->studentModel->find($id);
        
        if (!$student) {
            Response::error('学生不存在', 404);
            return;
        }
        
        // 获取班级信息
        if ($student['class_id']) {
            $class = $this->classModel->find($student['class_id']);
            $student['class_name'] = $class['name'] ?? null;
            $student['class_grade'] = $class['grade'] ?? null;
        }
        
        Response::success($student);
    }
    
    /**
     * 创建学生
     */
    public function store(array $params): void
    {
        $data = $this->getInput();
        
        $validator = new Validator($data);
        $validator->required('studentNo', '学号不能为空')
                  ->required('name', '姓名不能为空')
                  ->in('gender', ['男', '女'], '性别值无效')
                  ->date('birthDate', 'Y-m-d', '出生日期格式不正确')
                  ->phone('phone', '手机号格式不正确')
                  ->email('email', '邮箱格式不正确');
        
        if ($validator->fails()) {
            Response::error($validator->getFirstError(), 400);
            return;
        }
        
        if ($this->studentModel->exists('student_no', $data['studentNo'])) {
            Response::error('学号已存在', 400);
            return;
        }
        
        $studentId = $this->studentModel->create([
            'student_no' => $data['studentNo'],
            'name' => $data['name'],
            'gender' => $data['gender'] ?? '男',
            'birth_date' => $data['birthDate'] ?? null,
            'class_id' => $data['classId'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'address' => $data['address'] ?? null,
            'id_card' => $data['idCard'] ?? null,
            'admission_date' => $data['admissionDate'] ?? null,
            'status' => $data['status'] ?? 1,
            'remark' => $data['remark'] ?? null
        ]);
        
        // 更新班级学生人数
        if (isset($data['classId'])) {
            $this->classModel->updateStudentCount($data['classId']);
        }
        
        Logger::info("Student created: {$data['studentNo']} - {$data['name']}");
        
        Response::success(['id' => $studentId], '创建成功');
    }
    
    /**
     * 更新学生
     */
    public function update(array $params): void
    {
        $id = (int) $params['id'];
        $student = $this->studentModel->find($id);
        
        if (!$student) {
            Response::error('学生不存在', 404);
            return;
        }
        
        $data = $this->getInput();
        
        $validator = new Validator($data);
        $validator->required('studentNo', '学号不能为空')
                  ->required('name', '姓名不能为空')
                  ->date('birthDate', 'Y-m-d', '出生日期格式不正确')
                  ->phone('phone', '手机号格式不正确')
                  ->email('email', '邮箱格式不正确');
        
        if ($validator->fails()) {
            Response::error($validator->getFirstError(), 400);
            return;
        }
        
        // 检查学号是否重复（排除自己）
        if ($this->studentModel->exists('student_no', $data['studentNo'], $id)) {
            Response::error('学号已存在', 400);
            return;
        }
        
        $oldClassId = $student['class_id'];
        
        $this->studentModel->update($id, [
            'student_no' => $data['studentNo'],
            'name' => $data['name'],
            'gender' => $data['gender'] ?? $student['gender'],
            'birth_date' => $data['birthDate'] ?? null,
            'class_id' => $data['classId'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'address' => $data['address'] ?? null,
            'id_card' => $data['idCard'] ?? null,
            'admission_date' => $data['admissionDate'] ?? null,
            'status' => $data['status'] ?? $student['status'],
            'remark' => $data['remark'] ?? null
        ]);
        
        // 更新班级学生人数
        if ($oldClassId) {
            $this->classModel->updateStudentCount($oldClassId);
        }
        if (isset($data['classId']) && $data['classId'] != $oldClassId) {
            $this->classModel->updateStudentCount($data['classId']);
        }
        
        Logger::info("Student updated: {$data['studentNo']} - {$data['name']}");
        
        Response::success(null, '更新成功');
    }
    
    /**
     * 删除学生
     */
    public function destroy(array $params): void
    {
        $id = (int) $params['id'];
        $student = $this->studentModel->find($id);
        
        if (!$student) {
            Response::error('学生不存在', 404);
            return;
        }
        
        $classId = $student['class_id'];
        
        $this->studentModel->delete($id);
        
        // 更新班级学生人数
        if ($classId) {
            $this->classModel->updateStudentCount($classId);
        }
        
        Logger::info("Student deleted: {$student['student_no']} - {$student['name']}");
        
        Response::success(null, '删除成功');
    }
    
    /**
     * 导入学生（Excel）
     */
    public function import(array $params): void
    {
        if (!isset($_FILES['file'])) {
            Response::error('请上传文件', 400);
            return;
        }
        
        $file = $_FILES['file'];
        
        // 检查文件类型
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['xlsx', 'xls', 'csv'])) {
            Response::error('仅支持 xlsx、xls、csv 格式', 400);
            return;
        }
        
        try {
            $excelService = new ExcelService();
            $result = $excelService->importStudents($file['tmp_name'], $ext);
            
            Logger::info("Students imported: {$result['success']} success, {$result['failed']} failed");
            
            Response::success($result, "导入完成，成功 {$result['success']} 条，失败 {$result['failed']} 条");
        } catch (\Exception $e) {
            Logger::error("Import failed: " . $e->getMessage());
            Response::error('导入失败：' . $e->getMessage(), 500);
        }
    }
    
    /**
     * 粘贴导入学生
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
            $result = $excelService->pasteImportStudents($data['content'], $data['classId'] ?? null);
            
            Logger::info("Students paste imported: {$result['success']} success, {$result['failed']} failed");
            
            Response::success($result, "导入完成，成功 {$result['success']} 条，失败 {$result['failed']} 条");
        } catch (\Exception $e) {
            Logger::error("Paste import failed: " . $e->getMessage());
            Response::error('导入失败：' . $e->getMessage(), 500);
        }
    }
    
    /**
     * 导出学生
     */
    public function export(array $params): void
    {
        $classId = isset($_GET['classId']) ? (int) $_GET['classId'] : null;
        
        try {
            $excelService = new ExcelService();
            $excelService->exportStudents($classId);
        } catch (\Exception $e) {
            Logger::error("Export failed: " . $e->getMessage());
            Response::error('导出失败：' . $e->getMessage(), 500);
        }
    }
    
    /**
     * 下载导入模板
     */
    public function template(array $params): void
    {
        try {
            $excelService = new ExcelService();
            $excelService->downloadStudentTemplate();
        } catch (\Exception $e) {
            Logger::error("Template download failed: " . $e->getMessage());
            Response::error('模板下载失败：' . $e->getMessage(), 500);
        }
    }
    
    private function getInput(): array
    {
        $input = file_get_contents('php://input');
        return json_decode($input, true) ?? [];
    }
}
