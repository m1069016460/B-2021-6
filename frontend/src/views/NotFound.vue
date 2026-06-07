<template>
  <div class="not-found">
    <div class="content">
      <h1>404</h1>
      <p>抱歉，您访问的页面不存在</p>
      <el-button type="primary" size="large" @click="goHome">
        <el-icon><HomeFilled /></el-icon>返回首页
      </el-button>
    </div>
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router'

const router = useRouter()

function goHome() {
  router.push('/')
}
</script>

<style lang="scss" scoped>
.not-found {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #f5f7fa 0%, #e4e7ed 100%);
  
  .content {
    text-align: center;
    
    h1 {
      font-size: 120px;
      font-weight: 700;
      background: linear-gradient(135deg, #667eea, #764ba2);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      margin-bottom: 16px;
    }
    
    p {
      font-size: 18px;
      color: #909399;
      margin-bottom: 32px;
    }
    
    .el-button {
      background: linear-gradient(135deg, #667eea, #764ba2);
      border: none;
      padding: 12px 32px;
      
      &:hover {
        opacity: 0.9;
      }
    }
  }
}
</style>
