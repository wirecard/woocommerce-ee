<?php

define('REPO', getenv('TRAVIS_REPO_SLUG'));
define('REPO_NAME', explode('/', REPO)[1]);
define('SCRIPT_DIR', __DIR__);
define('WIKI_DIR', SCRIPT_DIR . '/../' . REPO_NAME . '.wiki');
define('WIKI_FILE', WIKI_DIR . '/Home.md');
define('README_FILE', SCRIPT_DIR . '/../README.md');
define('EXTENSION_DIR', SCRIPT_DIR . '/../wirecard-woocommerce-extension');
define('INTERNAL_README_FILE', EXTENSION_DIR . '/readme.txt');
define('INTERNAL_PHP_FILE', EXTENSION_DIR . '/woocommerce-wirecard-payment-gateway.php');
define('VERSION_FILE', SCRIPT_DIR . '/../SHOPVERSIONS');
define('TRAVIS_FILE', SCRIPT_DIR . '/../.travis.yml');
define('CHANGELOG_FILE', SCRIPT_DIR . '/../CHANGELOG.md');

// Update this if you're using a different shop system.
require SCRIPT_DIR . '/../wirecard-woocommerce-extension/vendor/autoload.php';

use Symfony\Component\Yaml\Yaml;

/**
 * Maps over the configured PHP versions and prefixes them
 *
 * @param $version
 * @return string
 */
function prefixWithPhp($version)
{
    return 'PHP ' . number_format($version, 1);
}

/**
 * Joins an array with commas and a conjunction before the last item
 * (e.g. "x, y and z")
 *
 * @param $list
 * @param string $conjunction
 * @return string
 */
function naturalLanguageJoin($list, $conjunction = 'and')
{
    $last = array_pop($list);

    if ($list) {
        return implode(', ', $list) . ' ' . $conjunction . ' ' . $last;
    }

    return $last;
}

/**
 * Wraps each line of the changelog in proper formatting.
 *
 * @param $change
 * @return string
 */
function generateChangelogLine($change)
{
    return "<li>{$change}</li>";
}

/**
 * Generates the necessary version string for the compatible shop versions and PHP versions.
 *
 * @param $shopVersions
 * @param $phpVersions
 * @return array
 */
function makeTextVersions($shopVersions, $phpVersions)
{
    $versionRanges = [];
    foreach ($shopVersions['shopversions'] as $shopVersionObject) {
        $shopVersion  = (array) $shopVersionObject;
        $versionRanges[$shopVersion['name']] = $shopVersion['tested'];
        // We don't need a from-to range if the versions are the same.
        if ($shopVersion['compatibility'] !== $shopVersion['tested']) {
            $versionRanges[ $shopVersion['name'] ] = $shopVersion['compatibility'] . ' - ' . $shopVersion['tested'];
        }
    }
    $phpVersions      = array_map('prefixWithPhp', $phpVersions);
    $phpVersionString = naturalLanguageJoin($phpVersions);

    return [
        'versionRanges'    => $versionRanges,
        'phpVersionString' => $phpVersionString,
    ];
}

/**
 * Generates the text with tested versions
 *
 * @param $shopVersions
 * @param $phpVersions
 * @return string
 */
function generateTestedVersionsString($shopVersions, $phpVersions)
{
    $testedVersions  = '';
    $releaseVersions = makeTextVersions($shopVersions, $phpVersions);

    foreach ($shopVersions['shopversions'] as $shopVersionObject) {
        $shopVersion     = (array) $shopVersionObject;
        $testedVersions .= "{$shopVersion['name']} {$shopVersion['tested']}";
        $testedVersions .= ( $shopVersionObject == end($shopVersions['shopversions']) ) ? ' ' : ', ';
    }
    $testedVersions .= "with {$releaseVersions['phpVersionString']}</em><br>";

    return $testedVersions;
}

/**
 * Generates the text with compatibility versions
 *
 * @param $shopVersions
 * @param $phpVersions
 * @return string
 */
function generateCompatibilityVersionsString($shopVersions, $phpVersions)
{
    $compatibilityVersions = '';
    $releaseVersions       = makeTextVersions($shopVersions, $phpVersions);
    foreach ($releaseVersions['versionRanges'] as $releaseName => $releaseVersion) {
        $compatibilityVersions .= "{$releaseName} {$releaseVersion}";
        $compatibilityVersions .= ( $releaseVersion == end($releaseVersions['versionRanges']) ) ? ' ' : ', ';
    }
    $compatibilityVersions .= "with {$releaseVersions['phpVersionString']}</em><br>";
    return $compatibilityVersions;
}


/**
 * Generates the text for the release notes on GitHub
 *
 * @param $shopVersions
 * @param $phpVersions
 * @return string
 */
function generateReleaseVersions($shopVersions, $phpVersions)
{

    $releaseNotes  = '<ul>' . join('', array_map('generateChangelogLine', $shopVersions['changelog'])) . '</ul>';
    $releaseNotes .= '<em><strong>Tested version(s):</strong> ';
    $releaseNotes .= generateTestedVersionsString($shopVersions, $phpVersions);

    $releaseNotes .= '<em><strong>Compatibility:</strong> ';
    $releaseNotes .= generateCompatibilityVersionsString($shopVersions, $phpVersions);
    return $releaseNotes;
}

/**
 * Updates the compatibility versions and release date on the home page of the repository wiki
 * (NOTE: This function directly manipulates the necessary file)
 *
 * @param $shopVersions
 * @param $phpVersions
 */
function generateWikiRelease($shopVersions, $phpVersions)
{
    if (! file_exists(WIKI_FILE)) {
        fwrite(STDERR, 'ERROR: Wiki files do not exist.' . PHP_EOL);
        exit(1);
    }

    $wikiPage    = file_get_contents(WIKI_FILE);
    $releaseDate = date('Y-m-d');

    // Matching all the replaceable table rows.
    // The format is | **<string>** | <content> |
    $testedRegex        = '/^\|\s?\*.?Tested.*\|(.*)\|/mi';
    $compatibilityRegex = '/^\|\s?\*.?Compatibility.*\|(.*)\|/mi';
    $extVersionRegex    = '/^\|\s?\*.?Extension.*\|(.*)\|/mi';

    $testedReplace        = '| **Tested version(s):** | ' . generateTestedVersionsString($shopVersions, $phpVersions) . ' |';
    $compatibilityReplace = '| **Compatibility:** | ' . generateCompatibilityVersionsString(
        $shopVersions,
        $phpVersions
    ) . ' |';
    $extVersionReplace    = '| **Extension version** | ![Release](https://img.shields.io/github/release/' . REPO . ".png?nolink \"Release\") ({$releaseDate}), [change log](https://github.com/" . REPO . '/releases) |';

    $wikiPage = preg_replace($testedRegex, $testedReplace, $wikiPage);
    $wikiPage = preg_replace($compatibilityRegex, $compatibilityReplace, $wikiPage);
    $wikiPage = preg_replace($extVersionRegex, $extVersionReplace, $wikiPage);

    file_put_contents(WIKI_FILE, $wikiPage);
}

/**
 * Updates the README badge to use the latest shop version we're compatible with.
 * (NOTE: This function directly manipulates the necessary file)
 *
 * @param $shopVersions
 */
function generateReadmeReleaseBadge($shopVersions)
{
    if (! file_exists(README_FILE)) {
        fwrite(STDERR, 'ERROR: README file does not exist.' . PHP_EOL);
        exit(1);
    }

    $readmeContent = file_get_contents(README_FILE);

    foreach ($shopVersions['shopversions'] as $shopVersionObject) {
        $shopVersion = (array) $shopVersionObject;
        $badge       = $shopVersion['name'] . ' v' . $shopVersion['tested'];
        $badgeUrl    = str_replace(' ', '-', $badge);
        // We're matching the image tag in Markdown. [![Shopsytem v1.2.3] ... ]
        $badgeRegex    = "/\[\!\[{$shopVersion['name']}.*\]/mi";
        $badgeReplace  = "[![{$badge}](https://img.shields.io/badge/{$badgeUrl}-green.svg)]";
        $readmeContent = preg_replace($badgeRegex, $badgeReplace, $readmeContent);
    }
    file_put_contents(README_FILE, $readmeContent);
}

/**
 * Updates the given file with the version strings provided in @replaceMatrix
 * (NOTE: This function directly manipulates the necessary file)
 *
 * @param $fileName
 * @param $replaceMatrix
 */

function updateVersionInFile($fileName, $replaceMatrix)
{
    if (! file_exists($fileName)) {
        fwrite(STDERR, "ERROR: {$fileName} file does not exist." . PHP_EOL);
        exit(1);
    }
    $fileContent = file_get_contents($fileName);

    foreach ($replaceMatrix as $replaceElementKey => $replaceElementValue) {
        $replaceRegRegex = "/{$replaceElementKey}([0-9\.]{3,5})/";
        $fileContent = preg_replace($replaceRegRegex, $replaceElementKey . $replaceElementValue, $fileContent);
    }

    file_put_contents($fileName, $fileContent);
}


/**
 * Updates the internal woocommerce-wirecard-payment-gateway.php file with latest release version
 * (NOTE: This function directly manipulates the necessary file)
 *
 * @param $shopVersions
 * @param $phpVersions
 */

function updateInternalPhpFile($shopVersions)
{
    
    $replaceMatrix = [
        "Version: " => $shopVersions["release"],
        "WIRECARD_EXTENSION_VERSION', '" => $shopVersions["release"]
    ];

    updateVersionInFile(INTERNAL_PHP_FILE, $replaceMatrix);
}


/**
 * Loads and parses the versions file.
 *
 * @param $filePath
 * @return array
 */

function parseVersionsFile($filePath)
{
    // Bail out if we don"t have defined shop versions and throw a loud error.
    if (! file_exists($filePath)) {
        fwrite(STDERR, 'ERROR: No shop version file exists' . PHP_EOL);
        exit(1);
    }

    // Load the file and parse json out of it
    $json = json_decode(
        file_get_contents(VERSION_FILE)
    );

    // compare release versions
    $cmp = function ($a, $b) {
        return version_compare($a->release, $b->release);
    };

    // if file contains an array of versions return the latest
    if (is_array($json)) {
        uasort($json, $cmp);
        return (array) end($json);
    } else {
        return (array) $json;
    }
}

/**
 * Updates the internal readme.txt file with latest tested platform plugin and php versions
 * (NOTE: This function directly manipulates the necessary file)
 *
 * @param $shopVersions
 * @param $phpVersions
 */
function updateInternalReadme($shopVersions, $phpVersions)
{
    
    $shopVersionsArray = (array) $shopVersions["shopversions"];
    $platformArray = (array) $shopVersionsArray["platform"];
    sort($phpVersions);
    
    $replaceMatrix = [
        "Stable tag: " => $shopVersions["release"],
        "Tested up to: " => $platformArray["tested"],
        "Requires PHP: " => $phpVersions[0]
        ];
    
    updateVersionInFile(INTERNAL_README_FILE, $replaceMatrix);
}


$shopVersions = parseVersionsFile(VERSION_FILE);
// Grab the Travis config for parsing the supported PHP versions
$travisConfig = Yaml::parseFile(TRAVIS_FILE);
$travisMatrix = $travisConfig['matrix'];
$phpVersions  = [];
foreach ($travisMatrix['include'] as $version) {
    if (! empty($version['php'])) {
        if (! in_array($version['php'], $phpVersions)) {
            array_push($phpVersions, $version['php']);
        }
    }
}

// Get the arguments passed to the command line script.
$options = getopt('wrip');

// The indication of a command line argument being passed is an entry in the array with a "false" value.
// So instead we check if the key exists in the array.

// If we get -w passed, we're doing a wiki update.
if (key_exists('w', $options)) {
    generateWikiRelease($shopVersions, $phpVersions);
    exit(0);
}

// If -r is passed, that's for the badge in the README
if (key_exists('r', $options)) {
    generateReadmeReleaseBadge($shopVersions);
    exit(0);
}

// If -i is passed, that's for the versions in readme.txt
if (key_exists('i', $options)) {
    updateInternalReadme($shopVersions, $phpVersions);
    exit(0);
}

// If -p is passed, that's for the versions in woocommerce-wirecard-payment-gateway.php
if (key_exists('p', $options)) {
    updateInternalPhpFile($shopVersions);
    exit(0);
}

// Otherwise just output the release notes, the rest will be handled by Travis
echo generateReleaseVersions($shopVersions, $phpVersions);
