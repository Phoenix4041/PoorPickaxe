<?php

declare(strict_types=1);

namespace PoorPickaxe;

use customiesdevs\customies\item\CustomiesItemFactory;
use pocketmine\plugin\PluginBase;
use pocketmine\resourcepacks\ZippedResourcePack;
use pocketmine\utils\SingletonTrait;
use pocketmine\utils\TextFormat;
use PoorPickaxe\command\GivePickaxeCommand;
use PoorPickaxe\command\SetMiningModeCommand;
use PoorPickaxe\item\HammerItem;
use PoorPickaxe\listener\BlockBreakListener;
use PoorPickaxe\manager\ConfigManager;
use PoorPickaxe\manager\MiningManager;
use Symfony\Component\Filesystem\Path;

final class Loader extends PluginBase
{
    use SingletonTrait;

    private ConfigManager $configManager;
    private MiningManager $miningManager;

    protected function onLoad(): void
    {
        self::setInstance($this);
    }

    protected function onEnable(): void
    {
        $this->initializeManagers();
        $this->registerCommands();
        $this->registerListeners();
        $this->loadResourcePack();
        $this->registerCustomItem();

        $this->getLogger()->info(TextFormat::GREEN . 'PoorPickaxe has been enabled successfully!');
    }

    protected function onDisable(): void
    {
        $this->getLogger()->info(TextFormat::RED . 'PoorPickaxe has been disabled.');
    }

    private function initializeManagers(): void
    {
        $this->saveDefaultConfig();
        $this->configManager = new ConfigManager($this);
        $this->miningManager = new MiningManager($this->configManager);
    }

    private function registerCommands(): void
    {
        $commandMap = $this->getServer()->getCommandMap();
        $commandMap->registerAll('PoorPickaxe', [
            new SetMiningModeCommand($this->miningManager),
            new GivePickaxeCommand($this)
        ]);
    }

    private function registerListeners(): void
    {
        $pluginManager = $this->getServer()->getPluginManager();
        $pluginManager->registerEvents(new BlockBreakListener($this->miningManager), $this);
    }

    private function loadResourcePack(): void
    {
        $this->saveResource('poorpick.zip');

        $rpManager = $this->getServer()->getResourcePackManager();
        $packPath = Path::join($this->getDataFolder(), 'poorpick.zip');

        $rpManager->setResourceStack(
            array_merge(
                $rpManager->getResourceStack(),
                [new ZippedResourcePack($packPath)]
            )
        );

        $reflection = new \ReflectionProperty($rpManager, 'serverForceResources');
        $reflection->setValue($rpManager, true);
    }

    private function registerCustomItem(): void
    {
        CustomiesItemFactory::getInstance()->registerItem(
            HammerItem::class,
            'items:spinel_pickaxe',
            'Spinel'
        );
    }

    public function getConfigManager(): ConfigManager
    {
        return $this->configManager;
    }

    public function getMiningManager(): MiningManager
    {
        return $this->miningManager;
    }

    public function isDevelopmentMode(): bool
    {
        return $this->configManager->isDevelopmentMode();
    }
}