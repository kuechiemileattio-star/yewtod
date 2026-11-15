<?php

namespace App\Jobs;

use App\Models\AiAnalysis;
use App\Services\GeminiAnalyzer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use RuntimeException;
use Throwable;

/**
 * Analyse IA en file d'attente (cahier § 12.1) : évite la limite de temps
 * d'exécution PHP du mutualisé. Le résultat est relu par l'éditeur.
 */
class AnalyzePdfWithAi implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 90;

    public function __construct(public readonly int $analysisId, public readonly string $path, public readonly string $kind = 'publication') {}

    public function handle(GeminiAnalyzer $gemini): void
    {
        $analysis = AiAnalysis::query()->find($this->analysisId);
        if ($analysis === null) {
            return;
        }
        $analysis->forceFill(['status' => 'running'])->save();

        try {
            $analysis->forceFill(['status' => 'done', 'result' => $gemini->analyze($this->path, $this->kind)])->save();
        } catch (RuntimeException $e) {
            // Messages déjà neutres (quota, fichier invalide, échec générique).
            $analysis->forceFill(['status' => 'failed', 'error' => mb_substr($e->getMessage(), 0, 255)])->save();
        } catch (Throwable $e) {
            report($e);
            $analysis->forceFill(['status' => 'failed', 'error' => GeminiAnalyzer::NEUTRAL_MESSAGE])->save();
        }
    }
}
