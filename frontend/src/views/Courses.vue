<template>
  <div class="courses-page">
    <div class="page-header">
      <h1>课程管理</h1>
      <el-button type="primary" @click="handleAdd">
        <el-icon><Plus /></el-icon>添加课程
      </el-button>
    </div>
    
    <div class="card">
      <div class="search-bar">
        <el-input v-model="searchParams.keyword" placeholder="搜索课程代码/名称" clearable @keyup.enter="fetchData">
          <template #prefix><el-icon><Search /></el-icon></template>
        </el-input>
        <el-select v-model="searchParams.semester" placeholder="选择学期" clearable @change="fetchData">
          <el-option v-for="s in semesters" :key="s" :label="s" :value="s" />
        </el-select>
        <el-select v-model="searchParams.courseType" placeholder="课程类型" clearable @change="fetchData">
          <el-option label="必修" value="必修" />
          <el-option label="选修" value="选修" />
        </el-select>
        <el-button type="primary" @click="fetchData">搜索</el-button>
        <el-button @click="resetSearch">重置</el-button>
      </div>
      
      <el-table :data="tableData" v-loading="loading" stripe>
        <el-table-column prop="code" label="课程代码" width="120" />
        <el-table-column prop="name" label="课程名称" min-width="150" />
        <el-table-column prop="credits" label="学分" width="80" />
        <el-table-column prop="hours" label="学时" width="80" />
        <el-table-column prop="course_type" label="类型" width="80">
          <template #default="{ row }">
            <el-tag :type="row.course_type === '必修' ? 'danger' : 'success'" size="small">{{ row.course_type }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="semester" label="学期" width="120" />
        <el-table-column prop="teacher_name" label="授课教师" width="100" />
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
          :page-sizes="[10, 20, 50]"
          layout="total, sizes, prev, pager, next, jumper"
          @size-change="fetchData"
          @current-change="fetchData"
        />
      </div>
    </div>
    
    <el-dialog v-model="dialogVisible" :title="isEdit ? '编辑课程' : '添加课程'" width="500px" destroy-on-close>
      <el-form ref="formRef" :model="form" :rules="rules" label-width="80px">
        <el-form-item label="课程代码" prop="code">
          <el-input v-model="form.code" placeholder="如：CS101" />
        </el-form-item>
        <el-form-item label="课程名称" prop="name">
          <el-input v-model="form.name" placeholder="请输入课程名称" />
        </el-form-item>
        <el-row :gutter="16">
          <el-col :span="12">
            <el-form-item label="学分" prop="credits">
              <el-input-number v-model="form.credits" :min="0.5" :max="10" :step="0.5" style="width: 100%" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="学时">
              <el-input-number v-model="form.hours" :min="0" :max="200" style="width: 100%" />
            </el-form-item>
          </el-col>
        </el-row>
        <el-form-item label="课程类型" prop="courseType">
          <el-radio-group v-model="form.courseType">
            <el-radio value="必修">必修</el-radio>
            <el-radio value="选修">选修</el-radio>
          </el-radio-group>
        </el-form-item>
        <el-form-item label="学期">
          <el-input v-model="form.semester" placeholder="如：2024-2025-1" />
        </el-form-item>
        <el-form-item label="授课教师">
          <el-select v-model="form.teacherId" placeholder="选择教师" clearable style="width: 100%">
            <el-option v-for="t in teachers" :key="t.id" :label="t.real_name" :value="t.id" />
          </el-select>
        </el-form-item>
        <el-form-item label="描述">
          <el-input v-model="form.description" type="textarea" rows="2" placeholder="课程描述" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="saving" @click="handleSave">保存</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessageBox } from 'element-plus'
import { courseApi } from '@/api'
import { showSuccess } from '@/utils/request'

const loading = ref(false)
const saving = ref(false)
const tableData = ref([])
const semesters = ref([])
const teachers = ref([])
const dialogVisible = ref(false)
const isEdit = ref(false)
const formRef = ref(null)

const searchParams = reactive({ keyword: '', semester: null, courseType: null })
const pagination = reactive({ page: 1, pageSize: 20, total: 0 })
const form = reactive({ id: null, code: '', name: '', credits: 2, hours: 32, courseType: '必修', semester: '', teacherId: null, description: '' })

const rules = {
  code: [{ required: true, message: '请输入课程代码', trigger: 'blur' }],
  name: [{ required: true, message: '请输入课程名称', trigger: 'blur' }],
  credits: [{ required: true, message: '请输入学分', trigger: 'blur' }],
  courseType: [{ required: true, message: '请选择课程类型', trigger: 'change' }]
}

async function fetchData() {
  loading.value = true
  try {
    const res = await courseApi.getList({ page: pagination.page, pageSize: pagination.pageSize, ...searchParams })
    tableData.value = res.data.items
    pagination.total = res.data.total
    semesters.value = res.data.semesters || []
    teachers.value = res.data.teachers || []
  } finally {
    loading.value = false
  }
}

function resetSearch() {
  Object.assign(searchParams, { keyword: '', semester: null, courseType: null })
  pagination.page = 1
  fetchData()
}

function handleAdd() {
  isEdit.value = false
  Object.assign(form, { id: null, code: '', name: '', credits: 2, hours: 32, courseType: '必修', semester: '', teacherId: null, description: '' })
  dialogVisible.value = true
}

function handleEdit(row) {
  isEdit.value = true
  Object.assign(form, { id: row.id, code: row.code, name: row.name, credits: row.credits, hours: row.hours, courseType: row.course_type, semester: row.semester, teacherId: row.teacher_id, description: row.description })
  dialogVisible.value = true
}

async function handleSave() {
  const valid = await formRef.value.validate().catch(() => false)
  if (!valid) return
  saving.value = true
  try {
    if (isEdit.value) {
      await courseApi.update(form.id, form)
      showSuccess('更新成功')
    } else {
      await courseApi.create(form)
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
    await ElMessageBox.confirm(`确定要删除课程"${row.name}"吗？`, '提示', { type: 'warning' })
    await courseApi.delete(row.id)
    showSuccess('删除成功')
    fetchData()
  } catch (e) {}
}

onMounted(() => { fetchData() })
</script>
