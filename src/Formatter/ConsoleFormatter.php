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
        $record = $record->with(extra: [
            ...$record->extra,
            'super_kernel_timestamp' => $this->decorate(
                $record->datetime->format(self::DATE_FORMAT),
                self::ANSI_FAINT,
            ),
            'super_kernel_level' => $this->decorate(
                '[' . $record->level->getName() . ']',
                $this->levelColor($record->level),
            ),
        ]);

        return rtrim(parent::format($record)) . "\n";
    }

    public function isDecorated(): bool
    {
        return $this->decorated;
    }

    private function decorate(string $value, string $color): string
    {
        if (!$this->decorated) {
            return $value;
        }

        return $color . $value . self::ANSI_RESET;
    }

    private function levelColor(Level $level): string
    {
        return match ($level) {
            Level::Emergency,
            Level::Alert,
            Level::Critical,
            Level::Error   => self::ANSI_RED,
            Level::Warning => self::ANSI_YELLOW,
            Level::Notice  => self::ANSI_CYAN,
            Level::Info,
            Level::Debug => self::ANSI_GREEN,
        };
    }
}
