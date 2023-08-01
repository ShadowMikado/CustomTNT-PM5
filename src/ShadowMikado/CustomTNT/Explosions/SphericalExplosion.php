<?php

namespace ShadowMikado\CustomTNT\Explosions;

use pocketmine\block\Block;
use pocketmine\block\RuntimeBlockStateRegistry;
use pocketmine\block\TNT;
use pocketmine\block\VanillaBlocks;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageByBlockEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityExplodeEvent;
use pocketmine\item\VanillaItems;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\utils\AssumptionFailedError;
use pocketmine\world\format\SubChunk;
use pocketmine\world\particle\HugeExplodeSeedParticle;
use pocketmine\world\Position;
use pocketmine\world\sound\ExplodeSound;
use pocketmine\world\utils\SubChunkExplorer;
use pocketmine\world\utils\SubChunkExplorerStatus;
use pocketmine\world\World;
use ShadowMikado\CustomTNT\Blocks\CustomTNT;

use function ceil;
use function floor;
use function min;
use function mt_rand;
use function sqrt;

class SphericalExplosion
{
    private int $rays = 16;
    public World $world;

    /** @var Block[] */
    public array $affectedBlocks = [];
    public array $drops = [];
    public float $stepLen = 0.3;

    private SubChunkExplorer $subChunkExplorer;

    public function __construct(
        public Position $source,
        public float $radius,
        private Entity|Block|null $what = null
    ) {
        if (!$this->source->isValid()) {
            throw new \InvalidArgumentException("Position does not have a valid world");
        }
        $this->world = $this->source->getWorld();

        if ($radius <= 0) {
            throw new \InvalidArgumentException("Explosion radius must be greater than 0, got $radius");
        }
        $this->subChunkExplorer = new SubChunkExplorer($this->world);
    }


    public function explodeA(): bool
    {
        if ($this->radius < 0.1) {
            return false;
        }

        $center = (new Vector3($this->source->x, $this->source->y, $this->source->z))->floor();
        $radius = $this->radius;
        $blockFactory = RuntimeBlockStateRegistry::getInstance();

        for ($x = -$radius; $x <= $radius; $x++) {
            for ($y = -$radius; $y <= $radius; $y++) {
                for ($z = -$radius; $z <= $radius; $z++) {
                    $pos = $center->add($x, $y, $z);
                    if ($center->distance($pos) <= $radius) {

                        if ($this->subChunkExplorer->moveTo($pos->x, $pos->y, $pos->z) === SubChunkExplorerStatus::INVALID) {
                            continue;
                        }
                        $subChunk = $this->subChunkExplorer->currentSubChunk;
                        if ($subChunk === null) {
                            throw new AssumptionFailedError("SubChunkExplorer subchunk should not be null here");
                        }

                        $state = $subChunk->getBlockStateId($pos->x & SubChunk::COORD_MASK, $pos->y & SubChunk::COORD_MASK, $pos->z & SubChunk::COORD_MASK);

                        $blastResistance = $blockFactory->blastResistance[$state] ?? 0;
                        if ($blastResistance >= 0) {
                            if (!isset($this->affectedBlocks[World::blockHash($pos->x, $pos->y, $pos->z)])) {
                                $_block = $this->world->getBlockAt($pos->x, $pos->y, $pos->z, true, false);
                                foreach ($_block->getAffectedBlocks() as $_affectedBlock) {
                                    $_affectedBlockPos = $_affectedBlock->getPosition();
                                    $this->affectedBlocks[World::blockHash($_affectedBlockPos->x, $_affectedBlockPos->y, $_affectedBlockPos->z)] = $_affectedBlock;
                                }
                            }
                        }
                    }
                }
            }
        }

        return true;
    }

    public function explodeB(): bool
    {
        $center = (new Vector3($this->source->x, $this->source->y, $this->source->z))->floor();
        $yield = min(100, (1 / $this->radius) * 100);

        if ($this->what instanceof Entity) {
            $ev = new EntityExplodeEvent($this->what, $this->source, $this->affectedBlocks, $yield);
            $ev->call();
            if ($ev->isCancelled()) {
                return false;
            } else {
                $yield = $ev->getYield();
                $this->affectedBlocks = $ev->getBlockList();
            }
        }


        $explosionSize = $this->radius; //* 2;
        $minX = (int) floor($this->source->x - $explosionSize - 1);
        $maxX = (int) ceil($this->source->x + $explosionSize + 1);
        $minY = (int) floor($this->source->y - $explosionSize - 1);
        $maxY = (int) ceil($this->source->y + $explosionSize + 1);
        $minZ = (int) floor($this->source->z - $explosionSize - 1);
        $maxZ = (int) ceil($this->source->z + $explosionSize + 1);

        $explosionBB = new AxisAlignedBB($minX, $minY, $minZ, $maxX, $maxY, $maxZ);

        /** @var Entity[] $list */
        $list = $this->world->getNearbyEntities($explosionBB, $this->what instanceof Entity ? $this->what : null);
        foreach ($list as $entity) {
            $entityPos = $entity->getPosition();
            $distance = $entityPos->distance($this->source) / $explosionSize;

            if ($distance <= 1) {
                $motion = $entityPos->subtractVector($this->source)->normalize();

                $impact = (1 - $distance) * ($exposure = 1);

                $damage = (int) ((($impact * $impact + $impact) / 2) * 8 * $explosionSize + 1);

                if ($this->what instanceof Entity) {
                    $ev = new EntityDamageByEntityEvent($this->what, $entity, EntityDamageEvent::CAUSE_ENTITY_EXPLOSION, $damage);
                } elseif ($this->what instanceof Block) {
                    $ev = new EntityDamageByBlockEvent($this->what, $entity, EntityDamageEvent::CAUSE_BLOCK_EXPLOSION, $damage);
                } else {
                    $ev = new EntityDamageEvent($entity, EntityDamageEvent::CAUSE_BLOCK_EXPLOSION, $damage);
                }

                $entity->attack($ev);
                $entity->setMotion($entity->getMotion()->addVector($motion->multiply($impact)));
            }
        }

        $air = VanillaItems::AIR();
        $airBlock = VanillaBlocks::AIR();

        foreach ($this->affectedBlocks as $block) {
            $pos = $block->getPosition();
            if ($block instanceof TNT || $block instanceof CustomTNT) {
                $block->ignite(mt_rand(10, 30));
            } else {
                if (mt_rand(0, 100) < $yield) {
                    foreach ($block->getDrops($air) as $drop) {
                        $this->drops[] = $drop;
                    }
                }
                if (($t = $this->world->getTileAt($pos->x, $pos->y, $pos->z)) !== null) {
                    $t->onBlockDestroyed();
                }
                $this->world->setBlockAt($pos->x, $pos->y, $pos->z, $airBlock);
            }
        }

        foreach ($this->drops as $drop) {
            $this->world->dropItem($center->subtract(0, $this->radius + 1, 0), $drop);
        }
        $this->world->addParticle($center, new HugeExplodeSeedParticle());
        $this->world->addSound($center, new ExplodeSound());

        return true;
    }
}
