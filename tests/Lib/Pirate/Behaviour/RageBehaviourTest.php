<?php

declare(strict_types=1);

namespace Stu\Lib\Pirate\Behaviour;

use Doctrine\Common\Collections\ArrayCollection;
use Mockery;
use Mockery\MockInterface;
use Stu\Module\Ship\Lib\Battle\FightLibInterface;
use Stu\Module\Ship\Lib\Battle\ShipAttackCoreInterface;
use Stu\Module\Ship\Lib\FleetWrapperInterface;
use Stu\Module\Ship\Lib\Interaction\InteractionCheckerInterface;
use Stu\Module\Ship\Lib\ShipWrapperFactoryInterface;
use Stu\Module\Ship\Lib\ShipWrapperInterface;
use Stu\Orm\Entity\FleetInterface;
use Stu\Orm\Entity\ShipInterface;
use Stu\Orm\Repository\ShipRepositoryInterface;
use Stu\StuTestCase;

class RageBehaviourTest extends StuTestCase
{
    /** @var MockInterface|ShipRepositoryInterface */
    private $shipRepository;
    /** @var MockInterface|InteractionCheckerInterface */
    private $interactionChecker;
    /** @var MockInterface|FightLibInterface */
    private $fightLib;
    /** @var MockInterface|ShipAttackCoreInterface */
    private $shipAttackCore;
    /** @var MockInterface|ShipWrapperFactoryInterface */
    private $shipWrapperFactory;

    /** @var MockInterface|FleetWrapperInterface */
    private $fleetWrapper;

    private PirateBehaviourInterface $subject;

    protected function setUp(): void
    {
        $this->shipRepository = $this->mock(ShipRepositoryInterface::class);
        $this->interactionChecker = $this->mock(InteractionCheckerInterface::class);
        $this->fightLib = $this->mock(FightLibInterface::class);
        $this->shipAttackCore = $this->mock(ShipAttackCoreInterface::class);
        $this->shipWrapperFactory = $this->mock(ShipWrapperFactoryInterface::class);

        $this->fleetWrapper = mock(FleetWrapperInterface::class);

        $this->subject = new RageBehaviour(
            $this->shipRepository,
            $this->interactionChecker,
            $this->fightLib,
            $this->shipAttackCore,
            $this->shipWrapperFactory,
            $this->initLoggerUtil()
        );
    }

    public function testActionExpectNoActionIfNoTargets(): void
    {
        $wrapper = $this->mock(ShipWrapperInterface::class);
        $ship = $this->mock(ShipInterface::class);

        $this->fleetWrapper->shouldReceive('getLeadWrapper')
            ->once()
            ->andReturn($wrapper);
        $wrapper->shouldReceive('get')
            ->once()
            ->andReturn($ship);

        $this->shipRepository->shouldReceive('getPirateTargets')
            ->with($ship)
            ->once()
            ->andReturn([]);

        $this->subject->action($this->fleetWrapper);
    }

    public function testActionExpectNoActionIfTargetNotOnPosition(): void
    {
        $wrapper = $this->mock(ShipWrapperInterface::class);
        $ship = $this->mock(ShipInterface::class);
        $target = $this->mock(ShipInterface::class);

        $this->fleetWrapper->shouldReceive('getLeadWrapper')
            ->once()
            ->andReturn($wrapper);
        $wrapper->shouldReceive('get')
            ->once()
            ->andReturn($ship);

        $this->shipRepository->shouldReceive('getPirateTargets')
            ->with($ship)
            ->once()
            ->andReturn([$target]);

        $this->interactionChecker->shouldReceive('checkPosition')
            ->with($ship, $target)
            ->once()
            ->andReturn(false);

        $this->subject->action($this->fleetWrapper);
    }

    public function testActionExpectNoActionIfCantAttackTarget(): void
    {
        $wrapper = $this->mock(ShipWrapperInterface::class);
        $ship = $this->mock(ShipInterface::class);
        $target = $this->mock(ShipInterface::class);

        $this->fleetWrapper->shouldReceive('getLeadWrapper')
            ->once()
            ->andReturn($wrapper);
        $wrapper->shouldReceive('get')
            ->once()
            ->andReturn($ship);

        $this->shipRepository->shouldReceive('getPirateTargets')
            ->with($ship)
            ->once()
            ->andReturn([$target]);

        $this->interactionChecker->shouldReceive('checkPosition')
            ->with($ship, $target)
            ->once()
            ->andReturn(true);

        $this->fightLib->shouldReceive('canAttackTarget')
            ->with($ship, $target, false)
            ->once()
            ->andReturn(false);

        $this->subject->action($this->fleetWrapper);
    }

    public function testActionExpectAttackOfSingleTarget(): void
    {
        $wrapper = $this->mock(ShipWrapperInterface::class);
        $ship = $this->mock(ShipInterface::class);
        $target = $this->mock(ShipInterface::class);
        $targetWrapper = $this->mock(ShipWrapperInterface::class);

        $this->fleetWrapper->shouldReceive('getLeadWrapper')
            ->andReturn($wrapper);
        $wrapper->shouldReceive('get')
            ->once()
            ->andReturn($ship);

        $this->shipRepository->shouldReceive('getPirateTargets')
            ->with($ship)
            ->once()
            ->andReturn([$target]);

        $this->interactionChecker->shouldReceive('checkPosition')
            ->with($ship, $target)
            ->once()
            ->andReturn(true);

        $this->fightLib->shouldReceive('canAttackTarget')
            ->with($ship, $target, false)
            ->once()
            ->andReturn(true);

        $this->shipWrapperFactory->shouldReceive('wrapShip')
            ->with($target)
            ->once()
            ->andReturn($targetWrapper);

        $this->shipAttackCore->shouldReceive('attack')
            ->with($wrapper, $targetWrapper, false, Mockery::any())
            ->once()
            ->andReturn(true);

        $this->subject->action($this->fleetWrapper);
    }

    public function testActionExpectAttackOfWeakestTarget(): void
    {
        $wrapper = $this->mock(ShipWrapperInterface::class);
        $ship = $this->mock(ShipInterface::class);
        $target = $this->mock(ShipInterface::class);

        $target2 = $this->mock(ShipInterface::class);
        $targetWrapper2 = $this->mock(ShipWrapperInterface::class);

        $target3_1 = $this->mock(ShipInterface::class);
        $target3_2 = $this->mock(ShipInterface::class);
        $targetFleet3 = $this->mock(FleetInterface::class);

        $target->shouldReceive('getFleet')
            ->andReturn(null);
        $target->shouldReceive('getHealthPercentage')
            ->andReturn(75);
        $target2->shouldReceive('getFleet')
            ->andReturn(null);
        $target2->shouldReceive('getHealthPercentage')
            ->andReturn(73);
        $target3_1->shouldReceive('getFleet')
            ->andReturn($targetFleet3);
        $target3_1->shouldReceive('getHealthPercentage')
            ->andReturn(73);
        $target3_2->shouldReceive('getHealthPercentage')
            ->andReturn(75);
        $targetFleet3->shouldReceive('getShips')
            ->andReturn(new ArrayCollection([$target3_1, $target3_2]));


        $this->fleetWrapper->shouldReceive('getLeadWrapper')
            ->andReturn($wrapper);
        $wrapper->shouldReceive('get')
            ->once()
            ->andReturn($ship);

        $this->shipRepository->shouldReceive('getPirateTargets')
            ->with($ship)
            ->once()
            ->andReturn([$target, $target2, $target3_1]);

        $this->interactionChecker->shouldReceive('checkPosition')
            ->with($ship, $target)
            ->once()
            ->andReturn(true);
        $this->interactionChecker->shouldReceive('checkPosition')
            ->with($ship, $target2)
            ->once()
            ->andReturn(true);
        $this->interactionChecker->shouldReceive('checkPosition')
            ->with($ship, $target3_1)
            ->once()
            ->andReturn(true);

        $this->fightLib->shouldReceive('canAttackTarget')
            ->with($ship, $target, false)
            ->once()
            ->andReturn(true);
        $this->fightLib->shouldReceive('canAttackTarget')
            ->with($ship, $target2, false)
            ->once()
            ->andReturn(true);
        $this->fightLib->shouldReceive('canAttackTarget')
            ->with($ship, $target3_1, false)
            ->once()
            ->andReturn(true);

        $this->shipWrapperFactory->shouldReceive('wrapShip')
            ->with($target2)
            ->once()
            ->andReturn($targetWrapper2);

        $this->shipAttackCore->shouldReceive('attack')
            ->with($wrapper, $targetWrapper2, false, Mockery::any())
            ->once()
            ->andReturn(true);

        $this->subject->action($this->fleetWrapper);
    }
}
