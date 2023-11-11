<?php

declare(strict_types=1);

namespace Stu\Module\Research;

use Stu\Component\Research\ResearchEnum;
use Stu\Orm\Entity\ResearchedInterface;
use Stu\Orm\Entity\UserInterface;
use Stu\Orm\Repository\FactionRepositoryInterface;
use Stu\Orm\Repository\ResearchDependencyRepositoryInterface;
use Stu\Orm\Repository\ResearchedRepositoryInterface;
use Stu\Orm\Repository\ResearchRepositoryInterface;

final class TechlistRetriever implements TechlistRetrieverInterface
{
    private ResearchRepositoryInterface $researchRepository;

    private ResearchDependencyRepositoryInterface $researchDependencyRepository;

    private ResearchedRepositoryInterface $researchedRepository;

    private FactionRepositoryInterface $factionRepository;

    public function __construct(
        ResearchRepositoryInterface $researchRepository,
        ResearchDependencyRepositoryInterface $researchDependencyRepository,
        ResearchedRepositoryInterface $researchedRepository,
        FactionRepositoryInterface $factionRepository
    ) {
        $this->researchRepository = $researchRepository;
        $this->researchDependencyRepository = $researchDependencyRepository;
        $this->researchedRepository = $researchedRepository;
        $this->factionRepository = $factionRepository;
    }

    public function getResearchList(UserInterface $user): array
    {
        $finished_list = array_map(
            fn (ResearchedInterface $researched): int => $researched->getResearch()->getId(),
            array_filter(
                $this->getFinishedResearchList($user),
                fn (ResearchedInterface $researched): bool => $researched->getFinished() > 0
            )
        );

        $result = $this->researchRepository->getAvailableResearch($user->getId());
        $list_result = [];

        //load dependencies
        $dependencies = $this->loadDependencies();

        //load excludes
        $excludes = $this->loadExcludes();

        // calculate possible research items
        foreach ($result as $obj) {
            // check for existent user award
            if ($obj->getNeededAwardId() !== null && !$user->hasAward($obj->getNeededAwardId())) {
                continue;
            }

            $key = $obj->getId();

            // excludelogic
            if (isset($excludes[$key])) {
                foreach ($excludes[$key] as $exclude) {
                    if (in_array($exclude->getResearchId(), $finished_list)) {
                        continue 2;
                    }
                }
            }

            // dependencie logic
            if (isset($dependencies[$key])) {
                // check for AND condition
                foreach ($dependencies[$key]['AND'] as $and_condition) {
                    if (!in_array($and_condition, $finished_list)) {
                        continue 2;
                    }
                }

                // check for OR condition
                if (!empty($dependencies[$key]['OR'])) {
                    $or_condition_met = false;
                    foreach ($dependencies[$key]['OR'] as $or_condition) {
                        if (in_array($or_condition, $finished_list)) {
                            $or_condition_met = true;
                            break;
                        }
                    }
                    if (!$or_condition_met) {
                        continue;
                    }
                }
            }

            $list_result[$key] = $obj;
        }


        foreach ($this->factionRepository->findAll() as $faction) {
            $startResearch = $faction->getStartResearch();
            if ($startResearch !== null) {
                $startResearchId = $startResearch->getId();
                if (isset($list_result[$startResearchId])) {
                    unset($list_result[$startResearchId]);
                }
            }
        }

        return $list_result;
    }

    private function loadDependencies(): array
    {
        $dependencies = [];

        $dependencies_result = $this->researchDependencyRepository->getByMode(
            [ResearchEnum::RESEARCH_MODE_REQUIRE, ResearchEnum::RESEARCH_MODE_REQUIRE_SOME]
        );

        foreach ($dependencies_result as $dependency) {
            $research_id = $dependency->getResearchId();
            $mode = $dependency->getMode();

            if (!isset($dependencies[$research_id])) {
                $dependencies[$research_id] = [
                    'AND' => [],
                    'OR' => []
                ];
            }

            if ($mode === ResearchEnum::RESEARCH_MODE_REQUIRE) {
                $dependencies[$research_id]['AND'][] = $dependency->getDependsOn();
            } elseif ($mode === ResearchEnum::RESEARCH_MODE_REQUIRE_SOME) {
                $dependencies[$research_id]['OR'][] = $dependency->getDependsOn();
            }
        }

        return $dependencies;
    }

    private function loadExcludes(): array
    {
        $excludes = [];
        $exclude_result = $this->researchDependencyRepository->getByMode([ResearchEnum::RESEARCH_MODE_EXCLUDE]);

        foreach ($exclude_result as $dependency) {
            $research_id = $dependency->getDependsOn();
            if (array_key_exists($research_id, $excludes) === false) {
                $excludes[$research_id] = [];
            }
            $excludes[$research_id][] = $dependency;
        }

        return $excludes;
    }

    public function getFinishedResearchList(UserInterface $user): array
    {
        return $this->researchedRepository->getListByUser($user->getId());
    }
}
