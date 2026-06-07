-- 学生成绩管理系统数据库初始化脚本
-- 字符集设置
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- 用户表
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE COMMENT '用户名',
    `password` VARCHAR(255) NOT NULL COMMENT '密码',
    `real_name` VARCHAR(50) NULL COMMENT '真实姓名',
    `email` VARCHAR(100) NULL COMMENT '邮箱',
    `phone` VARCHAR(20) NULL COMMENT '手机号',
    `role` ENUM('admin', 'teacher') DEFAULT 'teacher' COMMENT '角色',
    `status` TINYINT DEFAULT 1 COMMENT '状态：1正常 0禁用',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_username` (`username`),
    INDEX `idx_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户表';

-- 班级表
CREATE TABLE IF NOT EXISTS `classes` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL COMMENT '班级名称',
    `grade` VARCHAR(50) NOT NULL COMMENT '年级',
    `major` VARCHAR(100) NULL COMMENT '专业',
    `teacher_id` INT UNSIGNED NULL COMMENT '班主任ID',
    `student_count` INT DEFAULT 0 COMMENT '学生人数',
    `description` TEXT NULL COMMENT '描述',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_grade` (`grade`),
    INDEX `idx_teacher` (`teacher_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='班级表';

-- 学生表
CREATE TABLE IF NOT EXISTS `students` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `student_no` VARCHAR(50) NOT NULL UNIQUE COMMENT '学号',
    `name` VARCHAR(50) NOT NULL COMMENT '姓名',
    `gender` ENUM('男', '女') DEFAULT '男' COMMENT '性别',
    `birth_date` DATE NULL COMMENT '出生日期',
    `class_id` INT UNSIGNED NULL COMMENT '班级ID',
    `phone` VARCHAR(20) NULL COMMENT '联系电话',
    `email` VARCHAR(100) NULL COMMENT '邮箱',
    `address` VARCHAR(255) NULL COMMENT '地址',
    `id_card` VARCHAR(18) NULL COMMENT '身份证号',
    `admission_date` DATE NULL COMMENT '入学日期',
    `status` TINYINT DEFAULT 1 COMMENT '状态：1在读 0毕业/休学',
    `remark` TEXT NULL COMMENT '备注',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_student_no` (`student_no`),
    INDEX `idx_class` (`class_id`),
    INDEX `idx_name` (`name`),
    INDEX `idx_status` (`status`),
    FOREIGN KEY (`class_id`) REFERENCES `classes`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='学生表';

-- 课程表
CREATE TABLE IF NOT EXISTS `courses` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `code` VARCHAR(50) NOT NULL UNIQUE COMMENT '课程代码',
    `name` VARCHAR(100) NOT NULL COMMENT '课程名称',
    `credits` DECIMAL(3,1) DEFAULT 0 COMMENT '学分',
    `hours` INT DEFAULT 0 COMMENT '学时',
    `course_type` ENUM('必修', '选修') DEFAULT '必修' COMMENT '课程类型',
    `semester` VARCHAR(50) NULL COMMENT '学期',
    `teacher_id` INT UNSIGNED NULL COMMENT '授课教师ID',
    `description` TEXT NULL COMMENT '课程描述',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_code` (`code`),
    INDEX `idx_semester` (`semester`),
    INDEX `idx_type` (`course_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='课程表';

-- 成绩表
CREATE TABLE IF NOT EXISTS `grades` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `student_id` INT UNSIGNED NOT NULL COMMENT '学生ID',
    `course_id` INT UNSIGNED NOT NULL COMMENT '课程ID',
    `score` DECIMAL(5,2) NOT NULL COMMENT '成绩',
    `grade_level` VARCHAR(10) NULL COMMENT '等级：优秀/良好/中等/及格/不及格',
    `semester` VARCHAR(50) NOT NULL COMMENT '学期',
    `exam_type` ENUM('期中', '期末', '平时', '补考') DEFAULT '期末' COMMENT '考试类型',
    `remark` TEXT NULL COMMENT '备注',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_student` (`student_id`),
    INDEX `idx_course` (`course_id`),
    INDEX `idx_semester` (`semester`),
    INDEX `idx_exam_type` (`exam_type`),
    UNIQUE KEY `uk_grade` (`student_id`, `course_id`, `semester`, `exam_type`),
    FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='成绩表';

-- 成绩修改申请表
CREATE TABLE IF NOT EXISTS `grade_change_requests` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `grade_id` INT UNSIGNED NOT NULL COMMENT '成绩ID',
    `applicant_id` INT UNSIGNED NOT NULL COMMENT '申请人ID',
    `original_score` DECIMAL(5,2) NOT NULL COMMENT '原始成绩',
    `new_score` DECIMAL(5,2) NOT NULL COMMENT '修改后成绩',
    `reason` TEXT NOT NULL COMMENT '修改原因',
    `status` ENUM('pending', 'approved', 'rejected') DEFAULT 'pending' COMMENT '状态',
    `reviewer_id` INT UNSIGNED NULL COMMENT '审核人ID',
    `reject_reason` TEXT NULL COMMENT '驳回理由',
    `reviewed_at` TIMESTAMP NULL COMMENT '审核时间',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_grade` (`grade_id`),
    INDEX `idx_applicant` (`applicant_id`),
    INDEX `idx_status` (`status`),
    FOREIGN KEY (`grade_id`) REFERENCES `grades`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`applicant_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='成绩修改申请表';

SET FOREIGN_KEY_CHECKS = 1;

-- ========================================
-- 初始数据
-- ========================================

-- 管理员账号（密码: admin123）
INSERT INTO `users` (`username`, `password`, `real_name`, `email`, `role`, `status`) VALUES
('admin', '$2y$10$30i9B9aa59PZHXfNThtwDOmwszD4grv1aKD0lfQb11.JfyYGzjZ8q', '系统管理员', 'admin@example.com', 'admin', 1);

-- 教师账号（密码: teacher123）
INSERT INTO `users` (`username`, `password`, `real_name`, `email`, `phone`, `role`, `status`) VALUES
('teacher', '$2y$10$/63RMqLD8VmpYshTap4EOOULKtK0I3.PqTOS132Q3tum7Xo/7CCr6', '张老师', 'teacher@example.com', '13800138001', 'teacher', 1),
('teacher2', '$2y$10$/63RMqLD8VmpYshTap4EOOULKtK0I3.PqTOS132Q3tum7Xo/7CCr6', '李老师', 'teacher2@example.com', '13800138002', 'teacher', 1);

-- 班级数据
INSERT INTO `classes` (`name`, `grade`, `major`, `teacher_id`, `description`) VALUES
('计算机科学1班', '2024级', '计算机科学与技术', 2, '2024级计算机科学与技术专业1班'),
('计算机科学2班', '2024级', '计算机科学与技术', 2, '2024级计算机科学与技术专业2班'),
('软件工程1班', '2024级', '软件工程', 3, '2024级软件工程专业1班'),
('软件工程2班', '2024级', '软件工程', 3, '2024级软件工程专业2班'),
('计算机科学1班', '2023级', '计算机科学与技术', 2, '2023级计算机科学与技术专业1班'),
('软件工程1班', '2023级', '软件工程', 3, '2023级软件工程专业1班');

-- 学生数据
INSERT INTO `students` (`student_no`, `name`, `gender`, `class_id`, `phone`, `email`) VALUES
('2024001001', '张三', '男', 1, '13900000001', 'zhangsan@stu.edu.cn'),
('2024001002', '李四', '男', 1, '13900000002', 'lisi@stu.edu.cn'),
('2024001003', '王五', '女', 1, '13900000003', 'wangwu@stu.edu.cn'),
('2024001004', '赵六', '男', 1, '13900000004', 'zhaoliu@stu.edu.cn'),
('2024001005', '钱七', '女', 1, '13900000005', 'qianqi@stu.edu.cn'),
('2024002001', '孙八', '男', 2, '13900000006', 'sunba@stu.edu.cn'),
('2024002002', '周九', '女', 2, '13900000007', 'zhoujiu@stu.edu.cn'),
('2024002003', '吴十', '男', 2, '13900000008', 'wushi@stu.edu.cn'),
('2024003001', '郑一', '女', 3, '13900000009', 'zhengyi@stu.edu.cn'),
('2024003002', '王二', '男', 3, '13900000010', 'wanger@stu.edu.cn'),
('2024003003', '冯三', '女', 3, '13900000011', 'fengsan@stu.edu.cn'),
('2024004001', '陈四', '男', 4, '13900000012', 'chensi@stu.edu.cn'),
('2024004002', '褚五', '女', 4, '13900000013', 'chuwu@stu.edu.cn'),
('2023001001', '卫六', '男', 5, '13900000014', 'weiliu@stu.edu.cn'),
('2023001002', '蒋七', '女', 5, '13900000015', 'jiangqi@stu.edu.cn'),
('2023002001', '沈八', '男', 6, '13900000016', 'shenba@stu.edu.cn'),
('2023002002', '韩九', '女', 6, '13900000017', 'hanjiu@stu.edu.cn'),
('2023002003', '杨十', '男', 6, '13900000018', 'yangshi@stu.edu.cn');

-- 更新班级学生人数
UPDATE `classes` SET `student_count` = (SELECT COUNT(*) FROM `students` WHERE `students`.`class_id` = `classes`.`id`);

-- 课程数据
INSERT INTO `courses` (`code`, `name`, `credits`, `hours`, `course_type`, `semester`, `teacher_id`, `description`) VALUES
('CS101', '程序设计基础', 4.0, 64, '必修', '2024-2025-1', 2, 'C语言程序设计基础课程'),
('CS102', '数据结构', 4.0, 64, '必修', '2024-2025-1', 2, '数据结构与算法课程'),
('CS103', '计算机网络', 3.0, 48, '必修', '2024-2025-1', 3, '计算机网络原理'),
('CS104', '操作系统', 3.5, 56, '必修', '2024-2025-1', 2, '操作系统原理'),
('CS105', '数据库原理', 3.0, 48, '必修', '2024-2025-1', 3, '数据库系统原理'),
('CS201', 'Web开发技术', 3.0, 48, '选修', '2024-2025-1', 2, 'Web前后端开发技术'),
('CS202', '人工智能导论', 2.0, 32, '选修', '2024-2025-1', 3, '人工智能基础知识'),
('MATH101', '高等数学', 5.0, 80, '必修', '2024-2025-1', NULL, '高等数学课程'),
('MATH102', '线性代数', 3.0, 48, '必修', '2024-2025-1', NULL, '线性代数课程'),
('ENG101', '大学英语', 2.0, 32, '必修', '2024-2025-1', NULL, '大学英语课程');

-- 成绩数据
INSERT INTO `grades` (`student_id`, `course_id`, `score`, `grade_level`, `semester`, `exam_type`) VALUES
-- 张三的成绩
(1, 1, 92, '优秀', '2024-2025-1', '期末'),
(1, 2, 88, '良好', '2024-2025-1', '期末'),
(1, 3, 85, '良好', '2024-2025-1', '期末'),
(1, 8, 78, '中等', '2024-2025-1', '期末'),
(1, 9, 82, '良好', '2024-2025-1', '期末'),
-- 李四的成绩
(2, 1, 76, '中等', '2024-2025-1', '期末'),
(2, 2, 68, '及格', '2024-2025-1', '期末'),
(2, 3, 72, '中等', '2024-2025-1', '期末'),
(2, 8, 65, '及格', '2024-2025-1', '期末'),
-- 王五的成绩
(3, 1, 95, '优秀', '2024-2025-1', '期末'),
(3, 2, 91, '优秀', '2024-2025-1', '期末'),
(3, 3, 88, '良好', '2024-2025-1', '期末'),
(3, 4, 85, '良好', '2024-2025-1', '期末'),
-- 赵六的成绩
(4, 1, 58, '不及格', '2024-2025-1', '期末'),
(4, 2, 62, '及格', '2024-2025-1', '期末'),
(4, 3, 70, '中等', '2024-2025-1', '期末'),
-- 钱七的成绩
(5, 1, 82, '良好', '2024-2025-1', '期末'),
(5, 2, 79, '中等', '2024-2025-1', '期末'),
(5, 3, 86, '良好', '2024-2025-1', '期末'),
(5, 5, 90, '优秀', '2024-2025-1', '期末'),
-- 孙八的成绩
(6, 1, 88, '良好', '2024-2025-1', '期末'),
(6, 2, 85, '良好', '2024-2025-1', '期末'),
(6, 4, 78, '中等', '2024-2025-1', '期末'),
-- 周九的成绩
(7, 1, 74, '中等', '2024-2025-1', '期末'),
(7, 2, 71, '中等', '2024-2025-1', '期末'),
(7, 3, 68, '及格', '2024-2025-1', '期末'),
-- 吴十的成绩
(8, 1, 93, '优秀', '2024-2025-1', '期末'),
(8, 2, 89, '良好', '2024-2025-1', '期末'),
(8, 5, 95, '优秀', '2024-2025-1', '期末'),
-- 郑一的成绩
(9, 1, 81, '良好', '2024-2025-1', '期末'),
(9, 2, 77, '中等', '2024-2025-1', '期末'),
(9, 6, 88, '良好', '2024-2025-1', '期末'),
-- 王二的成绩
(10, 1, 67, '及格', '2024-2025-1', '期末'),
(10, 2, 63, '及格', '2024-2025-1', '期末'),
(10, 3, 72, '中等', '2024-2025-1', '期末'),
-- 冯三的成绩
(11, 1, 90, '优秀', '2024-2025-1', '期末'),
(11, 2, 87, '良好', '2024-2025-1', '期末'),
(11, 7, 92, '优秀', '2024-2025-1', '期末'),
-- 陈四的成绩
(12, 1, 78, '中等', '2024-2025-1', '期末'),
(12, 2, 80, '良好', '2024-2025-1', '期末'),
-- 褚五的成绩
(13, 1, 84, '良好', '2024-2025-1', '期末'),
(13, 3, 79, '中等', '2024-2025-1', '期末'),
-- 2023级学生成绩
(14, 1, 86, '良好', '2024-2025-1', '期末'),
(14, 4, 82, '良好', '2024-2025-1', '期末'),
(15, 1, 91, '优秀', '2024-2025-1', '期末'),
(15, 5, 88, '良好', '2024-2025-1', '期末'),
(16, 2, 75, '中等', '2024-2025-1', '期末'),
(16, 6, 80, '良好', '2024-2025-1', '期末'),
(17, 1, 69, '及格', '2024-2025-1', '期末'),
(17, 3, 73, '中等', '2024-2025-1', '期末'),
(18, 2, 94, '优秀', '2024-2025-1', '期末'),
(18, 4, 89, '良好', '2024-2025-1', '期末');
