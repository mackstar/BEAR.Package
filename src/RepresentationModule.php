<?php
/**
 * This file is part of the BEAR.Package package
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace BEAR\Package;

use BEAR\Resource\JsonRenderer;
use BEAR\Resource\RenderInterface;
use Ray\Di\AbstractModule;

class RepresentationModule extends AbstractModule
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->bind(RenderInterface::class)->to(JsonRenderer::class);
    }
}
