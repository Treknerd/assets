<?php

declare(strict_types=1);

namespace Stu\Module\Spacecraft\Lib\Movement\Route;

use InvalidArgumentException;
use Mockery\MockInterface;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use Stu\Orm\Entity\MapInterface;
use Stu\Orm\Entity\StarSystemInterface;
use Stu\Orm\Entity\StarSystemMapInterface;
use Stu\Orm\Repository\MapRepositoryInterface;
use Stu\Orm\Repository\StarSystemMapRepositoryInterface;
use Stu\StuTestCase;

class LoadWaypointsTest extends StuTestCase
{
    /** @var MockInterface&MapRepositoryInterface */
    private MockInterface $mapRepository;

    /** @var MockInterface&StarSystemMapRepositoryInterface */
    private MockInterface $starSystemMapRepository;

    private LoadWaypointsInterface $subject;

    #[Override]
    protected function setUp(): void
    {
        $this->mapRepository = $this->mock(MapRepositoryInterface::class);
        $this->starSystemMapRepository = $this->mock(StarSystemMapRepositoryInterface::class);

        $this->subject = new LoadWaypoints(
            $this->mapRepository,
            $this->starSystemMapRepository
        );
    }

    public function testLoadExpectExceptionWhenCalledWithDifferentTypes1(): void
    {
        static::expectExceptionMessage('start and destination have different type');
        static::expectException(InvalidArgumentException::class);

        $this->subject->load(
            $this->mock(MapInterface::class),
            $this->mock(StarSystemMapInterface::class)
        );
    }

    public function testLoadExpectExceptionWhenCalledWithDifferentTypes2(): void
    {
        static::expectExceptionMessage('start and destination have different type');
        static::expectException(InvalidArgumentException::class);

        $this->subject->load(
            $this->mock(StarSystemMapInterface::class),
            $this->mock(MapInterface::class)
        );
    }

    public static function provideTestLoadForMapFieldsData(): array
    {
        return [
            //startX, destX, startY,  destY, sortAscending?
            [1,         1,      1,      2,      true],
            [1,         2,      1,      1,      true],
            [2,         1,      1,      1,      false],
            [1,         1,      2,      1,      false],
        ];
    }

    #[DataProvider('provideTestLoadForMapFieldsData')]
    public function testLoadForMapFields(
        int $startX,
        int $destX,
        int $startY,
        int $destY,
        bool $sortAscending
    ): void {
        $start = $this->mock(MapInterface::class);
        $destination = $this->mock(MapInterface::class);
        $waypoints = [$start, $destination];

        $start->shouldReceive('getX')
            ->withNoArgs()
            ->once()
            ->andReturn($startX);
        $start->shouldReceive('getY')
            ->withNoArgs()
            ->once()
            ->andReturn($startY);
        $start->shouldReceive('getLayer->getId')
            ->withNoArgs()
            ->once()
            ->andReturn(42);

        $destination->shouldReceive('getX')
            ->withNoArgs()
            ->once()
            ->andReturn($destX);
        $destination->shouldReceive('getY')
            ->withNoArgs()
            ->once()
            ->andReturn($destY);

        $this->mapRepository->shouldReceive('getByCoordinateRange')
            ->with(
                42,
                min($startX, $destX),
                max($startX, $destX),
                min($startY, $destY),
                max($startY, $destY),
                $sortAscending
            )
            ->once()
            ->andReturn($waypoints);

        $result = $this->subject->load($start, $destination);

        $this->assertEquals(1, $result->count());
    }

    public static function provideTestLoadForSystemMapFieldsData(): array
    {
        return [
            //startX, destX, startY,  destY, sortAscending?
            [1,         1,      1,      2,      true],
            [1,         2,      1,      1,      true],
            [2,         1,      1,      1,      false],
            [1,         1,      2,      1,      false],
        ];
    }

    #[DataProvider('provideTestLoadForSystemMapFieldsData')]
    public function testLoadForSystemMapFields(
        int $startX,
        int $destX,
        int $startY,
        int $destY,
        bool $sortAscending
    ): void {
        $start = $this->mock(StarSystemMapInterface::class);
        $destination = $this->mock(StarSystemMapInterface::class);
        $system = $this->mock(StarSystemInterface::class);
        $waypoints = [$start, $destination];

        $start->shouldReceive('getX')
            ->withNoArgs()
            ->once()
            ->andReturn($startX);
        $start->shouldReceive('getY')
            ->withNoArgs()
            ->once()
            ->andReturn($startY);
        $start->shouldReceive('getSystem')
            ->withNoArgs()
            ->once()
            ->andReturn($system);

        $system->shouldReceive('getId')
            ->withNoArgs()
            ->once()
            ->andReturn(424242);

        $destination->shouldReceive('getX')
            ->withNoArgs()
            ->once()
            ->andReturn($destX);
        $destination->shouldReceive('getY')
            ->withNoArgs()
            ->once()
            ->andReturn($destY);

        $this->starSystemMapRepository->shouldReceive('getByCoordinateRange')
            ->with(
                424242,
                min($startX, $destX),
                max($startX, $destX),
                min($startY, $destY),
                max($startY, $destY),
                $sortAscending
            )
            ->once()
            ->andReturn($waypoints);

        $result = $this->subject->load($start, $destination);

        $this->assertEquals(1, $result->count());
    }
}
