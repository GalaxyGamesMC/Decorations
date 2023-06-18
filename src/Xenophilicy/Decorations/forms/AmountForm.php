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

namespace Xenophilicy\Decorations\forms;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat as TF;
use Xenophilicy\Decorations\decoration\Decoration;
use BreathTakinglyBinary\libDynamicForms\CustomForm;
use BreathTakinglyBinary\libDynamicForms\Form;

/**
 * Class AmountForm
 * @package Xenophilicy\Decorations\forms
 */
class AmountForm extends CustomForm implements FormConstants {
    
    /** @var Decoration */
    private Decoration $decoration;
    
    public function __construct(Decoration $decoration, int $limit, Form $previousForm){
        $this->decoration = $decoration;
        parent::__construct(self::TITLE, $previousForm);
        $this->addLabel(TF::LIGHT_PURPLE . "How many do you want?");
        $this->addSlider(TF::BLUE . "Amount", 1, $limit, self::AMOUNT);
        $this->addLabel(TF::LIGHT_PURPLE . "Where do you want it?");
        $this->addDropdown(TF::BLUE . "Location", self::LOCATION, [TF::GOLD . "Archive", TF::GOLD . "Inventory"], 1);
    }
    
    /**
     * Children classes should implement this method to properly
     * deal with non-null player responses.
     * @param Player $player
     * @param        $data
     */
    public function onResponse(Player $player, $data): void{
        $form = new ConfirmPurchaseForm($this->decoration, $data[self::AMOUNT], $data[self::LOCATION], $this);
        $player->sendForm($form);
    }
}
