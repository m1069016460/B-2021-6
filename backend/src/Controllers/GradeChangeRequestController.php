<?php

namespace App\Controllers;

use App\Models\GradeChangeRequest;
use App\Models\Grade;
use App\Config\App;
use App\Utils\Response;
use App\Utils\Validator;
use App\Utils\Logger;

class GradeChangeRequestController
{
    private GradeChangeRequest $requestModel;
    private Grade $gradeModel;

    public function __construct()
    {
        $this->requestModel = new GradeChangeRequest();
        $this->gradeModel = new Grade();
    }

    public function index(array $params): void
    {
        $page = (int) ($_GET['page'] ?? 1);
        $pageSize = (int) ($_GET['pageSize'] ?? 20);
        $status = $_GET['status'] ?? null;
        $applicantId = isset($_GET['applicantId']) ? (int) $_GET['applicantId'] : null;

        if ($status !== null && !in_array($status, ['pending', 'approved', 'rejected'])) {
            Response::error('无效的状态参数', 400);
            return;
        }

        $result = $this->requestModel->search($page, $pageSize, $status, $applicantId);

        Response::success([
            'items' => $result['items'],
            'total' => $result['total'],
            'page' => $result['page'],
            'pageSize' => $result['pageSize'],
            'totalPages' => ceil($result['total'] / $result['pageSize'])
        ]);
    }

    public function show(array $params): void
    {
        $id = (int) $params['id'];
        $request = $this->requestModel->findDetail($id);

        if (!$request) {
            Response::error('修改申请不存在', 404);
            return;
        }

        Response::success($request);
    }

    public function store(array $params): void
    {
        $data = $this->getInput();
        $user = $params['_user'];

        $validator = new Validator($data);
        $validator->required('gradeId', '成绩记录不能为空')
                  ->required('newScore', '修改后成绩不能为空')
                  ->required('reason', '修改原因不能为空')
                  ->numeric('newScore', '修改后成绩必须是数字')
                  ->between('newScore', 0, 100, '修改后成绩必须在0-100之间')
                  ->minLength('reason', 20, '修改原因不得少于20个汉字');

        if ($validator->fails()) {
            Response::error($validator->getFirstError(), 400);
            return;
        }

        $gradeId = (int) $data['gradeId'];
        $grade = $this->gradeModel->find($gradeId);
        if (!$grade) {
            Response::error('成绩记录不存在', 404);
            return;
        }

        if ($this->requestModel->hasPendingRequest($gradeId)) {
            Response::error('该成绩已有待审核的修改申请', 400);
            return;
        }

        $newScore = (float) $data['newScore'];
        if (abs($newScore - (float) $grade['score']) < 0.01) {
            Response::error('修改后成绩与原成绩相同', 400);
            return;
        }

        $requestId = $this->requestModel->create([
            'grade_id' => $gradeId,
            'applicant_id' => $user['id'],
            'original_score' => $grade['score'],
            'new_score' => $newScore,
            'reason' => trim($data['reason']),
            'status' => 'pending'
        ]);

        Logger::info("Grade change request created: ID {$requestId}, Grade ID {$gradeId}, by user {$user['id']}");

        Response::success(['id' => $requestId], '修改申请已提交，等待审核');
    }

    public function approve(array $params): void
    {
        $id = (int) $params['id'];
        $user = $params['_user'];

        $request = $this->requestModel->find($id);
        if (!$request) {
            Response::error('修改申请不存在', 404);
            return;
        }

        if ($request['status'] !== 'pending') {
            Response::error('只能审核待审核的申请', 400);
            return;
        }

        if ($request['applicant_id'] === $user['id']) {
            Response::error('不能审核自己提交的申请', 400);
            return;
        }

        $grade = $this->gradeModel->find($request['grade_id']);
        if (!$grade) {
            Response::error('关联的成绩记录不存在', 404);
            return;
        }

        $newScore = (float) $request['new_score'];
        $gradeLevel = App::calculateGradeLevel($newScore);

        $this->gradeModel->update($request['grade_id'], [
            'score' => $newScore,
            'grade_level' => $gradeLevel
        ]);

        $this->requestModel->update($id, [
            'status' => 'approved',
            'reviewer_id' => $user['id'],
            'reviewed_at' => date('Y-m-d H:i:s')
        ]);

        Logger::info("Grade change request approved: ID {$id}, by user {$user['id']}");

        Response::success(null, '审核通过，成绩已更新');
    }

    public function reject(array $params): void
    {
        $id = (int) $params['id'];
        $user = $params['_user'];
        $data = $this->getInput();

        $request = $this->requestModel->find($id);
        if (!$request) {
            Response::error('修改申请不存在', 404);
            return;
        }

        if ($request['status'] !== 'pending') {
            Response::error('只能审核待审核的申请', 400);
            return;
        }

        $validator = new Validator($data);
        $validator->required('rejectReason', '驳回理由不能为空')
                  ->minLength('rejectReason', 5, '驳回理由不得少于5个字');

        if ($validator->fails()) {
            Response::error($validator->getFirstError(), 400);
            return;
        }

        $this->requestModel->update($id, [
            'status' => 'rejected',
            'reviewer_id' => $user['id'],
            'reject_reason' => trim($data['rejectReason']),
            'reviewed_at' => date('Y-m-d H:i:s')
        ]);

        Logger::info("Grade change request rejected: ID {$id}, by user {$user['id']}");

        Response::success(null, '已驳回该申请');
    }

    public function resubmit(array $params): void
    {
        $id = (int) $params['id'];
        $user = $params['_user'];
        $data = $this->getInput();

        $request = $this->requestModel->find($id);
        if (!$request) {
            Response::error('修改申请不存在', 404);
            return;
        }

        if ($request['status'] !== 'rejected') {
            Response::error('只能重新提交被驳回的申请', 400);
            return;
        }

        if ($request['applicant_id'] !== $user['id']) {
            Response::error('只能重新提交自己的申请', 400);
            return;
        }

        $validator = new Validator($data);
        $validator->required('reason', '修改原因不能为空')
                  ->minLength('reason', 20, '修改原因不得少于20个汉字');

        if (isset($data['newScore'])) {
            $validator->numeric('newScore', '修改后成绩必须是数字')
                      ->between('newScore', 0, 100, '修改后成绩必须在0-100之间');
        }

        if ($validator->fails()) {
            Response::error($validator->getFirstError(), 400);
            return;
        }

        $updateData = [
            'status' => 'pending',
            'reviewer_id' => null,
            'reject_reason' => null,
            'reviewed_at' => null,
            'reason' => trim($data['reason'])
        ];

        if (isset($data['newScore'])) {
            $updateData['new_score'] = (float) $data['newScore'];
        }

        $this->requestModel->update($id, $updateData);

        Logger::info("Grade change request resubmitted: ID {$id}, by user {$user['id']}");

        Response::success(null, '申请已重新提交，等待审核');
    }

    private function getInput(): array
    {
        $input = file_get_contents('php://input');
        return json_decode($input, true) ?? [];
    }
}
