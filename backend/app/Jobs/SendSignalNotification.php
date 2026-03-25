<?php

namespace App\Jobs;

use App\Models\Signal;
use App\Services\FcmService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendSignalNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 30;

    public function __construct(
        private readonly Signal $signal
    ) {}

    public function handle(FcmService $fcmService): void
    {
        if ($this->signal->notification_sent) {
            Log::debug("Notification already sent for signal #{$this->signal->id}");
            return;
        }

        $result = $fcmService->sendSignalNotification($this->signal);

        Log::info("Signal notification job completed", [
            'signal_id' => $this->signal->id,
            'sent'      => $result['sent'],
            'failed'    => $result['failed'],
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("Signal notification job failed for signal #{$this->signal->id}", [
            'error' => $exception->getMessage(),
        ]);
    }
}
