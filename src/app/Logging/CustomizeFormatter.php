<?php

namespace App\Logging;

use Illuminate\Log\Logger;
use Monolog\Formatter\LineFormatter;
use Monolog\LogRecord;

class CustomizeFormatter extends LineFormatter
{
    /**
     * Laravelのtap設定から呼び出されるメソッド
     * 
     * @param Logger $logger
     * @return void
     */
    public function __invoke(Logger $logger): void
    {
        foreach ($logger->getHandlers() as $handler) {
            $handler->setFormatter($this);
        }
    }

    public function format(LogRecord $record): string
    {
        // 日本時間に変換
        $datetime = $record->datetime->setTimezone(new \DateTimeZone('Asia/Tokyo'));
        
        // カスタムフォーマット
        $output = "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n";
        
        $this->format = $output;
        
        return parent::format($record);
    }
}
