<?php

return [
    // Local-only override layer: path-repo state for `composer link` / `add` (gitignore).
    'overrides_file' => 'packages-local.json',
    // Committed manifest (team baseline).
    'composer_json' => 'composer.json',
    // Gitignored copy of composer.json + path repos + constraint tweaks; use COMPOSER=composer.local.json or `composer local-install`.
    'local_composer_json' => 'composer.local.json',
    'composer_binary' => 'composer',
    'repository_marker' => 'composer-link',
    'default_symlink' => true,
    'update_with_all_dependencies' => true,
];
