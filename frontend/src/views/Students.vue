<template>
  <div class="students-page">
    <div class="page-header">
      <h1>学生管理</h1>
      <div class="actions">
        <el-button type="primary" @click="handleAdd">
          <el-icon><Plus /></el-icon>添加学生
        </el-button>
        <el-dropdown trigger="click">
          <el-button>
            <el-icon><Upload /></el-icon>导入
            <el-icon class="el-icon--right"><ArrowDown /></el-icon>
          </el-button>
          <template #dropdown>
            <el-dropdown-menu>
              <el-dropdown-item @click="showImportDialog = true">Excel导入</el-dropdown-item>
              <el-dropdown-item @click="showPasteDialog = true">粘贴导入</el-dropdown-item>
              <el-dropdown-item divided @click="downloadTemplate">下载模板</el-dropdown-item>
            </el-dropdown-menu>
          </template>
        </el-dropdown>
        <el-button @click="handleExport">
          <el-icon><Download /></el-icon>导出
        </el-button>
      </div>
    </div>
    
    <div class="card">
      <!-- 搜索栏 -->
      <div class="search-bar">
        <el-input v-model="searchParams.keyword" placeholder="搜索学号/姓名" clearable @keyup.enter="fetchData">
          <template #prefix><el-icon><Search /></el-icon></template>
        </el-input>
        <el-select v-model="searchParams.classId" placeholder="选择班级" clearable @change="fetchData">
          <el-option v-for="c in classes" :key="c.id" :label="c.name" :value="c.id" />
        </el-select>
        <el-select v-model="searchParams.status" placeholder="状态" clearable @change="fetchData">
          <el-option label="在读" :value="1" />
          <el-option label="毕业/休学" :value="0" />
        </el-select>
        <el-button type="primary" @click="fetchData">搜索</el-button>
        <el-button @click="resetSearch">重置</el-button>
      </div>
      
      <!-- 表格 -->
      <el-table :data="tableData" v-loading="loading" stripe>
        <el-table-column prop="student_no" label="学号" width="120" />
        <el-table-column prop="name" label="姓名" width="100" />
        <el-table-column prop="gender" label="性别" width="70">
          <template #default="{ row }">
            <el-tag :type="row.gender === '男' ? 'primary' : 'danger'" size="small">{{ row.gender }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="class_name" label="班级" min-width="120" />
        <el-table-column prop="phone" label="联系电话" width="130" />
        <el-table-column prop="email" label="邮箱" min-width="160" />
        <el-table-column prop="status" label="状态" width="90">
          <template #default="{ row }">
            <el-tag :type="row.status === 1 ? 'success' : 'info'" size="small">
              {{ row.status === 1 ? '在读' : '毕业/休学' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="操作" width="160" fixed="right">
          <template #default="{ row }">
            <el-button type="primary" link size="small" @click="handleEdit(row)">编辑</el-button>
            <el-button type="primary" link size="small" @click="viewGrades(row)">成绩</el-button>
            <el-button type="danger" link size="small" @click="handleDelete(row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>
      
      <!-- 分页 -->
      <div class="pagination-wrapper">
        <el-pagination
          v-model:current-page="pagination.page"
          v-model:page-size="pagination.pageSize"
          :total="pagination.total"
          :page-sizes="[10, 20, 50, 100]"
          layout="total, sizes, prev, pager, next, jumper"
          @size-change="fetchData"
          @current-change="fetchData"
        />
      </div>
    </div>
    
    <!-- 编辑弹窗 -->
    <el-dialog v-model="dialogVisible" :title="isEdit ? '编辑学生' : '添加学生'" width="600px" destroy-on-close>
      <el-form ref="formRef" :model="form" :rules="rules" label-width="80px">
        <el-row :gutter="16">
          <el-col :span="12">
            <el-form-item label="学号" prop="studentNo">
              <el-input v-model="form.studentNo" placeholder="请输入学号" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="姓名" prop="name">
              <el-input v-model="form.name" placeholder="请输入姓名" />
            </el-form-item>
          </el-col>
        </el-row>
        <el-row :gutter="16">
          <el-col :span="12">
            <el-form-item label="性别" prop="gender">
              <el-radio-group v-model="form.gender">
                <el-radio value="男">男</el-radio>
                <el-radio value="女">女</el-radio>
              </el-radio-group>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="班级" prop="classId">
              <el-select v-model="form.classId" placeholder="选择班级" style="width: 100%">
                <el-option v-for="c in classes" :key="c.id" :label="c.name" :value="c.id" />
              </el-select>
            </el-form-item>
          </el-col>
        </el-row>
        <el-row :gutter="16">
          <el-col :span="12">
            <el-form-item label="手机号" prop="phone">
              <el-input v-model="form.phone" placeholder="请输入手机号" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="邮箱" prop="email">
              <el-input v-model="form.email" placeholder="请输入邮箱" />
            </el-form-item>
          </el-col>
        </el-row>
        <el-form-item label="状态" prop="status">
          <el-radio-group v-model="form.status">
            <el-radio :value="1">在读</el-radio>
            <el-radio :value="0">毕业/休学</el-radio>
          </el-radio-group>
        </el-form-item>
        <el-form-item label="备注">
          <el-input v-model="form.remark" type="textarea" rows="2" placeholder="备注信息" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="saving" @click="handleSave">保存</el-button>
      </template>
    </el-dialog>
    
    <!-- Excel导入弹窗 -->
    <el-dialog v-model="showImportDialog" title="Excel导入" width="520px" @closed="resetImportDialog">
      <div v-if="!importResult">
        <el-upload
          ref="uploadRef"
          drag
          accept=".xlsx,.xls,.csv"
          :auto-upload="false"
          :limit="1"
          :on-change="handleFileChange"
          :on-remove="handleFileRemove"
        >
          <el-icon class="el-icon--upload"><UploadFilled /></el-icon>
          <div class="el-upload__text">将文件拖到此处，或<em>点击上传</em></div>
          <template #tip>
            <div class="el-upload__tip">
              仅支持 xlsx、xls、csv 格式，
              <el-link type="primary" :underline="false" @click.stop="downloadTemplate">下载导入模板</el-link>
            </div>
          </template>
        </el-upload>
      </div>
      
      <!-- 导入结果展示 -->
      <div v-else class="import-result">
        <el-result 
          :icon="importResult.failed === 0 ? 'success' : 'warning'" 
          :title="importResult.failed === 0 ? '导入成功' : '导入完成'"
        >
          <template #sub-title>
            <div class="result-summary">
              <el-statistic title="成功" :value="importResult.success" class="success-stat" />
              <el-statistic title="失败" :value="importResult.failed" class="fail-stat" />
            </div>
          </template>
          <template #extra>
            <div v-if="importResult.errors && importResult.errors.length > 0" class="error-list">
              <el-collapse>
                <el-collapse-item title="查看错误详情" name="errors">
                  <ul>
                    <li v-for="(err, idx) in importResult.errors" :key="idx" class="error-item">
                      {{ err }}
                    </li>
                  </ul>
                </el-collapse-item>
              </el-collapse>
            </div>
          </template>
        </el-result>
      </div>
      
      <template #footer>
        <el-button @click="showImportDialog = false">{{ importResult ? '关闭' : '取消' }}</el-button>
        <el-button v-if="!importResult" type="primary" :loading="importing" :disabled="!uploadFile" @click="handleImport">
          导入
        </el-button>
        <el-button v-else type="primary" @click="resetImportDialog">继续导入</el-button>
      </template>
    </el-dialog>
    
    <!-- 粘贴导入弹窗 -->
    <el-dialog v-model="showPasteDialog" title="粘贴导入" width="650px" @closed="resetPasteDialog">
      <div v-if="!pasteResult">
        <el-form label-width="80px">
          <el-form-item label="目标班级">
            <el-select v-model="pasteForm.classId" placeholder="选择班级（可不选）" clearable style="width: 100%">
              <el-option v-for="c in classes" :key="c.id" :label="c.name" :value="c.id" />
            </el-select>
          </el-form-item>
          <el-form-item label="数据内容">
            <el-input
              v-model="pasteForm.content"
              type="textarea"
              rows="10"
              placeholder="请粘贴学生数据，每行一条，支持空格/制表符/逗号分隔：&#10;学号 姓名 性别&#10;&#10;示例：&#10;2024001001 张三 男&#10;2024001002 李四 女&#10;2024001003,王五,男"
            />
          </el-form-item>
        </el-form>
        <el-alert type="info" :closable="false" show-icon>
          <template #title>
            <span>格式说明：每行一条记录，字段依次为：学号（必填）、姓名（必填）、性别（可选，默认男）</span>
          </template>
        </el-alert>
      </div>
      
      <!-- 粘贴导入结果展示 -->
      <div v-else class="import-result">
        <el-result 
          :icon="pasteResult.failed === 0 ? 'success' : 'warning'" 
          :title="pasteResult.failed === 0 ? '导入成功' : '导入完成'"
        >
          <template #sub-title>
            <div class="result-summary">
              <el-statistic title="成功" :value="pasteResult.success" class="success-stat" />
              <el-statistic title="失败" :value="pasteResult.failed" class="fail-stat" />
            </div>
          </template>
          <template #extra>
            <div v-if="pasteResult.errors && pasteResult.errors.length > 0" class="error-list">
              <el-collapse>
                <el-collapse-item title="查看错误详情" name="errors">
                  <ul>
                    <li v-for="(err, idx) in pasteResult.errors" :key="idx" class="error-item">
                      {{ err }}
                    </li>
                  </ul>
                </el-collapse-item>
              </el-collapse>
            </div>
          </template>
        </el-result>
      </div>
      
      <template #footer>
        <el-button @click="showPasteDialog = false">{{ pasteResult ? '关闭' : '取消' }}</el-button>
        <el-button v-if="!pasteResult" type="primary" :loading="importing" :disabled="!pasteForm.content.trim()" @click="handlePasteImport">
          导入
        </el-button>
        <el-button v-else type="primary" @click="resetPasteDialog">继续导入</el-button>
      </template>
    </el-dialog>
    
    <!-- 成绩查看弹窗 -->
    <el-dialog v-model="showGradesDialog" :title="`${currentStudent?.name} 的成绩`" width="800px">
      <el-empty v-if="studentGrades.length === 0" description="暂无成绩记录" />
      <el-table v-else :data="studentGrades" stripe>
        <el-table-column prop="course_name" label="课程" />
        <el-table-column prop="score" label="成绩" width="80">
          <template #default="{ row }">
            <el-tag :type="row.score >= 60 ? 'success' : 'danger'">{{ row.score }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="grade_level" label="等级" width="80" />
        <el-table-column prop="semester" label="学期" width="120" />
        <el-table-column prop="exam_type" label="考试类型" width="100" />
      </el-table>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessageBox, ElMessage } from 'element-plus'
import { studentApi, gradeApi } from '@/api'
import { showSuccess } from '@/utils/request'

const loading = ref(false)
const saving = ref(false)
const importing = ref(false)
const tableData = ref([])
const classes = ref([])
const dialogVisible = ref(false)
const showImportDialog = ref(false)
const showPasteDialog = ref(false)
const showGradesDialog = ref(false)
const isEdit = ref(false)
const currentStudent = ref(null)
const studentGrades = ref([])
const uploadFile = ref(null)
const uploadRef = ref(null)
const formRef = ref(null)
const importResult = ref(null)
const pasteResult = ref(null)

const searchParams = reactive({ keyword: '', classId: null, status: null })
const pagination = reactive({ page: 1, pageSize: 20, total: 0 })
const form = reactive({
  id: null, studentNo: '', name: '', gender: '男', classId: null,
  phone: '', email: '', status: 1, remark: ''
})
const pasteForm = reactive({ classId: null, content: '' })

const validatePhone = (rule, value, callback) => {
  if (value && !/^1[3-9]\d{9}$/.test(value)) {
    callback(new Error('手机号格式不正确'))
  } else callback()
}

const validateEmail = (rule, value, callback) => {
  if (value && !/^[\w-]+(\.[\w-]+)*@[\w-]+(\.[\w-]+)+$/.test(value)) {
    callback(new Error('邮箱格式不正确'))
  } else callback()
}

const rules = {
  studentNo: [{ required: true, message: '请输入学号', trigger: 'blur' }],
  name: [{ required: true, message: '请输入姓名', trigger: 'blur' }],
  gender: [{ required: true, message: '请选择性别', trigger: 'change' }],
  phone: [{ validator: validatePhone, trigger: 'blur' }],
  email: [{ validator: validateEmail, trigger: 'blur' }]
}

async function fetchData() {
  loading.value = true
  try {
    const res = await studentApi.getList({
      page: pagination.page,
      pageSize: pagination.pageSize,
      ...searchParams
    })
    tableData.value = res.data.items
    pagination.total = res.data.total
    classes.value = res.data.classes || []
  } finally {
    loading.value = false
  }
}

function resetSearch() {
  Object.assign(searchParams, { keyword: '', classId: null, status: null })
  pagination.page = 1
  fetchData()
}

function handleAdd() {
  isEdit.value = false
  Object.assign(form, { id: null, studentNo: '', name: '', gender: '男', classId: null, phone: '', email: '', status: 1, remark: '' })
  dialogVisible.value = true
}

function handleEdit(row) {
  isEdit.value = true
  Object.assign(form, {
    id: row.id, studentNo: row.student_no, name: row.name, gender: row.gender,
    classId: row.class_id, phone: row.phone, email: row.email, status: row.status, remark: row.remark
  })
  dialogVisible.value = true
}

async function handleSave() {
  const valid = await formRef.value.validate().catch(() => false)
  if (!valid) return
  saving.value = true
  try {
    if (isEdit.value) {
      await studentApi.update(form.id, form)
      showSuccess('更新成功')
    } else {
      await studentApi.create(form)
      showSuccess('添加成功')
    }
    dialogVisible.value = false
    fetchData()
  } finally {
    saving.value = false
  }
}

async function handleDelete(row) {
  try {
    await ElMessageBox.confirm(`确定要删除学生"${row.name}"吗？`, '提示', { type: 'warning' })
    await studentApi.delete(row.id)
    showSuccess('删除成功')
    fetchData()
  } catch (e) {}
}

async function viewGrades(row) {
  currentStudent.value = row
  try {
    const res = await gradeApi.getStudentGrades(row.id)
    studentGrades.value = res.data.grades || []
    showGradesDialog.value = true
  } catch (e) {}
}

function handleFileChange(file) {
  uploadFile.value = file.raw
}

function handleFileRemove() {
  uploadFile.value = null
}

function resetImportDialog() {
  uploadFile.value = null
  importResult.value = null
  if (uploadRef.value) {
    uploadRef.value.clearFiles()
  }
}

function resetPasteDialog() {
  pasteResult.value = null
  pasteForm.content = ''
  pasteForm.classId = null
}

async function handleImport() {
  if (!uploadFile.value) {
    ElMessage.warning('请先选择要导入的文件')
    return
  }
  importing.value = true
  try {
    const formData = new FormData()
    formData.append('file', uploadFile.value)
    const res = await studentApi.import(formData)
    importResult.value = res.data
    if (res.data.success > 0) {
      fetchData()
    }
  } catch (e) {
    importResult.value = { success: 0, failed: 0, errors: [e.message || '导入失败'] }
  } finally {
    importing.value = false
  }
}

async function handlePasteImport() {
  if (!pasteForm.content.trim()) {
    ElMessage.warning('请输入要导入的数据')
    return
  }
  importing.value = true
  try {
    const res = await studentApi.pasteImport(pasteForm)
    pasteResult.value = res.data
    if (res.data.success > 0) {
      fetchData()
    }
  } catch (e) {
    pasteResult.value = { success: 0, failed: 0, errors: [e.message || '导入失败'] }
  } finally {
    importing.value = false
  }
}

function handleExport() {
  const url = studentApi.export({ classId: searchParams.classId })
  window.open(url, '_blank')
}

function downloadTemplate() {
  const url = studentApi.getTemplate()
  window.open(url, '_blank')
}

onMounted(() => { fetchData() })
</script>

<style lang="scss" scoped>
.students-page {
  .actions {
    display: flex;
    gap: 12px;
  }
}

.import-result {
  .result-summary {
    display: flex;
    justify-content: center;
    gap: 60px;
    margin: 16px 0;
    
    .success-stat {
      :deep(.el-statistic__number) {
        color: var(--el-color-success);
      }
    }
    
    .fail-stat {
      :deep(.el-statistic__number) {
        color: var(--el-color-danger);
      }
    }
  }
  
  .error-list {
    width: 100%;
    max-width: 400px;
    margin: 0 auto;
    
    ul {
      list-style: none;
      padding: 0;
      margin: 0;
      text-align: left;
    }
    
    .error-item {
      padding: 8px 0;
      color: var(--el-color-danger);
      font-size: 13px;
      border-bottom: 1px dashed var(--el-border-color-lighter);
      
      &:last-child {
        border-bottom: none;
      }
    }
  }
}
</style>
