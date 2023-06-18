<?php
# MADE BY:
#  __    __                                          __        __  __  __
# /  |  /  |                                        /  |      /  |/  |/  |
# $$ |  $$ |  ______   _______    ______    ______  $$ |____  $$/ $$ |$$/   _______  __    __
# $$  \/$$/  /      \ /       \  /      \  /      \ $$      \ /  |$$ |/  | /       |/  |  /  |
#  $$  $$<  /$$$$$$  |$$$$$$$  |/$$$$$$  |/$$$$$$  |$$$$$$$  |$$ |$$ |$$ |/$$$$$$$/ $$ |  $$ |
#   $$$$  \ $$    $$ |$$ |  $$ |$$ |  $$ |$$ |  $$ |$$ |  $$ |$$ |$$ |$$ |$$ |      $$ |  $$ |
#  $$ /$$  |$$$$$$$$/ $$ |  $$ |$$ \__$$ |$$ |__$$ |$$ |  $$ |$$ |$$ |$$ |$$ \_____ $$ \__$$ |
# $$ |  $$ |$$       |$$ |  $$ |$$    $$/ $$    $$/ $$ |  $$ |$$ |$$ |$$ |$$       |$$    $$ |
# $$/   $$/  $$$$$$$/ $$/   $$/  $$$$$$/  $$$$$$$/  $$/   $$/ $$/ $$/ $$/  $$$$$$$/  $$$$$$$ |
#                                         $$ |                                      /  \__$$ |
#                                         $$ |                                      $$    $$/
#                                         $$/                                        $$$$$$/

namespace Xenophilicy\Decorations\decoration;

use pocketmine\block\Block;
use pocketmine\data\bedrock\EnchantmentIdMap;
use pocketmine\data\bedrock\EnchantmentIds;
use pocketmine\entity\Entity;
use pocketmine\entity\Location;
use pocketmine\entity\Skin;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;
use pocketmine\item\StringToItemParser;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\player\Player;
use Xenophilicy\Decorations\Decorations;
use Xenophilicy\Decorations\entity\DecorationEntity;

/**
 * Class Decoration
 * @package Xenophilicy\Decorations\decoration
 */
class Decoration {
    
    /** @var DecorationCategory */
    private DecorationCategory $category;
    /** @var string */
    private mixed $id;
    /** @var array */
    private mixed $model;
    /** @var float */
    private mixed $scale;
    /** @var string */
    private mixed $format;
    /** @var int */
    private mixed $price;
    /** @var array */
    private array $skinData;
    /** @var string|null */
    private mixed $nametag;
    /** @var int|null */
    private mixed $limit;
    /** @var array|null */
    private mixed $rotation;
    /** @var array|null */
    private mixed $range;
    
    public function __construct(DecorationCategory $category, string $id, array $datum){
        $this->category = $category;
        $this->id = $datum["id"] ?? null;
        $this->model = $datum["model"] ?? [];
        $this->scale = $datum["scale"] ?? 1;
        $this->format = $datum["format"] ?? $id;
        $this->price = $datum["price"] ?? 0;
        $this->limit = $datum["limit"] ?? null;
        $this->nametag = $datum["nametag"] ?? null;
        $this->rotation = $datum["rotation"] ?? [];
        $this->range = $datum["scale-range"] ?? null;
    }
    
    public function getPlayerLimit(): ?int{
        return $this->limit;
    }
    
    public function getCategory(): DecorationCategory{
        return $this->category;
    }
    
    public function getPrice(): int{
        return $this->price;
    }
    
    public function getScaleRange(): ?array{
        return $this->range;
    }
    
    public function buildImage(): bool{
        $path = Decorations::getInstance()->getDecorationDirectory(false);
        if(!file_exists($path . $this->model["texture"])) return false;
        if(!file_exists($path . $this->model["geometry"])) return false;
        $image = imagecreatefrompng($path . $this->model["texture"]);
        $l = (int)@getimagesize($path . $this->model["texture"])[1];
        if(!$l || !$image) return false;
        $bytes = "";
        for($y = 0; $y < $l; $y++){
            for($x = 0; $x < 64; $x++){
                $rgba = @imagecolorat($image, $x, $y);
                $a = ((~((int)($rgba >> 24))) << 1) & 0xff;
                $r = ($rgba >> 16) & 0xff;
                $g = ($rgba >> 8) & 0xff;
                $b = $rgba & 0xff;
                $bytes .= chr($r) . chr($g) . chr($b) . chr($a);
            }
        }
        @imagedestroy($image);
        $this->skinData = [$bytes, file_get_contents($path . $this->model["geometry"])];
        return true;
    }
    
    public function convertToItem(int $amount): Item{
        $item = StringToItemParser::getInstance()->parse(Decorations::$settings["item"]["name"]);
        $item->setCount($amount);
        $item->setCustomName($this->getFormat());
        $item->setLore([Decorations::$settings["item"]["lore"]]);
        $enchant = EnchantmentIdMap::getInstance()->fromId(EnchantmentIds::SHARPNESS);
        $enchant = new EnchantmentInstance($enchant, 1);
        if(Decorations::$settings["item"]["enchantment"]) $item->addEnchantment($enchant);
        $item->getNamedTag()->setString(DecorationEntity::DECO_ID, $this->getId());
        return $item;
    }
    
    public function getFormat(): string{
        return $this->format;
    }
    
    public function getId(): string{
        return $this->id;
    }

    /**
     * @throws \JsonException
     */
    public function spawn(Player $player, Block $block): ?Entity{
        Decorations::getInstance()->getArchiveManager()->getArchive($player->getName())->addSpawned($this->getId(), 1);
        $location = Location::fromObject($block->getPosition()->ceil()->add(.5, 1, .5), $player->getWorld(), $this->getYaw() ?? 0, $this->getPitch() ?? 0);
        $nbt = CompoundTag::create();
        $nbt->setString(DecorationEntity::DECO_ID, $this->getId());
        $nbt->setString(DecorationEntity::OWNER, $player->getName());
        $player->saveNBT();
        /** @var DecorationEntity $entity */
        $skin = new Skin("Decorations", $this->skinData[0], "", $this->model["identifier"], $this->skinData[1]);
        $entity = new DecorationEntity($location, $skin, $nbt);
        $entity->getNetworkProperties()->setFloat(EntityMetadataProperties::SCALE, $this->getScale());
        $entity->sendSkin();
        if(!is_null($this->nametag)) $entity->setNameTag($this->nametag);
        $entity->saveNBT();
        $entity->spawnToAll();
        $entity->sendData($entity->getViewers());
        return $entity;
    }
    
    public function getYaw(): ?int{
        return $this->rotation["yaw"] ?? null;
    }
    
    public function getPitch(): ?int{
        return $this->rotation["pitch"] ?? null;
    }
    
    public function getScale(): float{
        return $this->scale;
    }
}