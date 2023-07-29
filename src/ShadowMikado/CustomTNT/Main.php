<?php

namespace ShadowMikado\CustomTNT;

use customiesdevs\customies\block\CustomiesBlockFactory;
use customiesdevs\customies\block\Material;
use customiesdevs\customies\block\Model;
use customiesdevs\customies\item\CreativeInventoryInfo;
use pocketmine\block\BlockBreakInfo;
use pocketmine\block\BlockIdentifier;
use pocketmine\block\BlockTypeIds;
use pocketmine\block\BlockTypeInfo;
use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\EntityFactory;
use pocketmine\event\Listener;
use pocketmine\inventory\CreativeInventory;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\plugin\PluginBase;
use pocketmine\resourcepacks\ZippedResourcePack;
use pocketmine\utils\Config;
use pocketmine\utils\SingletonTrait;
use pocketmine\world\World;
use ShadowMikado\CustomTNT\Blocks\CustomTNT;
use ShadowMikado\CustomTNT\Entities\CustomPrimedTNT;
use Symfony\Component\Filesystem\Path;

class Main extends PluginBase implements Listener
{
    use SingletonTrait;
    public static Config $config;

    public function onLoad(): void
    {
        self::setInstance($this);
    }

    public function onEnable(): void
    {
        $this->getServer()->getPluginManager()->registerEvents($this, $this);

        $this->saveDefaultConfig();
        $this->saveResource("CustomTNT.mcpack");
        self::$config = $this->getConfig();

        $rpManager = $this->getServer()->getResourcePackManager();
		$rpManager->setResourceStack(array_merge($rpManager->getResourceStack(), [new ZippedResourcePack(Path::join($this->getDataFolder(), "CustomTNT.mcpack"))]));
		(new \ReflectionProperty($rpManager, "serverForceResources"))->setValue($rpManager, true);

        $id = BlockTypeIds::newId();
        $config = $this->getConfig();

        $geo = $config->get("Textures")["Geometry"];

        if ($geo == "") {
            $geo = null;
        }

        $textures = $config->get("Textures");

        $materials = [];
        foreach (["Up", "Down", "North", "South", "East", "West"] as $direction) {
            $material = new Material(constant("customiesdevs\customies\block\Material::TARGET_" . strtoupper($direction)), $textures[$direction], Material::RENDER_METHOD_ALPHA_TEST);
            $materials[] = $material;
        }

        $model = new Model($materials, $geo, new Vector3(-8, 0, -8), new Vector3(16, 16, 16));

        $name = $this->getConfig()->get("Custom Name");
        CustomiesBlockFactory::getInstance()->registerBlock(static fn () => new CustomTNT(new BlockIdentifier($id), $name, new BlockTypeInfo(new BlockBreakInfo(1))), "customies:" . strtolower(str_replace(" ", "_", $name)), $model, new CreativeInventoryInfo(CreativeInventoryInfo::CATEGORY_ITEMS, CreativeInventoryInfo::NONE));

        EntityFactory::getInstance()->register(CustomPrimedTNT::class, function (World $world, CompoundTag $nbt): CustomPrimedTNT {
            return new CustomPrimedTNT(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        }, ['CustomPrimedTnt', 'CustomPrimedTNT']);

        CreativeInventory::getInstance()->add(CustomiesBlockFactory::getInstance()->get("customies:" . strtolower(str_replace(" ", "_", $name)))->asItem());
    }

    public function onDisable(): void
    {
    }
}
