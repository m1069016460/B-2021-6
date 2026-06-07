<template>
  <div class="register-page">
    <div class="register-bg">
      <div class="shape shape-1"></div>
      <div class="shape shape-2"></div>
    </div>
    
    <div class="register-container">
      <div class="register-card">
        <div class="register-header">
          <img src="/favicon.svg" alt="Logo" class="logo" />
          <h1>注册账号</h1>
          <p>创建您的账号以使用成绩管理系统</p>
        </div>
        
        <el-form
          ref="formRef"
          :model="form"
          :rules="rules"
          class="register-form"
          label-position="top"
        >
          <el-form-item label="用户名" prop="username">
            <el-input
              v-model="form.username"
              placeholder="请输入用户名（3-20个字符）"
              :prefix-icon="User"
            />
          </el-form-item>
          
          <el-form-item label="密码" prop="password">
            <el-input
              v-model="form.password"
              type="password"
              placeholder="请输入密码（至少6个字符）"
              :prefix-icon="Lock"
              show-password
            />
          </el-form-item>
          
          <el-form-item label="确认密码" prop="confirmPassword">
            <el-input
              v-model="form.confirmPassword"
              type="password"
              placeholder="请再次输入密码"
              :prefix-icon="Lock"
              show-password
            />
          </el-form-item>
          
          <el-form-item label="真实姓名" prop="realName">
            <el-input
              v-model="form.realName"
              placeholder="请输入真实姓名"
              :prefix-icon="UserFilled"
            />
          </el-form-item>
          
          <el-row :gutter="16">
            <el-col :span="12">
              <el-form-item label="邮箱" prop="email">
                <el-input
                  v-model="form.email"
                  placeholder="选填"
                  :prefix-icon="Message"
                />
              </el-form-item>
            </el-col>
            <el-col :span="12">
              <el-form-item label="手机号" prop="phone">
                <el-input
                  v-model="form.phone"
                  placeholder="选填"
                  :prefix-icon="Phone"
                />
              </el-form-item>
            </el-col>
          </el-row>
          
          <el-form-item>
            <el-button
              type="primary"
              size="large"
              class="register-btn"
              :loading="loading"
              @click="handleSubmit"
            >
              注 册
            </el-button>
          </el-form-item>
        </el-form>
        
        <div class="register-footer">
          已有账号？
          <router-link to="/login">立即登录</router-link>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useUserStore } from '@/stores/user'
import { showSuccess } from '@/utils/request'
import { User, Lock, UserFilled, Message, Phone } from '@element-plus/icons-vue'

const router = useRouter()
const userStore = useUserStore()

const formRef = ref(null)
const loading = ref(false)

const form = reactive({
  username: '',
  password: '',
  confirmPassword: '',
  realName: '',
  email: '',
  phone: ''
})

// 验证规则
const validatePass = (rule, value, callback) => {
  if (value !== form.password) {
    callback(new Error('两次输入的密码不一致'))
  } else {
    callback()
  }
}

const validateEmail = (rule, value, callback) => {
  if (value && !/^[\w-]+(\.[\w-]+)*@[\w-]+(\.[\w-]+)+$/.test(value)) {
    callback(new Error('邮箱格式不正确'))
  } else {
    callback()
  }
}

const validatePhone = (rule, value, callback) => {
  if (value && !/^1[3-9]\d{9}$/.test(value)) {
    callback(new Error('手机号格式不正确'))
  } else {
    callback()
  }
}

const rules = {
  username: [
    { required: true, message: '请输入用户名', trigger: 'blur' },
    { min: 3, max: 20, message: '用户名长度为3-20个字符', trigger: 'blur' }
  ],
  password: [
    { required: true, message: '请输入密码', trigger: 'blur' },
    { min: 6, message: '密码至少6个字符', trigger: 'blur' }
  ],
  confirmPassword: [
    { required: true, message: '请确认密码', trigger: 'blur' },
    { validator: validatePass, trigger: 'blur' }
  ],
  realName: [
    { required: true, message: '请输入真实姓名', trigger: 'blur' }
  ],
  email: [
    { validator: validateEmail, trigger: 'blur' }
  ],
  phone: [
    { validator: validatePhone, trigger: 'blur' }
  ]
}

async function handleSubmit() {
  const valid = await formRef.value.validate().catch(() => false)
  if (!valid) return
  
  loading.value = true
  try {
    await userStore.register({
      username: form.username,
      password: form.password,
      realName: form.realName,
      email: form.email || null,
      phone: form.phone || null
    })
    showSuccess('注册成功，请登录')
    router.push('/login')
  } catch (error) {
    // 错误已在拦截器中处理
  } finally {
    loading.value = false
  }
}
</script>

<style lang="scss" scoped>
.register-page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #f5f7fa 0%, #e4e7ed 100%);
  position: relative;
  overflow: hidden;
  padding: 40px 20px;
}

.register-bg {
  position: absolute;
  inset: 0;
  overflow: hidden;
  
  .shape {
    position: absolute;
    border-radius: 50%;
    opacity: 0.3;
    
    &.shape-1 {
      width: 500px;
      height: 500px;
      background: linear-gradient(135deg, #667eea, #764ba2);
      top: -150px;
      right: -150px;
      animation: float 8s ease-in-out infinite;
    }
    
    &.shape-2 {
      width: 400px;
      height: 400px;
      background: linear-gradient(135deg, #764ba2, #667eea);
      bottom: -100px;
      left: -100px;
      animation: float 6s ease-in-out infinite reverse;
    }
  }
}

@keyframes float {
  0%, 100% { transform: translateY(0) rotate(0deg); }
  50% { transform: translateY(-20px) rotate(5deg); }
}

.register-container {
  position: relative;
  z-index: 1;
}

.register-card {
  width: 480px;
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(20px);
  border-radius: 20px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
  padding: 40px;
  
  .register-header {
    text-align: center;
    margin-bottom: 24px;
    
    .logo {
      width: 56px;
      height: 56px;
      margin-bottom: 12px;
    }
    
    h1 {
      font-size: 22px;
      font-weight: 600;
      color: #303133;
      margin-bottom: 8px;
    }
    
    p {
      font-size: 14px;
      color: #909399;
    }
  }
  
  .register-form {
    :deep(.el-form-item__label) {
      font-weight: 500;
      color: #606266;
    }
    
    :deep(.el-input__wrapper) {
      border-radius: 8px;
      box-shadow: 0 0 0 1px #e4e7ed inset;
      
      &:hover {
        box-shadow: 0 0 0 1px #c0c4cc inset;
      }
      
      &.is-focus {
        box-shadow: 0 0 0 1px #667eea inset;
      }
    }
    
    .el-form-item {
      margin-bottom: 18px;
    }
  }
  
  .register-btn {
    width: 100%;
    height: 46px;
    font-size: 16px;
    border-radius: 10px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    border: none;
    margin-top: 8px;
    
    &:hover {
      opacity: 0.9;
    }
  }
  
  .register-footer {
    text-align: center;
    color: #909399;
    font-size: 14px;
    margin-top: 16px;
    
    a {
      color: #667eea;
      font-weight: 500;
      
      &:hover {
        text-decoration: underline;
      }
    }
  }
}

@media (max-width: 520px) {
  .register-card {
    width: 100%;
    padding: 30px 20px;
  }
}
</style>
