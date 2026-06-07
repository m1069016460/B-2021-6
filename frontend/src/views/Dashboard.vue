<template>
  <div class="dashboard">
    <div class="page-header">
      <h1>仪表盘</h1>
      <span class="date">{{ currentDate }}</span>
    </div>
    
    <!-- 统计卡片 -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="icon primary">
          <el-icon><User /></el-icon>
        </div>
        <div class="content">
          <div class="label">学生总数</div>
          <div class="value">{{ overview.summary?.studentCount || 0 }}</div>
        </div>
      </div>
      
      <div class="stat-card">
        <div class="icon success">
          <el-icon><Reading /></el-icon>
        </div>
        <div class="content">
          <div class="label">课程总数</div>
          <div class="value">{{ overview.summary?.courseCount || 0 }}</div>
        </div>
      </div>
      
      <div class="stat-card">
        <div class="icon warning">
          <el-icon><School /></el-icon>
        </div>
        <div class="content">
          <div class="label">班级总数</div>
          <div class="value">{{ overview.summary?.classCount || 0 }}</div>
        </div>
      </div>
      
      <div class="stat-card">
        <div class="icon info">
          <el-icon><TrendCharts /></el-icon>
        </div>
        <div class="content">
          <div class="label">平均分</div>
          <div class="value">{{ overview.summary?.avgScore || 0 }}</div>
        </div>
      </div>
    </div>
    
    <!-- 图表区域 -->
    <el-row :gutter="20">
      <el-col :xs="24" :lg="12">
        <div class="card">
          <div class="card-header">
            <h3>成绩等级分布</h3>
          </div>
          <div class="card-body">
            <v-chart :option="gradeDistributionOption" autoresize style="height: 300px" />
          </div>
        </div>
      </el-col>
      
      <el-col :xs="24" :lg="12">
        <div class="card">
          <div class="card-header">
            <h3>班级成绩排名</h3>
          </div>
          <div class="card-body">
            <v-chart :option="classRankingOption" autoresize style="height: 300px" />
          </div>
        </div>
      </el-col>
    </el-row>
    
    <el-row :gutter="20" style="margin-top: 20px">
      <el-col :xs="24" :lg="14">
        <div class="card">
          <div class="card-header">
            <h3>成绩趋势</h3>
          </div>
          <div class="card-body">
            <v-chart :option="trendOption" autoresize style="height: 300px" />
          </div>
        </div>
      </el-col>
      
      <el-col :xs="24" :lg="10">
        <div class="card">
          <div class="card-header">
            <h3>快速统计</h3>
          </div>
          <div class="card-body">
            <div class="quick-stats">
              <div class="stat-item">
                <span class="label">及格率</span>
                <el-progress 
                  :percentage="overview.summary?.passRate || 0" 
                  :color="'#52c41a'"
                  :stroke-width="12"
                />
              </div>
              <div class="stat-item">
                <span class="label">优秀率</span>
                <el-progress 
                  :percentage="overview.summary?.excellentRate || 0" 
                  :color="'#667eea'"
                  :stroke-width="12"
                />
              </div>
              <div class="stat-item">
                <span class="label">成绩记录</span>
                <span class="value">{{ overview.summary?.gradeCount || 0 }} 条</span>
              </div>
              <div class="stat-item">
                <span class="label">最高分</span>
                <span class="value">{{ overview.summary?.maxScore || 0 }} 分</span>
              </div>
              <div class="stat-item">
                <span class="label">最低分</span>
                <span class="value">{{ overview.summary?.minScore || 0 }} 分</span>
              </div>
            </div>
          </div>
        </div>
      </el-col>
    </el-row>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { use } from 'echarts/core'
import { CanvasRenderer } from 'echarts/renderers'
import { PieChart, BarChart, LineChart } from 'echarts/charts'
import { GridComponent, TooltipComponent, LegendComponent } from 'echarts/components'
import VChart from 'vue-echarts'
import { statisticsApi } from '@/api'
import dayjs from 'dayjs'

use([CanvasRenderer, PieChart, BarChart, LineChart, GridComponent, TooltipComponent, LegendComponent])

const overview = ref({})
const loading = ref(false)
const currentDate = dayjs().format('YYYY年MM月DD日 dddd')

// 成绩等级分布图表
const gradeDistributionOption = computed(() => {
  const data = overview.value.gradeDistribution || []
  return {
    tooltip: { trigger: 'item', formatter: '{b}: {c} ({d}%)' },
    legend: { bottom: 0 },
    color: ['#52c41a', '#1890ff', '#faad14', '#ff7a45', '#f5222d'],
    series: [{
      type: 'pie',
      radius: ['40%', '70%'],
      avoidLabelOverlap: false,
      itemStyle: { borderRadius: 8, borderColor: '#fff', borderWidth: 2 },
      label: { show: false },
      emphasis: { label: { show: true, fontSize: 14 } },
      data: data.map(item => ({
        name: item.grade_level,
        value: item.count
      }))
    }]
  }
})

// 班级排名图表
const classRankingOption = computed(() => {
  const data = (overview.value.classRanking || []).slice(0, 8)
  return {
    tooltip: { trigger: 'axis' },
    grid: { left: '3%', right: '4%', bottom: '3%', containLabel: true },
    xAxis: { type: 'value', max: 100 },
    yAxis: { type: 'category', data: data.map(item => item.class_name).reverse() },
    series: [{
      type: 'bar',
      data: data.map(item => parseFloat(item.avg_score).toFixed(1)).reverse(),
      itemStyle: {
        borderRadius: [0, 4, 4, 0],
        color: { type: 'linear', x: 0, y: 0, x2: 1, y2: 0, colorStops: [
          { offset: 0, color: '#667eea' },
          { offset: 1, color: '#764ba2' }
        ]}
      },
      barWidth: 16
    }]
  }
})

// 趋势图表
const trendOption = computed(() => {
  const data = overview.value.monthlyTrend || []
  return {
    tooltip: { trigger: 'axis' },
    grid: { left: '3%', right: '4%', bottom: '3%', containLabel: true },
    xAxis: { type: 'category', data: data.map(item => item.month), boundaryGap: false },
    yAxis: { type: 'value', min: 0, max: 100 },
    series: [{
      type: 'line',
      data: data.map(item => parseFloat(item.avg_score).toFixed(1)),
      smooth: true,
      areaStyle: {
        color: { type: 'linear', x: 0, y: 0, x2: 0, y2: 1, colorStops: [
          { offset: 0, color: 'rgba(102, 126, 234, 0.5)' },
          { offset: 1, color: 'rgba(102, 126, 234, 0.05)' }
        ]}
      },
      lineStyle: { color: '#667eea', width: 2 },
      itemStyle: { color: '#667eea' }
    }]
  }
})

async function fetchData() {
  loading.value = true
  try {
    const res = await statisticsApi.getOverview()
    overview.value = res.data
  } catch (error) {
    // 错误已处理
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchData()
})
</script>

<style lang="scss" scoped>
.dashboard {
  .page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
    
    h1 {
      font-size: 22px;
      font-weight: 600;
    }
    
    .date {
      color: #909399;
      font-size: 14px;
    }
  }
  
  .quick-stats {
    .stat-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 12px 0;
      border-bottom: 1px solid #f0f0f0;
      
      &:last-child {
        border-bottom: none;
      }
      
      .label {
        color: #606266;
        font-size: 14px;
      }
      
      .value {
        font-size: 16px;
        font-weight: 600;
        color: #303133;
      }
      
      .el-progress {
        width: 60%;
      }
    }
  }
}
</style>
