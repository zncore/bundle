<?php

namespace ZnCore\Bundle\Base;

use Psr\Container\ContainerInterface;
use ZnCore\ConfigManager\Interfaces\ConfigManagerInterface;
use ZnCore\ConfigManager\Traits\ConfigManagerAwareTrait;
use ZnCore\Container\Traits\ContainerAttributeTrait;

/**
 * Абстрактный класс импорта конкретной конфиурации бандла.
 */
abstract class BaseLoader
{

    use ContainerAttributeTrait;
    use ConfigManagerAwareTrait;

    /**
     * @var string Имя загрузчика.
     * 
     * Обычно имя загрузчика и имя метода в бандле совпадают.
     */
    protected $name;

    /**
     * Загрузить конфигурации из списка бандлов.
     * 
     * @param array $bundles
     */
    abstract public function loadAll(array $bundles): void;

    public function __construct(ContainerInterface $container, ConfigManagerInterface $configManager)
    {
        $this->setContainer($container);
        $this->setConfigManager($configManager);
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * Загрузить конфигурации из одного бандла.
     * 
     * @param BaseBundle $bundle
     * @return array
     */
    protected function load(BaseBundle $bundle): array
    {
        if (!$this->isAllow($bundle)) {
            return [];
        }
        return call_user_func([$bundle, $this->getName()]);
    }

    /**
     * Проверяет, доступна ли конфигурация у бандла.
     * 
     * @param BaseBundle $bundle
     * @return bool
     */
    protected function isAllow(BaseBundle $bundle): bool
    {
        return method_exists($bundle, $this->getName());
    }
}
