<template>
  <div class="login-page">
    <div class="login-bg">
      <div class="shape shape-1"></div>
      <div class="shape shape-2"></div>
      <div class="shape shape-3"></div>
    </div>
    
    <div class="login-container">
      <div class="login-card">
        <div class="login-header">
          <img src="/favicon.svg" alt="Logo" class="logo" />
          <h1>学生成绩管理系统</h1>
          <p>专业的学生成绩管理与统计分析平台</p>
        </div>
        
        <el-form
          ref="formRef"
          :model="form"
          :rules="rules"
          class="login-form"
          @keyup.enter="handleSubmit"
        >
          <el-form-item prop="username">
            <el-input
              v-model="form.username"
              placeholder="请输入用户名"
              size="large"
              :prefix-icon="User"
            />
          </el-form-item>
          
          <el-form-item prop="password">
            <el-input
              v-model="form.password"
              type="password"
              placeholder="请输入密码"
              size="large"
              :prefix-icon="Lock"
              show-password
            />
          </el-form-item>
          
          <el-form-item>
            <el-button
              type="primary"
              size="large"
              class="login-btn"
              :loading="loading"
              @click="handleSubmit"
            >
              登 录
            </el-button>
          </el-form-item>
        </el-form>
        
        <div class="login-footer">
          还没有账号？
          <router-link to="/register">立即注册</router-link>
        </div>
      </div>
      
      <div class="demo-account">
        <el-divider>测试账号</el-divider>
        <div class="accounts">
          <div class="account" @click="fillDemo('admin', 'admin123')">
            <el-tag type="danger">管理员</el-tag>
            <span>admin / admin123</span>
          </div>
          <div class="account" @click="fillDemo('teacher', 'teacher123')">
            <el-tag type="primary">教师</el-tag>
            <span>teacher / teacher123</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useUserStore } from '@/stores/user'
import { showSuccess } from '@/utils/request'
import { User, Lock } from '@element-plus/icons-vue'

const router = useRouter()
const route = useRoute()
const userStore = useUserStore()

const formRef = ref(null)
const loading = ref(false)

const form = reactive({
  username: '',
  password: ''
})

const rules = {
  username: [
    { required: true, message: '请输入用户名', trigger: 'blur' }
  ],
  password: [
    { required: true, message: '请输入密码', trigger: 'blur' }
  ]
}

function fillDemo(username, password) {
  form.username = username
  form.password = password
}

async function handleSubmit() {
  const valid = await formRef.value.validate().catch(() => false)
  if (!valid) return
  
  loading.value = true
  try {
    await userStore.login(form)
    showSuccess('登录成功')
    const redirect = route.query.redirect || '/'
    router.push(redirect)
  } catch (error) {
    // 错误已在拦截器中处理
  } finally {
    loading.value = false
  }
}
</script>

<style lang="scss" scoped>
.login-page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #f5f7fa 0%, #e4e7ed 100%);
  position: relative;
  overflow: hidden;
}

.login-bg {
  position: absolute;
  inset: 0;
  overflow: hidden;
  
  .shape {
    position: absolute;
    border-radius: 50%;
    opacity: 0.3;
    
    &.shape-1 {
      width: 400px;
      height: 400px;
      background: linear-gradient(135deg, #667eea, #764ba2);
      top: -100px;
      left: -100px;
      animation: float 8s ease-in-out infinite;
    }
    
    &.shape-2 {
      width: 300px;
      height: 300px;
      background: linear-gradient(135deg, #764ba2, #667eea);
      bottom: -50px;
      right: -50px;
      animation: float 6s ease-in-out infinite reverse;
    }
    
    &.shape-3 {
      width: 200px;
      height: 200px;
      background: linear-gradient(135deg, #667eea, #5a6fd6);
      top: 50%;
      right: 20%;
      animation: float 7s ease-in-out infinite;
    }
  }
}

@keyframes float {
  0%, 100% { transform: translateY(0) rotate(0deg); }
  50% { transform: translateY(-20px) rotate(5deg); }
}

.login-container {
  position: relative;
  z-index: 1;
}

.login-card {
  width: 420px;
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(20px);
  border-radius: 20px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
  padding: 40px;
  
  .login-header {
    text-align: center;
    margin-bottom: 32px;
    
    .logo {
      width: 64px;
      height: 64px;
      margin-bottom: 16px;
    }
    
    h1 {
      font-size: 24px;
      font-weight: 600;
      color: #303133;
      margin-bottom: 8px;
    }
    
    p {
      font-size: 14px;
      color: #909399;
    }
  }
  
  .login-form {
    :deep(.el-input__wrapper) {
      border-radius: 10px;
      box-shadow: 0 0 0 1px #e4e7ed inset;
      padding: 4px 15px;
      
      &:hover {
        box-shadow: 0 0 0 1px #c0c4cc inset;
      }
      
      &.is-focus {
        box-shadow: 0 0 0 1px #667eea inset;
      }
    }
    
    .el-form-item {
      margin-bottom: 20px;
    }
  }
  
  .login-btn {
    width: 100%;
    height: 48px;
    font-size: 16px;
    border-radius: 10px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    border: none;
    
    &:hover {
      opacity: 0.9;
    }
  }
  
  .login-footer {
    text-align: center;
    color: #909399;
    font-size: 14px;
    
    a {
      color: #667eea;
      font-weight: 500;
      
      &:hover {
        text-decoration: underline;
      }
    }
  }
}

.demo-account {
  margin-top: 24px;
  padding: 20px;
  background: rgba(255, 255, 255, 0.8);
  border-radius: 12px;
  
  :deep(.el-divider__text) {
    background: transparent;
    color: #909399;
    font-size: 12px;
  }
  
  .accounts {
    display: flex;
    flex-direction: column;
    gap: 8px;
  }
  
  .account {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 8px 12px;
    background: #f5f7fa;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s;
    
    &:hover {
      background: #e6e8eb;
    }
    
    span {
      font-size: 13px;
      color: #606266;
      font-family: monospace;
    }
  }
}

@media (max-width: 480px) {
  .login-card {
    width: 90vw;
    padding: 30px 20px;
  }
}
</style>
