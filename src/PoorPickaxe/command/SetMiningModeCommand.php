<?php

declare(strict_types=1);

namespace PoorPickaxe\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as TF;
use PoorPickaxe\Loader;
use PoorPickaxe\manager\MiningManager;

final class SetMiningModeCommand extends Command
{
    public function __construct(
        private readonly MiningManager $miningManager
    ) {
        parent::__construct(
            'setminingmode',
            'Change your mining mode',
            '/setminingmode <mode>',
            ['smm', 'miningmode']
        );

        $this->setPermission('poorpickaxe.command.setmode');
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void
    {
        if (!$sender instanceof Player) {
            $sender->sendMessage(TF::RED . 'This command can only be used in-game.');
            return;
        }

        if (!$this->testPermission($sender)) {
            return;
        }

        if (empty($args)) {
            $this->showAvailableModes($sender);
            return;
        }

        $mode = strtolower($args[0]);
        $configManager = Loader::getInstance()->getConfigManager();
        $pattern = $configManager->getMiningPattern($mode);

        if ($pattern === null) {
            $sender->sendMessage(TF::RED . "Invalid mining mode: {$mode}");
            $this->showAvailableModes($sender);
            return;
        }

        $this->miningManager->setPlayerMiningMode($sender, $mode);
        
        $sender->sendMessage(TF::GREEN . "Mining mode changed to: " . TF::AQUA . $pattern->displayName);
        $sender->sendMessage(TF::GRAY . "Size: " . $pattern->getSize() . " - " . $pattern->description);
    }

    private function showAvailableModes(CommandSender $sender): void
    {
        $configManager = Loader::getInstance()->getConfigManager();
        $patterns = $configManager->getAllMiningPatterns();
        
        $sender->sendMessage(TF::GOLD . '=== Available Mining Modes ===');
        
        foreach ($patterns as $pattern) {
            $current = '';
            if ($sender instanceof Player) {
                $playerMode = $this->miningManager->getPlayerMiningMode($sender);
                $current = $playerMode === $pattern->name ? TF::GREEN . ' [CURRENT]' : '';
            }
            
            $sender->sendMessage(
                TF::YELLOW . $pattern->name . TF::DARK_GRAY . ' - ' . 
                TF::WHITE . $pattern->displayName . TF::GRAY . ' (' . $pattern->getSize() . ')' . 
                $current
            );
            
            if (!empty($pattern->description)) {
                $sender->sendMessage(TF::GRAY . '  ' . $pattern->description);
            }
        }
        
        $sender->sendMessage(TF::GRAY . 'Usage: /setminingmode <mode>');
    }
}