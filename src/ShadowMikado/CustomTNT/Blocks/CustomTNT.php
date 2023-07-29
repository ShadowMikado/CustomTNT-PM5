<?php

namespace ShadowMikado\CustomTNT\Blocks;

use pocketmine\block\Opaque;
use pocketmine\block\VanillaBlocks;
use ShadowMikado\CustomTNT\Main;
use pocketmine\entity\Location;
use pocketmine\entity\projectile\Projectile;
use pocketmine\item\Durable;
use pocketmine\item\enchantment\VanillaEnchantments;
use pocketmine\item\FlintSteel;
use pocketmine\item\Item;
use pocketmine\item\ItemTypeIds;
use pocketmine\math\RayTraceResult;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\utils\Random;
use pocketmine\world\sound\IgniteSound;
use ShadowMikado\CustomTNT\Entities\CustomPrimedTNT;

use function cos;
use function sin;
use const M_PI;

class CustomTNT extends Opaque
{
	protected bool $unstable = false; //TODO: Usage unclear, seems to be a weird hack in vanilla

	public function isUnstable(): bool
	{
		return $this->unstable;
	}

	/** @return $this */
	public function setUnstable(bool $unstable): self
	{
		$this->unstable = $unstable;
		return $this;
	}

	public function onBreak(Item $item, ?Player $player = null, array &$returnedItems = []): bool
	{
		if ($this->unstable) {
			$this->ignite();
			return true;
		}
		return parent::onBreak($item, $player, $returnedItems);
	}

	public function onInteract(Item $item, int $face, Vector3 $clickVector, ?Player $player = null, array &$returnedItems = []): bool
	{
		if ($item->getTypeId() === ItemTypeIds::FIRE_CHARGE) {
			$item->pop();
			$this->ignite();
			return true;
		}
		if ($item instanceof FlintSteel || $item->hasEnchantment(VanillaEnchantments::FIRE_ASPECT())) {
			if ($item instanceof Durable) {
				$item->applyDamage(1);
			}
			$this->ignite();
			return true;
		}

		return false;
	}

	public function ignite(int $fuse = 80): void
	{
		$world = $this->position->getWorld();
		$world->setBlock($this->position, VanillaBlocks::AIR());

		$mot = (new Random())->nextSignedFloat() * M_PI * 2;

		$tnt = new CustomPrimedTNT(Location::fromObject($this->position->add(0.5, 0, 0.5), $world));
		$fuse = Main::$config->get("Time Before Explode") * 10; //Converts to seconds
		$tnt->setFuse($fuse);
		$tnt->setWorksUnderwater(Main::$config->get("Works Underwater"));
		$tnt->setMotion(new Vector3(-sin($mot) * 0.02, 0.2, -cos($mot) * 0.02));
		$tnt->spawnToAll();
		$tnt->broadcastSound(new IgniteSound());
	}

	public function getFlameEncouragement(): int
	{
		return 15;
	}

	public function getFlammability(): int
	{
		return 100;
	}

	public function onIncinerate(): void
	{
		$this->ignite();
	}

	public function onProjectileHit(Projectile $projectile, RayTraceResult $hitResult): void
	{
		if ($projectile->isOnFire()) {
			$this->ignite();
		}
	}
}
