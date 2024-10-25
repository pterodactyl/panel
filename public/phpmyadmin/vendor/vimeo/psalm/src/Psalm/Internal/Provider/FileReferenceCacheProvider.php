<?php

namespace Psalm\Internal\Provider;

use Psalm\Config;
use Psalm\Internal\Codebase\Analyzer;
use Psalm\Internal\Provider\Providers;
use RuntimeException;
use UnexpectedValueException;

use function file_exists;
use function file_put_contents;
use function igbinary_serialize;
use function igbinary_unserialize;
use function is_array;
use function is_dir;
use function is_readable;
use function mkdir;
use function serialize;
use function unserialize;

use const DIRECTORY_SEPARATOR;
use const LOCK_EX;

/**
 * @psalm-import-type  FileMapType from Analyzer
 *
 * Used to determine which files reference other files, necessary for using the --diff
 * option from the command line.
 */
class FileReferenceCacheProvider
{
    private const REFERENCE_CACHE_NAME = 'references';
    private const CLASSLIKE_FILE_CACHE_NAME = 'classlike_files';
    private const NONMETHOD_CLASS_REFERENCE_CACHE_NAME = 'file_class_references';
    private const METHOD_CLASS_REFERENCE_CACHE_NAME = 'method_class_references';
    private const ANALYZED_METHODS_CACHE_NAME = 'analyzed_methods';
    private const CLASS_METHOD_CACHE_NAME = 'class_method_references';
    private const METHOD_DEPENDENCIES_CACHE_NAME = 'class_method_dependencies';
    private const CLASS_PROPERTY_CACHE_NAME = 'class_property_references';
    private const CLASS_METHOD_RETURN_CACHE_NAME = 'class_method_return_references';
    private const FILE_METHOD_RETURN_CACHE_NAME = 'file_method_return_references';
    private const FILE_CLASS_MEMBER_CACHE_NAME = 'file_class_member_references';
    private const FILE_CLASS_PROPERTY_CACHE_NAME = 'file_class_property_references';
    private const ISSUES_CACHE_NAME = 'issues';
    private const FILE_MAPS_CACHE_NAME = 'file_maps';
    private const TYPE_COVERAGE_CACHE_NAME = 'type_coverage';
    private const CONFIG_HASH_CACHE_NAME = 'config';
    private const METHOD_MISSING_MEMBER_CACHE_NAME = 'method_missing_member';
    private const FILE_MISSING_MEMBER_CACHE_NAME = 'file_missing_member';
    private const UNKNOWN_MEMBER_CACHE_NAME = 'unknown_member_references';
    private const METHOD_PARAM_USE_CACHE_NAME = 'method_param_uses';

    /**
     * @var Config
     */
    protected $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function hasConfigChanged(): bool
    {
        $new_hash = $this->config->computeHash();
        $has_changed = $new_hash !== $this->getConfigHashCache();
        $this->setConfigHashCache($new_hash);
        return $has_changed;
    }

    /**
     * @psalm-suppress MixedAssignment
     */
    public function getCachedFileReferences(): ?array
    {
        $cache_directory = $this->config->getCacheDirectory();

        if (!$cache_directory) {
            return null;
        }

        $reference_cache_location = $cache_directory . DIRECTORY_SEPARATOR . self::REFERENCE_CACHE_NAME;

        if (!is_readable($reference_cache_location)) {
            return null;
        }

        if ($this->config->use_igbinary) {
            $reference_cache = igbinary_unserialize(Providers::safeFileGetContents($reference_cache_location));
        } else {
            $reference_cache = unserialize(Providers::safeFileGetContents($reference_cache_location));
        }

        if (!is_array($reference_cache)) {
            throw new UnexpectedValueException('The reference cache must be an array');
        }

        return $reference_cache;
    }

    /**
     * @psalm-suppress MixedAssignment
     */
    public function getCachedClassLikeFiles(): ?array
    {
        $cache_directory = $this->config->getCacheDirectory();

        if (!$cache_directory) {
            return null;
        }

        $reference_cache_location = $cache_directory . DIRECTORY_SEPARATOR . self::CLASSLIKE_FILE_CACHE_NAME;

        if (!is_readable($reference_cache_location)) {
            return null;
        }

        if ($this->config->use_igbinary) {
            $reference_cache = igbinary_unserialize(Providers::safeFileGetContents($reference_cache_location));
        } else {
            $reference_cache = unserialize(Providers::safeFileGetContents($reference_cache_location));
        }

        if (!is_array($reference_cache)) {
            throw new UnexpectedValueException('The reference cache must be an array');
        }

        return $reference_cache;
    }

    /**
     * @psalm-suppress MixedAssignment
     */
    public function getCachedNonMethodClassReferences(): ?array
    {
        $cache_directory = $this->config->getCacheDirectory();

        if (!$cache_directory) {
            return null;
        }

        $reference_cache_location = $cache_directory . DIRECTORY_SEPARATOR . self::NONMETHOD_CLASS_REFERENCE_CACHE_NAME;

        if (!is_readable($reference_cache_location)) {
            return null;
        }

        if ($this->config->use_igbinary) {
            $reference_cache = igbinary_unserialize(Providers::safeFileGetContents($reference_cache_location));
        } else {
            $reference_cache = unserialize(Providers::safeFileGetContents($reference_cache_location));
        }

        if (!is_array($reference_cache)) {
            throw new UnexpectedValueException('The reference cache must be an array');
        }

        return $reference_cache;
    }

    /**
     * @psalm-suppress MixedAssignment
     */
    public function getCachedMethodClassReferences(): ?array
    {
        $cache_directory = $this->config->getCacheDirectory();

        if (!$cache_directory) {
            return null;
        }

        $reference_cache_location = $cache_directory . DIRECTORY_SEPARATOR . self::METHOD_CLASS_REFERENCE_CACHE_NAME;

        if (!is_readable($reference_cache_location)) {
            return null;
        }

        if ($this->config->use_igbinary) {
            $reference_cache = igbinary_unserialize(Providers::safeFileGetContents($reference_cache_location));
        } else {
            $reference_cache = unserialize(Providers::safeFileGetContents($reference_cache_location));
        }

        if (!is_array($reference_cache)) {
            throw new UnexpectedValueException('The reference cache must be an array');
        }

        return $reference_cache;
    }

    /**
     * @psalm-suppress MixedAssignment
     */
    public function getCachedMethodMemberReferences(): ?array
    {
        $cache_directory = $this->config->getCacheDirectory();

        if (!$cache_directory) {
            return null;
        }

        $class_member_cache_location = $cache_directory . DIRECTORY_SEPARATOR . self::CLASS_METHOD_CACHE_NAME;

        if (!is_readable($class_member_cache_location)) {
            return null;
        }

        $class_member_reference_cache = Providers::safeFileGetContents($class_member_cache_location);
        if ($this->config->use_igbinary) {
            $class_member_reference_cache = igbinary_unserialize($class_member_reference_cache);
        } else {
            $class_member_reference_cache = unserialize($class_member_reference_cache);
        }

        if (!is_array($class_member_reference_cache)) {
            throw new UnexpectedValueException('The reference cache must be an array');
        }

        return $class_member_reference_cache;
    }

    /**
     * @psalm-suppress MixedAssignment
     */
    public function getCachedMethodDependencies(): ?array
    {
        $cache_directory = $this->config->getCacheDirectory();

        if (!$cache_directory) {
            return null;
        }

        $method_dependencies_cache_location
            = $cache_directory . DIRECTORY_SEPARATOR . self::METHOD_DEPENDENCIES_CACHE_NAME;

        if (!is_readable($method_dependencies_cache_location)) {
            return null;
        }

        $method_dependencies_cache = Providers::safeFileGetContents($method_dependencies_cache_location);
        if ($this->config->use_igbinary) {
            $method_dependencies_cache = igbinary_unserialize($method_dependencies_cache);
        } else {
            $method_dependencies_cache = unserialize($method_dependencies_cache);
        }

        if (!is_array($method_dependencies_cache)) {
            throw new UnexpectedValueException('The reference cache must be an array');
        }

        return $method_dependencies_cache;
    }

    /**
     * @psalm-suppress MixedAssignment
     */
    public function getCachedMethodPropertyReferences(): ?array
    {
        $cache_directory = $this->config->getCacheDirectory();

        if (!$cache_directory) {
            return null;
        }

        $class_member_cache_location = $cache_directory . DIRECTORY_SEPARATOR . self::CLASS_PROPERTY_CACHE_NAME;

        if (!is_readable($class_member_cache_location)) {
            return null;
        }

        $class_member_reference_cache = Providers::safeFileGetContents($class_member_cache_location);
        if ($this->config->use_igbinary) {
            $class_member_reference_cache = igbinary_unserialize($class_member_reference_cache);
        } else {
            $class_member_reference_cache = unserialize($class_member_reference_cache);
        }

        if (!is_array($class_member_reference_cache)) {
            throw new UnexpectedValueException('The reference cache must be an array');
        }

        return $class_member_reference_cache;
    }

    /**
     * @psalm-suppress MixedAssignment
     */
    public function getCachedMethodMethodReturnReferences(): ?array
    {
        $cache_directory = $this->config->getCacheDirectory();

        if (!$cache_directory) {
            return null;
        }

        $class_member_cache_location = $cache_directory . DIRECTORY_SEPARATOR . self::CLASS_METHOD_RETURN_CACHE_NAME;

        if (!is_readable($class_member_cache_location)) {
            return null;
        }

        $class_member_reference_cache = Providers::safeFileGetContents($class_member_cache_location);
        if ($this->config->use_igbinary) {
            $class_member_reference_cache = igbinary_unserialize($class_member_reference_cache);
        } else {
            $class_member_reference_cache = unserialize($class_member_reference_cache);
        }

        if (!is_array($class_member_reference_cache)) {
            throw new UnexpectedValueException('The reference cache must be an array');
        }

        return $class_member_reference_cache;
    }

    /**
     * @psalm-suppress MixedAssignment
     */
    public function getCachedMethodMissingMemberReferences(): ?array
    {
        $cache_directory = $this->config->getCacheDirectory();

        if (!$cache_directory) {
            return null;
        }

        $class_member_cache_location = $cache_directory . DIRECTORY_SEPARATOR . self::METHOD_MISSING_MEMBER_CACHE_NAME;

        if (!is_readable($class_member_cache_location)) {
            return null;
        }

        $class_member_reference_cache = Providers::safeFileGetContents($class_member_cache_location);
        if ($this->config->use_igbinary) {
            $class_member_reference_cache = igbinary_unserialize($class_member_reference_cache);
        } else {
            $class_member_reference_cache = unserialize($class_member_reference_cache);
        }

        if (!is_array($class_member_reference_cache)) {
            throw new UnexpectedValueException('The reference cache must be an array');
        }

        return $class_member_reference_cache;
    }

    /**
     * @psalm-suppress MixedAssignment
     */
    public function getCachedFileMemberReferences(): ?array
    {
        $cache_directory = $this->config->getCacheDirectory();

        if (!$cache_directory) {
            return null;
        }

        $file_class_member_cache_location = $cache_directory . DIRECTORY_SEPARATOR . self::FILE_CLASS_MEMBER_CACHE_NAME;

        if (!is_readable($file_class_member_cache_location)) {
            return null;
        }

        $file_class_member_reference_cache = Providers::safeFileGetContents($file_class_member_cache_location);
        if ($this->config->use_igbinary) {
            $file_class_member_reference_cache = igbinary_unserialize($file_class_member_reference_cache);
        } else {
            $file_class_member_reference_cache = unserialize($file_class_member_reference_cache);
        }

        if (!is_array($file_class_member_reference_cache)) {
            throw new UnexpectedValueException('The reference cache must be an array');
        }

        return $file_class_member_reference_cache;
    }

    /**
     * @psalm-suppress MixedAssignment
     */
    public function getCachedFilePropertyReferences(): ?array
    {
        $cache_directory = $this->config->getCacheDirectory();

        if (!$cache_directory) {
            return null;
        }

        $file_class_member_cache_location = $cache_directory
            . DIRECTORY_SEPARATOR
            . self::FILE_CLASS_PROPERTY_CACHE_NAME;

        if (!is_readable($file_class_member_cache_location)) {
            return null;
        }

        $file_class_member_reference_cache = Providers::safeFileGetContents($file_class_member_cache_location);
        if ($this->config->use_igbinary) {
            $file_class_member_reference_cache = igbinary_unserialize($file_class_member_reference_cache);
        } else {
            $file_class_member_reference_cache = unserialize($file_class_member_reference_cache);
        }

        if (!is_array($file_class_member_reference_cache)) {
            throw new UnexpectedValueException('The reference cache must be an array');
        }

        return $file_class_member_reference_cache;
    }

    /**
     * @psalm-suppress MixedAssignment
     */
    public function getCachedFileMethodReturnReferences(): ?array
    {
        $cache_directory = $this->config->getCacheDirectory();

        if (!$cache_directory) {
            return null;
        }

        $file_class_member_cache_location = $cache_directory
            . DIRECTORY_SEPARATOR
            . self::FILE_METHOD_RETURN_CACHE_NAME;

        if (!is_readable($file_class_member_cache_location)) {
            return null;
        }

        $file_class_member_reference_cache = Providers::safeFileGetContents($file_class_member_cache_location);
        if ($this->config->use_igbinary) {
            $file_class_member_reference_cache = igbinary_unserialize($file_class_member_reference_cache);
        } else {
            $file_class_member_reference_cache = unserialize($file_class_member_reference_cache);
        }

        if (!is_array($file_class_member_reference_cache)) {
            throw new UnexpectedValueException('The reference cache must be an array');
        }

        return $file_class_member_reference_cache;
    }

    /**
     * @psalm-suppress MixedAssignment
     */
    public function getCachedFileMissingMemberReferences(): ?array
    {
        $cache_directory = $this->config->getCacheDirectory();

        if (!$cache_directory) {
            return null;
        }

        $file_class_member_cache_location
            = $cache_directory . DIRECTORY_SEPARATOR . self::FILE_MISSING_MEMBER_CACHE_NAME;

        if (!is_readable($file_class_member_cache_location)) {
            return null;
        }

        $file_class_member_reference_cache = Providers::safeFileGetContents($file_class_member_cache_location);
        if ($this->config->use_igbinary) {
            $file_class_member_reference_cache = igbinary_unserialize($file_class_member_reference_cache);
        } else {
            $file_class_member_reference_cache = unserialize($file_class_member_reference_cache);
        }

        if (!is_array($file_class_member_reference_cache)) {
            throw new UnexpectedValueException('The reference cache must be an array');
        }

        return $file_class_member_reference_cache;
    }

    /**
     * @psalm-suppress MixedAssignment
     */
    public function getCachedMixedMemberNameReferences(): ?array
    {
        $cache_directory = $this->config->getCacheDirectory();

        if (!$cache_directory) {
            return null;
        }

        $reference_cache_location = $cache_directory . DIRECTORY_SEPARATOR . self::UNKNOWN_MEMBER_CACHE_NAME;

        if (!is_readable($reference_cache_location)) {
            return null;
        }

        if ($this->config->use_igbinary) {
            $reference_cache = igbinary_unserialize(Providers::safeFileGetContents($reference_cache_location));
        } else {
            $reference_cache = unserialize(Providers::safeFileGetContents($reference_cache_location));
        }

        if (!is_array($reference_cache)) {
            throw new UnexpectedValueException('The reference cache must be an array');
        }

        return $reference_cache;
    }

    /**
     * @psalm-suppress MixedAssignment
     */
    public function getCachedMethodParamUses(): ?array
    {
        $cache_directory = $this->config->getCacheDirectory();

        if (!$cache_directory) {
            return null;
        }

        $reference_cache_location = $cache_directory . DIRECTORY_SEPARATOR . self::METHOD_PARAM_USE_CACHE_NAME;

        if (!is_readable($reference_cache_location)) {
            return null;
        }

        if ($this->config->use_igbinary) {
            $reference_cache = igbinary_unserialize(Providers::safeFileGetContents($reference_cache_location));
        } else {
            $reference_cache = unserialize(Providers::safeFileGetContents($reference_cache_location));
        }

        if (!is_array($reference_cache)) {
            throw new UnexpectedValueException('The method param use cache must be an array');
        }

        return $reference_cache;
    }

    /**
     * @psalm-suppress MixedAssignment
     */
    public function getCachedIssues(): ?array
    {
        $cache_directory = $this->config->getCacheDirectory();

        if (!$cache_directory) {
            return null;
        }

        $issues_cache_location = $cache_directory . DIRECTORY_SEPARATOR . self::ISSUES_CACHE_NAME;

        if (!is_readable($issues_cache_location)) {
            return null;
        }

        if ($this->config->use_igbinary) {
            $issues_cache = igbinary_unserialize(Providers::safeFileGetContents($issues_cache_location));
        } else {
            $issues_cache = unserialize(Providers::safeFileGetContents($issues_cache_location));
        }

        if (!is_array($issues_cache)) {
            throw new UnexpectedValueException('The reference cache must be an array');
        }

        return $issues_cache;
    }

    public function setCachedFileReferences(array $file_references): void
    {
        $cache_directory = $this->config->getCacheDirectory();

        if (!$cache_directory) {
            return;
        }

        $reference_cache_location = $cache_directory . DIRECTORY_SEPARATOR . self::REFERENCE_CACHE_NAME;

        if ($this->config->use_igbinary) {
            file_put_contents($reference_cache_location, igbinary_serialize($file_references), LOCK_EX);
        } else {
            file_put_contents($reference_cache_location, serialize($file_references), LOCK_EX);
        }
    }

    public function setCachedClassLikeFiles(array $file_references): void
    {
        $cache_directory = $this->config->getCacheDirectory();

        if (!$cache_directory) {
            return;
        }

        $reference_cache_location = $cache_directory . DIRECTORY_SEPARATOR . self::CLASSLIKE_FILE_CACHE_NAME;

        if ($this->config->use_igbinary) {
            file_put_contents($reference_cache_location, igbinary_serialize($file_references), LOCK_EX);
        } else {
            file_put_contents($reference_cache_location, serialize($file_references), LOCK_EX);
        }
    }

    public function setCachedNonMethodClassReferences(array $file_class_references): void
    {
        $cache_directory = $this->config->getCacheDirectory();

        if (!$cache_directory) {
            return;
        }

        $reference_cache_location = $cache_directory . DIRECTORY_SEPARATOR . self::NONMETHOD_CLASS_REFERENCE_CACHE_NAME;

        if ($this->config->use_igbinary) {
            file_put_contents($reference_cache_location, igbinary_serialize($file_class_references), LOCK_EX);
        } else {
            file_put_contents($reference_cache_location, serialize($file_class_references), LOCK_EX);
        }
    }

    public function setCachedMethodClassReferences(array $method_class_references): void
    {
        $cache_directory = $this->config->getCacheDirectory();

        if (!$cache_directory) {
            return;
        }

        $reference_cache_location = $cache_directory . DIRECTORY_SEPARATOR . self::METHOD_CLASS_REFERENCE_CACHE_NAME;

        if ($this->config->use_igbinary) {
            file_put_contents($reference_cache_location, igbinary_serialize($method_class_references), LOCK_EX);
        } else {
            file_put_contents($reference_cache_location, serialize($method_class_references), LOCK_EX);
        }
    }

    public function setCachedMethodMemberReferences(array $member_references): void
    {
        $cache_directory = $this->config->getCacheDirectory();

        if (!$cache_directory) {
            return;
        }

        $member_cache_location = $cache_directory . DIRECTORY_SEPARATOR . self::CLASS_METHOD_CACHE_NAME;

        if ($this->config->use_igbinary) {
            file_put_contents($member_cache_location, igbinary_serialize($member_references), LOCK_EX);
        } else {
            file_put_contents($member_cache_location, serialize($member_references), LOCK_EX);
        }
    }

    public function setCachedMethodDependencies(array $member_references): void
    {
        $cache_directory = $this->config->getCacheDirectory();

        if (!$cache_directory) {
            return;
        }

        $member_cache_location = $cache_directory . DIRECTORY_SEPARATOR . self::METHOD_DEPENDENCIES_CACHE_NAME;

        if ($this->config->use_igbinary) {
            file_put_contents($member_cache_location, igbinary_serialize($member_references), LOCK_EX);
        } else {
            file_put_contents($member_cache_location, serialize($member_references), LOCK_EX);
        }
    }

    public function setCachedMethodPropertyReferences(array $property_references): void
    {
        $cache_directory = $this->config->getCacheDirectory();

        if (!$cache_directory) {
            return;
        }

        $member_cache_location = $cache_directory . DIRECTORY_SEPARATOR . self::CLASS_PROPERTY_CACHE_NAME;

        if ($this->config->use_igbinary) {
            file_put_contents($member_cache_location, igbinary_serialize($property_references), LOCK_EX);
        } else {
            file_put_contents($member_cache_location, serialize($property_references), LOCK_EX);
        }
    }

    public function setCachedMethodMethodReturnReferences(array $method_return_references): void
    {
        $cache_directory = $this->config->getCacheDirectory();

        if (!$cache_directory) {
            return;
        }

        $member_cache_location = $cache_directory . DIRECTORY_SEPARATOR . self::CLASS_METHOD_RETURN_CACHE_NAME;

        if ($this->config->use_igbinary) {
            file_put_contents($member_cache_location, igbinary_serialize($method_return_references), LOCK_EX);
        } else {
            file_put_contents($member_cache_location, serialize($method_return_references), LOCK_EX);
        }
    }

    public function setCachedMethodMissingMemberReferences(array $member_references): void
    {
        $cache_directory = $this->config->getCacheDirectory();

        if (!$cache_directory) {
            return;
        }

        $member_cache_location = $cache_directory . DIRECTORY_SEPARATOR . self::METHOD_MISSING_MEMBER_CACHE_NAME;

        if ($this->config->use_igbinary) {
            file_put_contents($member_cache_location, igbinary_serialize($member_references), LOCK_EX);
        } else {
            file_put_contents($member_cache_location, serialize($member_references), LOCK_EX);
        }
    }

    public function setCachedFileMemberReferences(array $member_references): void
    {
        $cache_directory = $this->config->getCacheDirectory();

        if (!$cache_directory) {
            return;
        }

        $member_cache_location = $cache_directory . DIRECTORY_SEPARATOR . self::FILE_CLASS_MEMBER_CACHE_NAME;

        if ($this->config->use_igbinary) {
            file_put_contents($member_cache_location, igbinary_serialize($member_references), LOCK_EX);
        } else {
            file_put_contents($member_cache_location, serialize($member_references), LOCK_EX);
        }
    }

    public function setCachedFilePropertyReferences(array $property_references): void
    {
        $cache_directory = $this->config->getCacheDirectory();

        if (!$cache_directory) {
            return;
        }

        $member_cache_location = $cache_directory . DIRECTORY_SEPARATOR . self::FILE_CLASS_PROPERTY_CACHE_NAME;

        if ($this->config->use_igbinary) {
            file_put_contents($member_cache_location, igbinary_serialize($property_references), LOCK_EX);
        } else {
            file_put_contents($member_cache_location, serialize($property_references), LOCK_EX);
        }
    }

    public function setCachedFileMethodReturnReferences(array $method_return_references): void
    {
        $cache_directory = $this->config->getCacheDirectory();

        if (!$cache_directory) {
            return;
        }

        $member_cache_location = $cache_directory . DIRECTORY_SEPARATOR . self::FILE_METHOD_RETURN_CACHE_NAME;

        if ($this->config->use_igbinary) {
            file_put_contents($member_cache_location, igbinary_serialize($method_return_references), LOCK_EX);
        } else {
            file_put_contents($member_cache_location, serialize($method_return_references), LOCK_EX);
        }
    }

    public function setCachedFileMissingMemberReferences(array $member_references): void
    {
        $cache_directory = $this->config->getCacheDirectory();

        if (!$cache_directory) {
            return;
        }

        $member_cache_location = $cache_directory . DIRECTORY_SEPARATOR . self::FILE_MISSING_MEMBER_CACHE_NAME;

        if ($this->config->use_igbinary) {
            file_put_contents($member_cache_location, igbinary_serialize($member_references), LOCK_EX);
        } else {
            file_put_contents($member_cache_location, serialize($member_references), LOCK_EX);
        }
    }

    public function setCachedMixedMemberNameReferences(array $references): void
    {
        $cache_directory = $this->config->getCacheDirectory();

        if (!$cache_directory) {
            return;
        }

        $reference_cache_location = $cache_directory . DIRECTORY_SEPARATOR . self::UNKNOWN_MEMBER_CACHE_NAME;

        if ($this->config->use_igbinary) {
            file_put_contents($reference_cache_location, igbinary_serialize($references), LOCK_EX);
        } else {
            file_put_contents($reference_cache_location, serialize($references), LOCK_EX);
        }
    }

    public function setCachedMethodParamUses(array $uses): void
    {
        $cache_directory = $this->config->getCacheDirectory();

        if (!$cache_directory) {
            return;
        }

        $reference_cache_location = $cache_directory . DIRECTORY_SEPARATOR . self::METHOD_PARAM_USE_CACHE_NAME;

        if ($this->config->use_igbinary) {
            file_put_contents($reference_cache_location, igbinary_serialize($uses), LOCK_EX);
        } else {
            file_put_contents($reference_cache_location, serialize($uses), LOCK_EX);
        }
    }

    public function setCachedIssues(array $issues): void
    {
        $cache_directory = $this->config->getCacheDirectory();

        if (!$cache_directory) {
            return;
        }

        $issues_cache_location = $cache_directory . DIRECTORY_SEPARATOR . self::ISSUES_CACHE_NAME;

        if ($this->config->use_igbinary) {
            file_put_contents($issues_cache_location, igbinary_serialize($issues), LOCK_EX);
        } else {
            file_put_contents($issues_cache_location, serialize($issues), LOCK_EX);
        }
    }

    /**
     * @return array<string, array<string, int>>|false
     */
    public function getAnalyzedMethodCache()
    {
        $cache_directory = $this->config->getCacheDirectory();

        $analyzed_methods_cache_location = $cache_directory . DIRECTORY_SEPARATOR . self::ANALYZED_METHODS_CACHE_NAME;

        if ($cache_directory
            && file_exists($analyzed_methods_cache_location)
        ) {
            if ($this->config->use_igbinary) {
                /** @var array<string, array<string, int>> */
                return igbinary_unserialize(Providers::safeFileGetContents($analyzed_methods_cache_location));
            } else {
                /** @var array<string, array<string, int>> */
                return unserialize(Providers::safeFileGetContents($analyzed_methods_cache_location));
            }
        }

        return false;
    }

    /**
     * @param array<string, array<string, int>> $analyzed_methods
     */
    public function setAnalyzedMethodCache(array $analyzed_methods): void
    {
        $cache_directory = Config::getInstance()->getCacheDirectory();

        if ($cache_directory) {
            $analyzed_methods_cache_location = $cache_directory
                . DIRECTORY_SEPARATOR
                . self::ANALYZED_METHODS_CACHE_NAME;

            if ($this->config->use_igbinary) {
                file_put_contents($analyzed_methods_cache_location, igbinary_serialize($analyzed_methods), LOCK_EX);
            } else {
                file_put_contents($analyzed_methods_cache_location, serialize($analyzed_methods), LOCK_EX);
            }
        }
    }

    /**
     * @return array<string, FileMapType>|false
     */
    public function getFileMapCache()
    {
        $cache_directory = $this->config->getCacheDirectory();

        $file_maps_cache_location = $cache_directory . DIRECTORY_SEPARATOR . self::FILE_MAPS_CACHE_NAME;

        if ($cache_directory
            && file_exists($file_maps_cache_location)
        ) {
            if ($this->config->use_igbinary) {
                /**
                 * @var array<string, FileMapType>
                 */
                $file_maps_cache = igbinary_unserialize(Providers::safeFileGetContents($file_maps_cache_location));
            } else {
                /**
                 * @var array<string, FileMapType>
                 */
                $file_maps_cache = unserialize(Providers::safeFileGetContents($file_maps_cache_location));
            }

            return $file_maps_cache;
        }

        return false;
    }

    /**
     * @param array<string, FileMapType> $file_maps
     */
    public function setFileMapCache(array $file_maps): void
    {
        $cache_directory = Config::getInstance()->getCacheDirectory();

        if ($cache_directory) {
            $file_maps_cache_location = $cache_directory . DIRECTORY_SEPARATOR . self::FILE_MAPS_CACHE_NAME;

            if ($this->config->use_igbinary) {
                file_put_contents($file_maps_cache_location, igbinary_serialize($file_maps), LOCK_EX);
            } else {
                file_put_contents($file_maps_cache_location, serialize($file_maps), LOCK_EX);
            }
        }
    }

    /**
     * @return array<string, array{int, int}>|false
     */
    public function getTypeCoverage()
    {
        $cache_directory = Config::getInstance()->getCacheDirectory();

        $type_coverage_cache_location = $cache_directory . DIRECTORY_SEPARATOR . self::TYPE_COVERAGE_CACHE_NAME;

        if ($cache_directory
            && file_exists($type_coverage_cache_location)
        ) {
            if ($this->config->use_igbinary) {
                /** @var array<string, array{int, int}> */
                $type_coverage_cache = igbinary_unserialize(
                    Providers::safeFileGetContents($type_coverage_cache_location)
                );
            } else {
                /** @var array<string, array{int, int}> */
                $type_coverage_cache = unserialize(
                    Providers::safeFileGetContents($type_coverage_cache_location)
                );
            }

            return $type_coverage_cache;
        }

        return false;
    }

    /**
     * @param array<string, array{int, int}> $mixed_counts
     */
    public function setTypeCoverage(array $mixed_counts): void
    {
        $cache_directory = Config::getInstance()->getCacheDirectory();

        if ($cache_directory) {
            $type_coverage_cache_location = $cache_directory . DIRECTORY_SEPARATOR . self::TYPE_COVERAGE_CACHE_NAME;

            if ($this->config->use_igbinary) {
                file_put_contents($type_coverage_cache_location, igbinary_serialize($mixed_counts), LOCK_EX);
            } else {
                file_put_contents($type_coverage_cache_location, serialize($mixed_counts), LOCK_EX);
            }
        }
    }

    /**
     * @return string|false
     */
    public function getConfigHashCache()
    {
        $cache_directory = $this->config->getCacheDirectory();

        $config_hash_cache_location = $cache_directory . DIRECTORY_SEPARATOR . self::CONFIG_HASH_CACHE_NAME;

        if ($cache_directory
            && file_exists($config_hash_cache_location)
        ) {
            return Providers::safeFileGetContents($config_hash_cache_location);
        }

        return false;
    }

    public function setConfigHashCache(string $hash): void
    {
        $cache_directory = Config::getInstance()->getCacheDirectory();

        if (!$cache_directory) {
            return;
        }

        if (!is_dir($cache_directory)) {
            try {
                if (mkdir($cache_directory, 0777, true) === false) {
                    // any other error than directory already exists/permissions issue
                    throw new RuntimeException(
                        'Failed to create ' . $cache_directory . ' cache directory for unknown reasons'
                    );
                }
            } catch (RuntimeException $e) {
                // Race condition (#4483)
                if (!is_dir($cache_directory)) {
                    // rethrow the error with default message
                    // it contains the reason why creation failed
                    throw $e;
                }
            }
        }

        $config_hash_cache_location = $cache_directory . DIRECTORY_SEPARATOR . self::CONFIG_HASH_CACHE_NAME;

        file_put_contents(
            $config_hash_cache_location,
            $hash,
            LOCK_EX
        );
    }
}
