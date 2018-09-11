<?php

declare(strict_types=1);

namespace BestIt\Sniffs;

use PHP_CodeSniffer\Files\File;
use function explode;
use function preg_match;
use function sprintf;
use function str_replace;

/**
 * The basic calls for checking sniffs against files.
 *
 * @package BestIt\Sniffs
 * @author Bjoern Lange <bjoern.lange@bestit-online.de>
 */
trait DefaultSniffIntegrationTestTrait
{
    /**
     * Returns a list of files which start with correct*
     *
     * @return array With the path to a file as the first parameter.
     */
    public function getCorrectFileListAsDataProvider(): array
    {
        $providerFiles = [];

        foreach (glob($this->getFixturePath() . '/correct/*.php') as $file) {
            $providerFiles[basename($file)] = [$file];
        }

        return $providerFiles;
    }

    /**
     * Test that the given files contain no errors.
     *
     * @param string $file Provided file to test
     *
     * @return void
     *
     * @dataProvider getCorrectFileListAsDataProvider
     */
    public function testCorrect(string $file): void
    {
        $this->assertFileCorrect($file);
    }

    /**
     * Tests files which have to be without errors.
     *
     * @param string $file File to test
     *
     * @return void
     */
    abstract protected function assertFileCorrect(string $file): void;

    /**
     * Tests errors.
     *
     * @param string $file Fixture file
     * @param string $error Error code
     * @param int[] $lines Lines where the error code occurs
     * @param bool $withFixable Should we checked the fixed version?
     *
     * @return void
     *
     * @dataProvider getErrorAsserts
     */
    public function testErrors(string $file, string $error, array $lines, bool $withFixable = false): void
    {
        $report = $this->assertErrorsInFile($file, $error, $lines);

        if ($withFixable) {
            $this->assertAllFixedInFile($report);
        }
    }

    /**
     * Asserts all errors in a given file.
     *
     * @param string $file Filename of the fixture
     * @param string $error Error code
     * @param int[] $lines Array of lines where the error code occurs
     * @param array $sniffProperties Array of sniff properties
     *
     * @return File The php cs file
     */
    abstract protected function assertErrorsInFile(
        string $file,
        string $error,
        array $lines,
        array $sniffProperties = []
    ): File;

    /**
     * Returns data for errors.
     *
     * @return array List of error data (Filepath, error code, error lines, fixable)
     */
    public function getErrorAsserts(): array
    {
        return $this->loadAssertData();
    }

    /**
     * Loads the assertion data out of the file names.
     *
     * The file name gives information about which errors in which line should occur.
     * Example files would be ErrorCode.1.php, ErrorCode.1,2,3.php, ErrorCode.1,2,3.fixed.php. The error code must be
     * the original code value from your sniff, the numbers after the first dot are the erroneous lines.
     *
     * If you provide an additional file which is suffixed with "fixed" then this is the correct formatted file for its
     * erroneous sibling.
     *
     * @param bool $forErrors Load data for errors?
     *
     * @return array The assert data as data providers.
     */
    private function loadAssertData(bool $forErrors = true): array
    {
        //
        $pattern = '/(?P<code>[\w]*)(\(\d\))?\.(?P<errorLines>[\d\,]*)(?P<fixedSuffix>\.fixed)?\.php/';
        $errorData = [];

        foreach ($this->getFixtureFiles($forErrors) as $file) {
            $fileName = basename($file);
            $matches = [];

            if (preg_match($pattern, $fileName, $matches)) {
                if (@$matches['fixedSuffix']) {
                    $errorData[str_replace('.fixed', '', $fileName)][] = true;
                } else {
                    $errorData[$fileName] = [
                        $file,
                        $matches['code'],
                        array_map('intval', explode(',', $matches['errorLines']))
                    ];
                }
            }
        }

        return $errorData;
    }

    /***
     * Returns the test files for errors or warnings.
     *
     * @param bool $forErrors Load data for errors?
     *
     * @return array The testable files.
     */
    private function getFixtureFiles(bool $forErrors = true): array
    {
        return glob(sprintf(
            $this->getFixturePath() . '/with_%s/*.php',
            $forErrors ? 'errors' : 'warnings'
        )) ?: [];
    }
}