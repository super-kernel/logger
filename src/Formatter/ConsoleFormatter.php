<?php

declare(strict_types=1);

namespace SuperKernel\Logger\Formatter;

use Monolog\Formatter\LineFormatter;
use Monolog\Level;
use Monolog\LogRecord;

use function rtrim;

final class ConsoleFormatter extends LineFormatter
{
    private const string ANSI_RESET = "\033[0m";

    private const string ANSI_FAINT = "\033[2m";

    private const string ANSI_RED = "\033[31m";

    private const string ANSI_GREEN = "\033[32m";

    private const string ANSI_YELLOW = "\033[33m";

    private const string ANSI_CYAN = "\033[36m";

    private const string LINE_FORMAT = '%extra.super_kernel_timestamp% %extra.super_kernel_level% %message%' . "\n";

    private const string DATE_FORMAT = 'Y-m-d\\TH:i:s.vP';

    public function __construct(private readonly bool $decorated = false)
    {
        parent::__construct(
            format: self::LINE_FORMAT,
            dateFormat: self::DATE_FORMAT,
            allowInlineLineBreaks: true,
            ignoreEmptyContextAndExtra: true,
            includeStacktraces: true,
        );
    }

    public function format(LogRecord $record): string
    {
        $timestamp = $record->datetime->format(self::DATE_FORMAT);
        $level = '[' . $record->level->getName() . ']';
        $levelColor = match ($record->level) {
            Level::Emergency,
            Level::Alert,
            Level::Critical,
            Level::Error   => self::ANSI_RED,
            Level::Warning => self::ANSI_YELLOW,
            Level::Notice  => self::ANSI_CYAN,
            Level::Info,
            Level::Debug => self::ANSI_GREEN,
        };

        if ($this->decorated) {
            $timestamp = self::ANSI_FAINT . $timestamp . self::ANSI_RESET;
            $level = $levelColor . $level . self::ANSI_RESET;
        }

        $record = $record->with(extra: [
            ...$record->extra,
            'super_kernel_timestamp' => $timestamp,
            'super_kernel_level'     => $level,
        ]);

        return rtrim(parent::format($record)) . "\n";
    }

    public function isDecorated(): bool
    {
        return $this->decorated;
    }
}
