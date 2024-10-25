<?php

declare(strict_types=1);

namespace PhpMyAdmin;

use DirectoryIterator;
use PhpMyAdmin\Utils\HttpRequest;
use stdClass;

use function array_key_exists;
use function array_shift;
use function basename;
use function bin2hex;
use function count;
use function date;
use function explode;
use function fclose;
use function file_exists;
use function file_get_contents;
use function fopen;
use function fread;
use function fseek;
use function function_exists;
use function gzuncompress;
use function implode;
use function in_array;
use function intval;
use function is_bool;
use function is_dir;
use function is_file;
use function json_decode;
use function ord;
use function preg_match;
use function str_contains;
use function str_replace;
use function strlen;
use function strpos;
use function strtolower;
use function substr;
use function trim;
use function unpack;

use const DIRECTORY_SEPARATOR;
use const PHP_EOL;

/**
 * Git class to manipulate Git data
 */
class Git
{
    /**
     * Enable Git information search and process
     *
     * @var bool
     */
    private $showGitRevision;

    /**
     * Git has been found and the data fetched
     *
     * @var bool
     */
    private $hasGit = false;

    public function __construct(bool $showGitRevision)
    {
        $this->showGitRevision = $showGitRevision;
    }

    public function hasGitInformation(): bool
    {
        return $this->hasGit;
    }

    /**
     * detects if Git revision
     *
     * @param string $git_location (optional) verified git directory
     */
    public function isGitRevision(&$git_location = null): bool
    {
        if (! $this->showGitRevision) {
            return false;
        }

        // caching
        if (isset($_SESSION['is_git_revision']) && array_key_exists('git_location', $_SESSION)) {
            // Define location using cached value
            $git_location = $_SESSION['git_location'];

            return (bool) $_SESSION['is_git_revision'];
        }

        // find out if there is a .git folder
        // or a .git file (--separate-git-dir)
        $git = '.git';
        if (is_dir($git)) {
            if (! @is_file($git . '/config')) {
                $_SESSION['git_location'] = null;
                $_SESSION['is_git_revision'] = false;

                return false;
            }

            $git_location = $git;
        } elseif (is_file($git)) {
            $contents = (string) file_get_contents($git);
            $gitmatch = [];
            // Matches expected format
            if (! preg_match('/^gitdir: (.*)$/', $contents, $gitmatch)) {
                $_SESSION['git_location'] = null;
                $_SESSION['is_git_revision'] = false;

                return false;
            }

            if (! @is_dir($gitmatch[1])) {
                $_SESSION['git_location'] = null;
                $_SESSION['is_git_revision'] = false;

                return false;
            }

            //Detected git external folder location
            $git_location = $gitmatch[1];
        } else {
            $_SESSION['git_location'] = null;
            $_SESSION['is_git_revision'] = false;

            return false;
        }

        // Define session for caching
        $_SESSION['git_location'] = $git_location;
        $_SESSION['is_git_revision'] = true;

        return true;
    }

    private function readPackFile(string $packFile, int $packOffset): ?string
    {
        // open pack file
        $packFileRes = fopen($packFile, 'rb');
        if ($packFileRes === false) {
            return null;
        }

        // seek to start
        fseek($packFileRes, $packOffset);

        // parse header
        $headerData = fread($packFileRes, 1);
        if ($headerData === false) {
            return null;
        }

        $header = ord($headerData);
        $type = ($header >> 4) & 7;
        $hasnext = ($header & 128) >> 7;
        $size = $header & 0xf;
        $offset = 4;

        while ($hasnext) {
            $readData = fread($packFileRes, 1);
            if ($readData === false) {
                return null;
            }

            $byte = ord($readData);
            $size |= ($byte & 0x7f) << $offset;
            $hasnext = ($byte & 128) >> 7;
            $offset += 7;
        }

        // we care only about commit objects
        if ($type != 1) {
            return null;
        }

        // read data
        $commit = fread($packFileRes, $size);
        fclose($packFileRes);

        if ($commit === false) {
            return null;
        }

        return $commit;
    }

    private function getPackOffset(string $packFile, string $hash): ?int
    {
        // load index
        $index_data = @file_get_contents($packFile);
        if ($index_data === false) {
            return null;
        }

        // check format
        if (substr($index_data, 0, 4) != "\377tOc") {
            return null;
        }

        // check version
        $version = unpack('N', substr($index_data, 4, 4));
        if ($version[1] != 2) {
            return null;
        }

        // parse fanout table
        $fanout = unpack(
            'N*',
            substr($index_data, 8, 256 * 4)
        );

        // find where we should search
        $firstbyte = intval(substr($hash, 0, 2), 16);
        // array is indexed from 1 and we need to get
        // previous entry for start
        if ($firstbyte == 0) {
            $start = 0;
        } else {
            $start = $fanout[$firstbyte];
        }

        $end = $fanout[$firstbyte + 1];

        // stupid linear search for our sha
        $found = false;
        $offset = 8 + (256 * 4);
        for ($position = $start; $position < $end; $position++) {
            $sha = strtolower(
                bin2hex(
                    substr($index_data, $offset + ($position * 20), 20)
                )
            );
            if ($sha == $hash) {
                $found = true;
                break;
            }
        }

        if (! $found) {
            return null;
        }

        // read pack offset
        $offset = 8 + (256 * 4) + (24 * $fanout[256]);
        $packOffsets = unpack(
            'N',
            substr($index_data, $offset + ($position * 4), 4)
        );

        return $packOffsets[1];
    }

    /**
     * Un pack a commit with gzuncompress
     *
     * @param string $gitFolder The Git folder
     * @param string $hash      The commit hash
     *
     * @return array|false|null
     */
    private function unPackGz(string $gitFolder, string $hash)
    {
        $commit = false;

        $gitFileName = $gitFolder . '/objects/'
            . substr($hash, 0, 2) . '/' . substr($hash, 2);
        if (@file_exists($gitFileName)) {
            $commit = @file_get_contents($gitFileName);

            if ($commit === false) {
                $this->hasGit = false;

                return null;
            }

            $commitData = gzuncompress($commit);
            if ($commitData === false) {
                return null;
            }

            $commit = explode("\0", $commitData, 2);
            $commit = explode("\n", $commit[1]);
            $_SESSION['PMA_VERSION_COMMITDATA_' . $hash] = $commit;
        } else {
            $pack_names = [];
            // work with packed data
            $packs_file = $gitFolder . '/objects/info/packs';
            $packs = '';

            if (@file_exists($packs_file)) {
                $packs = @file_get_contents($packs_file);
            }

            if ($packs) {
                // File exists. Read it, parse the file to get the names of the
                // packs. (to look for them in .git/object/pack directory later)
                foreach (explode("\n", $packs) as $line) {
                    // skip blank lines
                    if (strlen(trim($line)) == 0) {
                        continue;
                    }

                    // skip non pack lines
                    if ($line[0] !== 'P') {
                        continue;
                    }

                    // parse names
                    $pack_names[] = substr($line, 2);
                }
            } else {
                // '.git/objects/info/packs' file can be missing
                // (at least in mysGit)
                // File missing. May be we can look in the .git/object/pack
                // directory for all the .pack files and use that list of
                // files instead
                $dirIterator = new DirectoryIterator($gitFolder . '/objects/pack');
                foreach ($dirIterator as $file_info) {
                    $file_name = $file_info->getFilename();
                    // if this is a .pack file
                    if (! $file_info->isFile() || substr($file_name, -5) !== '.pack') {
                        continue;
                    }

                    $pack_names[] = $file_name;
                }
            }

            $hash = strtolower($hash);
            foreach ($pack_names as $pack_name) {
                $index_name = str_replace('.pack', '.idx', $pack_name);

                $packOffset = $this->getPackOffset($gitFolder . '/objects/pack/' . $index_name, $hash);
                if ($packOffset === null) {
                    continue;
                }

                $commit = $this->readPackFile($gitFolder . '/objects/pack/' . $pack_name, $packOffset);
                if ($commit !== null) {
                    $commit = gzuncompress($commit);
                    if ($commit !== false) {
                        $commit = explode("\n", $commit);
                    }
                }

                $_SESSION['PMA_VERSION_COMMITDATA_' . $hash] = $commit;
            }
        }

        return $commit;
    }

    /**
     * Extract committer, author and message from commit body
     *
     * @param array $commit The commit body
     *
     * @return array<int,array<string,string>|string>
     */
    private function extractDataFormTextBody(array $commit): array
    {
        $author = [
            'name' => '',
            'email' => '',
            'date' => '',
        ];
        $committer = [
            'name' => '',
            'email' => '',
            'date' => '',
        ];

        do {
            $dataline = array_shift($commit);
            $datalinearr = explode(' ', $dataline, 2);
            $linetype = $datalinearr[0];
            if (! in_array($linetype, ['author', 'committer'])) {
                continue;
            }

            $user = $datalinearr[1];
            preg_match('/([^<]+)<([^>]+)> ([0-9]+)( [^ ]+)?/', $user, $user);
            $user2 = [
                'name' => trim($user[1]),
                'email' => trim($user[2]),
                'date' => date('Y-m-d H:i:s', (int) $user[3]),
            ];
            if (isset($user[4])) {
                $user2['date'] .= $user[4];
            }

            if ($linetype === 'author') {
                $author = $user2;
            } elseif ($linetype === 'committer') {
                $committer = $user2;
            }
        } while ($dataline != '');

        $message = trim(implode(' ', $commit));

        return [$author, $committer, $message];
    }

    /**
     * Is the commit remote
     *
     * @param mixed  $commit         The commit
     * @param bool   $isRemoteCommit Is the commit remote ?, will be modified by reference
     * @param string $hash           The commit hash
     *
     * @return stdClass|null The commit body from the GitHub API
     */
    private function isRemoteCommit(&$commit, bool &$isRemoteCommit, string $hash): ?stdClass
    {
        $httpRequest = new HttpRequest();

        // check if commit exists in Github
        if ($commit !== false && isset($_SESSION['PMA_VERSION_REMOTECOMMIT_' . $hash])) {
            $isRemoteCommit = $_SESSION['PMA_VERSION_REMOTECOMMIT_' . $hash];

            return null;
        }

        $link = 'https://www.phpmyadmin.net/api/commit/' . $hash . '/';
        $is_found = $httpRequest->create($link, 'GET');
        if ($is_found === false) {
            $isRemoteCommit = false;
            $_SESSION['PMA_VERSION_REMOTECOMMIT_' . $hash] = false;

            return null;
        }

        if ($is_found === null) {
            // no remote link for now, but don't cache this as GitHub is down
            $isRemoteCommit = false;

            return null;
        }

        $isRemoteCommit = true;
        $_SESSION['PMA_VERSION_REMOTECOMMIT_' . $hash] = true;
        if ($commit === false) {
            // if no local commit data, try loading from Github
            return json_decode((string) $is_found);
        }

        return null;
    }

    private function getHashFromHeadRef(string $gitFolder, string $refHead): array
    {
        $branch = false;

        // are we on any branch?
        if (! str_contains($refHead, '/')) {
            return [trim($refHead), $branch];
        }

        // remove ref: prefix
        $refHead = substr(trim($refHead), 5);
        if (strpos($refHead, 'refs/heads/') === 0) {
            $branch = substr($refHead, 11);
        } else {
            $branch = basename($refHead);
        }

        $refFile = $gitFolder . '/' . $refHead;
        if (@file_exists($refFile)) {
            $hash = @file_get_contents($refFile);
            if ($hash === false) {
                $this->hasGit = false;

                return [null, null];
            }

            return [trim($hash), $branch];
        }

        // deal with packed refs
        $packedRefs = @file_get_contents($gitFolder . '/packed-refs');
        if ($packedRefs === false) {
            $this->hasGit = false;

            return [null, null];
        }

        // split file to lines
        $refLines = explode(PHP_EOL, $packedRefs);
        foreach ($refLines as $line) {
            // skip comments
            if ($line[0] === '#') {
                continue;
            }

            // parse line
            $parts = explode(' ', $line);
            // care only about named refs
            if (count($parts) != 2) {
                continue;
            }

            // have found our ref?
            if ($parts[1] == $refHead) {
                $hash = $parts[0];
                break;
            }
        }

        if (! isset($hash)) {
            $this->hasGit = false;

            // Could not find ref
            return [null, null];
        }

        return [$hash, $branch];
    }

    private function getCommonDirContents(string $gitFolder): ?string
    {
        if (! is_file($gitFolder . '/commondir')) {
            return null;
        }

        $commonDirContents = @file_get_contents($gitFolder . '/commondir');
        if ($commonDirContents === false) {
            return null;
        }

        return trim($commonDirContents);
    }

    /**
     * detects Git revision, if running inside repo
     */
    public function checkGitRevision(): ?array
    {
        // find out if there is a .git folder
        $gitFolder = '';
        if (! $this->isGitRevision($gitFolder)) {
            $this->hasGit = false;

            return null;
        }

        $ref_head = @file_get_contents($gitFolder . '/HEAD');

        if (! $ref_head) {
            $this->hasGit = false;

            return null;
        }

        $commonDirContents = $this->getCommonDirContents($gitFolder);
        if ($commonDirContents !== null) {
            $gitFolder .= DIRECTORY_SEPARATOR . $commonDirContents;
        }

        [$hash, $branch] = $this->getHashFromHeadRef($gitFolder, $ref_head);
        if ($hash === null) {
            return null;
        }

        $commit = false;
        if (! preg_match('/^[0-9a-f]{40}$/i', $hash)) {
            $commit = false;
        } elseif (isset($_SESSION['PMA_VERSION_COMMITDATA_' . $hash])) {
            $commit = $_SESSION['PMA_VERSION_COMMITDATA_' . $hash];
        } elseif (function_exists('gzuncompress')) {
            $commit = $this->unPackGz($gitFolder, $hash);
            if ($commit === null) {
                return null;
            }
        }

        $is_remote_commit = false;
        $commit_json = $this->isRemoteCommit(
            $commit, // Will be modified if necessary by the function
            $is_remote_commit, // Will be modified if necessary by the function
            $hash
        );

        $is_remote_branch = false;
        if ($is_remote_commit && $branch !== false) {
            // check if branch exists in Github
            if (isset($_SESSION['PMA_VERSION_REMOTEBRANCH_' . $hash])) {
                $is_remote_branch = $_SESSION['PMA_VERSION_REMOTEBRANCH_' . $hash];
            } else {
                $httpRequest = new HttpRequest();
                $link = 'https://www.phpmyadmin.net/api/tree/' . $branch . '/';
                $is_found = $httpRequest->create($link, 'GET', true);
                if (is_bool($is_found)) {
                    $is_remote_branch = $is_found;
                    $_SESSION['PMA_VERSION_REMOTEBRANCH_' . $hash] = $is_found;
                }

                if ($is_found === null) {
                    // no remote link for now, but don't cache this as Github is down
                    $is_remote_branch = false;
                }
            }
        }

        if ($commit !== false) {
            [$author, $committer, $message] = $this->extractDataFormTextBody($commit);
        } elseif (isset($commit_json->author, $commit_json->committer, $commit_json->message)) {
            $author = [
                'name' => $commit_json->author->name,
                'email' => $commit_json->author->email,
                'date' => $commit_json->author->date,
            ];
            $committer = [
                'name' => $commit_json->committer->name,
                'email' => $commit_json->committer->email,
                'date' => $commit_json->committer->date,
            ];
            $message = trim($commit_json->message);
        } else {
            $this->hasGit = false;

            return null;
        }

        $this->hasGit = true;

        return [
            'hash' => $hash,
            'branch' => $branch,
            'message' => $message,
            'author' => $author,
            'committer' => $committer,
            'is_remote_commit' => $is_remote_commit,
            'is_remote_branch' => $is_remote_branch,
        ];
    }
}
