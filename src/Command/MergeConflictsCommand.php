<?php

declare(strict_types=1);

namespace App\Command;

use Gettext\Generator\PoGenerator;
use Gettext\Loader\PoLoader;
use Gettext\Translation;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class MergeConflictsCommand extends Command
{
    const OPTION_BASE = 'base';
    const OPTION_OURS = 'ours';
    const OPTION_THEIRS = 'theirs';

    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'merge-conflicts';

    protected function configure(): void
    {
        $this->addArgument(
            'option',
            InputArgument::REQUIRED,
            sprintf('Options: %s, %s, %s', self::OPTION_BASE, self::OPTION_OURS, self::OPTION_THEIRS)
        );

        $this->addArgument(
            'input',
            InputArgument::REQUIRED
        );

        $this->addArgument(
            'output',
            InputArgument::REQUIRED
        );

        $this->addOption(
            'fuzzy',
            'f',
            InputOption::VALUE_NONE,
            'Mantiene las traducciones procesadas como fuzzy'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $option = (string) $input->getArgument('option');
        $inputPath = (string) $input->getArgument('input');
        $outputPath = (string) $input->getArgument('output');
        $fuzzy = (bool) $input->getOption('fuzzy');

        $io = new SymfonyStyle($input, $output);

        $poLoader = new PoLoader();
        $poWriter = new PoGenerator();

        $translations = $poLoader->loadFile($inputPath);

        $total = 0;
        $processedTranslations = 0;
        $ignoredTranslations = 0;
        $optionNotAvailable = 0;

        foreach ($translations->getTranslations() as $translation) {
            /** @var Translation $translation */
            $total++;

            if ($translation->isDisabled() || null === $translation->getTranslation() || false === strpos($translation->getTranslation(), '#-#-#-#-#')) {
                $ignoredTranslations++;
                continue;
            }

            $matches = [];
            // ¿versión multilínea?
            // preg_match('/\#-\#-\#-\#-\#  '.$option.' (?:.*)  \#-\#-\#-\#-\#(?:.*)[\r\n]+((.|\n[^#-#-#-#-#])*)/', $translation->getTranslation(), $matches);
            preg_match('/\#-\#-\#-\#-\#  '.$option.' (?:.*)  \#-\#-\#-\#-\#(?:.*)[\r\n]+([^\r\n]+)/', $translation->getTranslation(), $matches);

            if (empty($matches[1])) {
                $io->warning(sprintf('Option "%s" not found for translation "%s"', $option, $translation->getTranslation()));
                $optionNotAvailable++;
                continue;
            }

            $translation->translate($matches[1]);

            // Si no pedimos mantener los fuzzy, los eliminamos si existen
            if (!$fuzzy && $translation->getFlags()->has('fuzzy')) {
                $translation->getFlags()->delete('fuzzy');
            }

            $processedTranslations++;
        }

        $poWriter->generateFile($translations, $outputPath);

        $io->success(
            sprintf('Fichero a procesar: %s', $inputPath).PHP_EOL.
            sprintf('Fichero procesado: %s', $outputPath).PHP_EOL.
            sprintf('Conflictos resueltos: %s', $processedTranslations).PHP_EOL.
            ($optionNotAvailable > 0 ? sprintf('Opción "%s" no encontrada: %s veces', $option, $optionNotAvailable).PHP_EOL : null).
            sprintf('Traducciones sin modificar: %s', $ignoredTranslations).PHP_EOL.
            sprintf('Total procesadas: %s', $total)
        );

        return self::SUCCESS;
    }

}