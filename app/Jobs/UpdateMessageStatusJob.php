<?php

namespace App\Jobs;

use App\Models\MessageBatch;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateMessageStatusJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $jobBatchId,
        public int $userId,
        public string $status
    ) {}

    public function handle()
    {
        if ($this->batch()->cancelled()) {
            return;
        }

        $batch = MessageBatch::find($this->jobBatchId);
        
        if (!$batch) {
            return;
        }

        $stats = $batch->stats ?? [
            'total' => 0,
            'success' => 0,
            'failed' => 0,
            'pending' => 0
        ];

        if ($this->status === 'success') {
            $stats['success']++;
        } else {
            $stats['failed']++;
        }

        $stats['pending'] = $stats['total'] - ($stats['success'] + $stats['failed']);
        
        $batch->update([
            'stats' => $stats,
            'status' => $stats['pending'] > 0 ? 'processing' : 'completed'
        ]);
    }
}