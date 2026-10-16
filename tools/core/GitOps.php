<?php
declare(strict_types=1);

namespace PulsoCivico\Tools;

class GitOps
{
    /**
     * Devuelve el estado simplificado de git.
     */
    public static function status(): array
    {
        $checkRepo = [];
        $returnCode = 0;
        exec('git rev-parse --is-inside-work-tree 2>&1', $checkRepo, $returnCode);
        if ($returnCode !== 0) {
            return [
                'is_git' => false,
                'message' => 'No es un repositorio Git activo',
                'clean' => true,
                'changes' => [],
            ];
        }

        $statusOutput = [];
        exec('git status --short 2>&1', $statusOutput);
        
        $branchOutput = [];
        exec('git branch --show-current 2>&1', $branchOutput);

        $changes = [];
        foreach ($statusOutput as $line) {
            $line = trim($line);
            if ($line !== '') {
                $status = substr($line, 0, 2);
                $file = trim(substr($line, 2));
                $changes[] = [
                    'status' => $status,
                    'file' => $file,
                ];
            }
        }

        return [
            'branch' => trim($branchOutput[0] ?? 'unknown'),
            'clean' => empty($changes),
            'total_changes' => count($changes),
            'changes' => $changes,
        ];
    }

    /**
     * Compara o revisa diff sin ruido.
     */
    public static function diffSummary(): array
    {
        $output = [];
        exec('git diff --stat 2>&1', $output);
        return [
            'summary' => implode("\n", $output),
        ];
    }

    public static function execGit(string $cmd): array
    {
        $output = [];
        $code = 0;
        exec("git --no-pager {$cmd} 2>&1", $output, $code);
        return [
            'cmd' => "git {$cmd}",
            'code' => $code,
            'output' => $output,
        ];
    }
}

