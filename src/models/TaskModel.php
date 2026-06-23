<?php
// ============================================================
//  src/models/TaskModel.php
//  Data-access layer for the `tasks` table
//  Uses only standard SQL — compatible with MySQL 5.7, 8.0, 8.4
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
            // CASE WHEN used instead of FIELD() — works on all MySQL versions
            $stmt = $this->db->query(
                'SELECT * FROM tasks
                 ORDER BY
                   CASE WHEN status = \'pending\'   THEN 0
                        WHEN status = \'completed\' THEN 1
                        ELSE 2 END,
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
        // Use COUNT + CASE instead of SUM(status="x") — strict SQL compatible
        $row = $this->db
            ->query("SELECT
                       COUNT(*) AS total,
                       COUNT(CASE WHEN status = 'pending'   THEN 1 END) AS pending,
                       COUNT(CASE WHEN status = 'completed' THEN 1 END) AS completed,
                       CASE WHEN COUNT(*) = 0 THEN 0
                            ELSE ROUND(
                              COUNT(CASE WHEN status = 'completed' THEN 1 END)
                              / COUNT(*) * 100
                            )
                       END AS pct
                     FROM tasks")
            ->fetch();

        // Cast all values to int so JSON always returns numbers not strings
        return $row ? [
            'total'     => (int) $row['total'],
            'pending'   => (int) $row['pending'],
            'completed' => (int) $row['completed'],
            'pct'       => (int) $row['pct'],
        ] : ['total' => 0, 'pending' => 0, 'completed' => 0, 'pct' => 0];
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
