<?php
/**
 * Excel服务类 - 处理导入导出
 */

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Models\Student;
use App\Models\Grade;
use App\Models\Course;
use App\Models\ClassModel;
use App\Config\App;
use App\Utils\Logger;

class ExcelService
{
    private Student $studentModel;
    private Grade $gradeModel;
    private Course $courseModel;
    private ClassModel $classModel;
    
    public function __construct()
    {
        $this->studentModel = new Student();
        $this->gradeModel = new Grade();
        $this->courseModel = new Course();
        $this->classModel = new ClassModel();
    }
    
    /**
     * 导入学生
     */
    public function importStudents(string $filePath, string $ext): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();
        
        // 跳过表头
        array_shift($rows);
        
        $success = 0;
        $failed = 0;
        $errors = [];
        
        foreach ($rows as $index => $row) {
            $lineNum = $index + 2;
            
            // 跳过空行
            if (empty($row[0]) && empty($row[1])) {
                continue;
            }
            
            try {
                $studentNo = trim($row[0] ?? '');
                $name = trim($row[1] ?? '');
                $gender = trim($row[2] ?? '男');
                $className = trim($row[3] ?? '');
                $phone = trim($row[4] ?? '');
                $email = trim($row[5] ?? '');
                
                if (empty($studentNo) || empty($name)) {
                    $errors[] = "第{$lineNum}行：学号和姓名不能为空";
                    $failed++;
                    continue;
                }
                
                // 检查学号是否已存在
                if ($this->studentModel->exists('student_no', $studentNo)) {
                    $errors[] = "第{$lineNum}行：学号 {$studentNo} 已存在";
                    $failed++;
                    continue;
                }
                
                // 查找班级
                $classId = null;
                if (!empty($className)) {
                    $classes = $this->classModel->where(['name' => $className]);
                    if (!empty($classes)) {
                        $classId = $classes[0]['id'];
                    }
                }
                
                $this->studentModel->create([
                    'student_no' => $studentNo,
                    'name' => $name,
                    'gender' => in_array($gender, ['男', '女']) ? $gender : '男',
                    'class_id' => $classId,
                    'phone' => $phone,
                    'email' => $email,
                    'status' => 1
                ]);
                
                $success++;
                
                // 更新班级学生人数
                if ($classId) {
                    $this->classModel->updateStudentCount($classId);
                }
                
            } catch (\Exception $e) {
                $errors[] = "第{$lineNum}行：" . $e->getMessage();
                $failed++;
            }
        }
        
        return [
            'success' => $success,
            'failed' => $failed,
            'errors' => array_slice($errors, 0, 10) // 只返回前10个错误
        ];
    }
    
    /**
     * 粘贴导入学生
     */
    public function pasteImportStudents(string $content, ?int $classId = null): array
    {
        $lines = explode("\n", trim($content));
        
        $success = 0;
        $failed = 0;
        $errors = [];
        
        foreach ($lines as $index => $line) {
            $lineNum = $index + 1;
            $line = trim($line);
            
            if (empty($line)) {
                continue;
            }
            
            try {
                // 支持制表符或空格分隔
                $parts = preg_split('/[\t,\s]+/', $line);
                
                if (count($parts) < 2) {
                    $errors[] = "第{$lineNum}行：格式错误，至少需要学号和姓名";
                    $failed++;
                    continue;
                }
                
                $studentNo = trim($parts[0]);
                $name = trim($parts[1]);
                $gender = isset($parts[2]) ? trim($parts[2]) : '男';
                
                if (empty($studentNo) || empty($name)) {
                    $errors[] = "第{$lineNum}行：学号和姓名不能为空";
                    $failed++;
                    continue;
                }
                
                // 检查学号是否已存在
                if ($this->studentModel->exists('student_no', $studentNo)) {
                    $errors[] = "第{$lineNum}行：学号 {$studentNo} 已存在";
                    $failed++;
                    continue;
                }
                
                $this->studentModel->create([
                    'student_no' => $studentNo,
                    'name' => $name,
                    'gender' => in_array($gender, ['男', '女']) ? $gender : '男',
                    'class_id' => $classId,
                    'status' => 1
                ]);
                
                $success++;
                
            } catch (\Exception $e) {
                $errors[] = "第{$lineNum}行：" . $e->getMessage();
                $failed++;
            }
        }
        
        // 更新班级学生人数
        if ($classId && $success > 0) {
            $this->classModel->updateStudentCount($classId);
        }
        
        return [
            'success' => $success,
            'failed' => $failed,
            'errors' => array_slice($errors, 0, 10)
        ];
    }
    
    /**
     * 导出学生
     */
    public function exportStudents(?int $classId = null): void
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // 设置表头
        $headers = ['学号', '姓名', '性别', '班级', '联系电话', '邮箱', '入学日期', '状态'];
        $sheet->fromArray($headers, null, 'A1');
        
        // 设置表头样式
        $sheet->getStyle('A1:H1')->getFont()->setBold(true);
        
        // 查询数据
        if ($classId) {
            $students = $this->studentModel->getByClassId($classId);
        } else {
            $students = $this->studentModel->all();
        }
        
        // 填充数据
        $row = 2;
        foreach ($students as $student) {
            $class = $student['class_id'] ? $this->classModel->find($student['class_id']) : null;
            
            $sheet->setCellValue('A' . $row, $student['student_no']);
            $sheet->setCellValue('B' . $row, $student['name']);
            $sheet->setCellValue('C' . $row, $student['gender']);
            $sheet->setCellValue('D' . $row, $class['name'] ?? '');
            $sheet->setCellValue('E' . $row, $student['phone'] ?? '');
            $sheet->setCellValue('F' . $row, $student['email'] ?? '');
            $sheet->setCellValue('G' . $row, $student['admission_date'] ?? '');
            $sheet->setCellValue('H' . $row, $student['status'] == 1 ? '在读' : '毕业/休学');
            $row++;
        }
        
        // 自动调整列宽
        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        // 输出文件
        $this->outputExcel($spreadsheet, '学生列表_' . date('YmdHis'));
    }
    
    /**
     * 下载学生导入模板
     */
    public function downloadStudentTemplate(): void
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // 设置表头
        $headers = ['学号*', '姓名*', '性别', '班级名称', '联系电话', '邮箱'];
        $sheet->fromArray($headers, null, 'A1');
        
        // 设置表头样式
        $sheet->getStyle('A1:F1')->getFont()->setBold(true);
        
        // 添加示例数据
        $sheet->fromArray(['2024001001', '张三', '男', '计算机1班', '13800000001', 'zhangsan@example.com'], null, 'A2');
        $sheet->fromArray(['2024001002', '李四', '女', '计算机1班', '13800000002', 'lisi@example.com'], null, 'A3');
        
        // 自动调整列宽
        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        $this->outputExcel($spreadsheet, '学生导入模板');
    }
    
    /**
     * 导入成绩
     */
    public function importGrades(string $filePath, string $ext): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();
        
        // 获取表头判断格式
        $header = array_shift($rows);
        $columnCount = count(array_filter($header, fn($v) => !empty(trim($v ?? ''))));
        
        // 判断是导出格式(8列)还是模板格式(5列)
        // 导出格式: 学号, 姓名, 课程代码, 课程名称, 成绩, 等级, 学期, 考试类型
        // 模板格式: 学号, 课程代码, 成绩, 学期, 考试类型
        $isExportFormat = $columnCount >= 8;
        
        $success = 0;
        $failed = 0;
        $errors = [];
        
        foreach ($rows as $index => $row) {
            $lineNum = $index + 2;
            
            if (empty($row[0]) && empty($row[1])) {
                continue;
            }
            
            try {
                if ($isExportFormat) {
                    // 导出格式列映射
                    $studentNo = trim($row[0] ?? '');
                    $courseCode = trim($row[2] ?? '');  // C列
                    $score = trim($row[4] ?? '');       // E列
                    $semester = trim($row[6] ?? '');    // G列
                    $examType = trim($row[7] ?? '期末'); // H列
                } else {
                    // 模板格式列映射
                    $studentNo = trim($row[0] ?? '');
                    $courseCode = trim($row[1] ?? '');
                    $score = trim($row[2] ?? '');
                    $semester = trim($row[3] ?? '');
                    $examType = trim($row[4] ?? '期末');
                }
                
                if (empty($studentNo) || empty($courseCode) || $score === '') {
                    $errors[] = "第{$lineNum}行：学号、课程代码和成绩不能为空";
                    $failed++;
                    continue;
                }
                
                // 查找学生
                $student = $this->studentModel->findByStudentNo($studentNo);
                if (!$student) {
                    $errors[] = "第{$lineNum}行：学号 {$studentNo} 不存在";
                    $failed++;
                    continue;
                }
                
                // 查找课程
                $course = $this->courseModel->findByCode($courseCode);
                if (!$course) {
                    $errors[] = "第{$lineNum}行：课程代码 {$courseCode} 不存在";
                    $failed++;
                    continue;
                }
                
                $scoreVal = (float) $score;
                if ($scoreVal < 0 || $scoreVal > 100) {
                    $errors[] = "第{$lineNum}行：成绩必须在0-100之间";
                    $failed++;
                    continue;
                }
                
                // 检查是否已存在 - 重复则报错
                if ($this->gradeModel->existsGrade($student['id'], $course['id'], $semester, $examType)) {
                    $errors[] = "第{$lineNum}行：该成绩记录已存在（学号：{$studentNo}，课程：{$courseCode}，学期：{$semester}，类型：{$examType}）";
                    $failed++;
                    continue;
                }
                
                // 创建新成绩
                $this->gradeModel->create([
                    'student_id' => $student['id'],
                    'course_id' => $course['id'],
                    'score' => $scoreVal,
                    'grade_level' => App::calculateGradeLevel($scoreVal),
                    'semester' => $semester ?: $course['semester'],
                    'exam_type' => $examType
                ]);
                
                $success++;
                
            } catch (\Exception $e) {
                $errors[] = "第{$lineNum}行：" . $e->getMessage();
                $failed++;
            }
        }
        
        return [
            'success' => $success,
            'failed' => $failed,
            'errors' => array_slice($errors, 0, 10)
        ];
    }
    
    /**
     * 粘贴导入成绩
     */
    public function pasteImportGrades(string $content, ?string $semester = null, ?int $courseId = null): array
    {
        $lines = explode("\n", trim($content));
        
        $success = 0;
        $failed = 0;
        $errors = [];
        
        // 获取课程信息
        $course = $courseId ? $this->courseModel->find($courseId) : null;
        
        foreach ($lines as $index => $line) {
            $lineNum = $index + 1;
            $line = trim($line);
            
            if (empty($line)) {
                continue;
            }
            
            try {
                $parts = preg_split('/[\t,\s]+/', $line);
                
                if (count($parts) < 2) {
                    $errors[] = "第{$lineNum}行：格式错误，至少需要学号和成绩";
                    $failed++;
                    continue;
                }
                
                $studentNo = trim($parts[0]);
                $score = trim($parts[1]);
                
                // 查找学生
                $student = $this->studentModel->findByStudentNo($studentNo);
                if (!$student) {
                    $errors[] = "第{$lineNum}行：学号 {$studentNo} 不存在";
                    $failed++;
                    continue;
                }
                
                if (!$course) {
                    $errors[] = "第{$lineNum}行：请选择课程";
                    $failed++;
                    continue;
                }
                
                $scoreVal = (float) $score;
                if ($scoreVal < 0 || $scoreVal > 100) {
                    $errors[] = "第{$lineNum}行：成绩必须在0-100之间";
                    $failed++;
                    continue;
                }
                
                $sem = $semester ?: $course['semester'];
                
                // 检查是否已存在
                if ($this->gradeModel->existsGrade($student['id'], $course['id'], $sem, '期末')) {
                    $errors[] = "第{$lineNum}行：该成绩记录已存在";
                    $failed++;
                    continue;
                }
                
                $this->gradeModel->create([
                    'student_id' => $student['id'],
                    'course_id' => $course['id'],
                    'score' => $scoreVal,
                    'grade_level' => App::calculateGradeLevel($scoreVal),
                    'semester' => $sem,
                    'exam_type' => '期末'
                ]);
                
                $success++;
                
            } catch (\Exception $e) {
                $errors[] = "第{$lineNum}行：" . $e->getMessage();
                $failed++;
            }
        }
        
        return [
            'success' => $success,
            'failed' => $failed,
            'errors' => array_slice($errors, 0, 10)
        ];
    }
    
    /**
     * 导出成绩
     */
    public function exportGrades(?int $courseId = null, ?string $semester = null): void
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // 设置表头
        $headers = ['学号', '姓名', '课程代码', '课程名称', '成绩', '等级', '学期', '考试类型'];
        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle('A1:H1')->getFont()->setBold(true);
        
        // 查询数据
        $result = $this->gradeModel->search(1, 10000, null, $courseId, $semester, null);
        
        // 填充数据
        $row = 2;
        foreach ($result['items'] as $grade) {
            $sheet->setCellValue('A' . $row, $grade['student_no']);
            $sheet->setCellValue('B' . $row, $grade['student_name']);
            $sheet->setCellValue('C' . $row, $grade['course_code']);
            $sheet->setCellValue('D' . $row, $grade['course_name']);
            $sheet->setCellValue('E' . $row, $grade['score']);
            $sheet->setCellValue('F' . $row, $grade['grade_level']);
            $sheet->setCellValue('G' . $row, $grade['semester']);
            $sheet->setCellValue('H' . $row, $grade['exam_type']);
            $row++;
        }
        
        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        $this->outputExcel($spreadsheet, '成绩列表_' . date('YmdHis'));
    }
    
    /**
     * 下载成绩导入模板
     */
    public function downloadGradeTemplate(): void
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // 设置表头
        $headers = ['学号*', '课程代码*', '成绩*', '学期', '考试类型'];
        $sheet->fromArray($headers, null, 'A1');
        
        // 设置表头样式
        $sheet->getStyle('A1:E1')->getFont()->setBold(true);
        
        // 添加示例数据
        $sheet->fromArray(['2024001001', 'CS101', '85', '2024-2025-1', '期末'], null, 'A2');
        $sheet->fromArray(['2024001002', 'CS101', '92', '2024-2025-1', '期末'], null, 'A3');
        $sheet->fromArray(['2024001003', 'CS102', '78', '2024-2025-1', '期中'], null, 'A4');
        
        // 自动调整列宽
        foreach (range('A', 'E') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        $this->outputExcel($spreadsheet, '成绩导入模板');
    }
    
    /**
     * 输出Excel文件
     */
    private function outputExcel(Spreadsheet $spreadsheet, string $filename): void
    {
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '.xlsx"');
        header('Cache-Control: max-age=0');
        
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit();
    }
}
