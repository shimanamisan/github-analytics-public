<?php

namespace App\Logging;

use Monolog\Formatter\LineFormatter;
use Monolog\LogRecord;

class CustomizeFormatter extends LineFormatter
{
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
