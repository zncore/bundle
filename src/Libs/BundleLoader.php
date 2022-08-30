<?php

namespace ZnCore\Bundle\Libs;

use ZnCore\Arr\Helpers\ArrayHelper;
use ZnCore\Bundle\Base\BaseBundle;
use ZnCore\Bundle\Base\BaseLoader;
use ZnCore\ConfigManager\Interfaces\ConfigManagerInterface;
use ZnCore\Container\Helpers\ContainerHelper;
use ZnCore\Instance\Helpers\ClassHelper;
use ZnCore\Instance\Helpers\InstanceHelper;

/**
 * Загрузчик бандлов.
 */
class BundleLoader
{

    private $bundles = [];
    private $import = [];
    private $loadersConfig = [];

    public function __construct(array $bundles = [], array $import = [])
    {
        $this->addBundles($bundles);
        $this->import = $import;
    }

    /**
     * Добавить бандлы.
     *
     * @param array $bundles Массив описания бандлов
     */
    protected function addBundles(array $bundles)
    {
        foreach ($bundles as $bundleDefinition) {
            $bundleInstance = $this->createBundleInstance($bundleDefinition);
            $bundleClass = get_class($bundleInstance);
            if (!isset($this->bundles[$bundleClass])) {
                if ($bundleInstance->deps()) {
                    $this->addBundles($bundleInstance->deps());
                }
                $this->bundles[$bundleClass] = $bundleInstance;
            }
        }
    }

    /**
     * Зарегистрировать загрузчик конфигурации бандла.
     *
     * @param string $name Имя загрузчика
     * @param object | string | array $loader Объявление загрузчика
     */
    public function registerLoader(string $name, $loader)
    {
        $this->loadersConfig[$name] = $loader;
    }

    /**
     * Загрузить все конфиги приложения.
     *
     * @param string $appName Имя приложения
     */
    public function loadMainConfig(string $appName): void
    {
        $loaders = $this->getLoadersConfig();
        $loaders = ArrayHelper::extractByKeys($loaders, $this->import);
        foreach ($loaders as $loaderName => $loaderDefinition) {
            $this->load($loaderName, $loaderDefinition);
        }
    }

    private function getLoadersConfig()
    {
        return $this->loadersConfig;
    }

    /**
     * Создать объект бандла.
     *
     * @param object | string | array $bundleDefinition Объявление бандла
     * @return BaseBundle
     * @throws \ZnCore\Contract\Common\Exceptions\InvalidConfigException
     */
    private function createBundleInstance($bundleDefinition): BaseBundle
    {
        /** @var BaseBundle $bundleInstance */
        if (is_object($bundleDefinition)) {
            $bundleInstance = $bundleDefinition;
        } elseif (is_string($bundleDefinition)) {
            $bundleInstance = InstanceHelper::create($bundleDefinition, [['all']]);
        } elseif (is_array($bundleDefinition)) {
            $bundleInstance = InstanceHelper::create($bundleDefinition);
        }
        return $bundleInstance;
    }

    private function load(string $loaderName, $loaderDefinition): void
    {
        /** @var BaseLoader $loaderInstance */
        $loaderInstance = ClassHelper::createObject($loaderDefinition);
        if ($loaderInstance->getName() == null) {
            $loaderInstance->setName($loaderName);
        }
        $bundles = $this->filterBundlesByLoader($this->bundles, $loaderName);
        $configManager = ContainerHelper::getContainer()->get(ConfigManagerInterface::class);
        $configManager->set('bundles', $bundles);
        $loaderInstance->loadAll($bundles);
    }

    private function filterBundlesByLoader(array $bundles, string $loaderName): array
    {
        $resultBundles = [];
        foreach ($bundles as $bundle) {
            /** @var BaseBundle $bundle */
            $importList = $bundle->getImportList();
            if (in_array($loaderName, $importList) || in_array('all', $importList)) {
                $resultBundles[] = $bundle;
            }
        }
        return $resultBundles;
    }
}
