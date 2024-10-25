<?php

namespace LanguageServerProtocol;

class WorkspaceEditClientCapabilitiesChangeAnnotationSupport
{
    /**
     * Whether the client groups edits with equal labels into tree nodes,
     * for instance all edits labelled with "Changes in Strings" would
     * be a tree node.
     *
     * @var bool|null
     */
    public $groupsOnLabel;

    public function __construct(
        bool $groupsOnLabel = null
    ) {
        $this->groupsOnLabel = $groupsOnLabel;
    }
}
