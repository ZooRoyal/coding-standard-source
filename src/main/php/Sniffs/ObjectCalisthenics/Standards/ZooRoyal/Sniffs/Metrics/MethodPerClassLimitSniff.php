<?php

declare(strict_types=1);

namespace Zooroyal\CodingStandard\Sniffs\ObjectCalisthenics\Standards\ZooRoyal\Sniffs\Metrics;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use Zooroyal\CodingStandard\Sniffs\ObjectCalisthenics\Standards\ZooRoyal\Helper\ClassAnalyzer;
use Zooroyal\CodingStandard\Sniffs\ObjectCalisthenics\Standards\ZooRoyal\Helper\NamingHelper;

final class MethodPerClassLimitSniff implements Sniff
{
    /**
     * @var string
     */
    private const ERROR_MESSAGE = '%s has too many methods: %d. Can be up to %d methods.';

    public int $maxCount = 25;

    /**
     * @return array<int>
     */
    public function register(): array
    {
        return [T_CLASS, T_INTERFACE, T_TRAIT];
    }

    public function process(File $file, $position)
    {
        $methodCount = ClassAnalyzer::getClassMethodCount($file, $position);

        if ($methodCount > $this->maxCount) {
            $typeName = NamingHelper::getTypeName($file, $position);
            $message = sprintf(self::ERROR_MESSAGE, $typeName, $methodCount, $this->maxCount);

            $file->addError($message, $position, 'MethodPerClassLimit');
        }
    }
}
