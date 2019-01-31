<?php

/*
 * This file is part of the Eventum (Issue Tracking System) package.
 *
 * @copyright (c) Eventum Team
 * @license GNU General Public License, version 2 or later (GPL-2+)
 *
 * For the full copyright and license information,
 * please see the COPYING and AUTHORS files
 * that were distributed with this source code.
 */

namespace Eventum\Scm\Payload;

use Date_Helper;
use Eventum\Model\Entity\Commit;

class GitlabPayload implements PayloadInterface
{
    public const EVENT_TYPE_ISSUE = 'issue';
    public const EVENT_TYPE_MERGE_REQUEST = 'merge_request';
    public const EVENT_TYPE_NOTE = 'note';

    public const NOTEABLE_TYPE_MERGE_REQUEST = 'MergeRequest';
    public const NOTEABLE_TYPE_ISSUE = 'Issue';

    /** @var array */
    private $payload = [];

    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }

    public function createCommit(array $commit): Commit
    {
        return (new Commit())
            ->setChangeset($commit['id'])
            ->setAuthorEmail($commit['author']['email'])
            ->setAuthorName($commit['author']['name'])
            ->setCommitDate(Date_Helper::getDateTime($commit['timestamp']))
            ->setMessage(trim($commit['message']));
    }

    /**
     * Get event name from payload.
     * The key is present for System Hooks
     */
    public function getEventName(): ?string
    {
        return $this->payload['event_name'] ?? null;
    }

    public function getEventType(): ?string
    {
        return $this->payload['event_type'] ?? null;
    }

    public function getNoteableType(): ?string
    {
        return $this->payload['object_attributes']['noteable_type'] ?? null;
    }

    public function getAction(): ?string
    {
        return $this->payload['object_attributes']['action'] ?? null;
    }

    public function getUser(): array
    {
        return $this->payload['user'];
    }

    public function getUsername(): string
    {
        $user = $this->getUser();

        return "{$user['name']} (@{$user['username']})";
    }

    /**
     * Get description
     * - For issue events: returns issue body
     * - For note events: returns note body
     */
    public function getDescription(): ?string
    {
        return $this->payload['object_attributes']['description'] ?? null;
    }

    public function getUrl(): ?string
    {
        return $this->payload['object_attributes']['url'] ?? null;
    }

    public function getIssueId(): ?int
    {
        return $this->payload['issue']['iid'] ?? $this->payload['object_attributes']['iid'] ?? null;
    }

    public function getMergeRequestId(): ?int
    {
        return $this->payload['merge_request']['iid'] ?? null;
    }

    public function getTitle(): ?int
    {
        return $this->payload['object_attributes']['title'] ?? null;
    }

    /**
     * Get branch the commit was made on
     */
    public function getBranch(): ?string
    {
        $ref = $this->payload['ref'];

        if (strpos($ref, 'refs/heads/') === 0) {
            return substr($ref, 11);
        }

        return null;
    }

    public function getProject(): string
    {
        return $this->payload['project']['path_with_namespace'];
    }

    public function getCommits(): array
    {
        return $this->payload['commits'];
    }

    /**
     * Get repo url
     */
    public function getRepoUrl(): string
    {
        return explode(':', $this->payload['repository']['url'], 2)[0];
    }

    /**
     * {@inheritdoc}
     */
    public function getPayload(): array
    {
        return $this->payload;
    }
}
