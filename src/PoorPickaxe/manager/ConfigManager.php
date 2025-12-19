<?php

declare(strict_types=1);

namespace PoorPickaxe\manager;

use pocketmine\item\Item;
use pocketmine\utils\TextFormat;
use PoorPickaxe\Loader;
use customiesdevs\customies\item\CustomiesItemFactory;
use PoorPickaxe\model\MiningPattern;

final class ConfigManager
{
    private const DEFAULT_MINING_MODE = 'cube';
    private const DEFAULT_RADIUS = 1;
    private const DEFAULT_DURABILITY = 3457;
    private const DEFAULT_MINING_EFFICIENCY = 10;

    private array $miningPatterns = [];

    public function __construct(
        private readonly Loader $plugin
    ) {
        $this->loadMiningPatterns();
    }

    private function loadMiningPatterns(): void
    {
        $patterns = $this->plugin->getConfig()->get('mining-patterns', []);
        
        foreach ($patterns as $name => $data) {
            $this->miningPatterns[$name] = new MiningPattern(
                name: $name,
                displayName: $data['display-name'] ?? ucfirst($name),
                width: $data['width'] ?? 1,
                height: $data['height'] ?? 1,
                depth: $data['depth'] ?? 1,
                description: $data['description'] ?? ''
            );
        }

        if (empty($this->miningPatterns)) {
            $this->loadDefaultPatterns();
        }
    }

    private function loadDefaultPatterns(): void
    {
        $this->miningPatterns = [
            'single' => new MiningPattern('single', 'Single Block', 0, 0, 0, 'Mines only the block you hit'),
            'cube' => new MiningPattern('cube', '3x3x3 Cube', 1, 1, 1, 'Mines a 3x3x3 cube around the block'),
            'tunnel' => new MiningPattern('tunnel', '3x3 Tunnel', 1, 1, 0, 'Mines a 3x3 tunnel forward'),
            'vein' => new MiningPattern('vein', 'Vein Miner', 2, 2, 2, 'Mines connected blocks of the same type'),
            'large_cube' => new MiningPattern('large_cube', '5x5x5 Cube', 2, 2, 2, 'Mines a 5x5x5 cube around the block')
        ];
    }

    public function getMiningPattern(string $name): ?MiningPattern
    {
        return $this->miningPatterns[$name] ?? null;
    }

    public function getAllMiningPatterns(): array
    {
        return $this->miningPatterns;
    }

    public function getDefaultMiningMode(): string
    {
        return $this->plugin->getConfig()->get('default-mining-mode', self::DEFAULT_MINING_MODE);
    }

    public function getDurability(): int
    {
        return $this->plugin->getConfig()->get('durability', self::DEFAULT_DURABILITY);
    }

    public function getMiningEfficiency(): int
    {
        return $this->plugin->getConfig()->get('mining-efficiency', self::DEFAULT_MINING_EFFICIENCY);
    }

    public function getItemName(string $miningMode): string
    {
        $template = $this->plugin->getConfig()->get('item-name', '&l&9PoorPickaxe&r');
        $pattern = $this->getMiningPattern($miningMode);
        $displayName = $pattern?->displayName ?? $miningMode;
        
        return TextFormat::colorize(str_replace('{MODE}', $displayName, $template));
    }

    public function getItemLore(string $miningMode): array
    {
        $pattern = $this->getMiningPattern($miningMode);
        $loreTemplate = $this->plugin->getConfig()->get('item-lore', []);
        
        $lore = [];
        foreach ($loreTemplate as $line) {
            $line = str_replace('{MODE}', $pattern?->displayName ?? $miningMode, $line);
            $line = str_replace('{DESCRIPTION}', $pattern?->description ?? '', $line);
            $lore[] = TextFormat::colorize($line);
        }
        
        return $lore;
    }

    public function createPickaxeItem(string $miningMode, int $amount = 1): Item
    {
        $item = CustomiesItemFactory::getInstance()->get('items:spinel_pickaxe');
        
        if ($item === null) {
            throw new \RuntimeException('Failed to create Spinel Pickaxe item');
        }

        $item->setCustomName($this->getItemName($miningMode));
        $item->setLore($this->getItemLore($miningMode));
        $item->setCount($amount);
        
        $nbt = $item->getNamedTag();
        $nbt->setString('mining_mode', $miningMode);
        $item->setNamedTag($nbt);

        return $item;
    }

    public function getMaxBlocksPerBreak(): int
    {
        return $this->plugin->getConfig()->get('max-blocks-per-break', 125);
    }

    public function isVeinMinerEnabled(): bool
    {
        return $this->plugin->getConfig()->get('enable-vein-miner', true);
    }

    public function getBreakDelay(): int
    {
        return $this->plugin->getConfig()->get('break-delay-ticks', 0);
    }

    public function isDevelopmentMode(): bool
    {
        return $this->plugin->getConfig()->get('development-mode', false);
    }

    public function getIgnoredBlocks(): array
    {
        return $this->plugin->getConfig()->get('ignored-blocks', []);
    }

    public function shouldDropItems(): bool
    {
        return $this->plugin->getConfig()->get('drop-items', true);
    }

    public function shouldConsumeHunger(): bool
    {
        return $this->plugin->getConfig()->get('consume-hunger', true);
    }

    public function getHungerMultiplier(): float
    {
        return (float) $this->plugin->getConfig()->get('hunger-multiplier', 1.0);
    }
}