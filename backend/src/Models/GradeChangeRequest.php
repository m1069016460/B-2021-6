<?php

namespace App\Models;

class GradeChangeRequest extends BaseModel
{
    protected string $table = 'grade_change_requests';

    public function search(int $page, int $pageSize, ?string $status = null, ?int $applicantId = null): array
    {
        $where = ['1=1'];
        $params = [];

        if ($status !== null) {
            $where[] = "r.status = ?";
            $params[] = $status;
        }

        if ($applicantId !== null) {
            $where[] = "r.applicant_id = ?";
            $params[] = $applicantId;
        }

        $whereStr = implode(' AND ', $where);
        $offset = ($page - 1) * $pageSize;

        $sql = "SELECT r.*,
                       g.score as current_score, g.student_id, g.course_id, g.semester, g.exam_type,
                       s.student_no, s.name as student_name,
                       c.code as course_code, c.name as course_name,
                       u1.real_name as applicant_name,
                       u2.real_name as reviewer_name
                FROM {$this->table} r
                JOIN grades g ON r.grade_id = g.id
                JOIN students s ON g.student_id = s.id
                JOIN courses c ON g.course_id = c.id
                JOIN users u1 ON r.applicant_id = u1.id
                LEFT JOIN users u2 ON r.reviewer_id = u2.id
                WHERE {$whereStr}
                ORDER BY r.created_at DESC
                LIMIT {$pageSize} OFFSET {$offset}";
        $items = $this->query($sql, $params);

        $countSql = "SELECT COUNT(*) as count FROM {$this->table} r WHERE {$whereStr}";
        $countResult = $this->query($countSql, $params);
        $total = (int) $countResult[0]['count'];

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'pageSize' => $pageSize
        ];
    }

    public function findDetail(int $id): ?array
    {
        $sql = "SELECT r.*,
                       g.score as current_score, g.student_id, g.course_id, g.semester, g.exam_type, g.grade_level,
                       s.student_no, s.name as student_name,
                       c.code as course_code, c.name as course_name,
                       u1.real_name as applicant_name,
                       u2.real_name as reviewer_name
                FROM {$this->table} r
                JOIN grades g ON r.grade_id = g.id
                JOIN students s ON g.student_id = s.id
                JOIN courses c ON g.course_id = c.id
                JOIN users u1 ON r.applicant_id = u1.id
                LEFT JOIN users u2 ON r.reviewer_id = u2.id
                WHERE r.id = ?";
        $result = $this->query($sql, [$id]);
        return $result[0] ?? null;
    }

    public function hasPendingRequest(int $gradeId): bool
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table}
                WHERE grade_id = ? AND status = 'pending'";
        $result = $this->query($sql, [$gradeId]);
        return (int) $result[0]['count'] > 0;
    }
}
