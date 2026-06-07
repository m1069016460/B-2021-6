<template>
  <div class="users-page">
    <div class="page-header">
      <h1>用户管理</h1>
      <el-button type="primary" @click="handleAdd">
        <el-icon><Plus /></el-icon>添加用户
      </el-button>
    </div>
    
    <div class="card">
      <div class="search-bar">
        <el-input v-model="searchParams.keyword" placeholder="搜索用户名/姓名" clearable @keyup.enter="fetchData">
          <template #prefix><el-icon><Search /></el-icon></template>
        </el-input>
        <el-select v-model="searchParams.role" placeholder="选择角色" clearable @change="fetchData">
          <el-option label="管理员" value="admin" />
          <el-option label="教师" value="teacher" />
        </el-select>
        <el-button type="primary" @click="fetchData">搜索</el-button>
        <el-button @click="resetSearch">重置</el-button>
      </div>
      
      <el-table :data="tableData" v-loading="loading" stripe>
        <el-table-column prop="username" label="用户名" width="120" />
        <el-table-column prop="real_name" label="真实姓名" width="100" />
        <el-table-column prop="role" label="角色" width="100">
          <template #default="{ row }">
            <el-tag :type="row.role === 'admin' ? 'danger' : 'primary'" size="small">
              {{ row.role === 'admin' ? '管理员' : '教师' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="email" label="邮箱" min-width="180" />
        <el-table-column prop="phone" label="手机号" width="130" />
        <el-table-column prop="status" label="状态" width="80">
          <template #default="{ row }">
            <el-tag :type="row.status === 1 ? 'success' : 'info'" size="small">
              {{ row.status === 1 ? '正常' : '禁用' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="created_at" label="创建时间" width="170" />
        <el-table-column label="操作" width="140" fixed="right">
          <template #default="{ row }">
            <el-button type="primary" link size="small" @click="handleEdit(row)" :disabled="row.id === currentUserId">编辑</el-button>
            <el-button type="danger" link size="small" @click="handleDelete(row)" :disabled="row.id === currentUserId">删除</el-button>
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
    
    <el-dialog v-model="dialogVisible" :title="isEdit ? '编辑用户' : '添加用户'" width="500px" destroy-on-close>
      <el-form ref="formRef" :model="form" :rules="rules" label-width="80px">
        <el-form-item label="用户名" prop="username">
          <el-input v-model="form.username" placeholder="请输入用户名" :disabled="isEdit" />
        </el-form-item>
        <el-form-item label="密码" :prop="isEdit ? '' : 'password'">
          <el-input v-model="form.password" type="password" :placeholder="isEdit ? '不修改请留空' : '请输入密码'" show-password />
        </el-form-item>
        <el-form-item label="真实姓名" prop="realName">
          <el-input v-model="form.realName" placeholder="请输入真实姓名" />
        </el-form-item>
        <el-row :gutter="16">
          <el-col :span="12">
            <el-form-item label="邮箱" prop="email">
              <el-input v-model="form.email" placeholder="请输入邮箱" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="手机号" prop="phone">
              <el-input v-model="form.phone" placeholder="请输入手机号" />
            </el-form-item>
          </el-col>
        </el-row>
        <el-form-item label="角色" prop="role">
          <el-radio-group v-model="form.role" :disabled="isEdit && editingUserId === currentUserId">
            <el-radio value="admin">管理员</el-radio>
            <el-radio value="teacher">教师</el-radio>
          </el-radio-group>
        </el-form-item>
        <el-form-item label="状态" prop="status">
          <el-radio-group v-model="form.status" :disabled="isEdit && editingUserId === currentUserId">
            <el-radio :value="1">正常</el-radio>
            <el-radio :value="0">禁用</el-radio>
          </el-radio-group>
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
import { ref, reactive, computed, onMounted } from 'vue'
import { ElMessageBox } from 'element-plus'
import { userApi } from '@/api'
import { useUserStore } from '@/stores/user'
import { showSuccess } from '@/utils/request'

const userStore = useUserStore()
const currentUserId = computed(() => userStore.user?.id)

const loading = ref(false)
const saving = ref(false)
const tableData = ref([])
const dialogVisible = ref(false)
const isEdit = ref(false)
const editingUserId = ref(null)
const formRef = ref(null)

const searchParams = reactive({ keyword: '', role: null })
const pagination = reactive({ page: 1, pageSize: 20, total: 0 })
const form = reactive({ id: null, username: '', password: '', realName: '', email: '', phone: '', role: 'teacher', status: 1 })

const validatePhone = (rule, value, callback) => {
  if (value && !/^1[3-9]\d{9}$/.test(value)) callback(new Error('手机号格式不正确'))
  else callback()
}

const validateEmail = (rule, value, callback) => {
  if (value && !/^[\w-]+(\.[\w-]+)*@[\w-]+(\.[\w-]+)+$/.test(value)) callback(new Error('邮箱格式不正确'))
  else callback()
}

const rules = {
  username: [{ required: true, message: '请输入用户名', trigger: 'blur' }, { min: 3, max: 20, message: '用户名长度为3-20个字符', trigger: 'blur' }],
  password: [{ required: true, message: '请输入密码', trigger: 'blur' }, { min: 6, message: '密码至少6个字符', trigger: 'blur' }],
  realName: [{ required: true, message: '请输入真实姓名', trigger: 'blur' }],
  role: [{ required: true, message: '请选择角色', trigger: 'change' }],
  phone: [{ validator: validatePhone, trigger: 'blur' }],
  email: [{ validator: validateEmail, trigger: 'blur' }]
}

async function fetchData() {
  loading.value = true
  try {
    const res = await userApi.getList({ page: pagination.page, pageSize: pagination.pageSize, ...searchParams })
    tableData.value = res.data.items
    pagination.total = res.data.total
  } finally {
    loading.value = false
  }
}

function resetSearch() {
  Object.assign(searchParams, { keyword: '', role: null })
  pagination.page = 1
  fetchData()
}

function handleAdd() {
  isEdit.value = false
  editingUserId.value = null
  Object.assign(form, { id: null, username: '', password: '', realName: '', email: '', phone: '', role: 'teacher', status: 1 })
  dialogVisible.value = true
}

function handleEdit(row) {
  isEdit.value = true
  editingUserId.value = row.id
  Object.assign(form, { id: row.id, username: row.username, password: '', realName: row.real_name, email: row.email, phone: row.phone, role: row.role, status: row.status })
  dialogVisible.value = true
}

async function handleSave() {
  const valid = await formRef.value.validate().catch(() => false)
  if (!valid) return
  saving.value = true
  try {
    if (isEdit.value) {
      await userApi.update(form.id, form)
      showSuccess('更新成功')
    } else {
      await userApi.create(form)
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
    await ElMessageBox.confirm(`确定要删除用户"${row.username}"吗？`, '提示', { type: 'warning' })
    await userApi.delete(row.id)
    showSuccess('删除成功')
    fetchData()
  } catch (e) {}
}

onMounted(() => { fetchData() })
</script>
