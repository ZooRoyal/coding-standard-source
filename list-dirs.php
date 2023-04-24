<?php
require __DIR__ . '/vendor/autoload.php';

$excludes = [
    '.git',
    '.idea',
    '.vagrant',
    'node_modules',
    'vendor',
    'bower_components',
    '.pnpm',
    '.pnpm-store',
];
//function searchForFile($name, $path, $exclusions = [], $minDepth = 0) {
//  $foundFilePath = false;
//
//  $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path), RecursiveIteratorIterator::SELF_FIRST);
//  foreach ($iterator as $file) {
//    if ($file->isFile() && $file->getFilename() === $name) {
//      $foundFilePath = $file->getPathname();
//      break;
//    }
//  }
//
//  if ($foundFilePath) {
//    return $foundFilePath;
//  } else {
//    foreach ($exclusions as $exclusion) {
//      $excludedPath = $path . '/' . $exclusion;
//      if (is_dir($excludedPath)) {
//        $foundFilePath = searchForFile($name, $excludedPath);
//        if ($foundFilePath) {
//          break;
//        }
//      }
//    }
//    return $foundFilePath;
//  }
//}
//var_export(searchForFile('.git','.',['node_modules','vendor']));

//$finder = new \Symfony\Component\Finder\Finder();
//$finder
//    ->in(getcwd())
//    ->name('composer.json')
//    ->depth('>= 2')
//    ->ignoreVCS(false)
//    ->ignoreDotFiles(false)
//    ->exclude(['node_modules', 'vendor']);
//var_export(array_keys(iterator_to_array($finder)));
// 1m2s in zooroyalweb docker

//$nette = Nette\Utils\Finder::findFiles('composer.json')
//    ->from(getcwd())
//    ->descentFilter(static fn($fileInfo) => !in_array($fileInfo->getBaseName(), ['node_modules', 'vendor'], true))
//    ->collect();
//$nette = Nette\Utils\Finder::findFiles('composer.json')
//    ->from(getcwd())
//    ->descentFilter(static fn($fileInfo) => !in_array($fileInfo->getBaseName(), ['node_modules', 'vendor'], true))
//    ->collect();
//$nette = Nette\Utils\Finder::findFiles('composer.json')
//    ->from(getcwd())
//    ->descentFilter(static fn($fileInfo) => !in_array($fileInfo->getBaseName(), ['node_modules', 'vendor'], true))
//    ->collect();
//$nette = Nette\Utils\Finder::findFiles('composer.json')
//    ->from(getcwd())
//    ->descentFilter(static fn($fileInfo) => !in_array($fileInfo->getBaseName(), ['node_modules', 'vendor'], true))
//    ->collect();
//var_export(array_keys($nette));
// 14s in zooroyalweb docker

//function listFolderFiles(&$cache, $name, $dir, $exclusions = [], int $minDepth = 0, int $depthNow = 0): array
//{
//    $result = [];
//
//    if (in_array(basename($dir), $exclusions, true)) {
//        return $result;
//    }
//
//    if (!isset($cache[$dir])) {
//        $ffs = scandir($dir);
//
//        unset($ffs[array_search('.', $ffs, true)]);
//        unset($ffs[array_search('..', $ffs, true)]);
//
//        $cache[$dir] = $ffs;
//    }
//
//    // prevent empty ordered elements
//    if (count($cache[$dir]) < 1) {
//        return $result;
//    }
//
//    foreach ($cache[$dir] as $ff) {
//        if (
//            $ff === $name
//            && $depthNow >= $minDepth
//        ) {
//            $result[] = $dir . '/' . $ff;
//        }
//
//        if (!isset($cache[$dir . '/' . $ff]) && !is_dir($dir . '/' . $ff)) {
//            $cache[$dir . '/' . $ff] = [];
//            continue;
//        }
//
//        $subFolderResults = listFolderFiles($cache, $name, $dir . '/' . $ff, $exclusions, $minDepth, $depthNow + 1);
//        $result = [...$result, ...$subFolderResults];
//    }
//
//    return $result;
//}
//
//$cache = [];
//
//listFolderFiles($cache, 'composer.json', getcwd(), ['node_modules', 'vendor', '.git'], 2);
//listFolderFiles($cache, 'composer.json', getcwd(), ['node_modules', 'vendor', '.git'], 2);
//listFolderFiles($cache, 'composer.json', getcwd(), ['node_modules', 'vendor', '.git'], 2);
//listFolderFiles($cache, 'composer.json', getcwd(), ['node_modules', 'vendor', '.git'], 2);
//listFolderFiles($cache, 'composer.json', getcwd(), ['node_modules', 'vendor', '.git'], 2);
//listFolderFiles($cache, 'composer.json', getcwd(), ['node_modules', 'vendor', '.git'], 2);
//
//var_export(listFolderFiles($cache, 'composer.json', getcwd(), ['node_modules', 'vendor', '.git'], 2));
//// 7s in zooroyalweb docker
//
//echo memory_get_peak_usage(true);

// test internal implementation
use Zooroyal\CodingStandard\CommandLine\ApplicationLifeCycle\ContainerFactory;

$container = ContainerFactory::getContainerInstance();

$enhancedFileInfoFactory = $container
    ->get(\Zooroyal\CodingStandard\CommandLine\EnhancedFileInfo\EnhancedFileInfoFactory::class);
$fileSearch = $container
    ->get(\Zooroyal\CodingStandard\CommandLine\ExclusionList\Excluders\FastCachedFileSearch::class);

$cwdFileInfo = $enhancedFileInfoFactory->buildFromPath(getcwd());
$excludesFileInfos = $enhancedFileInfoFactory->buildFromArrayOfPaths($excludes);

//$fileSearch->listFolderFiles('composer.json', $cwdFileInfo, $excludesFileInfos, 2);
//$fileSearch->listFolderFiles('composer.json', $cwdFileInfo, $excludesFileInfos, 2);
//$fileSearch->listFolderFiles('composer.json', $cwdFileInfo, $excludesFileInfos, 2);
$resultingFileInfos = $fileSearch->listFolderFiles('composer.json', $cwdFileInfo, $excludesFileInfos, 2);

$result = array_map(
    static fn(\Zooroyal\CodingStandard\CommandLine\EnhancedFileInfo\EnhancedFileInfo $fileInfo
    ) => $fileInfo->getPathname(),
    $resultingFileInfos
);
// time php -dxdebug.output_dir=/coding-standard -dxdebug.mode=profile /coding-standard/list-dirs.php
// 0m10.602s

var_export($result);