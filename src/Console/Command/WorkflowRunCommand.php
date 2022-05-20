<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Console\Command;

use Composer\Autoload\ClassLoader;
use Kiboko\Component\Runtime\Workflow\Console as WorkflowConsoleRuntime;
use Symfony\Component\Console;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Dotenv\Dotenv;

final class WorkflowRunCommand extends Console\Command\Command
{
    protected static $defaultName = 'run:workflow';

    protected function configure(): void
    {
        $this->setDescription('Run the workflow satellite.');
        $this->addArgument('path', Console\Input\InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $style = new Console\Style\SymfonyStyle(
            $input,
            $output,
        );

        $style->writeln(sprintf('<fg=cyan>Running workflow in %s</>', $input->getArgument('path')));

        if (!file_exists($input->getArgument('path') . '/vendor/autoload.php')) {
            $style->error('There is no compiled workflow at the provided path');

            return 1;
        }

        $cwd = getcwd();
        chdir($input->getArgument('path'));

        $dotenv = new Dotenv();
        $dotenv->usePutenv();
        $dotenv->loadEnv('.env');

        /** @var ClassLoader $autoload */
        $autoload = include 'vendor/autoload.php';
        $autoload->addClassMap([
            /** @phpstan-ignore-next-line */
            \ProjectServiceContainer::class => 'container.php',
        ]);
        $autoload->register();

        $runtime = new WorkflowConsoleRuntime(
            $output,
            new \Kiboko\Component\Pipeline\PipelineRunner(new \Psr\Log\NullLogger()),
        );

        /** @var callable(runtime: WorkflowRuntimeInterface): \Runtime $workflow */
        $workflow = include 'workflow.php';

        $start = microtime(true);
        $workflow($runtime);
        $runtime->run();
        $end = microtime(true);

        $autoload->unregister();

        $style->writeln(sprintf('time: %s', $this->formatTime($end - $start)));

        chdir($cwd);

        return 0;
    }

    private function formatTime(float $time): string
    {
        if ($time < .00001) {
            return sprintf('<fg=cyan>%sµs</>', number_format($time * 1000000, 2));
        }
        if ($time < .0001) {
            return sprintf('<fg=cyan>%sµs</>', number_format($time * 1000000, 1));
        }
        if ($time < .001) {
            return sprintf('<fg=cyan>%sµs</>', number_format($time * 1000000));
        }
        if ($time < .01) {
            return sprintf('<fg=cyan>%sms</>', number_format($time * 1000, 2));
        }
        if ($time < .1) {
            return sprintf('<fg=cyan>%sms</>', number_format($time * 1000, 1));
        }
        if ($time < 1) {
            return sprintf('<fg=cyan>%sms</>', number_format($time * 1000));
        }
        if ($time < 10) {
            return sprintf('<fg=cyan>%ss</>', number_format($time, 2));
        }
        if ($time < 3600) {
            $minutes = floor($time / 60);
            $seconds = $time - (60 * $minutes);

            return sprintf('<fg=cyan>%smin</> <fg=cyan>%ss</>', number_format($minutes), number_format($seconds, 2));
        }
        $hours = floor($time / 3600);
        $minutes = floor(($time - (3600 * $hours)) / 60);
        $seconds = $time - (3600 * $hours) - (60 * $minutes);

        return sprintf('<fg=cyan>%sh</> <fg=cyan>%smin</> <fg=cyan>%ss</>', number_format($hours), number_format($minutes), number_format($seconds, 2));
    }
}
