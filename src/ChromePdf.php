<?php

namespace ChromePdf;

use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

class ChromePdf
{
    public const INPUT_TEXT = 2;
    public const INPUT_FILE = 4;
    public const INPUT_URL = 6;

    protected array $chromeBinaries = [
        'google-chrome',
        'chromium'
    ];

    protected array $chromeFlags = [
        '--no-sandbox',
        '--headless',
        '--disable-gpu',
        '--disable-crash-reporter',
        '--run-all-compositor-stages-before-draw',
        '--print-to-pdf-no-header',
    ];

    protected ?string $binary = null;

    protected bool $tmpFile = true;

    protected string $input;

    protected string $inputType;

    public static function make(string $input, ?string $output = null, int $inputType = self::INPUT_TEXT): string
    {
        return (new static())->setInput($input, $inputType)->print($output);
    }

    public function binary(): ?string
    {
        if ($this->binary) {
            return $this->binary;
        }

        $finder = new ExecutableFinder();
        foreach ($this->chromeBinaries as $chromeBinary) {
            if ($this->binary = $finder->find($chromeBinary)) {
                return $this->binary;
            }
        }

        return null;
    }

    public function setInput(string $input, int $type = self::INPUT_TEXT): static
    {
        $this->input = $input;
        $this->inputType = $type;

        return $this;
    }

    public function tmpFile(bool $status): static
    {
        $this->tmpFile = $status;

        return $this;
    }

    public function setFlag(array|string $flag): static
    {
        if (is_array($flag)) {
            foreach ($flag as $f) {
                $this->setFlag($f);
            }

            return $this;
        }

        $this->chromeFlags[] = $flag;

        return $this;
    }

    public function setFlags(array $flags): static
    {
        $this->chromeFlags = $flags;

        return $this;
    }

    public function getFlags(): array
    {
        return $this->chromeFlags;
    }

    public function print(?string $output = null): string
    {
        $binary = $this->binary();

        $tmpFile = null;
        $input = null;

        switch ($this->inputType) {
            case static::INPUT_TEXT:
                if ($this->tmpFile) {
                    $tmpFile = tempnam(sys_get_temp_dir(), 'chrome-pdf');
                    rename($tmpFile, $tmpFile . '.html');
                    $tmpFile = $tmpFile . '.html';
                    file_put_contents($tmpFile, $this->input);
                }
                $input = $tmpFile ?? 'data:text/html,' . rawurlencode($this->input);
                break;
            case static::INPUT_FILE:
                $input = 'file://' . $this->input;
                break;
            case static::INPUT_URL:
                $input = $this->input;
                break;
        }

        $outputFile = null;
        if (!$output) {
            $outputFile = tempnam(sys_get_temp_dir(), 'chrome-pdf');
        }

        try {
            $process = new Process([
                $binary,
                ...$this->chromeFlags,
                '--print-to-pdf=' . ($output ?? $outputFile),
                $input
            ]);

            $process->run();

            if (!$process->isSuccessful()) {
                throw new \RuntimeException($process->getErrorOutput(), $process->getExitCode());
            }
        } finally {
            if ($tmpFile) {
                unlink($tmpFile);
            }
        }

        if ($outputFile) {
            $result = file_get_contents($outputFile);
            unlink($outputFile);
            return $result;
        }

        return $output;
    }
}