# PoorPickaxe

### Advanced multi-block mining pickaxe plugin for PocketMine-MP 5.x that allows players to break multiple blocks simultaneously, featuring: Configurable mining patterns, Vein mining, Custom resource packs, and Optimized performance.

## Features

* **Multiple Mining Modes**: Various pre-configured patterns like cubes, tunnels, and strips
* **Vein Mining**: Automatically detects and mines connected ore veins of the same type
* **Highly Configurable**: Full control over patterns, durability, efficiency, and behavior
* **Optimized Performance**: Efficient algorithms with optional delays to prevent server lag
* **Custom Textures**: Built-in support for custom models via a resource pack
* **Modern Architecture**: Clean, maintainable code following PSR standards and PHP 8.1+

## Requirements

* PocketMine-MP 5.0.0+
* PHP 8.1+
* **Customies** (Latest version)

## Installation

1. Download the plugin (`PoorPickaxe.phar`)
2. Place in your server's `plugins/` folder
3. Ensure **Customies** is installed in the same folder
4. Restart the server
5. Plugin will generate `config.yml` automatically

## Configuration

The plugin creates a `config.yml` upon first run. Edit to customize mining patterns and behavior:

```yaml
mining-patterns:
  my_custom_pattern:
    display-name: "My Custom Pattern"
    width: 2      # 5 blocks wide (2 on each side + center)
    height: 1     # 3 blocks tall
    depth: 3      # 7 blocks deep
    description: "A custom mining pattern"
break-delay-ticks: 1
max-blocks-per-break: 128
vein-miner: true

```

The plugin also supports language customization through its internal system.

## Usage

### How It Works

**Multi-Block Mining**:

1. Player equips a PoorPickaxe with a specific mode
2. Player breaks a block within the world
3. The plugin calculates the pattern relative to the player's direction
4. Surrounding blocks are broken automatically based on the selected mode

**Mining Modes Management**:

1. Use commands to switch between available modes
2. Each mode has its own dimensions (Width x Height x Depth)
3. Vein mining mode ignores dimensions to follow ore veins
4. Durability and hunger are consumed based on blocks broken

**Command Display**:

```
§eCurrent Mode§f: §c{ModeName}
§eBlocks Broken§f: §c{Count}

```

### Technical Details

The plugin uses custom item registration and managers:

### Details

* **Item System**: Custom items registered via Customies for high-fidelity models
* **Mining Logic**: Direction-aware coordinate calculations for precise patterns
* **Performance**: Task-based delayed breaking to spread load across ticks
* **Storage**: Player-specific mining mode preferences persistence
* **Optimization**: Configurable limits to prevent massive world edits

### Known Limitations

* Requires Customies for custom item rendering
* Performance may vary if `max-blocks-per-break` is set too high
* Some custom blocks from other plugins might not be detected
* Vein mining is limited by the `max-blocks` configuration

## Architecture

This plugin is designed with modular components:

```
PoorPickaxe/
├── plugin.yml
├── resources/
│   └── config.yml
└── src/
    └── PoorPickaxe/
        ├── Loader.php
        ├── item/
        │   └── PoorPickaxeItem.php
        ├── manager/
        │   ├── ConfigManager.php
        │   └── MiningManager.php
        ├── listener/
        │   └── BlockBreakListener.php
        └── task/
            └── DelayedBreakTask.php

```

## Contributing

This plugin was created for educational purposes and advanced server utility.

## License

This project is released under the MIT License.

## Support

For issues or suggestions, contact Phoenix4041 or visit the GitHub repository.

## Updates & Improvements

### v2.0.0 - Major Refactor

**Features:**

* Complete refactor with modern PHP 8+ features
* Added multiple configurable mining patterns
* Implemented vein mining system
* Added player-specific mining mode preferences
* Improved performance with optimized algorithms
* Added comprehensive configuration options
* Implemented proper permission system
* Added delayed block breaking option

**Technical Implementation:**

* Singleton-based Loader for global API access
* Customies integration for native-feeling custom items
* Coordinate offset mapping for 3D pattern generation
* Efficient event-driven block breaking system
* PSR-compliant naming conventions and structure

## Version Support

| Version | Release Date | Status | Support |
| --- | --- | --- | --- |
| 2.0.0 | December 2025 | 🟢 Active | Full support |
| 1.1.0 | October 2024 | 🔴 Legacy | No support |

**Made with ❤️ by Phoenix4041**