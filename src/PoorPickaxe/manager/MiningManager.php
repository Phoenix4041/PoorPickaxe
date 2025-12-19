<?php

declare(strict_types=1);

namespace PoorPickaxe\manager;

use pocketmine\block\Block;
use pocketmine\block\BlockTypeIds;
use pocketmine\block\VanillaBlocks;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\world\Position;
use PoorPickaxe\model\MiningPattern;
use PoorPickaxe\task\AsyncBlockBreakTask;

final class MiningManager
{
    private array $playerMiningModes = [];
    private array $breakingPlayers = [];

    public function __construct(
        private readonly ConfigManager $configManager
    ) {}

    public function setPlayerMiningMode(Player $player, string $mode): bool
    {
        $pattern = $this->configManager->getMiningPattern($mode);
        
        if ($pattern === null) {
            return false;
        }

        $this->playerMiningModes[$player->getUniqueId()->toString()] = $mode;
        return true;
    }

    public function getPlayerMiningMode(Player $player): string
    {
        $uuid = $player->getUniqueId()->toString();
        return $this->playerMiningModes[$uuid] ?? $this->configManager->getDefaultMiningMode();
    }

    public function isPlayerBreaking(Player $player): bool
    {
        return isset($this->breakingPlayers[$player->getName()]);
    }

    public function setPlayerBreaking(Player $player, bool $breaking): void
    {
        if ($breaking) {
            $this->breakingPlayers[$player->getName()] = microtime(true);
        } else {
            unset($this->breakingPlayers[$player->getName()]);
        }
    }

    public function getBreakingTime(Player $player): float
    {
        if (!isset($this->breakingPlayers[$player->getName()])) {
            return 0.0;
        }

        return (microtime(true) - $this->breakingPlayers[$player->getName()]) * 1000;
    }

    public function clearPlayerData(Player $player): void
    {
        $uuid = $player->getUniqueId()->toString();
        unset($this->playerMiningModes[$uuid], $this->breakingPlayers[$player->getName()]);
    }

    public function getBlocksToBreak(Position $center, string $miningMode, Block $targetBlock): array
    {
        $pattern = $this->configManager->getMiningPattern($miningMode);
        
        if ($pattern === null) {
            return [];
        }

        if ($miningMode === 'vein' && $this->configManager->isVeinMinerEnabled()) {
            return $this->getVeinBlocks($center, $targetBlock, $pattern);
        }

        return $this->getPatternBlocks($center, $pattern);
    }

    private function getPatternBlocks(Position $center, MiningPattern $pattern): array
    {
        $blocks = [];
        $world = $center->getWorld();
        $maxBlocks = $this->configManager->getMaxBlocksPerBreak();

        if ($pattern->name === '2x2') {
            return $this->get2x2Blocks($center);
        }

        $minX = $center->getFloorX() - $pattern->width;
        $maxX = $center->getFloorX() + $pattern->width;
        $minY = $center->getFloorY() - $pattern->height;
        $maxY = $center->getFloorY() + $pattern->height;
        $minZ = $center->getFloorZ() - $pattern->depth;
        $maxZ = $center->getFloorZ() + $pattern->depth;

        for ($x = $minX; $x <= $maxX; $x++) {
            for ($y = $minY; $y <= $maxY; $y++) {
                for ($z = $minZ; $z <= $maxZ; $z++) {
                    if (count($blocks) >= $maxBlocks) {
                        return $blocks;
                    }

                    $pos = new Vector3($x, $y, $z);
                    
                    if ($pos->equals($center)) {
                        continue;
                    }

                    $block = $world->getBlockAt($x, $y, $z);
                    
                    if ($this->canBreakBlock($block)) {
                        $blocks[] = $pos;
                    }
                }
            }
        }

        return $blocks;
    }

    private function get2x2Blocks(Position $center): array
    {
        $blocks = [];
        $world = $center->getWorld();
        
        $positions = [
            new Vector3($center->getFloorX() + 1, $center->getFloorY(), $center->getFloorZ()),
            new Vector3($center->getFloorX(), $center->getFloorY(), $center->getFloorZ() + 1),
            new Vector3($center->getFloorX() + 1, $center->getFloorY(), $center->getFloorZ() + 1)
        ];

        foreach ($positions as $pos) {
            $block = $world->getBlockAt($pos->getFloorX(), $pos->getFloorY(), $pos->getFloorZ());
            
            if ($this->canBreakBlock($block)) {
                $blocks[] = $pos;
            }
        }

        return $blocks;
    }

    private function getVeinBlocks(Position $start, Block $targetBlock, MiningPattern $pattern): array
    {
        $blocks = [];
        $checked = [];
        $queue = [$start->asVector3()];
        $maxBlocks = $this->configManager->getMaxBlocksPerBreak();
        $world = $start->getWorld();
        $targetTypeId = $targetBlock->getTypeId();

        while (!empty($queue) && count($blocks) < $maxBlocks) {
            $current = array_shift($queue);
            $key = $current->getFloorX() . ':' . $current->getFloorY() . ':' . $current->getFloorZ();

            if (isset($checked[$key])) {
                continue;
            }

            $checked[$key] = true;
            $block = $world->getBlockAt($current->getFloorX(), $current->getFloorY(), $current->getFloorZ());

            if ($block->getTypeId() !== $targetTypeId || !$this->canBreakBlock($block)) {
                continue;
            }

            if (!$current->equals($start)) {
                $blocks[] = $current;
            }

            foreach ([
                $current->add(1, 0, 0),
                $current->add(-1, 0, 0),
                $current->add(0, 1, 0),
                $current->add(0, -1, 0),
                $current->add(0, 0, 1),
                $current->add(0, 0, -1)
            ] as $adjacent) {
                $distance = $start->distance($adjacent);
                $maxDistance = max($pattern->width, $pattern->height, $pattern->depth);
                
                if ($distance <= $maxDistance) {
                    $queue[] = $adjacent;
                }
            }
        }

        return $blocks;
    }

    private function canBreakBlock(Block $block): bool
    {
        $typeId = $block->getTypeId();
        
        if ($typeId === BlockTypeIds::AIR || $typeId === BlockTypeIds::BEDROCK) {
            return false;
        }

        $ignoredBlocks = $this->configManager->getIgnoredBlocks();
        
        foreach ($ignoredBlocks as $ignoredBlock) {
            if ($block instanceof $ignoredBlock) {
                return false;
            }
        }

        return true;
    }

    public function getMiningModeFromItem(?\pocketmine\item\Item $item): ?string
    {
        if ($item === null) {
            return null;
        }

        $nbt = $item->getNamedTag();
        return $nbt->getString('mining_mode', null);
    }
}