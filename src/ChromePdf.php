<?php

namespace ChromePdf;

use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

class ChromePdf
{
    public const INPUT_TEXT = 2;
    public const INPUT_FILE = 4;
    public const INPUT_URL = 6;

    /**
     * @var array|string[] List of potential binary names
     */
    protected array $chromeBinaries = [
        'google-chrome',
        'chromium'
    ];

    /**
     * @var array|string[] List of flags to be passed to chrome during rendering
     */
    protected array $chromeFlags = [
        '--no-sandbox',
        '--headless',
        '--disable-gpu',
        '--disable-crash-reporter',
        '--print-to-pdf-no-header',
    ];

    /**
     * @var string|null Cached path to chrome binary
     */
    protected ?string $binary = null;

    /**
     * @var bool Flag to use temporary file during rendering
     */
    protected bool $tmpFile = true;

    /**
     * @var string Content to be rendered
     */
    protected string $input;

    /**
     * @var string What type of input are we handling
     */
    protected string $inputType;

    /**
     * Helper method to render a pdf using the default configuration
     *
     * @param string $input
     * @param string|null $output
     * @param int $inputType
     * @return string
     */
    public static function make(string $input, ?string $output = null, int $inputType = self::INPUT_TEXT): string
    {
        return (new static())->setInput($input, $inputType)->print($output);
    }

    /**
     * Find the chrome binary to use
     *
     * @return string|null
     */
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

    /**
     * Set the content to be rendered
     *
     * @param string $input
     * @param int $type
     * @return $this
     */
    public function setInput(string $input, int $type = self::INPUT_TEXT): static
    {
        $this->input = $input;
        $this->inputType = $type;

        return $this;
    }

    /**
     * Set if a temporary file should be used or not
     *
     * @param bool $status
     * @return $this
     */
    public function tmpFile(bool $status): static
    {
        $this->tmpFile = $status;

        return $this;
    }

    /**
     * Set Chrome flag(s)
     *
     * @param array|string $flag
     * @return $this
     */
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

    /**
     * Replace all Chrome flags
     *
     * @param array $flags
     * @return $this
     */
    public function setFlags(array $flags): static
    {
        $this->chromeFlags = $flags;

        return $this;
    }

    /**
     * Remove a Chrome flag
     *
     * @param array|string $flag
     * @return $this
     */
    public function clearFlag(array|string $flag): static
    {
        if (is_array($flag)) {
            foreach ($flag as $f) {
                $this->clearFlag($f);
            }

            return $this;
        }

        if ($index = array_search($flag, $this->chromeFlags)) {
            unset($this->chromeFlags[$index]);
            $this->chromeFlags = array_values($this->chromeFlags);
        }

        return $this;
    }

    /**
     * Get Chrome flags currently set
     *
     * @return array|string[]
     */
    public function getFlags(): array
    {
        return $this->chromeFlags;
    }

    /**
     * Render a pdf
     *
     * @param string|null $output
     * @return string
     */
    public function print(?string $output = null): string
    {
        $binary = $this->binary();

        if (!$binary) {
            throw new \RuntimeException('A Chrome binary could not be found found');
        }

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