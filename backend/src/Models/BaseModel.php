<?php
/**
 * 基础模型类
 */

namespace App\Models;

use App\Config\Database;
use PDO;

abstract class BaseModel
{
    protected PDO $db;
    protected string $table;
    protected string $primaryKey = 'id';
    
    public function __construct()
    {
        $this->db = Database::getConnection();
    }
    
    /**
     * 查询所有记录
     */
    public function all(array $columns = ['*']): array
    {
        $cols = implode(', ', $columns);
        $sql = "SELECT {$cols} FROM {$this->table} ORDER BY id DESC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }
    
    /**
     * 根据ID查找
     */
    public function find(int $id): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }
    
    /**
     * 根据条件查找单条
     */
    public function findBy(string $column, $value): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$column} = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$value]);
        $result = $stmt->fetch();
        return $result ?: null;
    }
    
    /**
     * 根据条件查找多条
     */
    public function where(array $conditions, string $orderBy = 'id DESC'): array
    {
        $where = [];
        $values = [];
        foreach ($conditions as $column => $value) {
            $where[] = "{$column} = ?";
            $values[] = $value;
        }
        
        $whereStr = implode(' AND ', $where);
        $sql = "SELECT * FROM {$this->table} WHERE {$whereStr} ORDER BY {$orderBy}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($values);
        return $stmt->fetchAll();
    }
    
    /**
     * 创建记录
     */
    public function create(array $data): int
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        
        $sql = "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_values($data));
        
        return (int) $this->db->lastInsertId();
    }
    
    /**
     * 更新记录
     */
    public function update(int $id, array $data): bool
    {
        $set = [];
        foreach (array_keys($data) as $column) {
            $set[] = "{$column} = ?";
        }
        
        $setStr = implode(', ', $set);
        $sql = "UPDATE {$this->table} SET {$setStr} WHERE {$this->primaryKey} = ?";
        $stmt = $this->db->prepare($sql);
        
        $values = array_values($data);
        $values[] = $id;
        
        return $stmt->execute($values);
    }
    
    /**
     * 删除记录
     */
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id]);
    }
    
    /**
     * 计数
     */
    public function count(array $conditions = []): int
    {
        if (empty($conditions)) {
            $sql = "SELECT COUNT(*) as count FROM {$this->table}";
            $stmt = $this->db->query($sql);
        } else {
            $where = [];
            $values = [];
            foreach ($conditions as $column => $value) {
                $where[] = "{$column} = ?";
                $values[] = $value;
            }
            $whereStr = implode(' AND ', $where);
            $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE {$whereStr}";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($values);
        }
        
        $result = $stmt->fetch();
        return (int) $result['count'];
    }
    
    /**
     * 分页查询
     */
    public function paginate(int $page, int $pageSize, array $conditions = [], string $orderBy = 'id DESC'): array
    {
        $offset = ($page - 1) * $pageSize;
        
        if (empty($conditions)) {
            $sql = "SELECT * FROM {$this->table} ORDER BY {$orderBy} LIMIT {$pageSize} OFFSET {$offset}";
            $stmt = $this->db->query($sql);
            $items = $stmt->fetchAll();
            $total = $this->count();
        } else {
            $where = [];
            $values = [];
            foreach ($conditions as $column => $value) {
                $where[] = "{$column} = ?";
                $values[] = $value;
            }
            $whereStr = implode(' AND ', $where);
            
            $sql = "SELECT * FROM {$this->table} WHERE {$whereStr} ORDER BY {$orderBy} LIMIT {$pageSize} OFFSET {$offset}";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($values);
            $items = $stmt->fetchAll();
            $total = $this->count($conditions);
        }
        
        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'pageSize' => $pageSize
        ];
    }
    
    /**
     * 执行原生SQL
     */
    public function query(string $sql, array $params = []): array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * 执行原生SQL（无返回）
     */
    public function execute(string $sql, array $params = []): bool
    {
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
    
    /**
     * 检查是否存在
     */
    public function exists(string $column, $value, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE {$column} = ?";
        $params = [$value];
        
        if ($excludeId !== null) {
            $sql .= " AND {$this->primaryKey} != ?";
            $params[] = $excludeId;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        
        return (int) $result['count'] > 0;
    }
}
