<?php

namespace Mandryn\Mcp;

final class SqlGuard
{
    public const READ = 'read';
    public const WRITE = 'write';

    /** @var list<string> */
    private const READ_KEYWORDS = ['SELECT', 'SHOW', 'DESCRIBE', 'DESC', 'EXPLAIN', 'WITH'];

    public function __construct(private bool $allowWrites = false)
    {
    }

    /**
     * Classify a statement as 'read' or 'write' and enforce the write gate.
     *
     * @throws GuardException when the statement is empty, multi-statement,
     *                        malformed, or a blocked write
     */
    public function assertAllowed(string $sql): string
    {
        $sql = $this->stripLeadingComments($sql);

        if ($sql === '') {
            throw new GuardException('empty SQL statement');
        }

        $this->assertSingleStatement($sql);

        $firstWord = $this->firstWord($sql);

        $classification = self::WRITE;
        if (in_array($firstWord, self::READ_KEYWORDS, true)) {
            $classification = self::READ;
            if ($firstWord === 'WITH') {
                $classification = in_array($this->mainVerb($sql), self::READ_KEYWORDS, true)
                    ? self::READ
                    : self::WRITE;
            }
        }

        if ($classification === self::WRITE && !$this->allowWrites) {
            throw new GuardException(
                'write statement "' . $firstWord . '" blocked: restart with --allow-writes to permit writes'
            );
        }

        return $classification;
    }

    /**
     * Remove leading whitespace and MySQL comments (`/* ... *​/`, `-- ...`, `# ...`).
     *
     * @throws GuardException on an unterminated block comment
     */
    public function stripLeadingComments(string $sql): string
    {
        $sql = ltrim($sql);

        while ($sql !== '') {
            if (str_starts_with($sql, '/*')) {
                $end = strpos($sql, '*/', 2);
                if ($end === false) {
                    throw new GuardException('unterminated block comment in SQL');
                }
                $sql = ltrim(substr($sql, $end + 2));
            } elseif (str_starts_with($sql, '--') || str_starts_with($sql, '#')) {
                $newline = strpos($sql, "\n");
                $sql = $newline === false ? '' : ltrim(substr($sql, $newline + 1));
            } else {
                break;
            }
        }

        return $sql;
    }

    /**
     * At most one statement: a `;` may only appear as trailing punctuation.
     *
     * @throws GuardException
     */
    private function assertSingleStatement(string $sql): void
    {
        $length = strlen($sql);
        $quote = null;
        $escaped = false;

        for ($i = 0; $i < $length; $i++) {
            $char = $sql[$i];

            if ($quote !== null) {
                if ($escaped) {
                    $escaped = false;
                    continue;
                }
                if ($char === '\\') {
                    $escaped = true;
                    continue;
                }
                if ($char === $quote) {
                    $quote = null;
                }
                continue;
            }

            if ($char === "'" || $char === '"' || $char === '`') {
                $quote = $char;
                continue;
            }

            if ($char === ';') {
                $rest = substr($sql, $i + 1);
                if (trim($rest) !== '') {
                    throw new GuardException('multiple SQL statements are not allowed');
                }

                return;
            }
        }
    }

    private function firstWord(string $sql): string
    {
        return strtoupper($this->wordAt($this->skipLeadingNoise($sql)));
    }

    private function wordAt(string $sql): string
    {
        $word = '';
        $length = strlen($sql);

        for ($i = 0; $i < $length; $i++) {
            $char = $sql[$i];
            if (ctype_alnum($char) || $char === '_') {
                $word .= $char;
            } else {
                break;
            }
        }

        return $word;
    }

    /**
     * Skip whitespace and opening parentheses so wrapped statements like
     * "(SELECT ...)" classify correctly.
     */
    private function skipLeadingNoise(string $sql): string
    {
        $offset = 0;
        $length = strlen($sql);

        while ($offset < $length) {
            $char = $sql[$offset];
            if ($char === ' ' || $char === "\t" || $char === "\n" || $char === "\r" || $char === '(') {
                $offset++;
                continue;
            }
            break;
        }

        return substr($sql, $offset);
    }

    /**
     * For `WITH ...` statements, the verb that matters is the one that follows
     * the final top-level closing parenthesis of the CTE list: `WITH c AS
     * (SELECT ...) DELETE ...` is a write even though its first word is WITH.
     */
    private function mainVerb(string $sql): string
    {
        $length = strlen($sql);
        $depth = 0;
        $quote = null;
        $escaped = false;
        $lastTopLevelClose = -1;

        for ($i = 0; $i < $length; $i++) {
            $char = $sql[$i];

            if ($quote !== null) {
                if ($escaped) {
                    $escaped = false;
                    continue;
                }
                if ($char === '\\') {
                    $escaped = true;
                    continue;
                }
                if ($char === $quote) {
                    $quote = null;
                }
                continue;
            }

            if ($char === "'" || $char === '"' || $char === '`') {
                $quote = $char;
                continue;
            }

            if ($char === '(') {
                $depth++;
            } elseif ($char === ')') {
                $depth--;
                if ($depth === 0) {
                    $lastTopLevelClose = $i;
                }
            }
        }

        if ($lastTopLevelClose === -1) {
            return 'SELECT';
        }

        return $this->wordAt($this->skipLeadingNoise(substr($sql, $lastTopLevelClose + 1)));
    }
}
