<template>
  <div class="profile-page">
    <div class="page-header">
      <h1>个人中心</h1>
    </div>
    
    <el-row :gutter="20">
      <el-col :xs="24" :lg="8">
        <div class="card profile-card">
          <div class="avatar">
            {{ user?.realName?.charAt(0) || user?.username?.charAt(0) || 'U' }}
          </div>
          <h2>{{ user?.realName || user?.username }}</h2>
          <el-tag :type="user?.role === 'admin' ? 'danger' : 'primary'" size="large">
            {{ user?.role === 'admin' ? '管理员' : '教师' }}
          </el-tag>
          <div class="info-list">
            <div class="info-item">
              <el-icon><User /></el-icon>
              <span>{{ user?.username }}</span>
            </div>
            <div class="info-item" v-if="user?.email">
              <el-icon><Message /></el-icon>
              <span>{{ user?.email }}</span>
            </div>
            <div class="info-item" v-if="user?.phone">
              <el-icon><Phone /></el-icon>
              <span>{{ user?.phone }}</span>
            </div>
          </div>
        </div>
      </el-col>
      
      <el-col :xs="24" :lg="16">
        <el-tabs v-model="activeTab" class="profile-tabs">
          <el-tab-pane label="基本资料" name="info">
            <div class="card">
              <el-form ref="infoFormRef" :model="infoForm" :rules="infoRules" label-width="100px" class="form-container">
                <el-form-item label="用户名">
                  <el-input :value="user?.username" disabled />
                </el-form-item>
                <el-form-item label="真实姓名" prop="realName">
                  <el-input v-model="infoForm.realName" placeholder="请输入真实姓名" />
                </el-form-item>
                <el-form-item label="邮箱" prop="email">
                  <el-input v-model="infoForm.email" placeholder="请输入邮箱" />
                </el-form-item>
                <el-form-item label="手机号" prop="phone">
                  <el-input v-model="infoForm.phone" placeholder="请输入手机号" />
                </el-form-item>
                <el-form-item>
                  <el-button type="primary" :loading="savingInfo" @click="saveInfo">保存修改</el-button>
                </el-form-item>
              </el-form>
            </div>
          </el-tab-pane>
          
          <el-tab-pane label="修改密码" name="password">
            <div class="card">
              <el-form ref="pwdFormRef" :model="pwdForm" :rules="pwdRules" label-width="100px" class="form-container">
                <el-form-item label="当前密码" prop="oldPassword">
                  <el-input v-model="pwdForm.oldPassword" type="password" placeholder="请输入当前密码" show-password />
                </el-form-item>
                <el-form-item label="新密码" prop="newPassword">
                  <el-input v-model="pwdForm.newPassword" type="password" placeholder="请输入新密码" show-password />
                </el-form-item>
                <el-form-item label="确认密码" prop="confirmPassword">
                  <el-input v-model="pwdForm.confirmPassword" type="password" placeholder="请再次输入新密码" show-password />
                </el-form-item>
                <el-form-item>
                  <el-button type="primary" :loading="savingPwd" @click="savePassword">修改密码</el-button>
                </el-form-item>
              </el-form>
            </div>
          </el-tab-pane>
        </el-tabs>
      </el-col>
    </el-row>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useUserStore } from '@/stores/user'
import { showSuccess } from '@/utils/request'

const userStore = useUserStore()
const user = computed(() => userStore.user)

const activeTab = ref('info')
const savingInfo = ref(false)
const savingPwd = ref(false)
const infoFormRef = ref(null)
const pwdFormRef = ref(null)

const infoForm = reactive({ realName: '', email: '', phone: '' })
const pwdForm = reactive({ oldPassword: '', newPassword: '', confirmPassword: '' })

const validatePhone = (rule, value, callback) => {
  if (value && !/^1[3-9]\d{9}$/.test(value)) callback(new Error('手机号格式不正确'))
  else callback()
}

const validateEmail = (rule, value, callback) => {
  if (value && !/^[\w-]+(\.[\w-]+)*@[\w-]+(\.[\w-]+)+$/.test(value)) callback(new Error('邮箱格式不正确'))
  else callback()
}

const validateConfirmPwd = (rule, value, callback) => {
  if (value !== pwdForm.newPassword) callback(new Error('两次输入的密码不一致'))
  else callback()
}

const infoRules = {
  realName: [{ required: true, message: '请输入真实姓名', trigger: 'blur' }],
  email: [{ validator: validateEmail, trigger: 'blur' }],
  phone: [{ validator: validatePhone, trigger: 'blur' }]
}

const pwdRules = {
  oldPassword: [{ required: true, message: '请输入当前密码', trigger: 'blur' }],
  newPassword: [{ required: true, message: '请输入新密码', trigger: 'blur' }, { min: 6, message: '密码至少6个字符', trigger: 'blur' }],
  confirmPassword: [{ required: true, message: '请确认新密码', trigger: 'blur' }, { validator: validateConfirmPwd, trigger: 'blur' }]
}

function initForm() {
  if (user.value) {
    infoForm.realName = user.value.realName || ''
    infoForm.email = user.value.email || ''
    infoForm.phone = user.value.phone || ''
  }
}

async function saveInfo() {
  const valid = await infoFormRef.value.validate().catch(() => false)
  if (!valid) return
  savingInfo.value = true
  try {
    await userStore.updateProfile(infoForm)
    showSuccess('修改成功')
  } finally {
    savingInfo.value = false
  }
}

async function savePassword() {
  const valid = await pwdFormRef.value.validate().catch(() => false)
  if (!valid) return
  savingPwd.value = true
  try {
    await userStore.changePassword(pwdForm)
    showSuccess('密码修改成功')
    pwdForm.oldPassword = ''
    pwdForm.newPassword = ''
    pwdForm.confirmPassword = ''
  } finally {
    savingPwd.value = false
  }
}

onMounted(() => { initForm() })
</script>

<style lang="scss" scoped>
.profile-page {
  .profile-card {
    text-align: center;
    padding: 30px;
    
    .avatar {
      width: 100px;
      height: 100px;
      background: linear-gradient(135deg, #667eea, #764ba2);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 40px;
      font-weight: 600;
      color: #fff;
      margin: 0 auto 16px;
    }
    
    h2 {
      font-size: 20px;
      margin-bottom: 12px;
    }
    
    .el-tag {
      margin-bottom: 20px;
    }
    
    .info-list {
      text-align: left;
      margin-top: 20px;
      padding-top: 20px;
      border-top: 1px solid #f0f0f0;
      
      .info-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 0;
        color: #606266;
        
        .el-icon {
          color: #667eea;
        }
      }
    }
  }
  
  .profile-tabs {
    :deep(.el-tabs__header) {
      margin-bottom: 0;
    }
    
    :deep(.el-tabs__content) {
      padding: 0;
    }
    
    .card {
      border-radius: 0 0 8px 8px;
    }
  }
  
  .form-container {
    max-width: 400px;
    padding: 20px 0;
  }
}
</style>
