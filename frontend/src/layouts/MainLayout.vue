<template>
  <div class="main-layout">
    <!-- 侧边栏 -->
    <aside class="sidebar" :class="{ collapsed: isCollapsed }">
      <div class="logo">
        <img src="/favicon.svg" alt="Logo" />
        <span v-show="!isCollapsed">成绩管理系统</span>
      </div>
      
      <el-menu
        :default-active="currentRoute"
        :collapse="isCollapsed"
        router
        background-color="transparent"
        text-color="#ffffff"
        active-text-color="#ffffff"
      >
        <el-menu-item index="/">
          <el-icon><DataAnalysis /></el-icon>
          <template #title>仪表盘</template>
        </el-menu-item>
        
        <el-menu-item index="/students">
          <el-icon><User /></el-icon>
          <template #title>学生管理</template>
        </el-menu-item>
        
        <el-menu-item index="/classes">
          <el-icon><School /></el-icon>
          <template #title>班级管理</template>
        </el-menu-item>
        
        <el-menu-item index="/courses">
          <el-icon><Reading /></el-icon>
          <template #title>课程管理</template>
        </el-menu-item>
        
        <el-menu-item index="/grades">
          <el-icon><Notebook /></el-icon>
          <template #title>成绩管理</template>
        </el-menu-item>
        
        <el-menu-item index="/grade-change-requests">
          <el-icon><EditPen /></el-icon>
          <template #title>成绩修改审核</template>
        </el-menu-item>
        
        <el-menu-item index="/statistics">
          <el-icon><TrendCharts /></el-icon>
          <template #title>统计分析</template>
        </el-menu-item>
        
        <el-menu-item v-if="userStore.isAdmin" index="/users">
          <el-icon><UserFilled /></el-icon>
          <template #title>用户管理</template>
        </el-menu-item>
      </el-menu>
    </aside>
    
    <!-- 主内容区 -->
    <div class="main-content">
      <!-- 顶部栏 -->
      <header class="header">
        <div class="header-left">
          <el-icon class="toggle-btn" @click="toggleSidebar"><Fold v-if="!isCollapsed" /><Expand v-else /></el-icon>
          <el-breadcrumb separator="/">
            <el-breadcrumb-item :to="{ path: '/' }">首页</el-breadcrumb-item>
            <el-breadcrumb-item v-if="currentTitle">{{ currentTitle }}</el-breadcrumb-item>
          </el-breadcrumb>
        </div>
        
        <div class="header-right">
          <el-dropdown trigger="click" @command="handleCommand">
            <div class="user-info">
              <el-avatar :size="36" class="avatar">
                {{ userStore.user?.realName?.charAt(0) || userStore.user?.username?.charAt(0) || 'U' }}
              </el-avatar>
              <span class="username">{{ userStore.user?.realName || userStore.user?.username }}</span>
              <el-icon><ArrowDown /></el-icon>
            </div>
            <template #dropdown>
              <el-dropdown-menu>
                <el-dropdown-item command="profile">
                  <el-icon><User /></el-icon>个人中心
                </el-dropdown-item>
                <el-dropdown-item divided command="logout">
                  <el-icon><SwitchButton /></el-icon>退出登录
                </el-dropdown-item>
              </el-dropdown-menu>
            </template>
          </el-dropdown>
        </div>
      </header>
      
      <!-- 页面内容 -->
      <main class="content">
        <router-view v-slot="{ Component }">
          <transition name="fade" mode="out-in">
            <component :is="Component" />
          </transition>
        </router-view>
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useUserStore } from '@/stores/user'
import { ElMessageBox } from 'element-plus'

const route = useRoute()
const router = useRouter()
const userStore = useUserStore()

const isCollapsed = ref(false)

const currentRoute = computed(() => route.path)
const currentTitle = computed(() => route.meta.title)

function toggleSidebar() {
  isCollapsed.value = !isCollapsed.value
}

function handleCommand(command) {
  if (command === 'profile') {
    router.push('/profile')
  } else if (command === 'logout') {
    ElMessageBox.confirm('确定要退出登录吗？', '提示', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning'
    }).then(() => {
      userStore.logout()
    }).catch(() => {})
  }
}
</script>

<style lang="scss" scoped>
.main-layout {
  display: flex;
  height: 100vh;
  overflow: hidden;
}

.sidebar {
  width: 240px;
  background: linear-gradient(180deg, #667eea 0%, #764ba2 100%);
  display: flex;
  flex-direction: column;
  transition: width 0.3s ease;
  flex-shrink: 0;
  
  &.collapsed {
    width: 64px;
    
    .logo span {
      display: none;
    }
  }
  
  .logo {
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 0 16px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    
    img {
      width: 32px;
      height: 32px;
    }
    
    span {
      color: #fff;
      font-size: 16px;
      font-weight: 600;
      white-space: nowrap;
    }
  }
  
  :deep(.el-menu) {
    border: none;
    padding: 8px;
    
    .el-menu-item {
      margin: 4px 0;
      border-radius: 8px;
      height: 44px;
      
      &:hover {
        background: rgba(255, 255, 255, 0.15) !important;
      }
      
      &.is-active {
        background: rgba(255, 255, 255, 0.25) !important;
      }
    }
  }
}

.main-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  background: #f0f2f5;
}

.header {
  height: 60px;
  background: #fff;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 20px;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.08);
  flex-shrink: 0;
  
  .header-left {
    display: flex;
    align-items: center;
    gap: 16px;
    
    .toggle-btn {
      font-size: 20px;
      cursor: pointer;
      color: #606266;
      transition: color 0.3s;
      
      &:hover {
        color: #667eea;
      }
    }
  }
  
  .header-right {
    display: flex;
    align-items: center;
    
    .user-info {
      display: flex;
      align-items: center;
      gap: 8px;
      cursor: pointer;
      padding: 4px 8px;
      border-radius: 8px;
      transition: background 0.3s;
      
      &:hover {
        background: #f5f7fa;
      }
      
      .avatar {
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: #fff;
      }
      
      .username {
        font-size: 14px;
        color: #303133;
      }
    }
  }
}

.content {
  flex: 1;
  overflow-y: auto;
  padding: 20px;
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
