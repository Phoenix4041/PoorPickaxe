<?php

declare(strict_types=1);

namespace PoorPickaxe\item;

use customiesdevs\customies\item\component\HandEquippedComponent;
use customiesdevs\customies\item\component\MaxStackSizeComponent;
use customiesdevs\customies\item\CreativeInventoryInfo;
use customiesdevs\customies\item\ItemComponentsTrait;
use pocketmine\item\ItemIdentifier;
use pocketmine\item\Pickaxe;
use pocketmine\item\ToolTier;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use PoorPickaxe\Loader;

final class HammerItem extends Pickaxe implements \customiesdevs\customies\item\ItemComponents
{
    use ItemComponentsTrait {
        getComponents as _getComponents;
    }

    private const MINABLE_BLOCKS = [
        'minecraft:stone', 'minecraft:cobblestone', 'minecraft:stone_slab', 'minecraft:stone_slab2',
        'minecraft:stone_slab3', 'minecraft:stone_slab4', 'minecraft:coal_ore', 'minecraft:iron_ore',
        'minecraft:gold_ore', 'minecraft:diamond_ore', 'minecraft:emerald_ore', 'minecraft:lapis_ore',
        'minecraft:redstone_ore', 'minecraft:lit_redstone_ore', 'minecraft:copper_ore', 
        'minecraft:deepslate', 'minecraft:deepslate_coal_ore', 'minecraft:deepslate_copper_ore',
        'minecraft:deepslate_iron_ore', 'minecraft:deepslate_gold_ore', 'minecraft:deepslate_diamond_ore',
        'minecraft:deepslate_emerald_ore', 'minecraft:deepslate_lapis_ore', 'minecraft:lit_deepslate_redstone_ore',
        'minecraft:obsidian', 'minecraft:crying_obsidian', 'minecraft:netherrack', 'minecraft:nether_gold_ore',
        'minecraft:ancient_debris', 'minecraft:blackstone', 'minecraft:gilded_blackstone',
        'minecraft:basalt', 'minecraft:polished_basalt', 'minecraft:smooth_basalt',
        'minecraft:end_stone', 'minecraft:sandstone', 'minecraft:red_sandstone',
        'minecraft:prismarine', 'minecraft:dark_prismarine', 'minecraft:granite', 'minecraft:diorite',
        'minecraft:andesite', 'minecraft:calcite', 'minecraft:tuff', 'minecraft:dripstone_block',
        'minecraft:amethyst_block', 'minecraft:budding_amethyst',
        'minecraft:raw_iron_block', 'minecraft:raw_gold_block', 'minecraft:raw_copper_block',
        'minecraft:coal_block', 'minecraft:iron_block', 'minecraft:gold_block', 'minecraft:diamond_block',
        'minecraft:emerald_block', 'minecraft:lapis_block', 'minecraft:redstone_block', 'minecraft:copper_block',
        'minecraft:netherite_block', 'minecraft:quartz_block', 'minecraft:quartz_ore',
        'minecraft:nether_brick', 'minecraft:red_nether_brick', 'minecraft:brick_block',
        'minecraft:stonebrick', 'minecraft:mossy_stonebrick', 'minecraft:cracked_stonebrick',
        'minecraft:end_bricks', 'minecraft:purpur_block', 'minecraft:concrete',
        'minecraft:terracotta', 'minecraft:glazed_terracotta', 'minecraft:stained_hardened_clay',
        'minecraft:furnace', 'minecraft:lit_furnace', 'minecraft:dispenser', 'minecraft:dropper',
        'minecraft:observer', 'minecraft:smoker', 'minecraft:blast_furnace', 'minecraft:stonecutter_block',
        'minecraft:grindstone', 'minecraft:anvil', 'minecraft:enchanting_table', 'minecraft:brewing_stand',
        'minecraft:cauldron', 'minecraft:hopper', 'minecraft:beacon', 'minecraft:conduit',
        'minecraft:respawn_anchor', 'minecraft:lodestone', 'minecraft:chain',
        'minecraft:iron_door', 'minecraft:iron_trapdoor', 'minecraft:iron_bars',
        'minecraft:rail', 'minecraft:golden_rail', 'minecraft:detector_rail', 'minecraft:activator_rail',
        'minecraft:lantern', 'minecraft:soul_lantern', 'minecraft:bell',
        'minecraft:ice', 'minecraft:packed_ice', 'minecraft:blue_ice', 'minecraft:magma',
        'minecraft:bone_block', 'minecraft:coral', 'minecraft:coral_block', 'minecraft:mob_spawner'
    ];

    public function __construct(ItemIdentifier $identifier, string $name)
    {
        parent::__construct($identifier, $name, ToolTier::DIAMOND());
        
        $this->initComponent(
            'spinel_pickaxe',
            new CreativeInventoryInfo(
                CreativeInventoryInfo::CATEGORY_EQUIPMENT,
                CreativeInventoryInfo::GROUP_PICKAXE
            )
        );
        
        $this->addComponent(new HandEquippedComponent(true));
        $this->addComponent(new MaxStackSizeComponent(1));
    }

    public function getMaxDurability(): int
    {
        return Loader::getInstance()->getConfigManager()->getDurability();
    }

    protected function getBaseMiningEfficiency(): float
    {
        return (float) Loader::getInstance()->getConfigManager()->getMiningEfficiency();
    }

    public function getComponents(): CompoundTag
    {
        $itemData = $this->_getComponents();
        
        $digger = CompoundTag::create()->setByte('use_efficiency', 1);
        $destroySpeeds = new ListTag();

        foreach (self::MINABLE_BLOCKS as $blockName) {
            $destroySpeeds->push(
                CompoundTag::create()
                    ->setString('block', $blockName)
                    ->setInt('speed', (int) $this->getBaseMiningEfficiency())
            );
        }

        $destroySpeeds->push(
            CompoundTag::create()
                ->setTag(
                    'block',
                    CompoundTag::create()
                        ->setString('tags', "query.any_tag('stone', 'metal', 'iron_pick_diggable')")
                )
                ->setInt('speed', (int) $this->getBaseMiningEfficiency())
        );

        return $itemData->setTag(
            'components',
            $itemData->getCompoundTag('components')
                ->setTag('minecraft:digger', $digger->setTag('destroy_speeds', $destroySpeeds))
        );
    }
}