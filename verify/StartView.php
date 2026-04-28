<?php
namespace saso\verify;

use saso\framework\Setter;
use saso\framework\View;

final class StartView implements View
{
    use Setter;
    private string $title = '';
    private \Closure $content;
    /** @var list<array{id:int,mode:string,startedAt:string,status:string}> */
    public array $recent = [];

    public function __construct()
    {
        $this->recent = $this->loadRecent();
    }

    public function display(): void
    {
        require_once 'verify/template/start.php';
    }

    public function onRoot(): bool
    {
        return true;
    }

    public function getTitle(): string
    {
        return __('ui.verify.title', [], null, 'Data verification');
    }

    public function getContent(): \Closure
    {
        return $this->content;
    }

    /**
     * @return list<array{id:int,mode:string,startedAt:string,status:string}>
     */
    private function loadRecent(): array
    {
        try {
            $pdo = \saso\repository\DBConnection::getPdo();
            $stmt = $pdo->query(
                'SELECT id, mode, started_at, status'
                .' FROM verification_session'
                .' ORDER BY started_at DESC, id DESC LIMIT 10'
            );
            if ($stmt === false) {
                return [];
            }
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            if (!is_array($rows)) {
                return [];
            }
            $out = [];
            foreach ($rows as $r) {
                $out[] = [
                    'id'        => (int) ($r['id'] ?? 0),
                    'mode'      => (string) ($r['mode'] ?? ''),
                    'startedAt' => (string) ($r['started_at'] ?? ''),
                    'status'    => (string) ($r['status'] ?? ''),
                ];
            }
            return $out;
        } catch (\Throwable) {
            return [];
        }
    }
}
