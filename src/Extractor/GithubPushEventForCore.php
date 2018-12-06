<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Extractor;

use App\Exception\DoNotCareException;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Request;

/**
 * Extract information from a github push event hook
 * needed to trigger the core git split to single repos.
 *
 * Throws exceptions if not responsible.
 */
class GithubPushEventForCore
{
    private const TYPE_PATCH='patch';
    private const TYPE_TAG='tag';

    /**
     * @var string Used especially in logging as context.
     */
    public $jobUuid;

    /**
     * @var string Either 'patch' if a merged patch should be split, or 'tag' to apply tags to sub reps
     */
    public $type;

    /**
     * @var string The source branch to split FROM, eg. 'TYPO3_8-7', '9.2', 'master'
     */
    public $sourceBranch;

    /**
     * @var string The target branch to split TO, eg. '8.7', '9.2', 'master'
     */
    public $targetBranch;

    /**
     * @var string Set to the tag that has been pushed for type=tag
     */
    public $tag;

    /**
     * Extract information.
     *
     * @param Request $request
     * @throws DoNotCareException
     * @throws \Exception
     */
    public function __construct(Request $request = null)
    {
        if ($request) {
            $fullPullRequestInformation = json_decode($request->getContent(), true);
            if (!is_array($fullPullRequestInformation) || empty($fullPullRequestInformation['ref'])) {
                throw new DoNotCareException();
            }
            if ($this->isPushedPatch($fullPullRequestInformation)) {
                $this->type = self::TYPE_PATCH;
                $this->sourceBranch = $this->getSourceBranch($fullPullRequestInformation['ref']);
                $this->targetBranch = $this->getTargetBranch($this->sourceBranch);
            } elseif ($this->isPushedTag($fullPullRequestInformation)) {
                $this->type = self::TYPE_TAG;
                $this->tag = $this->getTag($fullPullRequestInformation['ref']);
                $this->sourceBranch = $this->getSourceBranch($fullPullRequestInformation['base_ref']);
                $this->targetBranch = $this->getTargetBranch($this->sourceBranch);
            } else {
                throw new DoNotCareException();
            }
            $this->jobUuid = Uuid::uuid4()->toString();
        }
    }

    /**
     * If the 'ref' part of a github pull request starts with 'refs/heads/',
     * then this has been a merged patch that should be split.
     *
     * @param array $requestInformation
     * @return bool
     */
    private function isPushedPatch(array $requestInformation): bool
    {
        return strpos($requestInformation['ref'], 'refs/heads/') === 0;
    }

    /**
     * If 'ref' starts with 'refs/tags/, and created is set and base_ref is not
     * empty, then this push event is an event for a new tag on git core master.
     *
     * @param array $requestInformation
     * @return bool
     */
    private function isPushedTag(array $requestInformation): bool
    {
        return strpos($requestInformation['ref'], 'refs/tags/') === 0
            && $requestInformation['created'] === true
            && !empty($requestInformation['base_ref']);
    }

    /**
     * Extract tag from a 'refs/tags/v9.5.1' a-like string
     *
     * @param string $ref
     * @return string
     * @throws DoNotCareException
     */
    private function getTag(string $ref): string
    {
        $tag = str_replace('refs/tags/', '', $ref);
        if (empty($tag)) {
            throw new DoNotCareException();
        }
        return $tag;
    }

    /**
     * Determine source branch from 'ref', eg. 'refs/heads/master' becomes 'master'
     *
     * @param string $ref
     * @return string
     * @throws DoNotCareException
     */
    private function getSourceBranch(string $ref): string
    {
        $sourceBranch = str_replace('refs/heads/', '', $ref);
        if (empty($sourceBranch)) {
            throw new DoNotCareException();
        }
        return $sourceBranch;
    }

    /**
     * Translate source branch of main git TYPO3.CMS repo to target branch name on split repos.
     *
     * @param string Source branch name
     * @return string Target branch name
     * @throws DoNotCareException
     */
    private function getTargetBranch(string $sourceBranch): string
    {
        if ($sourceBranch === 'master') {
            return $sourceBranch;
        } elseif ($sourceBranch === 'TYPO3_8-7') {
            return '8.7';
        } elseif (preg_match('/[0-9]+\.[0-9]+/', $sourceBranch)) {
            return $sourceBranch;
        } else {
            throw new DoNotCareException();
        }
    }
}