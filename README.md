# 学生成绩管理系统

一个功能完整的学生成绩管理系统，采用 PHP + MySQL 后端 和 Vue3 + Element Plus 前端技术栈，支持 Docker 一键部署。

## 📋 功能特性

### 用户管理
- 用户登录/注册/注销
- 角色权限（管理员、教师）
- 个人信息修改
- 密码修改

### 学生管理
- 学生信息增删改查
- 按班级筛选
- Excel 导入/导出
- 粘贴批量导入
- 下载导入模板

### 班级管理
- 班级信息增删改查
- 班主任分配
- 学生人数统计
- 班级学生列表

### 课程管理
- 课程信息增删改查
- 课程类型（必修/选修）
- 授课教师分配
- 学分学时管理

### 成绩管理
- 成绩录入/编辑/删除
- 自动等级计算
- Excel 导入/导出
- 粘贴批量导入
- 多条件筛选

### 统计分析
- 数据概览（学生数、课程数、班级数）
- 成绩分布图表
- 班级成绩对比
- 学生成绩排名
- 成绩趋势分析

## 🛠 技术栈

### 后端
- PHP 8.2
- MySQL 8.0
- Firebase JWT（认证）
- PHPSpreadsheet（Excel处理）
- Apache（Web服务器）

### 前端
- Vue 3
- Vite
- Vue Router
- Pinia
- Element Plus
- ECharts
- Axios

### 运维
- Docker & Docker Compose
- Nginx（生产环境）

## 🚀 快速开始

### Docker 部署（推荐）

```bash
进入根目录
# 启动所有服务
docker-compose up -d

# 等待服务启动完成（约30秒）
```

服务地址：
- 前端：http://localhost:3000
- 后端API：http://localhost:8000
- 数据库：localhost:3306

### 默认账号

| 角色 | 用户名 | 密码 |
|------|--------|------|
| 管理员 | admin | admin123 |
| 教师 | teacher | teacher123 |

### 本地开发

#### 后端

```bash
cd backend

# 安装 PHP 依赖
composer install

# 配置环境变量（或修改 src/Config/Database.php）
export DB_HOST=localhost
export DB_DATABASE=grade_system
export DB_USERNAME=root
export DB_PASSWORD=your_password
export JWT_SECRET=your_secret

# 启动 PHP 内置服务器
php -S localhost:8000 -t public
```

#### 前端

```bash
cd frontend

# 安装依赖
npm install

# 启动开发服务器
npm run dev

# 构建生产版本
npm run build
```

## 📁 项目结构

```
label2021/
├── backend/                    # PHP 后端
│   ├── public/                 # 入口目录
│   │   └── index.php          # API 入口
│   ├── src/
│   │   ├── Config/            # 配置文件
│   │   ├── Controllers/       # 控制器
│   │   ├── Models/            # 数据模型
│   │   ├── Services/          # 业务服务
│   │   ├── Utils/             # 工具类
│   │   ├── Middleware/        # 中间件
│   │   ├── Router.php         # 路由器
│   │   └── routes.php         # 路由定义
│   ├── Dockerfile
│   ├── apache.conf
│   ├── composer.json
│   └── init.sql               # 数据库初始化脚本
│
├── frontend/                   # Vue3 前端
│   ├── src/
│   │   ├── api/               # API 接口
│   │   ├── assets/            # 静态资源
│   │   ├── layouts/           # 布局组件
│   │   ├── router/            # 路由配置
│   │   ├── stores/            # Pinia 状态
│   │   ├── utils/             # 工具函数
│   │   ├── views/             # 页面组件
│   │   ├── App.vue
│   │   └── main.js
│   ├── public/
│   ├── Dockerfile
│   ├── nginx.conf
│   ├── vite.config.js
│   └── package.json
│
├── docker-compose.yml          # Docker 编排
└── README.md
```

## 🔧 API 接口

### 认证接口
| 方法 | 路径 | 说明 |
|------|------|------|
| POST | /api/auth/login | 用户登录 |
| POST | /api/auth/register | 用户注册 |
| GET | /api/auth/profile | 获取个人信息 |
| PUT | /api/auth/profile | 更新个人信息 |
| PUT | /api/auth/password | 修改密码 |

### 学生接口
| 方法 | 路径 | 说明 |
|------|------|------|
| GET | /api/students | 学生列表 |
| POST | /api/students | 添加学生 |
| PUT | /api/students/{id} | 更新学生 |
| DELETE | /api/students/{id} | 删除学生 |
| POST | /api/students/import | Excel导入 |
| GET | /api/students/export | 导出Excel |

### 班级/课程/成绩/统计接口
详见 `backend/src/routes.php`

## 📝 开发说明

### 密码加密
使用 PHP 的 `password_hash()` 和 `password_verify()` 函数。

### JWT 认证
- Token 有效期：7天
- 请求头格式：`Authorization: Bearer <token>`

### 成绩等级规则
| 分数范围 | 等级 |
|----------|------|
| 90-100 | 优秀 |
| 80-89 | 良好 |
| 70-79 | 中等 |
| 60-69 | 及格 |
| 0-59 | 不及格 |

## 📄 许可证

MIT License
