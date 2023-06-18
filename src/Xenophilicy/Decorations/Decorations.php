<?php

namespace Xenophilicy\Decorations;

use cooldogedev\BedrockEconomy\BedrockEconomy;
use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\EntityFactory;
use pocketmine\entity\Human;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\plugin\PluginBase;
use pocketmine\world\World;
use Xenophilicy\Decorations\archive\ArchiveManager;
use Xenophilicy\Decorations\commands\DecorationCommand;
use Xenophilicy\Decorations\decoration\Decoration;
use Xenophilicy\Decorations\decoration\DecorationManager;
use Xenophilicy\Decorations\entity\DecorationEntity;

/**
 * Class Decorations
 * @package Xenophilicy\Decorations
 */
class Decorations extends PluginBase {
    
    const CONFIG_VERSION = "1.1.0";
    
    /** @var array */
    public static array $settings;
    /** @var Decorations */
    private static Decorations $instance;
    /** @var BedrockEconomy|null $economy */
    private ?BedrockEconomy $economy;
    /** @var DecorationManager */
    private DecorationManager $decorationManager;
    /** @var ArchiveManager */
    private ArchiveManager $archiveManager;
    
    /**
     * @param string $setting
     * @return int|string|bool
     */
    public static function getSetting(string $setting): bool|int|string
    {
        return self::$settings[$setting] ?? false;
    }
    
    public static function getInstance(): self{
        return self::$instance;
    }
    
    public function getEconomy(): ?BedrockEconomy{
        return $this->economy;
    }
    
    public function getDecorationManager(): DecorationManager{
        return $this->decorationManager;
    }

    /**
     * @throws \JsonException
     */
    public function onDisable(): void
    {
        $archive = $this->getArchiveManager();
        if(is_null($archive)) return;
        $archive->saveData();
    }
    
    public function getArchiveManager(): ?ArchiveManager{
        return $this->archiveManager;
    }
    
    public function onEnable(): void
    {
        $this->saveDefaultConfig();
        self::$instance = $this;
        self::$settings = $this->getConfig()->getAll();
        if(version_compare(self::CONFIG_VERSION, self::$settings["VERSION"], "gt")){
            $this->getLogger()->critical("You've updated Decorations but have an outdated config verion, delete your old config to prevent unwanted errors");
            $this->getServer()->getPluginManager()->disablePlugin($this);
            return;
        }
        $this->saveResource("decorations.json");
        $path = $this->getDecorationDirectory(false);
        if(!is_dir($path)){
            mkdir($path);
            $path = $this->getDecorationDirectory(true);
            foreach(["mug", "table", "television"] as $model){
                $this->saveResource($path . "$model.geo.json");
                $this->saveResource($path . "$model.png");
            }
        }
        $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
        $this->getServer()->getInstance()->getCommandMap()->register("Decorations", new DecorationCommand());
        EntityFactory::getInstance()->register(DecorationEntity::class, function (World $world, CompoundTag $nbt): DecorationEntity {
            return new DecorationEntity(EntityDataHelper::parseLocation($nbt, $world), Human::parseSkinNBT($nbt), $nbt);
        }, ['Decoration']);
        $this->decorationManager = new DecorationManager();
        $this->archiveManager = new ArchiveManager();
        $this->economy = $this->getServer()->getPluginManager()->getPlugin("BedrockEconomy");
        if ($this->economy === null) {
            $this->getLogger()->notice("BedrockEconomy is not installed, so prices will revert to FREE");
        }
    }
    
    public function getDecorationDirectory(bool $internal): string{
        return ($internal ? "decorations" . DIRECTORY_SEPARATOR : $this->getDataFolder() . "decorations" . DIRECTORY_SEPARATOR);
    }
}