<?php

namespace Xenophilicy\Decorations\entity;

use pocketmine\entity\Human;
use pocketmine\entity\Location;
use pocketmine\entity\Skin;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\Player;
use Xenophilicy\Decorations\decoration\Decoration;
use Xenophilicy\Decorations\Decorations;
use Xenophilicy\Decorations\forms\SettingsForm;

/**
 * Class DecorationEntity
 * @package Xenophilicy\Decorations\entity
 */
class DecorationEntity extends Human {

    const DECO_ID = "decoID";
    const OWNER = "owner";
    public int $height = 1;
    public int $width = 0;
    protected float $gravity = 0;

    private string $owner;

    private string $decoID;

    public function __construct(Location $location, Skin $skin, ?CompoundTag $nbt = null)
    {
        parent::__construct($location, $skin, $nbt);
    }

    public function saveNBT(): CompoundTag
    {
        return parent::saveNBT()
            ->setString(self::DECO_ID, $this->decoID)
            ->setString(self::OWNER, $this->owner);
    }

    public function getOwner(): string{
        return $this->owner;
    }
    
    public function getDecoration(): Decoration{
        return Decorations::getInstance()->getDecorationManager()->getDecoration($this->decoID);
    }

    protected function initEntity(CompoundTag $nbt): void
    {
        parent::initEntity($nbt);
        $this->owner = $nbt->getString(self::OWNER);
        $this->decoID = $nbt->getString(self::DECO_ID);
    }

    public function attack(EntityDamageEvent $source): void
    {
        $source->cancel();
        if ($source instanceof EntityDamageByEntityEvent) {
            $damager = $source->getDamager();
            $entity = $source->getEntity();
            if(!$entity instanceof DecorationEntity) return;
            $source->cancel();
            if(!$damager instanceof Player) return;
            if(
                $entity->getOwner() !== $damager->getName() &&
                !$damager->hasPermission("decorations.override")
            ) return;
            $decoration = $entity->getDecoration();
            $form = new SettingsForm($decoration, null, $entity);
            $damager->sendForm($form);
        }
    }
}