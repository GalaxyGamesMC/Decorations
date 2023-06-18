<?php

namespace Xenophilicy\Decorations\forms;

use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as TF;
use Xenophilicy\Decorations\decoration\Decoration;
use Xenophilicy\Decorations\entity\DecorationEntity;
use BreathTakinglyBinary\libDynamicForms\CustomForm;
use BreathTakinglyBinary\libDynamicForms\Form;

/**
 * Class EditForm
 * @package Xenophilicy\Decorations\forms
 */
class EditForm extends CustomForm implements FormConstants {
    
    /** @var Decoration */
    private Decoration $decoration;
    /** @var DecorationEntity */
    private DecorationEntity $entity;
    /** @var array */
    private array $steps = [self::X => [], self::Y => [], self::Z => []];
    /** @var array */
    private array $scale = [];
    
    public function __construct(Decoration $decoration, DecorationEntity $entity, Form $previousForm){
        $this->decoration = $decoration;
        $this->entity = $entity;
        parent::__construct(self::TITLE, $previousForm);
        $this->addSlider(TF::GOLD . "Yaw", 0, 360, self::YAW, -1, (int)$entity->getLocation()->getYaw());
        $this->addSlider(TF::GOLD . "Pitch", 0, 180, self::PITCH, -1, (int)$entity->getLocation()->getPitch());
        for($i = -0.5; $i < 0.6; $i += 0.1){
            $this->steps[self::X][] = (string)($i + $entity->getPosition()->getX());
            $this->steps[self::Y][] = (string)($i + $entity->getPosition()->getY());
            $this->steps[self::Z][] = (string)($i + $entity->getPosition()->getZ());
        }
        $this->addStepSlider(TF::GOLD . "X", self::X, $this->steps[self::X], array_search($entity->getPosition()->getX(), $this->steps[self::X]));
        $this->addStepSlider(TF::GOLD . "Y", self::Y, $this->steps[self::Y], array_search($entity->getPosition()->getY(), $this->steps[self::Y]));
        $this->addStepSlider(TF::GOLD . "Z", self::Z, $this->steps[self::Z], array_search($entity->getPosition()->getZ(), $this->steps[self::Z]));
        $range = $this->decoration->getScaleRange();
        if(!is_null($range)){
            for($i = $range["min"]; $i < $range["max"]; $i += 0.1) $this->scale[] = (string)($i);
            $this->addStepSlider(TF::GOLD . "Scale", self::SCALE, $this->scale, array_search($entity->getScale(), $this->scale));
        }
    }
    
    /**
     * Children classes should implement this method to properly
     * deal with non-null player responses.
     * @param Player $player
     * @param        $data
     */
    public function onResponse(Player $player, $data): void{
        $this->entity->getLocation()->yaw = $data[self::YAW];
        $this->entity->getLocation()->pitch = $data[self::PITCH];
        $this->entity->x = (float)($this->steps[self::X][$data[self::X]]);
        $this->entity->y = (float)($this->steps[self::Y][$data[self::Y]]);
        $this->entity->z = (float)($this->steps[self::Z][$data[self::Z]]);
        if(!is_null($this->decoration->getScaleRange())) $this->entity->getNetworkProperties()->setFloat(EntityMetadataProperties::SCALE, $this->scale[$data[self::SCALE]]);
        $form = new AlertForm(TF::GREEN . "Your options have been saved", $this->getPreviousForm());
        $player->sendForm($form);
    }
}
