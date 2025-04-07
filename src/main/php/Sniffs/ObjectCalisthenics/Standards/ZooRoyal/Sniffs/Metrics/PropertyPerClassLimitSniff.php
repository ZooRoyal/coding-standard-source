<?php

declare(strict_types=1);

namespace Zooroyal\CodingStandard\Sniffs\ObjectCalisthenics\Standards\ZooRoyal\Sniffs\Metrics;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use Zooroyal\CodingStandard\Sniffs\ObjectCalisthenics\Standards\ZooRoyal\Helper\ClassAnalyzer;

final class PropertyPerClassLimitSniff implements Sniff
{
    /**
     * @var string
     */
    private const ERROR_MESSAGE = '"%s" has too many properties: %d. Can be up to %d properties.';

    public int $maxCount = 25;

    /**
     * @return array<int>
     */
    public function register(): array
    {
        return [T_CLASS, T_TRAIT];
    }

    public function process(File $file, $position)
    {
        $propertiesCount = ClassAnalyzer::getClassPropertiesCount($file, $position);

        if ($propertiesCount > $this->maxCount) {
            $tokenType = $file->getTokens()[$position]['content'];

            $message = sprintf(self::ERROR_MESSAGE, $tokenType, $propertiesCount, $this->maxCount);
            $file->addError($message, $position, 'PropertyPerClassLimit');
        }
    }
}
