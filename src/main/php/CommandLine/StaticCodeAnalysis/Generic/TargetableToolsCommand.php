<?php

declare(strict_types=1);

namespace Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic;

use Override;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\ToolCommandFacet\TargetableInputFacet;

abstract class TargetableToolsCommand extends AbstractToolCommand
{
    public function __construct(private readonly TargetableInputFacet $targetableFacet, ?string $name = null)
    {
        parent::__construct($name);
    }

    #[Override]
    protected function configure(): void
    {
        $this->setDefinition($this->targetableFacet->getInputDefinition());
    }
}
