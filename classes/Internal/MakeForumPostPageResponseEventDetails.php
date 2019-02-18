<?php

/*
 * Bear CMS addon for Bear Framework
 * https://bearcms.com/
 * Copyright (c) Amplilabs Ltd.
 * Free to use under the MIT license.
 */

namespace BearCMS\Internal;

/**
 * @property \BearFramework\App\Response $response
 * @property string $forumPostID
 * @internal
 */
class MakeForumPostPageResponseEventDetails
{

    use \IvoPetkov\DataObjectTrait;

    /**
     * 
     * @param \BearFramework\App\Response $response
     * @param string $forumPostID
     */
    public function __construct(\BearFramework\App\Response $response, string $forumPostID)
    {
        $this
                ->defineProperty('response', [
                    'type' => \BearFramework\App\Response::class
                ])
                ->defineProperty('forumPostID', [
                    'type' => 'string'
                ])
        ;
        $this->response = $response;
        $this->forumPostID = $forumPostID;
    }

}
