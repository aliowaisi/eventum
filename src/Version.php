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

namespace Eventum;

final class Version
{
    /** @var string */
    public $version;
    /** @var string */
    public $hash;
    /** @var null|string */
    public $branch;

    /**
     * Extract version components from various forms.
     *
     * a tag: '3.7.0@859ccf532731b653c5af71f4151f173bc8fd1d42'
     * a branch: 'dev-package-versions@859ccf532731b653c5af71f4151f173bc8fd1d42'
     * detached HEAD: 'dev-bc9c1a16dd77aba02cf22b5ed95c0d7a9f06afa6@bc9c1a16dd77aba02cf22b5ed95c0d7a9f06afa6'
     */
    public function __construct(string $versionString)
    {
        [$this->version, $this->hash] = explode('@', $versionString, 2);

        $parts = explode('-', $this->version, 2);

        if ($parts[0] === 'dev') {
            $branch = implode('-', array_splice($parts, 1));

            // skip detached head
            if ($branch !== $this->hash) {
                $this->branch = $branch;
            }
        }
    }
}
