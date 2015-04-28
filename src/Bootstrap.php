<?php
/**
 * This file is part of the BEAR.Package package
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace BEAR\Package;

use BEAR\AppMeta\AbstractAppMeta;
use BEAR\Sunday\Extension\Application\AppInterface;
use Doctrine\Common\Cache\ApcCache;
use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\FilesystemCache;
use Ray\Compiler\DiCompiler;
use Ray\Compiler\Exception\NotCompiled;
use Ray\Compiler\ScriptInjector;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

final class Bootstrap
{
    /**
     * @param AbstractAppMeta $appMeta
     * @param string          $contexts
     * @param Cache           $cache
     *
     * @return AppInterface
     */
    public function newApp(AbstractAppMeta $appMeta, $contexts, Cache $cache = null)
    {
        if (is_null($cache)) {
            $cache = function_exists('apc_fetch') ? new ApcCache : new FilesystemCache($appMeta->tmpDir);
        }
        $appId = $appMeta->name . $contexts;
        $app = $cache->fetch($appId);
        if ($app) {
            return $app;
        }
        $app = $this->createAppInstance($appMeta, $contexts);
        $cache->save($appId, $app);

        return $app;
    }

    /**
     * @param AbstractAppMeta $appMeta
     * @param string          $contexts
     *
     * @return AppInterface
     */
    private function createAppInstance(AbstractAppMeta $appMeta, $contexts)
    {
        $contextsArray = array_reverse(explode('-', $contexts));
        $module = new AppMetaModule($appMeta);
        foreach ($contextsArray as $context) {
            $class = $appMeta->name . '\Module\\' . ucwords($context) . 'Module';
            if (! class_exists($class)) {
                $class = 'BEAR\Package\Context\\' . ucwords($context) . 'Module';
            }
            /** @var $module AbstractModule */
            $module->override(new $class($module));
        }
        $module->override(new AppMetaModule($appMeta));
        $injector = new ScriptInjector($appMeta->tmpDir);
        try {
            $app = $injector->getInstance(AppInterface::class);
        } catch (NotCompiled $e) {
            $compiler = new DiCompiler($module, $appMeta->tmpDir);
            $app = $compiler->getInstance(AppInterface::class);
            $compiler->compile();
        }

        return $app;
    }
}
