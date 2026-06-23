<?php
// ============================================================
//  src/models/TaskModel.php
//  Data-access layer for the `tasks` table
// ============================================================

class TaskModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // ── READ ─────────────────────────────────────────────────

    /** Return all tasks, newest first, optionally filtered by status */
    public function getAll(string $filter = 'all'): array
    {
        $allowed = ['all', 'pending', 'completed'];
        if (!in_array($filter, $allowed, true)) $filter = 'all';

        if ($filter === 'all') {
            $stmt = $this->db->query(
                'SELECT * FROM tasks ORDER BY
                 CASE status WHEN "pending" THEN 0 WHEN "completed" THEN 1 ELSE 2 END,
                 created_at DESC'
            );
        } else {
            $stmt = $this->db->prepare(
                'SELECT * FROM tasks WHERE status = ? ORDER BY created_at DESC'
            );
            $stmt->execute([$filter]);
        }
        return $stmt->fetchAll();
    }

    /** Aggregate counts for the stats bar */
    public function getStats(): array
    {
        $row = $this->db
            ->query('SELECT
                       COUNT(*)                                   AS total,
                       COALESCE(SUM(status = "pending"),   0)    AS pending,
                       COALESCE(SUM(status = "completed"), 0)    AS completed,
                       COALESCE(ROUND(SUM(status="completed")/NULLIF(COUNT(*),0)*100), 0) AS pct
                     FROM tasks')
            ->fetch();
        return $row ?: ['total'=>0,'pending'=>0,'completed'=>0,'pct'=>0];
    }

    /** Find a single task by id */
    public function findById(int $id): array|false
    {
        $stmt = $this->db->prepare('SELECT * FROM tasks WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    // ── CREATE ───────────────────────────────────────────────

    public function create(string $title, string $priority): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO tasks (title, priority) VALUES (?, ?)'
        );
        $stmt->execute([$title, $priority]);
        return (int) $this->db->lastInsertId();
    }

    // ── UPDATE ───────────────────────────────────────────────

    public function setStatus(int $id, string $status): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE tasks SET status = ? WHERE id = ?'
        );
        return $stmt->execute([$status, $id]);
    }

    // ── DELETE ───────────────────────────────────────────────

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM tasks WHERE id = ?');
        return $stmt->execute([$id]);
    }

    // ── VALIDATION ───────────────────────────────────────────

    public function isDuplicate(string $title): bool
    {
        $stmt = $this->db->prepare(
            'SELECT 1 FROM tasks WHERE LOWER(title) = LOWER(?) LIMIT 1'
        );
        $stmt->execute([$title]);
        return (bool) $stmt->fetchColumn();
    }
}
