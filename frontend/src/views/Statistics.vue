<template>
  <div class="statistics-page">
    <div class="page-header">
      <h1>统计分析</h1>
    </div>
    
    <!-- 统计卡片 -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="icon primary"><el-icon><TrendCharts /></el-icon></div>
        <div class="content">
          <div class="label">平均分</div>
          <div class="value">{{ overview.summary?.avgScore || 0 }}</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="icon success"><el-icon><Select /></el-icon></div>
        <div class="content">
          <div class="label">及格率</div>
          <div class="value">{{ overview.summary?.passRate || 0 }}%</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="icon warning"><el-icon><Star /></el-icon></div>
        <div class="content">
          <div class="label">优秀率</div>
          <div class="value">{{ overview.summary?.excellentRate || 0 }}%</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="icon info"><el-icon><Document /></el-icon></div>
        <div class="content">
          <div class="label">成绩记录</div>
          <div class="value">{{ overview.summary?.gradeCount || 0 }}</div>
        </div>
      </div>
    </div>
    
    <!-- 筛选 -->
    <div class="card">
      <div class="search-bar">
        <el-select v-model="filterParams.classId" placeholder="选择班级" clearable @change="fetchClassStats">
          <el-option v-for="c in classes" :key="c.id" :label="c.name" :value="c.id" />
        </el-select>
        <el-select v-model="filterParams.courseId" placeholder="选择课程" clearable @change="fetchCourseStats">
          <el-option v-for="c in courses" :key="c.id" :label="c.name" :value="c.id" />
        </el-select>
        <el-select v-model="filterParams.semester" placeholder="选择学期" clearable @change="fetchData">
          <el-option v-for="s in semesters" :key="s" :label="s" :value="s" />
        </el-select>
      </div>
    </div>
    
    <!-- 图表 -->
    <el-row :gutter="20">
      <el-col :xs="24" :lg="12">
        <div class="card">
          <div class="card-header"><h3>分数段分布</h3></div>
          <div class="card-body">
            <v-chart :option="scoreDistOption" autoresize style="height: 300px" />
          </div>
        </div>
      </el-col>
      <el-col :xs="24" :lg="12">
        <div class="card">
          <div class="card-header"><h3>成绩等级分布</h3></div>
          <div class="card-body">
            <v-chart :option="gradeLevelOption" autoresize style="height: 300px" />
          </div>
        </div>
      </el-col>
    </el-row>
    
    <el-row :gutter="20" style="margin-top: 20px">
      <el-col :xs="24" :lg="14">
        <div class="card">
          <div class="card-header"><h3>成绩排名 TOP 20</h3></div>
          <div class="card-body">
            <el-table :data="ranking" stripe max-height="400">
              <el-table-column type="index" label="排名" width="60" />
              <el-table-column prop="student_no" label="学号" width="120" />
              <el-table-column prop="name" label="姓名" width="100" />
              <el-table-column prop="class_name" label="班级" />
              <el-table-column prop="avg_score" label="平均分" width="100">
                <template #default="{ row }">
                  <span style="font-weight: 600; color: #667eea">{{ parseFloat(row.avg_score).toFixed(1) }}</span>
                </template>
              </el-table-column>
            </el-table>
          </div>
        </div>
      </el-col>
      <el-col :xs="24" :lg="10">
        <div class="card">
          <div class="card-header"><h3>班级成绩对比</h3></div>
          <div class="card-body">
            <v-chart :option="classCompareOption" autoresize style="height: 300px" />
          </div>
        </div>
      </el-col>
    </el-row>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { use } from 'echarts/core'
import { CanvasRenderer } from 'echarts/renderers'
import { PieChart, BarChart } from 'echarts/charts'
import { GridComponent, TooltipComponent, LegendComponent } from 'echarts/components'
import VChart from 'vue-echarts'
import { statisticsApi, classApi, courseApi } from '@/api'

use([CanvasRenderer, PieChart, BarChart, GridComponent, TooltipComponent, LegendComponent])

const overview = ref({})
const distribution = ref({})
const ranking = ref([])
const classes = ref([])
const courses = ref([])
const semesters = ref([])

const filterParams = reactive({ classId: null, courseId: null, semester: null })

// 分数段分布图表
const scoreDistOption = computed(() => {
  const data = distribution.value.scoreDistribution || []
  return {
    tooltip: { trigger: 'axis' },
    grid: { left: '3%', right: '4%', bottom: '3%', containLabel: true },
    xAxis: { type: 'category', data: data.map(item => item.score_range) },
    yAxis: { type: 'value' },
    series: [{
      type: 'bar',
      data: data.map(item => item.count),
      itemStyle: {
        borderRadius: [4, 4, 0, 0],
        color: { type: 'linear', x: 0, y: 0, x2: 0, y2: 1, colorStops: [
          { offset: 0, color: '#667eea' },
          { offset: 1, color: '#764ba2' }
        ]}
      },
      barWidth: 40
    }]
  }
})

// 成绩等级分布图表
const gradeLevelOption = computed(() => {
  const data = distribution.value.gradeDistribution || []
  return {
    tooltip: { trigger: 'item', formatter: '{b}: {c} ({d}%)' },
    legend: { bottom: 0 },
    color: ['#52c41a', '#1890ff', '#faad14', '#ff7a45', '#f5222d'],
    series: [{
      type: 'pie',
      radius: ['40%', '70%'],
      itemStyle: { borderRadius: 8, borderColor: '#fff', borderWidth: 2 },
      label: { show: false },
      emphasis: { label: { show: true, fontSize: 14 } },
      data: data.map(item => ({ name: item.grade_level, value: item.count }))
    }]
  }
})

// 班级对比图表
const classCompareOption = computed(() => {
  const data = (overview.value.classRanking || []).slice(0, 6)
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
          { offset: 0, color: '#52c41a' },
          { offset: 1, color: '#73d13d' }
        ]}
      },
      barWidth: 16
    }]
  }
})

async function fetchData() {
  try {
    const [overviewRes, distRes, rankRes] = await Promise.all([
      statisticsApi.getOverview(),
      statisticsApi.getDistribution(filterParams),
      statisticsApi.getRanking({ ...filterParams, limit: 20 })
    ])
    overview.value = overviewRes.data
    distribution.value = distRes.data
    ranking.value = rankRes.data.ranking || []
  } catch (e) {}
}

async function fetchOptions() {
  try {
    const [classRes, courseRes] = await Promise.all([classApi.getAll(), courseApi.getAll()])
    classes.value = classRes.data.classes || []
    courses.value = courseRes.data.courses || []
    semesters.value = courseRes.data.semesters || []
  } catch (e) {}
}

async function fetchClassStats() { fetchData() }
async function fetchCourseStats() { fetchData() }

onMounted(() => { fetchData(); fetchOptions() })
</script>
