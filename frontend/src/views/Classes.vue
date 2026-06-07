<template>
  <div class="classes-page">
    <div class="page-header">
      <h1>班级管理</h1>
      <el-button type="primary" @click="handleAdd">
        <el-icon><Plus /></el-icon>添加班级
      </el-button>
    </div>
    
    <div class="card">
      <div class="search-bar">
        <el-input v-model="searchParams.keyword" placeholder="搜索班级名称" clearable @keyup.enter="fetchData">
          <template #prefix><el-icon><Search /></el-icon></template>
        </el-input>
        <el-select v-model="searchParams.grade" placeholder="选择年级" clearable @change="fetchData">
          <el-option v-for="g in grades" :key="g" :label="g" :value="g" />
        </el-select>
        <el-button type="primary" @click="fetchData">搜索</el-button>
        <el-button @click="resetSearch">重置</el-button>
      </div>
      
      <el-table :data="tableData" v-loading="loading" stripe>
        <el-table-column prop="name" label="班级名称" min-width="150" />
        <el-table-column prop="grade" label="年级" width="120" />
        <el-table-column prop="major" label="专业" min-width="150" />
        <el-table-column prop="teacher_name" label="班主任" width="100" />
        <el-table-column prop="student_count" label="学生人数" width="100">
          <template #default="{ row }">
            <el-tag type="primary" size="small">{{ row.student_count || 0 }}人</el-tag>
          </template>
        </el-table-column>
        <el-table-column label="操作" width="180" fixed="right">
          <template #default="{ row }">
            <el-button type="primary" link size="small" @click="viewStudents(row)">学生</el-button>
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
    
    <el-dialog v-model="dialogVisible" :title="isEdit ? '编辑班级' : '添加班级'" width="500px" destroy-on-close>
      <el-form ref="formRef" :model="form" :rules="rules" label-width="80px">
        <el-form-item label="班级名称" prop="name">
          <el-input v-model="form.name" placeholder="请输入班级名称" />
        </el-form-item>
        <el-form-item label="年级" prop="grade">
          <el-input v-model="form.grade" placeholder="如：2024级" />
        </el-form-item>
        <el-form-item label="专业">
          <el-input v-model="form.major" placeholder="请输入专业" />
        </el-form-item>
        <el-form-item label="班主任">
          <el-select v-model="form.teacherId" placeholder="选择班主任" clearable style="width: 100%">
            <el-option v-for="t in teachers" :key="t.id" :label="t.real_name" :value="t.id" />
          </el-select>
        </el-form-item>
        <el-form-item label="描述">
          <el-input v-model="form.description" type="textarea" rows="2" placeholder="班级描述" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="saving" @click="handleSave">保存</el-button>
      </template>
    </el-dialog>
    
    <el-dialog v-model="showStudentsDialog" :title="`${currentClass?.name} - 学生列表`" width="800px">
      <el-table :data="classStudents" stripe max-height="400">
        <el-table-column prop="student_no" label="学号" width="120" />
        <el-table-column prop="name" label="姓名" width="100" />
        <el-table-column prop="gender" label="性别" width="70" />
        <el-table-column prop="phone" label="电话" />
        <el-table-column prop="email" label="邮箱" />
      </el-table>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessageBox } from 'element-plus'
import { classApi } from '@/api'
import { showSuccess } from '@/utils/request'

const loading = ref(false)
const saving = ref(false)
const tableData = ref([])
const grades = ref([])
const teachers = ref([])
const dialogVisible = ref(false)
const showStudentsDialog = ref(false)
const isEdit = ref(false)
const currentClass = ref(null)
const classStudents = ref([])
const formRef = ref(null)

const searchParams = reactive({ keyword: '', grade: null })
const pagination = reactive({ page: 1, pageSize: 20, total: 0 })
const form = reactive({ id: null, name: '', grade: '', major: '', teacherId: null, description: '' })

const rules = {
  name: [{ required: true, message: '请输入班级名称', trigger: 'blur' }],
  grade: [{ required: true, message: '请输入年级', trigger: 'blur' }]
}

async function fetchData() {
  loading.value = true
  try {
    const res = await classApi.getList({ page: pagination.page, pageSize: pagination.pageSize, ...searchParams })
    tableData.value = res.data.items
    pagination.total = res.data.total
  } finally {
    loading.value = false
  }
}

async function fetchOptions() {
  try {
    const res = await classApi.getAll()
    grades.value = res.data.grades || []
    teachers.value = res.data.teachers || []
  } catch (e) {}
}

function resetSearch() {
  Object.assign(searchParams, { keyword: '', grade: null })
  pagination.page = 1
  fetchData()
}

function handleAdd() {
  isEdit.value = false
  Object.assign(form, { id: null, name: '', grade: '', major: '', teacherId: null, description: '' })
  dialogVisible.value = true
}

function handleEdit(row) {
  isEdit.value = true
  Object.assign(form, { id: row.id, name: row.name, grade: row.grade, major: row.major, teacherId: row.teacher_id, description: row.description })
  dialogVisible.value = true
}

async function handleSave() {
  const valid = await formRef.value.validate().catch(() => false)
  if (!valid) return
  saving.value = true
  try {
    if (isEdit.value) {
      await classApi.update(form.id, form)
      showSuccess('更新成功')
    } else {
      await classApi.create(form)
      showSuccess('添加成功')
    }
    dialogVisible.value = false
    fetchData()
    fetchOptions()
  } finally {
    saving.value = false
  }
}

async function handleDelete(row) {
  try {
    await ElMessageBox.confirm(`确定要删除班级"${row.name}"吗？`, '提示', { type: 'warning' })
    await classApi.delete(row.id)
    showSuccess('删除成功')
    fetchData()
    fetchOptions()
  } catch (e) {}
}

async function viewStudents(row) {
  currentClass.value = row
  try {
    const res = await classApi.getStudents(row.id)
    classStudents.value = res.data.students || []
    showStudentsDialog.value = true
  } catch (e) {}
}

onMounted(() => { fetchData(); fetchOptions() })
</script>
