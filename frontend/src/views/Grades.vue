<template>
  <div class="grades-page">
    <div class="page-header">
      <h1>成绩管理</h1>
      <div class="actions">
        <el-button type="primary" @click="handleAdd">
          <el-icon><Plus /></el-icon>录入成绩
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
      <div class="search-bar">
        <el-select v-model="searchParams.courseId" placeholder="选择课程" clearable @change="fetchData">
          <el-option v-for="c in courses" :key="c.id" :label="c.name" :value="c.id" />
        </el-select>
        <el-select v-model="searchParams.semester" placeholder="选择学期" clearable @change="fetchData">
          <el-option v-for="s in semesters" :key="s" :label="s" :value="s" />
        </el-select>
        <el-select v-model="searchParams.examType" placeholder="考试类型" clearable @change="fetchData">
          <el-option label="期中" value="期中" />
          <el-option label="期末" value="期末" />
          <el-option label="平时" value="平时" />
          <el-option label="补考" value="补考" />
        </el-select>
        <el-button type="primary" @click="fetchData">搜索</el-button>
        <el-button @click="resetSearch">重置</el-button>
      </div>
      
      <el-table :data="tableData" v-loading="loading" stripe>
        <el-table-column prop="student_no" label="学号" width="120" />
        <el-table-column prop="student_name" label="姓名" width="100" />
        <el-table-column prop="course_name" label="课程" min-width="150" />
        <el-table-column prop="score" label="成绩" width="80">
          <template #default="{ row }">
            <span :style="{ color: row.score < 60 ? '#f5222d' : row.score >= 90 ? '#52c41a' : '#303133', fontWeight: 600 }">
              {{ row.score }}
            </span>
          </template>
        </el-table-column>
        <el-table-column prop="grade_level" label="等级" width="80">
          <template #default="{ row }">
            <el-tag :type="getGradeLevelType(row.grade_level)" size="small">{{ row.grade_level }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="semester" label="学期" width="120" />
        <el-table-column prop="exam_type" label="考试类型" width="100" />
        <el-table-column label="操作" width="120" fixed="right">
          <template #default="{ row }">
            <el-button type="primary" link size="small" @click="handleEdit(row)">编辑</el-button>
            <el-button type="danger" link size="small" @click="handleDelete(row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>
      
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
    <el-dialog v-model="dialogVisible" :title="isEdit ? '申请成绩修改' : '录入成绩'" width="500px" destroy-on-close>
      <el-form ref="formRef" :model="form" :rules="rules" label-width="80px">
        <el-form-item label="学生" prop="studentId">
          <el-select v-model="form.studentId" placeholder="选择学生" filterable style="width: 100%" :disabled="isEdit">
            <el-option v-for="s in students" :key="s.id" :label="`${s.student_no} - ${s.name}`" :value="s.id" />
          </el-select>
        </el-form-item>
        <el-form-item label="课程" prop="courseId">
          <el-select v-model="form.courseId" placeholder="选择课程" style="width: 100%" :disabled="isEdit">
            <el-option v-for="c in courses" :key="c.id" :label="c.name" :value="c.id" />
          </el-select>
        </el-form-item>
        <el-form-item label="学期" prop="semester">
          <el-input v-model="form.semester" placeholder="如：2024-2025-1" />
        </el-form-item>
        <el-row :gutter="16">
          <el-col :span="12">
            <el-form-item label="成绩" prop="score">
              <el-input-number v-model="form.score" :min="0" :max="100" :precision="1" style="width: 100%" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="考试类型" prop="examType">
              <el-select v-model="form.examType" style="width: 100%">
                <el-option label="期中" value="期中" />
                <el-option label="期末" value="期末" />
                <el-option label="平时" value="平时" />
                <el-option label="补考" value="补考" />
              </el-select>
            </el-form-item>
          </el-col>
        </el-row>
        <el-form-item label="备注">
          <el-input v-model="form.remark" type="textarea" rows="2" />
        </el-form-item>
        <el-form-item v-if="isEdit" label="修改原因" prop="reason">
          <el-input v-model="form.reason" type="textarea" rows="3" placeholder="请输入修改原因（不少于20个汉字）" show-word-limit maxlength="500" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="saving" @click="handleSave">{{ isEdit ? '提交修改申请' : '保存' }}</el-button>
      </template>
    </el-dialog>
    
    <!-- Excel导入弹窗 -->
    <el-dialog v-model="showImportDialog" title="Excel导入成绩" width="520px" @closed="resetImportDialog">
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
              格式：学号、课程代码、成绩、学期、考试类型，
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
    <el-dialog v-model="showPasteDialog" title="粘贴导入成绩" width="650px" @closed="resetPasteDialog">
      <div v-if="!pasteResult">
        <el-form label-width="80px">
          <el-row :gutter="16">
            <el-col :span="12">
              <el-form-item label="课程" required>
                <el-select v-model="pasteForm.courseId" placeholder="选择课程" style="width: 100%">
                  <el-option v-for="c in courses" :key="c.id" :label="c.name" :value="c.id" />
                </el-select>
              </el-form-item>
            </el-col>
            <el-col :span="12">
              <el-form-item label="学期">
                <el-input v-model="pasteForm.semester" placeholder="如：2024-2025-1（可选）" />
              </el-form-item>
            </el-col>
          </el-row>
          <el-form-item label="数据内容">
            <el-input
              v-model="pasteForm.content"
              type="textarea"
              rows="10"
              placeholder="请粘贴成绩数据，每行一条，支持空格/制表符/逗号分隔：&#10;学号 成绩&#10;&#10;示例：&#10;2024001001 85&#10;2024001002 92&#10;2024001003,78"
            />
          </el-form-item>
        </el-form>
        <el-alert type="info" :closable="false" show-icon>
          <template #title>
            <span>格式说明：每行一条记录，字段依次为：学号（必填）、成绩（必填）</span>
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
        <el-button v-if="!pasteResult" type="primary" :loading="importing" :disabled="!pasteForm.courseId || !pasteForm.content.trim()" @click="handlePasteImport">
          导入
        </el-button>
        <el-button v-else type="primary" @click="resetPasteDialog">继续导入</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessageBox, ElMessage } from 'element-plus'
import { gradeApi, studentApi, courseApi, gradeChangeRequestApi } from '@/api'
import { showSuccess } from '@/utils/request'

const loading = ref(false)
const saving = ref(false)
const importing = ref(false)
const tableData = ref([])
const students = ref([])
const courses = ref([])
const semesters = ref([])
const dialogVisible = ref(false)
const showImportDialog = ref(false)
const showPasteDialog = ref(false)
const isEdit = ref(false)
const uploadFile = ref(null)
const uploadRef = ref(null)
const formRef = ref(null)
const importResult = ref(null)
const pasteResult = ref(null)

const searchParams = reactive({ courseId: null, semester: null, examType: null })
const pagination = reactive({ page: 1, pageSize: 20, total: 0 })
const form = reactive({ id: null, studentId: null, courseId: null, score: 0, semester: '', examType: '期末', remark: '', reason: '' })
const pasteForm = reactive({ courseId: null, semester: '', content: '' })

const rules = {
  studentId: [{ required: true, message: '请选择学生', trigger: 'change' }],
  courseId: [{ required: true, message: '请选择课程', trigger: 'change' }],
  semester: [{ required: true, message: '请输入学期', trigger: 'blur' }],
  score: [{ required: true, message: '请输入成绩', trigger: 'blur' }],
  examType: [{ required: true, message: '请选择考试类型', trigger: 'change' }],
  reason: [
    { required: true, message: '请输入修改原因', trigger: 'blur' },
    { min: 20, message: '修改原因不得少于20个汉字', trigger: 'blur' }
  ]
}

function getGradeLevelType(level) {
  const map = { '优秀': 'success', '良好': 'primary', '中等': 'warning', '及格': 'info', '不及格': 'danger' }
  return map[level] || 'info'
}

async function fetchData() {
  loading.value = true
  try {
    const res = await gradeApi.getList({ page: pagination.page, pageSize: pagination.pageSize, ...searchParams })
    tableData.value = res.data.items
    pagination.total = res.data.total
    students.value = res.data.students || []
    courses.value = res.data.courses || []
    semesters.value = res.data.semesters || []
  } finally {
    loading.value = false
  }
}

function resetSearch() {
  Object.assign(searchParams, { courseId: null, semester: null, examType: null })
  pagination.page = 1
  fetchData()
}

function handleAdd() {
  isEdit.value = false
  Object.assign(form, { id: null, studentId: null, courseId: null, score: 0, semester: '', examType: '期末', remark: '', reason: '' })
  dialogVisible.value = true
}

function handleEdit(row) {
  isEdit.value = true
  Object.assign(form, { id: row.id, studentId: row.student_id, courseId: row.course_id, score: row.score, semester: row.semester, examType: row.exam_type, remark: row.remark, reason: '' })
  dialogVisible.value = true
}

async function handleSave() {
  const valid = await formRef.value.validate().catch(() => false)
  if (!valid) return
  saving.value = true
  try {
    if (isEdit.value) {
      await gradeChangeRequestApi.submit({
        gradeId: form.id,
        newScore: form.score,
        reason: form.reason
      })
      showSuccess('修改申请已提交，等待审核')
    } else {
      await gradeApi.create(form)
      showSuccess('录入成功')
    }
    dialogVisible.value = false
    fetchData()
  } finally {
    saving.value = false
  }
}

async function handleDelete(row) {
  try {
    await ElMessageBox.confirm('确定要删除该成绩记录吗？', '提示', { type: 'warning' })
    await gradeApi.delete(row.id)
    showSuccess('删除成功')
    fetchData()
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
  pasteForm.courseId = null
  pasteForm.semester = ''
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
    const res = await gradeApi.import(formData)
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
  if (!pasteForm.courseId) {
    ElMessage.warning('请选择课程')
    return
  }
  if (!pasteForm.content.trim()) {
    ElMessage.warning('请输入要导入的数据')
    return
  }
  importing.value = true
  try {
    const res = await gradeApi.pasteImport(pasteForm)
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
  const url = gradeApi.export({ courseId: searchParams.courseId, semester: searchParams.semester })
  window.open(url, '_blank')
}

function downloadTemplate() {
  // 成绩导入模板URL
  const url = `/api/grades/template?token=${localStorage.getItem('token')}`
  window.open(url, '_blank')
}

onMounted(() => { fetchData() })
</script>

<style lang="scss" scoped>
.grades-page {
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
