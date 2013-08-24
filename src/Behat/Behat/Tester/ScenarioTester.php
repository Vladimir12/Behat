<?php

namespace Behat\Behat\Tester;

/*
 * This file is part of the Behat.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
use Behat\Behat\Context\Pool\ContextPoolInterface;
use Behat\Behat\Event\EventInterface;
use Behat\Behat\Event\ScenarioEvent;
use Behat\Behat\Event\StepEvent;
use Behat\Behat\Suite\SuiteInterface;
use Behat\Gherkin\Node\ScenarioNode;

/**
 * Scenario tester.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
class ScenarioTester extends IsolatedStepCollectionTester
{
    /**
     * Tests scenario.
     *
     * @param SuiteInterface       $suite
     * @param ContextPoolInterface $contexts
     * @param ScenarioNode         $scenario
     * @param Boolean              $skip
     *
     * @return integer
     */
    public function test(
        SuiteInterface $suite,
        ContextPoolInterface $contexts,
        ScenarioNode $scenario,
        $skip = false
    )
    {
        $status = $skip ? StepEvent::SKIPPED : StepEvent::PASSED;

        $contexts = $this->initializeContextPool($suite, $contexts);

        $event = new ScenarioEvent($suite, $contexts, $scenario);
        $this->dispatch(EventInterface::BEFORE_SCENARIO, $event);
        !$skip && $this->dispatch(EventInterface::HOOKABLE_BEFORE_SCENARIO, $event);

        if ($scenario->getFeature()->hasBackground()) {
            $background = $scenario->getFeature()->getBackground();

            $tester = $this->getBackgroundTester($suite, $contexts, $background);
            $status = $tester->test($suite, $scenario, $background, $contexts, $skip);
            $skip = StepEvent::PASSED !== $status;
        }

        foreach ($scenario->getSteps() as $step) {
            $tester = $this->getStepTester($suite, $contexts, $step);
            $status = max($status, $tester->test($suite, $contexts, $step, $scenario, $skip));
            $skip = StepEvent::PASSED !== $status;
        }

        $event = new ScenarioEvent($suite, $contexts, $scenario, $status);
        !$skip && $this->dispatch(EventInterface::HOOKABLE_AFTER_SCENARIO, $event);
        $this->dispatch(EventInterface::AFTER_SCENARIO, $event);

        return $status;
    }
}
