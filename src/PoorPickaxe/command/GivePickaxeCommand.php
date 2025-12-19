<?php

declare(strict_types=1);

namespace PoorPickaxe\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat as TF;
use PoorPickaxe\Loader;

final class GivePickaxeCommand extends Command
{
    public function __construct(
        private readonly Loader $plugin
    ) {
        parent::__construct(
            'givepickaxe',
            'Give a PoorPickaxe to a player',
            '/givepickaxe <player> [amount] [mode]',
            ['gp', 'givehammer']
        );

        $this->setPermission('poorpickaxe.command.give');
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void
    {
        if (!$this->testPermission($sender)) {
            return;
        }

        $targetName = $args[0] ?? ($sender instanceof Player ? $sender->getName() : null);
        
        if ($targetName === null) {
            $sender->sendMessage(TF::RED . 'Usage: ' . $this->getUsage());
            return;
        }

        $target = Server::getInstance()->getPlayerByPrefix($targetName);
        
        if (!$target instanceof Player) {
            $sender->sendMessage(TF::RED . "Player '{$targetName}' is not online.");
            return;
        }

        $amount = 1;
        if (isset($args[1]) && is_numeric($args[1])) {
            $amount = max(1, min(64, (int) $args[1]));
        }

        $configManager = $this->plugin->getConfigManager();
        $miningMode = $args[2] ?? $configManager->getDefaultMiningMode();
        
        $pattern = $configManager->getMiningPattern($miningMode);
        if ($pattern === null) {
            $availableModes = implode(', ', array_keys($configManager->getAllMiningPatterns()));
            $sender->sendMessage(TF::RED . "Invalid mining mode. Available modes: {$availableModes}");
            return;
        }

        $item = $configManager->createPickaxeItem($miningMode, $amount);

        if (!$target->getInventory()->canAddItem($item)) {
            $target->getWorld()->dropItem($target->getPosition(), $item);
            $target->sendMessage(TF::YELLOW . "Your inventory was full! Items dropped on the ground.");
        } else {
            $target->getInventory()->addItem($item);
        }

        $target->sendMessage(TF::GREEN . "You received {$amount}x PoorPickaxe ({$pattern->displayName})");
        
        if ($sender !== $target) {
            $sender->sendMessage(TF::GREEN . "{$target->getName()} received {$amount}x PoorPickaxe ({$pattern->displayName})");
        }
    }
}