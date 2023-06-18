<?php

namespace Xenophilicy\Decorations;

use pocketmine\block\VanillaBlocks;
use pocketmine\event\entity\EntitySpawnEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\utils\TextFormat as TF;
use Xenophilicy\Decorations\entity\DecorationEntity;

/**
 * Class EventListener
 * @package Xenophilicy\Decorations
 */
class EventListener implements Listener {
    
    /** @var Decorations */
    private $plugin;
    
    public function __construct(Decorations $plugin){
        $this->plugin = $plugin;
    }
    
    public function onDecorationSpawn(EntitySpawnEvent $event): void{
        $entity = $event->getEntity();
        if(!$entity instanceof DecorationEntity) return;
        $decoration = $entity->getDecoration();
        $entity->getNetworkProperties()->setFloat(EntityMetadataProperties::SCALE, $decoration->getScale());
        $entity->sendData($entity->getViewers());
    }

    /**
     * @priority HIGHEST
     * @param PlayerInteractEvent $event
     * @throws \JsonException
     */
    public function onInteract(PlayerInteractEvent $event): void{
        if($event->isCancelled()) return;
        $item = $event->getItem();
        $player = $event->getPlayer();
        if($event->getAction() !== PlayerInteractEvent::RIGHT_CLICK_BLOCK) return;
        if($item->getBlock()->isSameState(VanillaBlocks::BED())) return;
        if($item->getNamedTag()->getTag(DecorationEntity::DECO_ID) === null) return;
        var_dump($item->getNamedTag()->getTag(DecorationEntity::DECO_ID));
        $event->cancel();
        $id = $item->getNamedTag()->getString(DecorationEntity::DECO_ID);
        $decoration = Decorations::getInstance()->getDecorationManager()->getDecoration($id);
        $archive = Decorations::getInstance()->getArchiveManager()->getArchive($player->getName());
        $owned = $archive->getEntry($id)->getSpawned();
        $limit = ($decoration->getPlayerLimit() ?? 64) - $owned;
        if($limit === 0){
            $player->sendMessage(TF::GREEN . "You already have the maximum allowed amount of this decoration.");
            return;
        }
        $decoration->spawn($player, $event->getBlock());
        $player->sendMessage(TF::GREEN . "Decoration has been placed");
        $player->getInventory()->removeItem($item->setCount(1));
    }
}