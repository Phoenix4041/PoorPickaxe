<?php

declare(strict_types=1);

namespace PoorPickaxe\listener;

use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\TextFormat;
use PoorPickaxe\item\HammerItem;
use PoorPickaxe\Loader;
use PoorPickaxe\manager\MiningManager;

final class BlockBreakListener implements Listener
{
    public function __construct(
        private readonly MiningManager $miningManager
    ) {}

    /**
     * @priority HIGHEST
     * @ignoreCancelled true
     */
    public function onBlockBreak(BlockBreakEvent $event): void
    {
        $player = $event->getPlayer();
        $item = $event->getItem();

        if (!$item instanceof HammerItem) {
            return;
        }

        if ($this->miningManager->isPlayerBreaking($player)) {
            return;
        }

        $this->miningManager->setPlayerBreaking($player, true);
        
        $center = $event->getBlock()->getPosition();
        $miningMode = $this->miningManager->getMiningModeFromItem($item) 
            ?? $this->miningManager->getPlayerMiningMode($player);

        $blocksToBreak = $this->miningManager->getBlocksToBreak(
            $center,
            $miningMode,
            $event->getBlock()
        );

        $configManager = Loader::getInstance()->getConfigManager();
        $delay = $configManager->getBreakDelay();

        if ($delay > 0 && !empty($blocksToBreak)) {
            $this->scheduleBlockBreaking($player, $blocksToBreak, $delay);
        } else {
            $this->breakBlocks($player, $blocksToBreak);
        }

        if (Loader::getInstance()->isDevelopmentMode()) {
            $time = $this->miningManager->getBreakingTime($player);
            $count = count($blocksToBreak);
            Loader::getInstance()->getLogger()->info(
                TextFormat::GREEN . "Broke {$count} blocks in {$time}ms using mode: {$miningMode}"
            );
        }

        $this->miningManager->setPlayerBreaking($player, false);
    }

    private function breakBlocks(\pocketmine\player\Player $player, array $blocks): void
    {
        if (!$player->isOnline()) {
            return;
        }

        foreach ($blocks as $pos) {
            if (!$player->isOnline()) {
                break;
            }

            $player->breakBlock($pos);
        }
    }

    private function scheduleBlockBreaking(\pocketmine\player\Player $player, array $blocks, int $delay): void
    {
        $chunked = array_chunk($blocks, 5);
        $tick = 0;

        foreach ($chunked as $chunk) {
            Loader::getInstance()->getScheduler()->scheduleDelayedTask(
                new ClosureTask(fn() => $this->breakBlocks($player, $chunk)),
                $tick
            );
            $tick += $delay;
        }
    }

    public function onPlayerQuit(PlayerQuitEvent $event): void
    {
        $this->miningManager->clearPlayerData($event->getPlayer());
    }
}