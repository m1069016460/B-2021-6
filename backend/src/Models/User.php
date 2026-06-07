<?php
/**
 * 用户模型
 */

namespace App\Models;

class User extends BaseModel
{
    protected string $table = 'users';
    
    /**
     * 根据用户名查找
     */
    public function findByUsername(string $username): ?array
    {
        return $this->findBy('username', $username);
    }
    
    /**
     * 获取教师列表
     */
    public function getTeachers(): array
    {
        $sql = "SELECT id, username, real_name, email, phone FROM {$this->table} WHERE role = 'teacher' AND status = 1 ORDER BY id";
        return $this->query($sql);
    }
    
    /**
     * 分页查询带搜索
     */
    public function search(int $page, int $pageSize, ?string $keyword = null, ?string $role = null): array
    {
        $where = ['1=1'];
        $params = [];
        
        if ($keyword) {
            $where[] = "(username LIKE ? OR real_name LIKE ? OR email LIKE ?)";
            $params[] = "%{$keyword}%";
            $params[] = "%{$keyword}%";
            $params[] = "%{$keyword}%";
        }
        
        if ($role) {
            $where[] = "role = ?";
            $params[] = $role;
        }
        
        $whereStr = implode(' AND ', $where);
        $offset = ($page - 1) * $pageSize;
        
        // 获取数据
        $sql = "SELECT id, username, email, phone, real_name, role, status, created_at, updated_at 
                FROM {$this->table} 
                WHERE {$whereStr} 
                ORDER BY id DESC 
                LIMIT {$pageSize} OFFSET {$offset}";
        $items = $this->query($sql, $params);
        
        // 获取总数
        $countSql = "SELECT COUNT(*) as count FROM {$this->table} WHERE {$whereStr}";
        $countResult = $this->query($countSql, $params);
        $total = (int) $countResult[0]['count'];
        
        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'pageSize' => $pageSize
        ];
    }
}
