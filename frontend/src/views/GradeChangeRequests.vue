<template>
  <div class="grade-change-requests-page">
    <div class="page-header">
      <h1>成绩修改审核</h1>
      <div class="actions">
        <el-select v-model="searchParams.status" placeholder="审核状态" clearable @change="fetchData" style="width: 140px">
          <el-option label="待审核" value="pending" />
          <el-option label="已通过" value="approved" />
          <el-option label="已驳回" value="rejected" />
        </el-select>
        <el-button type="primary" @click="fetchData">搜索</el-button>
        <el-button @click="resetSearch">重置</el-button>
      </div>
    </div>

    <div class="card">
      <el-table :data="tableData" v-loading="loading" stripe>
        <el-table-column prop="student_no" label="学号" width="120" />
        <el-table-column prop="student_name" label="姓名" width="100" />
        <el-table-column prop="course_name" label="课程" min-width="150" />
        <el-table-column prop="original_score" label="原成绩" width="80">
          <template #default="{ row }">
            <span style="font-weight: 600">{{ row.original_score }}</span>
          </template>
        </el-table-column>
        <el-table-column prop="new_score" label="修改后" width="80">
          <template #default="{ row }">
            <span :style="{ color: row.new_score < 60 ? '#f5222d' : row.new_score >= 90 ? '#52c41a' : '#303133', fontWeight: 600 }">
              {{ row.new_score }}
            </span>
          </template>
        </el-table-column>
        <el-table-column prop="applicant_name" label="申请人" width="100" />
        <el-table-column prop="status" label="状态" width="100">
          <template #default="{ row }">
            <el-tag :type="statusTypeMap[row.status]" size="small">{{ statusTextMap[row.status] }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="created_at" label="申请时间" width="170">
          <template #default="{ row }">
            {{ formatTime(row.created_at) }}
          </template>
        </el-table-column>
        <el-table-column label="操作" width="180" fixed="right">
          <template #default="{ row }">
            <el-button type="primary" link size="small" @click="handleView(row)">查看</el-button>
            <el-button v-if="row.status === 'pending'" type="success" link size="small" @click="handleApprove(row)">通过</el-button>
            <el-button v-if="row.status === 'pending'" type="danger" link size="small" @click="handleReject(row)">驳回</el-button>
            <el-button v-if="row.status === 'rejected'" type="warning" link size="small" @click="handleResubmit(row)">重新提交</el-button>
          </template>
        </el-table-column>
      </el-table>

      <div class="pagination-wrapper">
        <el-pagination
          v-model:current-page="pagination.page"
          v-model:page-size="pagination.pageSize"
          :total="pagination.total"
          :page-sizes="[10, 20, 50, 100]"
          layout="total, sizes, prev, pager, next, jumper"
          @size-change="fetchData"
          @current-change="fetchData"
        />
      </div>
    </div>

    <el-dialog v-model="detailVisible" title="修改申请详情" width="600px" destroy-on-close>
      <el-descriptions :column="2" border>
        <el-descriptions-item label="学生">{{ currentRow.student_no }} - {{ currentRow.student_name }}</el-descriptions-item>
        <el-descriptions-item label="课程">{{ currentRow.course_name }}</el-descriptions-item>
        <el-descriptions-item label="学期">{{ currentRow.semester }}</el-descriptions-item>
        <el-descriptions-item label="考试类型">{{ currentRow.exam_type }}</el-descriptions-item>
        <el-descriptions-item label="原成绩">
          <span style="font-weight: 600">{{ currentRow.original_score }}</span>
        </el-descriptions-item>
        <el-descriptions-item label="修改后成绩">
          <span :style="{ color: currentRow.new_score < 60 ? '#f5222d' : '#52c41a', fontWeight: 600 }">
            {{ currentRow.new_score }}
          </span>
        </el-descriptions-item>
        <el-descriptions-item label="申请人">{{ currentRow.applicant_name }}</el-descriptions-item>
        <el-descriptions-item label="申请时间">{{ formatTime(currentRow.created_at) }}</el-descriptions-item>
        <el-descriptions-item label="状态">
          <el-tag :type="statusTypeMap[currentRow.status]" size="small">{{ statusTextMap[currentRow.status] }}</el-tag>
        </el-descriptions-item>
        <el-descriptions-item v-if="currentRow.reviewer_name" label="审核人">{{ currentRow.reviewer_name }}</el-descriptions-item>
        <el-descriptions-item label="修改原因" :span="2">{{ currentRow.reason }}</el-descriptions-item>
        <el-descriptions-item v-if="currentRow.reject_reason" label="驳回理由" :span="2">
          <span style="color: #f5222d">{{ currentRow.reject_reason }}</span>
        </el-descriptions-item>
        <el-descriptions-item v-if="currentRow.reviewed_at" label="审核时间" :span="2">{{ formatTime(currentRow.reviewed_at) }}</el-descriptions-item>
      </el-descriptions>

      <template #footer>
        <template v-if="currentRow.status === 'pending'">
          <el-button @click="detailVisible = false">取消</el-button>
          <el-button type="danger" @click="handleRejectFromDetail">驳回</el-button>
          <el-button type="success" @click="handleApproveFromDetail">通过</el-button>
        </template>
        <template v-else>
          <el-button @click="detailVisible = false">关闭</el-button>
        </template>
      </template>
    </el-dialog>

    <el-dialog v-model="rejectVisible" title="驳回申请" width="500px" destroy-on-close>
      <el-form ref="rejectFormRef" :model="rejectForm" :rules="rejectRules" label-width="80px">
        <el-form-item label="驳回理由" prop="rejectReason">
          <el-input v-model="rejectForm.rejectReason" type="textarea" rows="3" placeholder="请输入驳回理由（不少于5个字）" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="rejectVisible = false">取消</el-button>
        <el-button type="danger" :loading="actionLoading" @click="confirmReject">确认驳回</el-button>
      </template>
    </el-dialog>

    <el-dialog v-model="resubmitVisible" title="重新提交修改申请" width="500px" destroy-on-close>
      <el-form ref="resubmitFormRef" :model="resubmitForm" :rules="resubmitRules" label-width="80px">
        <el-form-item label="修改后成绩" prop="newScore">
          <el-input-number v-model="resubmitForm.newScore" :min="0" :max="100" :precision="1" style="width: 100%" />
        </el-form-item>
        <el-form-item label="修改原因" prop="reason">
          <el-input v-model="resubmitForm.reason" type="textarea" rows="3" placeholder="请输入修改原因（不少于20个汉字）" show-word-limit maxlength="500" />
        </el-form-item>
        <el-alert v-if="currentRow.reject_reason" type="warning" :closable="false" show-icon style="margin-bottom: 16px">
          <template #title>驳回理由：{{ currentRow.reject_reason }}</template>
        </el-alert>
      </el-form>
      <template #footer>
        <el-button @click="resubmitVisible = false">取消</el-button>
        <el-button type="primary" :loading="actionLoading" @click="confirmResubmit">重新提交</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessageBox } from 'element-plus'
import { gradeChangeRequestApi } from '@/api'
import { showSuccess } from '@/utils/request'

const loading = ref(false)
const actionLoading = ref(false)
const tableData = ref([])
const detailVisible = ref(false)
const rejectVisible = ref(false)
const resubmitVisible = ref(false)
const currentRow = ref({})
const rejectFormRef = ref(null)
const resubmitFormRef = ref(null)

const searchParams = reactive({ status: null })
const pagination = reactive({ page: 1, pageSize: 20, total: 0 })
const rejectForm = reactive({ rejectReason: '' })
const resubmitForm = reactive({ newScore: 0, reason: '' })

const statusTypeMap = { pending: 'warning', approved: 'success', rejected: 'danger' }
const statusTextMap = { pending: '待审核', approved: '已通过', rejected: '已驳回' }

const rejectRules = {
  rejectReason: [
    { required: true, message: '请输入驳回理由', trigger: 'blur' },
    { min: 5, message: '驳回理由不得少于5个字', trigger: 'blur' }
  ]
}

const resubmitRules = {
  newScore: [{ required: true, message: '请输入修改后成绩', trigger: 'blur' }],
  reason: [
    { required: true, message: '请输入修改原因', trigger: 'blur' },
    { min: 20, message: '修改原因不得少于20个汉字', trigger: 'blur' }
  ]
}

function formatTime(val) {
  if (!val) return '-'
  return val.replace('T', ' ').substring(0, 19)
}

async function fetchData() {
  loading.value = true
  try {
    const res = await gradeChangeRequestApi.getList({
      page: pagination.page,
      pageSize: pagination.pageSize,
      status: searchParams.status || undefined
    })
    tableData.value = res.data.items
    pagination.total = res.data.total
  } finally {
    loading.value = false
  }
}

function resetSearch() {
  searchParams.status = null
  pagination.page = 1
  fetchData()
}

function handleView(row) {
  currentRow.value = { ...row }
  detailVisible.value = true
}

async function handleApprove(row) {
  try {
    await ElMessageBox.confirm(
      `确定通过该成绩修改申请吗？\n学生：${row.student_name}\n课程：${row.course_name}\n成绩：${row.original_score} → ${row.new_score}`,
      '审核确认',
      { type: 'success' }
    )
    await gradeChangeRequestApi.approve(row.id)
    showSuccess('审核通过，成绩已更新')
    fetchData()
  } catch (e) {}
}

async function handleApproveFromDetail() {
  await handleApprove(currentRow.value)
  detailVisible.value = false
}

function handleReject(row) {
  currentRow.value = { ...row }
  rejectForm.rejectReason = ''
  rejectVisible.value = true
}

function handleRejectFromDetail() {
  rejectVisible.value = true
  detailVisible.value = false
}

async function confirmReject() {
  const valid = await rejectFormRef.value.validate().catch(() => false)
  if (!valid) return
  actionLoading.value = true
  try {
    await gradeChangeRequestApi.reject(currentRow.value.id, { rejectReason: rejectForm.rejectReason })
    showSuccess('已驳回该申请')
    rejectVisible.value = false
    fetchData()
  } finally {
    actionLoading.value = false
  }
}

function handleResubmit(row) {
  currentRow.value = { ...row }
  resubmitForm.newScore = row.new_score
  resubmitForm.reason = row.reason
  resubmitVisible.value = true
}

async function confirmResubmit() {
  const valid = await resubmitFormRef.value.validate().catch(() => false)
  if (!valid) return
  actionLoading.value = true
  try {
    await gradeChangeRequestApi.resubmit(currentRow.value.id, {
      newScore: resubmitForm.newScore,
      reason: resubmitForm.reason
    })
    showSuccess('申请已重新提交，等待审核')
    resubmitVisible.value = false
    fetchData()
  } finally {
    actionLoading.value = false
  }
}

onMounted(() => { fetchData() })
</script>

<style lang="scss" scoped>
.grade-change-requests-page {
  .actions {
    display: flex;
    gap: 12px;
  }
}
</style>
